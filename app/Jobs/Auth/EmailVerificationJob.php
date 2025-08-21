<?php

namespace App\Jobs\Auth;

use App\Mail\Auth\EmailVerificationMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class EmailVerificationJob implements ShouldQueue
{
    use Queueable, Dispatchable, InteractsWithQueue, SerializesModels;

    public mixed $customer;
    public string $token;

    /**
     * Create a new job instance.
     */
    public function __construct($customer, $token)
    {
        $this->customer = $customer;
        $this->token = $token;
        $this->onQueue('forgot-password');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Mail::to($this->customer->email)->send(new EmailVerificationMail($this->customer, $this->token));
    }
}
