<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class LoginNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $ip;
    public $userAgent;
    public $time;
    public $resetUrl;

    public function __construct($user, $ip, $userAgent, $time, $resetUrl)
    {
        $this->user      = $user;
        $this->ip        = $ip;
        $this->userAgent = $userAgent;
        $this->time      = $time;
        $this->resetUrl  = $resetUrl;
    }

    public function build()
    {
        return $this->subject('New Login to Your Ngemba Mining Account')
                    ->view('emails.login-notification');
    }
}