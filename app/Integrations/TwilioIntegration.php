<?php

namespace App\Integrations;

use Twilio\Rest\Client;
class TwilioIntegration
{
    protected $client;

    public function __construct()
    {
        $this->client = new Client(config('services.twilio.sid'), config('services.twilio.token'));
    }

    public function sendSMS($to, $message)
    {
        return $this->client->messages->create($to, [
            'from' => config('services.twilio.from'),
            'body' => $message
        ]);
    }

}
