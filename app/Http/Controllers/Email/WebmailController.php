<?php

namespace App\Http\Controllers\Email;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Models\Email;
use Carbon\Carbon;
use Illuminate\Support\Facades\Config;

class WebmailController extends Controller
{
    /**
     * Show the webmail index page
     */
    public function index()
    {
        $user = Auth::user();
        $error = null;
        $connected = false;
        $folders = [];
        $unreadCount = 0;
        $emails = [];

        // Check if user has email configured
        if ($user->email && $user->email_password) {
            $connected = true;
            
            // Get emails from database
            $emails = Email::where('user_id', $user->id)
                ->where('folder', 'INBOX')
                ->orderBy('created_at', 'desc')
                ->limit(20)
                ->get()
                ->map(function($email) {
                    return [
                        'id' => $email->id,
                        'subject' => $email->subject ?: '(No Subject)',
                        'from' => $email->from_email,
                        'from_name' => $email->from_name ?: $email->from_email,
                        'date' => $email->created_at->format('Y-m-d H:i:s'),
                        'is_read' => $email->is_read,
                        'has_attachment' => false,
                        'body_preview' => substr(strip_tags($email->body), 0, 100),
                    ];
                })
                ->toArray();

            $unreadCount = Email::where('user_id', $user->id)
                ->where('is_read', false)
                ->count();

            $folders = [
                ['name' => 'INBOX', 'count' => Email::where('user_id', $user->id)->where('folder', 'INBOX')->count(), 'unread' => Email::where('user_id', $user->id)->where('folder', 'INBOX')->where('is_read', false)->count()],
                ['name' => 'Sent', 'count' => Email::where('user_id', $user->id)->where('folder', 'Sent')->count(), 'unread' => 0],
                ['name' => 'Drafts', 'count' => Email::where('user_id', $user->id)->where('folder', 'Drafts')->count(), 'unread' => 0],
                ['name' => 'Trash', 'count' => Email::where('user_id', $user->id)->where('folder', 'Trash')->count(), 'unread' => 0],
            ];
        } else {
            $error = 'Email settings not configured. Please set up your email account.';
        }

        return view('email.index', compact('user', 'folders', 'unreadCount', 'emails', 'connected', 'error'));
    }

    /**
     * Get emails from a specific folder
     */
    public function getFolder(Request $request)
    {
        $user = Auth::user();
        $folderName = $request->input('folder', 'INBOX');

        $emails = Email::where('user_id', $user->id)
            ->where('folder', $folderName)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function($email) {
                return [
                    'id' => $email->id,
                    'subject' => $email->subject ?: '(No Subject)',
                    'from' => $email->from_email,
                    'from_name' => $email->from_name ?: $email->from_email,
                    'date' => $email->created_at->format('Y-m-d H:i:s'),
                    'is_read' => $email->is_read,
                    'has_attachment' => false,
                    'body_preview' => substr(strip_tags($email->body), 0, 100),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $emails,
            'folder' => $folderName,
            'count' => Email::where('user_id', $user->id)->where('folder', $folderName)->count(),
            'unread' => Email::where('user_id', $user->id)->where('folder', $folderName)->where('is_read', false)->count(),
        ]);
    }

    /**
     * Get a single email
     */
    public function getMessage(Request $request)
    {
        $user = Auth::user();
        $id = $request->input('uid');

        $email = Email::where('user_id', $user->id)
            ->where('id', $id)
            ->first();

        if (!$email) {
            return response()->json([
                'success' => false,
                'message' => 'Email not found'
            ]);
        }

        // Mark as read
        $email->is_read = true;
        $email->save();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $email->id,
                'subject' => $email->subject ?: '(No Subject)',
                'from' => $email->from_email,
                'from_name' => $email->from_name ?: $email->from_email,
                'to' => $email->to_email,
                'date' => $email->created_at->format('Y-m-d H:i:s'),
                'body_html' => $email->body_html ?: $email->body,
                'body_text' => strip_tags($email->body),
                'is_read' => $email->is_read,
                'has_attachment' => false,
                'folder' => $email->folder,
            ]
        ]);
    }

    /**
     * Send an email using user's personal email credentials
     */
    public function send(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'to' => 'required|email',
            'subject' => 'required|string|max:255',
            'body' => 'required|string',
        ]);

        // Check if user has email credentials configured
        if (!$user->email || !$user->email_password) {
            return response()->json([
                'success' => false,
                'message' => 'Please configure your email settings first.'
            ], 400);
        }

        try {
            // ✅ FIXED: Send using user's mail credentials
            $this->sendUsingUserMail($user, $request);

            // Save sent email to database
            Email::create([
                'user_id' => $user->id,
                'from_email' => $user->email,
                'from_name' => $user->full_name ?? $user->username,
                'to_email' => $request->to,
                'to_name' => $request->to,
                'subject' => $request->subject,
                'body' => $request->body,
                'body_html' => $request->body,
                'is_sent' => true,
                'folder' => 'Sent',
                'received_at' => Carbon::now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Email sent successfully using your personal email!'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send email: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send email using user's personal credentials with dynamic mailer
     */
    private function sendUsingUserMail($user, $request)
    {
        // ✅ Get decrypted password
        $password = decrypt($user->email_password);
        
        // ✅ Get SMTP settings with defaults
        $host = $user->email_smtp_host ?? 'smtp.gmail.com';
        $port = $user->email_smtp_port ?? 587;
        $encryption = $user->email_encryption ?? 'tls';
        
        // ✅ Create a dynamic mailer configuration
        $config = [
            'transport' => 'smtp',
            'host' => $host,
            'port' => $port,
            'encryption' => $encryption,
            'username' => $user->email,
            'password' => $password,
            'timeout' => null,
            'auth_mode' => null,
        ];

        // ✅ Create a new mailer instance with user's config
        $mailer = Mail::mailer();
        
        // ✅ Set the mailer configuration
        $mailer->setSymfonyTransport(
            (new \Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport(
                $host,
                $port,
                $encryption === 'ssl' ? 'ssl' : ($encryption === 'tls' ? 'tls' : null)
            ))
            ->setUsername($user->email)
            ->setPassword($password)
        );

        // ✅ Send the email
        $mailer->send([], [], function ($message) use ($request, $user) {
            $message->to($request->to)
                    ->from($user->email, $user->full_name ?? $user->username)
                    ->subject($request->subject)
                    ->html($request->body);
        });
    }

    /**
     * Delete an email
     */
    public function delete(Request $request)
    {
        $user = Auth::user();
        $id = $request->input('uid');

        $email = Email::where('user_id', $user->id)
            ->where('id', $id)
            ->first();

        if ($email) {
            $email->delete();
            return response()->json([
                'success' => true,
                'message' => 'Email deleted successfully'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Email not found'
        ]);
    }

    /**
     * Move email to another folder
     */
    public function move(Request $request)
    {
        $user = Auth::user();
        $id = $request->input('uid');
        $toFolder = $request->input('to_folder');

        $email = Email::where('user_id', $user->id)
            ->where('id', $id)
            ->first();

        if ($email) {
            $email->folder = $toFolder;
            $email->save();

            return response()->json([
                'success' => true,
                'message' => 'Email moved successfully'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Email not found'
        ]);
    }

    /**
     * Get email settings form
     */
    public function settings()
    {
        $user = Auth::user();
        return view('email.settings', compact('user'));
    }

    /**
     * Update email settings
     */
    public function updateSettings(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'email_password' => 'nullable|string|min:6',
            'email_smtp_host' => 'nullable|string|max:255',
            'email_smtp_port' => 'nullable|integer|min:1|max:65535',
            'email_encryption' => 'nullable|in:ssl,tls',
        ]);

        if ($request->email_password) {
            $user->email_password = encrypt($request->email_password);
        }
        $user->email_smtp_host = $request->email_smtp_host ?? 'smtp.gmail.com';
        $user->email_smtp_port = $request->email_smtp_port ?? 587;
        $user->email_encryption = $request->email_encryption ?? 'tls';
        $user->save();

        return redirect()->route('email.settings')
            ->with('success', 'Email settings updated successfully! You can now send emails from your personal account.');
    }

    /**
     * Test email connection
     */
    public function testConnection(Request $request)
    {
        $user = Auth::user();

        if (!$user->email || !$user->email_password) {
            return response()->json([
                'success' => false,
                'message' => 'Please configure your email settings first.'
            ]);
        }

        try {
            // Test connection by sending a test email
            $this->sendTestEmail($user);
            
            return response()->json([
                'success' => true,
                'message' => 'Email connection successful! Your email is configured correctly.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Connection failed: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Send a test email to verify connection
     */
    private function sendTestEmail($user)
    {
        $password = decrypt($user->email_password);
        $host = $user->email_smtp_host ?? 'smtp.gmail.com';
        $port = $user->email_smtp_port ?? 587;
        $encryption = $user->email_encryption ?? 'tls';

        $mailer = Mail::mailer();
        
        $mailer->setSymfonyTransport(
            (new \Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport(
                $host,
                $port,
                $encryption === 'ssl' ? 'ssl' : ($encryption === 'tls' ? 'tls' : null)
            ))
            ->setUsername($user->email)
            ->setPassword($password)
        );

        $mailer->raw('This is a test email from your webmail system.', function ($message) use ($user) {
            $message->to($user->email)
                    ->from($user->email, $user->full_name ?? $user->username)
                    ->subject('Test Email Connection');
        });
    }
}