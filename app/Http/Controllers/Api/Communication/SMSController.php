<?php

namespace App\Http\Controllers\Api\Communication;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Http\Controllers\Communication\SMSController as WebSMSController;

class SMSController extends Controller
{
    /**
     * GET /api/sms
     * Returns dashboard stats + SMS balance for the app header.
     */
    public function index(Request $request)
    {
        $webSms = app(WebSMSController::class);

        // Both are already `public` in the Web controller — safe to call.
        $stats   = $webSms->getDashboardStats();
        $balance = $webSms->getLiveBalance();

        return response()->json([
            'status' => true,
            'data' => [
                'total_sent' => $stats['total']     ?? 0,
                'delivered'  => $stats['delivered'] ?? 0,
                'failed'     => $stats['failed']    ?? 0,
                'balance'    => $balance['balance'] ?? 0,
                'currency'   => $balance['currency'] ?? 'TZS',
            ],
        ]);
    }

    /**
     * POST /api/sms/send
     * Send an SMS to one or more recipients.
     */
    public function send(Request $request)
    {
        $request->validate([
            'recipients' => 'required|string',
            'message'    => 'required|string|max:1600',
        ]);

        // 1. Parse & normalise phone numbers locally (no dependency on Web controller).
        $recipients = $this->parseRecipients($request->recipients);

        if (empty($recipients)) {
            return response()->json([
                'status'  => false,
                'message' => 'No valid recipients found.',
            ], 422);
        }

        // 2. Delegate the actual send to the Web controller.
        //    sendSms() is already `public` — safe to call.
        $webSms = app(WebSMSController::class);
        $result = $webSms->sendSms($recipients, $request->message);

        // 3. Write our own log row (no dependency on Web's logSms()).
        $this->logSmsLocally(
            $recipients,
            $request->message,
            $result,
            $request->user()->id,
            'api_send'
        );

        if ($result['success']) {
            return response()->json([
                'status'  => true,
                'message' => 'SMS sent successfully to ' . count($recipients) . ' recipient(s).',
            ]);
        }

        return response()->json([
            'status'  => false,
            'message' => 'Failed: ' . ($result['message'] ?? 'Unknown error'),
        ], 422);
    }

    /* ============================================================
     |  PRIVATE HELPERS (API-only, no Web controller dependency)
     * ============================================================ */

    /**
     * Parse raw phone input into an array of normalised numbers.
     */
    private function parseRecipients(string $raw): array
    {
        $parts = preg_split('/[\s,;]+/', $raw);
        $out   = [];

        foreach ($parts as $p) {
            $digits = preg_replace('/\D+/', '', $p);
            if ($digits && strlen($digits) >= 9) {
                $out[] = $digits;
            }
        }

        return array_values(array_unique($out));
    }

    /**
     * Insert one row per recipient into sms_logs (if the table exists).
     */
    private function logSmsLocally(array $recipients, string $message, array $result, int $userId, string $eventType): void
    {
        if (!Schema::hasTable('sms_logs')) {
            return;
        }

        $status   = $result['success'] ? 'sent' : 'failed';
        $senderId = 'NGEMBA MINE';

        foreach ($recipients as $recipient) {
            try {
                DB::table('sms_logs')->insert([
                    'sender_id'  => $senderId,
                    'phone'      => $recipient,
                    'message'    => $message,
                    'sent_by'    => $userId,
                    'event_type' => $eventType,
                    'is_auto'    => 0,
                    'sent_at'    => now(),
                    'status'     => $status,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } catch (\Throwable $e) {
                \Log::error('API SMS log failed: ' . $e->getMessage());
            }
        }
    }
}