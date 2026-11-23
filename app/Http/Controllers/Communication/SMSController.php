<?php

namespace App\Http\Controllers\Communication;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Models\User;
use App\Models\Branch;
use App\Models\Supplier;
use App\Models\Customer;

class SMSController extends Controller
{
    protected $apiToken;
    protected $senderId;

    protected $apiUrl      = 'https://kingosms.co.tz/api/v3/sms/send';
    protected $campaignUrl = 'https://kingosms.co.tz/api/v3/sms/campaign';
    protected $balanceUrl  = 'https://kingosms.co.tz/api/v3/balance';
    protected $profileUrl  = 'https://kingosms.co.tz/api/v3/me';
    protected $historyUrl  = 'https://kingosms.co.tz/api/v3/sms';

    protected $maxHistoryPages = 40;
    protected $tz = 'Africa/Dar_es_Salaam';

    public function __construct()
    {
        $settings       = $this->getSmsSettings();
        $this->apiToken = $settings['api_token'] ?? config('sms.api_token', '');
        $this->senderId = $settings['sender_id'] ?? config('sms.sender_id', 'NGEMBA MINE');
    }

    /* ============================================================
     |  DASHBOARD
     * ============================================================ */

    public function index()
    {
        $user = Auth::user();

        $settings = $this->getSmsSettings();
        $balance  = $this->getLiveBalance();

        /* ---- Live stats from KingoSMS ---- */
        $stats       = $this->getDashboardStats();
        $statsSource = ($stats['status'] ?? 'error') === 'success' ? 'kingosms' : 'error';
        $statsError  = $statsSource === 'error' ? ($stats['message'] ?? 'Unable to reach KingoSMS') : null;

        $totalSent    = $stats['total']     ?? 0;
        $totalSuccess = $stats['delivered'] ?? 0;
        $totalFailed  = $stats['failed']    ?? 0;

        /* ---- Recent logs from KingoSMS ---- */
        $recent = $this->getSmsHistoryPage(
            now($this->tz)->subDays(30)->startOfDay()->format('Y-m-d H:i:s'),
            now($this->tz)->format('Y-m-d H:i:s'),
            $this->tz,
            null, null, 1
        );
        $logs = $this->apiRowsToPaginator($recent['data'] ?? [], 1, 20);

        /* ---- Templates ---- */
        $templates = collect();
        try {
            if (Schema::hasTable('sms_templates')) {
                $templates = DB::table('sms_templates')
                    ->where(function ($q) use ($user) {
                        $q->where('branch_id', 0);
                        if ($user->assigned_branch !== null) {
                            $q->orWhere('branch_id', $user->assigned_branch);
                        }
                    })->get();
            }
        } catch (\Throwable $e) {}

        /* ---- Contacts ---- */
        $contacts = collect();
        try {
            $suppliers = Supplier::when(
                    $this->canFilterByBranch($user),
                    fn ($q) => $q->where('branch_id', $user->assigned_branch)
                )->select('id', 'name', 'contact', DB::raw("'supplier' as type"))->get();

            $customers = Customer::when(
                    $this->canFilterByBranch($user),
                    fn ($q) => $q->where('branch_id', $user->assigned_branch)
                )->select('id', 'name', 'contact', DB::raw("'customer' as type"))->get();

            $contacts = $suppliers->concat($customers);
        } catch (\Throwable $e) {}

        $creditBalance  = $balance['balance'] ?? 0;
        $balanceStatus  = $balance['status']  ?? 'error';
        $balanceMessage = $balance['message'] ?? '';

        return view('communication.sms.index', compact(
            'settings', 'logs',
            'totalSent', 'totalSuccess', 'totalFailed',
            'templates', 'contacts',
            'creditBalance', 'balanceStatus', 'balanceMessage',
            'statsSource', 'statsError'
        ));
    }

    /* ============================================================
     |  FULL LOGS PAGE (KingoSMS API + client-side filters)
     * ============================================================ */

    public function logs(Request $request)
    {
        /* ---- 1. Date window ---- */
        $from = $request->filled('from_date')
            ? \Carbon\Carbon::parse($request->from_date, $this->tz)->startOfDay()->format('Y-m-d H:i:s')
            : now($this->tz)->subDays(30)->startOfDay()->format('Y-m-d H:i:s');

        $to = $request->filled('to_date')
            ? \Carbon\Carbon::parse($request->to_date, $this->tz)->endOfDay()->format('Y-m-d H:i:s')
            : now($this->tz)->format('Y-m-d H:i:s');

        /* ---- 2. Optional API-supported filters ---- */
        $smsType   = $request->filled('sms_type')  ? $request->sms_type  : null;
        $direction = $request->filled('direction') ? $request->direction : null;

        /* ---- 3. Walk all pages within the window ---- */
        $apiOk    = true;
        $apiError = null;
        $allRows  = [];
        $page     = 1;

        while ($page <= $this->maxHistoryPages) {
            $res = $this->getSmsHistoryPage($from, $to, $this->tz, $smsType, $direction, $page);

            if (($res['status'] ?? 'error') !== 'success') {
                if ($page === 1) {
                    $apiOk    = false;
                    $apiError = $res['message'] ?? 'KingoSMS unreachable';
                }
                break;
            }

            $payload = $res['data'] ?? [];
            $rows    = $payload['data'] ?? [];

            foreach ($rows as $row) {
                $allRows[] = (object) [
                    'uid'       => $row['uid']       ?? null,
                    'phone'     => $row['to']        ?? null,
                    'sender_id' => $row['from']      ?? null,
                    'message'   => $row['message']   ?? '',
                    'sms_type'  => $row['sms_type']  ?? null,
                    'direction' => $row['direction'] ?? null,
                    'status'    => $row['status']    ?? null,
                    'sms_count' => $row['sms_count'] ?? null,
                    'cost'      => $row['cost']      ?? null,
                    'sent_at'   => $row['sent_at']   ?? null,
                    'sent_by'   => 'KingoSMS',
                ];
            }

            if (empty($payload['next_page_url'])) break;
            $page++;
        }

        /* ---- 4. Client-side filters: status, phone ---- */
        $filtered = collect($allRows);

        if ($request->filled('status')) {
            $want = strtolower($request->status);
            $filtered = $filtered->filter(function ($row) use ($want) {
                $st = strtolower((string) $row->status);
                return match ($want) {
                    'sent'    => in_array($st, ['sent', 'delivered', 'success'], true),
                    'pending' => !in_array($st, ['sent','delivered','success','failed','undelivered','rejected','expired'], true),
                    'failed'  => in_array($st, ['failed', 'undelivered', 'rejected', 'expired'], true),
                    default   => true,
                };
            });
        }

        if ($request->filled('phone')) {
            $needle   = preg_replace('/\D/', '', $request->phone);
            $filtered = $filtered->filter(function ($row) use ($needle) {
                return str_contains(preg_replace('/\D/', '', $row->phone ?? ''), $needle);
            });
        }

        $filtered = $filtered->values();

        /* ---- 5. Card totals from the filtered set ---- */
        $totalMessages = $filtered->count();

        $sentCount = $filtered->filter(function ($r) {
            return in_array(strtolower((string) $r->status), ['sent','delivered','success'], true);
        })->count();

        $failedCount = $filtered->filter(function ($r) {
            return in_array(strtolower((string) $r->status), ['failed','undelivered','rejected','expired'], true);
        })->count();

        $pendingCount = $totalMessages - $sentCount - $failedCount;

        /* ---- 6. Manual pagination of filtered set ---- */
        $perPage     = 25;
        $currentPage = max(1, (int) $request->get('page', 1));
        $slice       = $filtered->slice(($currentPage - 1) * $perPage, $perPage)->values();

        $logs = new LengthAwarePaginator(
            $slice,
            $totalMessages,
            $perPage,
            $currentPage,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        return view('communication.sms.logs', compact(
            'logs', 'totalMessages', 'sentCount', 'pendingCount', 'failedCount',
            'apiOk', 'apiError', 'from', 'to'
        ));
    }

    /* ============================================================
     |  SEND FORM
     * ============================================================ */

    public function sendForm()
    {
        $user = Auth::user();

        $suppliers = collect();
        $customers = collect();
        $managers  = collect();
        $users     = collect();
        $templates = collect();

        try { $suppliers = Supplier::when($this->canFilterByBranch($user), fn ($q) => $q->where('branch_id', $user->assigned_branch))->get(); } catch (\Throwable $e) {}
        try { $customers = Customer::when($this->canFilterByBranch($user), fn ($q) => $q->where('branch_id', $user->assigned_branch))->get(); } catch (\Throwable $e) {}

        try {
            $managers = User::where('role', 'branch_manager')
                ->when($this->canFilterByBranch($user), fn ($q) => $q->where('assigned_branch', $user->assigned_branch))
                ->select('id', 'username', 'full_name', 'phone_number')->get();
        } catch (\Throwable $e) {}

        try {
            $users = User::when($this->canFilterByBranch($user), fn ($q) => $q->where('assigned_branch', $user->assigned_branch))
                ->select('id', 'username', 'full_name', 'phone_number', 'role')->get();
        } catch (\Throwable $e) {}

        try {
            if (Schema::hasTable('sms_templates')) {
                $templates = DB::table('sms_templates')
                    ->where(function ($q) use ($user) {
                        $q->where('branch_id', 0);
                        if ($user->assigned_branch !== null) $q->orWhere('branch_id', $user->assigned_branch);
                    })->get();
            }
        } catch (\Throwable $e) {}

        $credits = $this->getLiveBalance();

        return view('communication.sms.send', compact('suppliers', 'customers', 'managers', 'users', 'templates', 'credits'));
    }

    /* ============================================================
     |  SEND
     * ============================================================ */

    public function send(Request $request)
    {
        $request->validate([
            'recipient_type' => 'required|string',
            'recipients'     => 'nullable|string',
            'message'        => 'required|string|max:1600',
            'sender_id'      => 'nullable|string|max:11',
            'supplier_id'    => 'nullable|exists:suppliers,id',
            'customer_id'    => 'nullable|exists:customers,id',
            'manager_id'     => 'nullable|exists:users,id',
            'user_id'        => 'nullable|exists:users,id',
        ]);

        try {
            $user          = Auth::user();
            $recipientType = $request->recipient_type;
            $recipients    = [];

            switch ($recipientType) {
                case 'custom':
                    $recipients = $this->processRecipients($request->recipients);
                    break;

                case 'all_contacts':
                    $recipients = array_merge(
                        Supplier::when($this->canFilterByBranch($user), fn ($q) => $q->where('branch_id', $user->assigned_branch))->pluck('contact')->toArray(),
                        Customer::when($this->canFilterByBranch($user), fn ($q) => $q->where('branch_id', $user->assigned_branch))->pluck('contact')->toArray()
                    );
                    break;

                case 'all_suppliers':
                    $recipients = Supplier::when($this->canFilterByBranch($user), fn ($q) => $q->where('branch_id', $user->assigned_branch))->pluck('contact')->toArray();
                    break;

                case 'specific_supplier':
                    if ($s = Supplier::find($request->supplier_id)) $recipients = [$s->contact];
                    break;

                case 'all_customers':
                    $recipients = Customer::when($this->canFilterByBranch($user), fn ($q) => $q->where('branch_id', $user->assigned_branch))->pluck('contact')->toArray();
                    break;

                case 'specific_customer':
                    if ($c = Customer::find($request->customer_id)) $recipients = [$c->contact];
                    break;

                case 'all_managers':
                    $recipients = User::where('role', 'branch_manager')
                        ->when($this->canFilterByBranch($user), fn ($q) => $q->where('assigned_branch', $user->assigned_branch))
                        ->whereNotNull('phone_number')->pluck('phone_number')->toArray();
                    break;

                case 'specific_manager':
                    if ($m = User::find($request->manager_id)) {
                        if ($m->phone_number) $recipients = [$m->phone_number];
                    }
                    break;

                case 'all_users':
                    $recipients = User::when($this->canFilterByBranch($user), fn ($q) => $q->where('assigned_branch', $user->assigned_branch))
                        ->whereNotNull('phone_number')->pluck('phone_number')->toArray();
                    break;

                case 'specific_user':
                    if ($u = User::find($request->user_id)) {
                        if ($u->phone_number) $recipients = [$u->phone_number];
                    }
                    break;

                default:
                    $recipients = [];
            }

            $recipients = array_values(array_unique(array_filter(
                array_map(fn ($p) => $this->formatPhoneNumber($p), $recipients)
            )));

            if (empty($recipients)) {
                return back()->with('error', '❌ No valid recipients found.');
            }

            $credits = $this->getLiveBalance();
            if (($credits['status'] ?? 'error') === 'error') {
                return back()->with('error', '❌ Cannot check SMS balance: ' . ($credits['message'] ?? 'API error'));
            }
            if (($credits['balance'] ?? 0) <= 0) {
                return back()->with('error', '❌ Insufficient SMS credits. Available: ' . $credits['balance'] . ' credits.');
            }

            $result = $this->sendSms($recipients, $request->message, $request->sender_id);
            $this->logSms($recipients, $request->message, $result, $user->id, $request->sender_id ?? $this->senderId, $recipientType);

            if ($result['success']) {
                return redirect()->route('communication.sms.index')
                    ->with('success', '✅ SMS sent successfully to ' . count($recipients) . ' recipient(s)!');
            }

            return back()->with('error', '❌ Failed to send SMS: ' . ($result['message'] ?? 'Unknown error'));

        } catch (\Throwable $e) {
            return back()->with('error', '❌ Failed to send SMS: ' . $e->getMessage());
        }
    }

    /* ============================================================
     |  TEMPLATES
     * ============================================================ */

    public function templates()
    {
        $user      = Auth::user();
        $templates = collect();

        try {
            if (Schema::hasTable('sms_templates')) {
                $templates = DB::table('sms_templates')
                    ->where(function ($q) use ($user) {
                        $q->where('branch_id', 0);
                        if ($user->assigned_branch !== null) $q->orWhere('branch_id', $user->assigned_branch);
                    })->get();
            }
        } catch (\Throwable $e) {}

        return view('communication.sms.templates', compact('templates'));
    }

    public function storeTemplate(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'message' => 'required|string',
            'description' => 'nullable|string',
        ]);
        $user = Auth::user();
        try {
            if (!Schema::hasTable('sms_templates')) {
                return back()->with('error', '❌ SMS templates table does not exist. Please run migrations.');
            }
            DB::table('sms_templates')->insert([
                'name' => $request->name,
                'message' => $request->message,
                'description' => $request->description,
                'branch_id' => $user->assigned_branch ?? 0,
                'created_by' => $user->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            return redirect()->route('communication.sms.templates')
                ->with('success', '✅ Template "' . $request->name . '" created successfully!');
        } catch (\Throwable $e) {
            return back()->with('error', '❌ Failed to create template: ' . $e->getMessage());
        }
    }

    public function updateTemplate(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'message' => 'required|string',
            'description' => 'nullable|string',
        ]);
        try {
            DB::table('sms_templates')->where('id', $id)->update([
                'name' => $request->name,
                'message' => $request->message,
                'description' => $request->description,
                'updated_at' => now(),
            ]);
            return redirect()->route('communication.sms.templates')
                ->with('success', '✅ Template updated successfully!');
        } catch (\Throwable $e) {
            return back()->with('error', '❌ Failed to update template: ' . $e->getMessage());
        }
    }

    public function deleteTemplate($id)
    {
        try {
            DB::table('sms_templates')->where('id', $id)->delete();
            return redirect()->route('communication.sms.templates')
                ->with('success', '✅ Template deleted successfully!');
        } catch (\Throwable $e) {
            return back()->with('error', '❌ Failed to delete template: ' . $e->getMessage());
        }
    }

    /* ============================================================
     |  SETTINGS
     * ============================================================ */

    public function settings()
    {
        $settings = $this->getSmsSettings();
        $branches = Branch::all();
        $balance  = $this->getLiveBalance();
        return view('communication.sms.settings', compact('settings', 'branches', 'balance'));
    }

    public function updateSettings(Request $request)
    {
        $request->validate([
            'api_token' => 'required|string',
            'sender_id' => 'required|string|max:11',
        ]);
        try {
            DB::table('kingosms_settings')->updateOrInsert(
                ['id' => 1],
                [
                    'api_token' => $request->api_token,
                    'sender_id' => $request->sender_id,
                    'default_sms_type' => 'plain',
                    'is_active' => $request->has('is_active') ? 1 : 0,
                    'updated_at' => now(),
                ]
            );
            return redirect()->route('communication.sms.settings')
                ->with('success', '✅ Settings updated successfully!');
        } catch (\Throwable $e) {
            return back()->with('error', '❌ Failed to update settings: ' . $e->getMessage());
        }
    }

    /* ============================================================
     |  BALANCE
     * ============================================================ */

    public function getLiveBalance()
    {
        try {
            $settings = $this->getSmsSettings();
            if (empty($settings['api_token'])) {
                return ['balance' => 0, 'currency' => 'TZS', 'status' => 'error', 'message' => 'API token not configured'];
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $settings['api_token'],
                'Accept' => 'application/json',
            ])->timeout(10)->get($this->balanceUrl);

            if (!$response->successful()) {
                $err = $response->json();
                return ['balance' => 0, 'currency' => 'TZS', 'status' => 'error',
                        'message' => $err['message'] ?? ('HTTP ' . $response->status())];
            }

            $data = $response->json();
            if (($data['status'] ?? null) !== 'success') {
                return ['balance' => 0, 'currency' => 'TZS', 'status' => 'error', 'message' => $data['message'] ?? 'Unknown API error'];
            }

            $balance = 0; $currency = 'TZS';

            if (isset($data['data']['remaining_balance'])) {
                $balanceString = $data['data']['remaining_balance'];
                $balance = $this->parseBalanceString($balanceString);
                if (preg_match('/^([A-Z]+)/', $balanceString, $m)) $currency = $m[1];
            } elseif (isset($data['data']['balance'])) {
                $balance = $data['data']['balance'];
                $currency = $data['data']['currency'] ?? 'TZS';
            } elseif (isset($data['data']['credits'])) {
                $balance = $data['data']['credits'];
            } elseif (isset($data['balance'])) {
                $balance = $data['balance'];
            } elseif (isset($data['credits'])) {
                $balance = $data['credits'];
            }

            return ['balance' => $balance, 'currency' => $currency, 'status' => 'success', 'raw' => $data];

        } catch (\Throwable $e) {
            return ['balance' => 0, 'currency' => 'TZS', 'status' => 'error', 'message' => $e->getMessage()];
        }
    }

    protected function parseBalanceString($s)
    {
        $s = preg_replace('/^[A-Z]+\s*/', '', (string) $s);
        $s = str_replace(',', '', $s);
        $s = preg_replace('/[^0-9.]/', '', $s);
        return (float) $s;
    }

    public function refreshBalance()
    {
        $balance = $this->getLiveBalance();
        try {
            DB::table('kingosms_settings')->where('id', 1)->update([
                'credit_balance' => $balance['balance'] ?? 0,
                'last_checked' => now(),
            ]);
        } catch (\Throwable $e) {}

        if (($balance['status'] ?? 'error') === 'success') {
            return redirect()->route('communication.sms.index')
                ->with('success', '✅ Balance refreshed! Available: ' . number_format($balance['balance']) . ' credits.');
        }
        return redirect()->route('communication.sms.index')
            ->with('warning', '⚠️ Could not fetch live balance: ' . ($balance['message'] ?? 'Unknown error'));
    }

    public function checkBalance() { return $this->getLiveBalance(); }
    public function getProfile()   { return $this->proxyProfile(); }

    public function debugBalance()
    {
        $settings = $this->getSmsSettings();
        $result = [
            'has_token'     => !empty($settings['api_token']),
            'token_preview' => substr($settings['api_token'] ?? '', 0, 20) . '...',
            'token_length'  => strlen($settings['api_token'] ?? ''),
            'settings'      => [
                'api_token' => $settings['api_token'] ? 'SET' : 'NOT SET',
                'sender_id' => $settings['sender_id'] ?? 'N/A',
                'is_active' => $settings['is_active'] ?? false,
            ],
        ];
        if (!empty($settings['api_token'])) {
            try {
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $settings['api_token'],
                    'Accept' => 'application/json',
                ])->timeout(10)->get($this->balanceUrl);
                $result['api_response'] = [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'json' => $response->json(),
                ];
            } catch (\Throwable $e) {
                $result['api_response'] = ['error' => $e->getMessage()];
            }
        }
        return response()->json($result);
    }

    protected function proxyProfile()
    {
        try {
            $settings = $this->getSmsSettings();
            if (empty($settings['api_token'])) {
                return response()->json(['status' => 'error', 'message' => 'API token not configured'], 401);
            }
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $settings['api_token'],
                'Accept' => 'application/json',
            ])->timeout(10)->get($this->profileUrl);

            return $response->successful()
                ? response()->json($response->json())
                : response()->json(['status' => 'error', 'message' => 'Failed to fetch profile'], $response->status());
        } catch (\Throwable $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /* ============================================================
     |  BULK SEND
     * ============================================================ */

    public function bulkSend(Request $request)
    {
        $request->validate([
            'recipients' => 'required|array',
            'recipients.*' => 'required|string',
            'message' => 'required|string|max:1600',
            'sender_id' => 'nullable|string|max:11',
        ]);

        try {
            $user = Auth::user();

            $recipients = collect($request->recipients)
                ->filter(fn ($p) => !empty($p))
                ->map(fn ($p) => $this->formatPhoneNumber($p))
                ->filter()
                ->unique()
                ->values()
                ->all();

            if (empty($recipients)) {
                return back()->with('error', '❌ No valid recipients found.');
            }

            $credits = $this->getLiveBalance();
            if (($credits['status'] ?? 'error') === 'error') {
                return back()->with('error', '❌ Cannot check SMS balance: ' . ($credits['message'] ?? 'API error'));
            }
            if (($credits['balance'] ?? 0) <= 0) {
                return back()->with('error', '❌ Insufficient SMS credits. Available: ' . $credits['balance'] . ' credits.');
            }

            $result = $this->sendSms($recipients, $request->message, $request->sender_id);
            $this->logSms($recipients, $request->message, $result, $user->id, $request->sender_id ?? $this->senderId, 'bulk_send');

            if ($result['success']) {
                return redirect()->route('communication.sms.index')
                    ->with('success', '✅ Bulk SMS sent successfully to ' . count($recipients) . ' recipients!');
            }
            return back()->with('error', '❌ Failed to send bulk SMS: ' . ($result['message'] ?? 'Unknown error'));
        } catch (\Throwable $e) {
            return back()->with('error', '❌ Failed to send bulk SMS: ' . $e->getMessage());
        }
    }

    /* ============================================================
     |  CORE SEND
     * ============================================================ */

    public function sendSms($recipients, $message, $senderId = null)
    {
        try {
            $settings = $this->getSmsSettings();
            $senderId = $senderId ?? $settings['sender_id'] ?? 'NGEMBA MINE';
            $recipientString = is_array($recipients) ? implode(',', $recipients) : $recipients;

            $payload = [
                'recipient' => $recipientString,
                'sender_id' => $senderId,
                'type'      => 'plain',
                'message'   => $message,
            ];

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . ($settings['api_token'] ?? ''),
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
            ])->timeout(30)->post($this->apiUrl, $payload);

            $result = $response->json();

            if ($response->successful() && ($result['status'] ?? null) === 'success') {
                return [
                    'success' => true,
                    'message' => 'SMS sent successfully',
                    'data'    => $result['data'] ?? null,
                    'uid'     => $this->extractUid($result['data'] ?? null),
                    'raw'     => $result,
                ];
            }

            return ['success' => false, 'message' => $result['message'] ?? 'Unknown error occurred', 'raw' => $result];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    protected function extractUid($data)
    {
        if (is_array($data))  return $data['uid'] ?? null;
        if (is_object($data)) return $data->uid ?? null;
        return null;
    }

    /* ============================================================
     |  KINGOSMS HISTORY
     * ============================================================ */

    public function getSmsHistoryPage($start, $end, $tz = null, $smsType = null, $direction = null, $page = 1)
    {
        try {
            $settings = $this->getSmsSettings();
            if (empty($settings['api_token'])) {
                return ['status' => 'error', 'message' => 'API token not configured'];
            }

            $query = [
                'start_date' => $start,
                'end_date'   => $end,
                'timezone'   => $tz ?: $this->tz,
                'page'       => $page,
            ];
            if ($smsType)   $query['sms_type']  = $smsType;
            if ($direction) $query['direction'] = $direction;

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $settings['api_token'],
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->timeout(20)->get($this->historyUrl, $query);

            if (!$response->successful()) {
                return ['status' => 'error', 'message' => 'HTTP ' . $response->status(), 'body' => $response->body()];
            }

            return $response->json();
        } catch (\Throwable $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    public function getDashboardStats($days = 30)
    {
        $start = now($this->tz)->subDays($days)->startOfDay()->format('Y-m-d H:i:s');
        $end   = now($this->tz)->format('Y-m-d H:i:s');

        $total = 0; $delivered = 0; $failed = 0; $page = 1;

        while ($page <= $this->maxHistoryPages) {
            $res = $this->getSmsHistoryPage($start, $end, $this->tz, null, null, $page);

            if (($res['status'] ?? null) !== 'success') {
                if ($page === 1) {
                    return ['status' => 'error', 'message' => $res['message'] ?? 'History fetch failed'];
                }
                break;
            }

            $payload = $res['data'] ?? [];
            $rows    = $payload['data'] ?? [];
            $total   = (int) ($payload['total'] ?? $total);

            foreach ($rows as $row) {
                $status = strtolower((string) ($row['status'] ?? ''));
                if (in_array($status, ['delivered', 'sent', 'success'], true)) $delivered++;
                elseif (in_array($status, ['failed', 'undelivered', 'rejected', 'expired'], true)) $failed++;
            }

            if (empty($payload['next_page_url'])) break;
            $page++;
        }

        return ['status' => 'success', 'total' => $total, 'delivered' => $delivered, 'failed' => $failed];
    }

    public function getSmsStatus($uid)
    {
        try {
            $settings = $this->getSmsSettings();
            if (empty($settings['api_token'])) {
                return ['status' => 'error', 'message' => 'API token not configured'];
            }
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $settings['api_token'],
                'Accept' => 'application/json',
            ])->timeout(15)->get($this->historyUrl . '/' . urlencode($uid));

            return $response->successful()
                ? $response->json()
                : ['status' => 'error', 'message' => 'HTTP ' . $response->status(), 'body' => $response->body()];
        } catch (\Throwable $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    /* ============================================================
     |  API ROWS → PAGINATOR
     * ============================================================ */

    protected function apiRowsToPaginator(array $data, int $page = 1, int $perPage = 25)
    {
        $rows    = $data['data'] ?? [];
        $total   = (int) ($data['total'] ?? count($rows));
        $perPage = (int) ($data['per_page'] ?? $perPage);

        $items = array_map(function ($row) {
            return (object) [
                'uid'       => $row['uid']       ?? null,
                'phone'     => $row['to']        ?? null,
                'sender_id' => $row['from']      ?? null,
                'message'   => $row['message']   ?? '',
                'sms_type'  => $row['sms_type']  ?? null,
                'direction' => $row['direction'] ?? null,
                'status'    => $row['status']    ?? null,
                'sms_count' => $row['sms_count'] ?? null,
                'cost'      => $row['cost']      ?? null,
                'sent_at'   => $row['sent_at']   ?? null,
                'sent_by'   => 'KingoSMS',
            ];
        }, $rows);

        return new LengthAwarePaginator(
            collect($items),
            $total,
            $perPage ?: 25,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );
    }

    /* ============================================================
     |  LOCAL LOG (audit only)
     * ============================================================ */

    protected function logSms($recipients, $message, $result, $userId, $senderId, $eventType)
    {
        if (!Schema::hasTable('sms_logs')) return;

        $recipients = is_array($recipients) ? $recipients : [$recipients];
        $status = $result['success'] ? 'sent' : 'failed';

        foreach ($recipients as $recipient) {
            try {
                $row = [
                    'sender_id'  => $senderId,
                    'phone'      => $recipient,
                    'message'    => $message,
                    'sent_by'    => $userId,
                    'event_type' => $eventType,
                    'is_auto'    => 0,
                    'sent_at'    => now(),
                    'status'     => $status,
                ];
                foreach (['branch_id', 'api_response', 'created_at', 'updated_at', 'provider_uid'] as $col) {
                    if (Schema::hasColumn('sms_logs', $col)) {
                        $row[$col] = match ($col) {
                            'branch_id'    => Auth::user()->assigned_branch ?? null,
                            'api_response' => json_encode($result),
                            'created_at'   => now(),
                            'updated_at'   => now(),
                            'provider_uid' => $result['uid'] ?? null,
                        };
                    }
                }
                DB::table('sms_logs')->insert($row);
            } catch (\Throwable $e) {
                Log::error('Failed to log SMS: ' . $e->getMessage());
            }
        }
    }

    /* ============================================================
     |  HELPERS
     * ============================================================ */

    protected function processRecipients($input)
    {
        if (is_array($input)) {
            return array_values(array_filter(array_map(fn ($p) => $this->formatPhoneNumber($p), $input)));
        }
        $parts = preg_split('/[\s,;]+/', (string) $input);
        return array_values(array_filter(array_map(fn ($p) => $this->formatPhoneNumber($p), $parts)));
    }

    public function formatPhoneNumber($phone)
    {
        $phone = preg_replace('/[^0-9]/', '', (string) $phone);
        if ($phone === '') return '';
        if (strlen($phone) === 12 && str_starts_with($phone, '255')) return $phone;
        if (strlen($phone) === 10 && preg_match('/^0[67]/', $phone)) return '255' . substr($phone, 1);
        if (strlen($phone) === 9  && preg_match('/^[67]/', $phone))   return '255' . $phone;
        if (strlen($phone) === 10 && $phone[0] === '0')               return '255' . substr($phone, 1);
        return $phone;
    }

    protected function canFilterByBranch($user)
    {
        return $user
            && !in_array($user->role, ['director', 'accountant'], true)
            && $user->assigned_branch !== null;
    }

    protected function getSmsSettings()
    {
        try {
            $s = DB::table('kingosms_settings')->where('id', 1)->first();
            if ($s) {
                return [
                    'api_token'      => $s->api_token,
                    'sender_id'      => $s->sender_id,
                    'is_active'      => $s->is_active,
                    'credit_balance' => $s->credit_balance ?? 0,
                    'last_checked'   => $s->last_checked,
                ];
            }
        } catch (\Throwable $e) {}

        return [
            'api_token'      => config('sms.api_token', ''),
            'sender_id'      => config('sms.sender_id', 'NGEMBA MINE'),
            'is_active'      => true,
            'credit_balance' => 0,
            'last_checked'   => null,
        ];
    }
}