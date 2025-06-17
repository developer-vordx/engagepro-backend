<?php

namespace App\Mail;

use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PasswordResetMail extends Mailable
{
    use Queueable, SerializesModels;

    public $token;
    public $email;

    /**
     * Create a new message instance.
     */
    public function __construct($token, $email)
    {
        $this->token = $token;
        $this->email = $email;
    }


    public function build()
    {
        try{ 

            $resetUrl = "http://localhost:3000/auth/change-password/?token={$this->token}&email={$this->email}";

            Log::info(['resetUrl' => $resetUrl]);
            return $this->subject('Reset Your Password')
                ->view('emails.password-reset')
                ->with([
                    'resetUrl' => $resetUrl,
                    'email' => $this->email
                ]);
        } catch(Exception $e){
            Log::info(['send mail error' =>$e->getMessage() ]);
        }

    }
}