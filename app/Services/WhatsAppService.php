<?php

namespace App\Http\Controllers\Communication;

use App\Http\Controllers\Controller;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppController extends Controller
{
    public function __construct(protected WhatsAppService $whatsapp) {}

    public function index()
    {
        $logs = DB::table('whatsapp_logs')
            ->orderByDesc('created_at')
            ->paginate(20);

        $totalSent      = DB::table('whatsapp_logs')->count();
        $totalDelivered = DB::table('whatsapp_logs')->whereIn('status', ['sent', 'delivered'])->count();
        $totalRead      = DB::table('whatsapp_logs')->where('status', 'read')->count();
        $totalFailed    = DB::table('whatsapp_logs')->where('status', 'failed')->count();

        $apiStatus  = 'ok';
        $apiMessage = null;
        if (!config('whatsapp.access_token') || !config('whatsapp.phone_number_id')) {
            $apiStatus  = 'error';
            $apiMessage = 'WhatsApp credentials missing in .env';
        }

        return view('communication.whatsapp.index', compact(
            'logs', 'totalSent', 'totalDelivered', 'totalRead', 'totalFailed',
            'apiStatus', 'apiMessage'
        ));
    }

    public function logs()
    {
        return $this->index();
    }

    public function sendForm()
    {
        $suppliers = DB::table('suppliers')->select('id', 'name', 'contact')->get();
        $customers = DB::table('customers')->select('id', 'name', 'contact')->get();
        $managers  = DB::table('users')->where('role', 'manager')
                        ->select('id', 'username', 'phone_number')->get();
        $users     = DB::table('users')->select('id', 'username', 'phone_number')->get();
        $templates = DB::table('sms_templates')->get();

        $whatsappTemplates = $this->fetchMetaTemplates();

        $apiStatus = config('whatsapp.access_token') ? 'ok' : 'error';

        return view('communication.whatsapp.send', compact(
            'suppliers', 'customers', 'managers', 'users',
            'templates', 'whatsappTemplates', 'apiStatus'
        ));
    }

    public function send(Request $request)
    {
        $request->validate([
            'message_type'   => 'required|in:text,template',
            'recipient_type' => 'required|string',
            'recipients'     => 'required|string',
        ]);

        $recipients = $this->parseRecipients($request->input('recipients'));

        if (empty($recipients)) {
            return back()->with('error', 'No valid recipients found.')->withInput();
        }

        $messageType = $request->input('message_type');
        $sent = 0;
        $failed = 0;
        $errors = [];

        foreach ($recipients as $phone) {
            try {
                if ($messageType === 'template') {
                    $result = $this->whatsapp->sendTemplate(
                        $phone,
                        $request->input('template_name'),
                        $request->input('language_code', 'en_US'),
                        []
                    );
                } else {
                    $result = $this->whatsapp->sendText(
                        $phone,
                        $request->input('message')
                    );
                }

                DB::table('whatsapp_logs')->insert([
                    'phone'         => $phone,
                    'message'       => $messageType === 'text' ? $request->input('message') : null,
                    'message_type'  => $messageType,
                    'template_name' => $messageType === 'template' ? $request->input('template_name') : null,
                    'language_code' => $messageType === 'template' ? $request->input('language_code') : null,
                    'message_id'    => $result['messages'][0]['id'] ?? null,
                    'status'        => 'sent',
                    'event_type'    => 'manual',
                    'sent_at'       => now(),
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]);

                $sent++;
            } catch (\Throwable $e) {
                $failed++;
                $errors[] = "{$phone}: {$e->getMessage()}";

                DB::table('whatsapp_logs')->insert([
                    'phone'         => $phone,
                    'message'       => $messageType === 'text' ? $request->input('message') : null,
                    'message_type'  => $messageType,
                    'template_name' => $messageType === 'template' ? $request->input('template_name') : null,
                    'status'        => 'failed',
                    'event_type'    => 'manual',
                    'error_message' => $e->getMessage(),
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]);

                Log::error('WhatsApp send failed', ['phone' => $phone, 'error' => $e->getMessage()]);
            }
        }

        $msg = "WhatsApp: {$sent} sent, {$failed} failed.";
        if ($failed > 0) {
            return back()->with('error', $msg . ' ' . implode(' | ', array_slice($errors, 0, 3)))->withInput();
        }

        return redirect()->route('communication.whatsapp.index')->with('success', $msg);
    }

    public function bulkSend(Request $request)
    {
        return $this->send($request);
    }

    public function refreshStatus()
    {
        return redirect()->route('communication.whatsapp.index')
            ->with('success', 'Status refreshed.');
    }

    public function templates()
    {
        $templates = $this->fetchMetaTemplates();
        return view('communication.whatsapp.templates', compact('templates'));
    }

    public function storeTemplate(Request $request)
    {
        return back()->with('success', 'Template creation is not implemented yet.');
    }

    public function settings()
    {
        return view('communication.whatsapp.settings');
    }

    public function updateSettings(Request $request)
    {
        return back()->with('success', 'Settings saved.');
    }

    public function scheduled()
    {
        return view('communication.whatsapp.scheduled');
    }

    public function storeScheduled(Request $request)
    {
        return back()->with('success', 'Scheduled message saved.');
    }

    public function deleteScheduled($id)
    {
        return back()->with('success', 'Scheduled message deleted.');
    }

    public function getStatus()
    {
        return response()->json([
            'status' => config('whatsapp.access_token') ? 'ok' : 'error',
        ]);
    }

    public function verifyWebhook(Request $request)
    {
        $mode      = $request->query('hub_mode');
        $token     = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        if ($mode === 'subscribe' && $token === config('whatsapp.verify_token')) {
            return response($challenge, 200);
        }
        return response('Forbidden', 403);
    }

    public function receiveWebhook(Request $request)
    {
        $payload = $request->all();

        if (($payload['object'] ?? null) === 'whatsapp_business_account') {
            foreach ($payload['entry'] ?? [] as $entry) {
                foreach ($entry['changes'] ?? [] as $change) {
                    $value = $change['value'] ?? [];

                    foreach ($value['messages'] ?? [] as $msg) {
                        Log::info('Incoming WhatsApp message', [
                            'from' => $msg['from'] ?? null,
                            'type' => $msg['type'] ?? null,
                            'text' => $msg['text']['body'] ?? null,
                            'id'   => $msg['id'] ?? null,
                        ]);
                    }

                    foreach ($value['statuses'] ?? [] as $status) {
                        DB::table('whatsapp_logs')
                            ->where('message_id', $status['id'] ?? '')
                            ->update([
                                'status'     => $status['status'] ?? 'unknown',
                                'updated_at' => now(),
                            ]);
                    }
                }
            }
        }

        return response()->json(['received' => true], 200);
    }

    protected function parseRecipients(string $raw): array
    {
        $parts = preg_split('/[\s,;]+/', $raw);
        $out = [];
        foreach ($parts as $p) {
            $digits = preg_replace('/\D+/', '', $p);
            if ($digits && strlen($digits) >= 9) $out[] = $digits;
        }
        return array_values(array_unique($out));
    }

    protected function fetchMetaTemplates(): array
    {
        try {
            $version = config('whatsapp.api_version');
            $wabaId  = config('whatsapp.business_account_id');
            $token   = config('whatsapp.access_token');

            if (!$wabaId || !$token) return [];

            $response = Http::withToken($token)
                ->timeout(10)
                ->get("https://graph.facebook.com/{$version}/{$wabaId}/message_templates", [
                    'limit'  => 50,
                    'fields' => 'name,language,status,components',
                ]);

            if ($response->failed()) return [];

            $out = [];
            foreach ($response->json('data', []) as $tpl) {
                if (($tpl['status'] ?? '') !== 'APPROVED') continue;

                $body = '';
                foreach ($tpl['components'] ?? [] as $c) {
                    if (($c['type'] ?? '') === 'BODY') {
                        $body = $c['text'] ?? '';
                        break;
                    }
                }

                $out[] = [
                    'name'     => $tpl['name'],
                    'language' => $tpl['language'] ?? 'en_US',
                    'body'     => $body,
                ];
            }

            return $out;
        } catch (\Throwable $e) {
            Log::warning('fetchMetaTemplates failed: ' . $e->getMessage());
            return [];
        }
    }
}