<?php

namespace App\Jobs\Auth;

use App\Mail\Api\Broker\Drivers\AcceptMail;
use App\Mail\Auth\TwoFactorVerifitionMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class TwoFactorVerifitionJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    protected $otp;
    protected $email;
    protected $name;
    public function __construct($email,$otp,$name)
    {
        $this->email = $email;
        $this->otp = $otp;
        $this->name = $name;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try{
            Mail::to($this->email)->send(new TwoFactorVerifitionMail($this->email,$this->otp,$this->name));
            Log::info(['success'=> '2fa accept mail send successfuly']);
        }catch(Exception $e){
            Log::error(['error'=> $e->getMessage()]);
        }
    }
}
