<?php

namespace App\Http\Controllers\Api\Communication;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\Log;

class WhatsAppController extends Controller
{
    protected $whatsapp;

    public function __construct(WhatsAppService $whatsapp)
    {
        $this->whatsapp = $whatsapp;
    }

    public function index(Request $request)
    {
        $logs = DB::table('whatsapp_logs')
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json([
            'status' => true,
            'data' => $logs->map(fn($l) => [
                'id' => (string) $l->id,
                'phone' => $l->phone,
                'message' => $l->message,
                'status' => strtoupper($l->status),
                'date' => \Carbon\Carbon::parse($l->created_at)->format('d M Y, H:i'),
            ]),
            'meta' => ['current_page' => $logs->currentPage(), 'last_page' => $logs->lastPage(), 'total' => $logs->total()]
        ]);
    }

    public function sendText(Request $request)
    {
        $request->validate([
            'recipients' => 'required|string',
            'message' => 'required|string',
        ]);

        $recipients = $this->parseRecipients($request->recipients);
        if (empty($recipients)) return response()->json(['status' => false, 'message' => 'No valid recipients.'], 422);

        $sent = 0; $failed = 0;
        foreach ($recipients as $phone) {
            try {
                $result = $this->whatsapp->sendText($phone, $request->message);
                DB::table('whatsapp_logs')->insert([
                    'phone' => $phone,
                    'message' => $request->message,
                    'message_type' => 'text',
                    'message_id' => $result['messages'][0]['id'] ?? null,
                    'status' => 'sent',
                    'event_type' => 'api',
                    'sent_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $sent++;
            } catch (\Throwable $e) {
                $failed++;
                Log::error('WhatsApp send failed: ' . $e->getMessage());
            }
        }

        return response()->json([
            'status' => true,
            'message' => "WhatsApp: {$sent} sent, {$failed} failed.",
        ]);
    }

    public function sendTemplate(Request $request)
    {
        $request->validate([
            'recipients' => 'required|string',
            'template_name' => 'required|string',
            'language_code' => 'nullable|string',
        ]);

        $recipients = $this->parseRecipients($request->recipients);
        if (empty($recipients)) return response()->json(['status' => false, 'message' => 'No valid recipients.'], 422);

        $sent = 0; $failed = 0;
        foreach ($recipients as $phone) {
            try {
                $result = $this->whatsapp->sendTemplate($phone, $request->template_name, $request->language_code ?? 'en_US', []);
                DB::table('whatsapp_logs')->insert([
                    'phone' => $phone,
                    'message_type' => 'template',
                    'template_name' => $request->template_name,
                    'language_code' => $request->language_code ?? 'en_US',
                    'message_id' => $result['messages'][0]['id'] ?? null,
                    'status' => 'sent',
                    'event_type' => 'api',
                    'sent_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $sent++;
            } catch (\Throwable $e) {
                $failed++;
                Log::error('WhatsApp template failed: ' . $e->getMessage());
            }
        }

        return response()->json([
            'status' => true,
            'message' => "WhatsApp Templates: {$sent} sent, {$failed} failed.",
        ]);
    }

    private function parseRecipients(string $raw): array
    {
        $parts = preg_split('/[\s,;]+/', $raw);
        $out = [];
        foreach ($parts as $p) {
            $digits = preg_replace('/\D+/', '', $p);
            if ($digits && strlen($digits) >= 9) $out[] = $digits;
        }
        return array_values(array_unique($out));
    }
}