<?php

namespace App\Mail\Auth;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TwoFactorVerifitionMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    protected $otp;
    protected $email;
    protected $name;
    public function __construct($emil,$otp,$name)
    {
        $this->email = $emil;
        $this->otp = $otp;
        $this->name = $name;
    }
    public function build()
    {
        return $this->subject('Driver Application')
            ->view('emails.auth.twofactorverification')
            ->with([
                'email' =>  $this->email,
                'otp' =>  $this->otp,
                'name' =>  $this->name,
            ]);
    }
    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
