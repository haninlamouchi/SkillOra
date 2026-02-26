<?php

namespace App\Service;

use Twilio\Rest\Client;

class SmsService
{
    private Client $client;
    private string $from;

    public function __construct(
        string $twilioSid,
        string $twilioToken,
        string $twilioFrom
    ) {
        $this->client = new Client($twilioSid, $twilioToken);
        $this->from   = $twilioFrom;
    }

    public function send(string $to, string $message): void
    {
        try {
            $this->client->messages->create($to, [
                'from' => $this->from,
                'body' => $message,
            ]);
        } catch (\Exception $e) {
            // Log l'erreur sans bloquer l'application
            error_log('SMS Error: ' . $e->getMessage());
        }
    }
}