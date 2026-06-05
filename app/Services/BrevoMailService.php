<?php
// app/Services/BrevoMailService.php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class BrevoMailService
{
    private string $apiKey;
    private string $fromEmail;
    private string $fromName;

    public function __construct()
    {
        $this->apiKey    = env('BREVO_API_KEY', '');
        $this->fromEmail = env('MAIL_FROM_ADDRESS', 'noreply@simmo.com');
        $this->fromName  = env('MAIL_FROM_NAME', 'SIMMo');
    }

    public function send(string $to, string $toName, string $subject, string $htmlContent): bool
    {
        try {
            $response = Http::withHeaders([
                'api-key'      => $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post('https://api.brevo.com/v3/smtp/email', [
                'sender'      => [
                    'name'  => $this->fromName,
                    'email' => $this->fromEmail,
                ],
                'to'          => [['email' => $to, 'name' => $toName]],
                'subject'     => $subject,
                'htmlContent' => $htmlContent,
            ]);

            if ($response->failed()) {
                Log::error('Brevo error: ' . $response->body());
                return false;
            }

            return true;

        } catch (\Exception $e) {
            Log::error('Brevo mail error: ' . $e->getMessage());
            return false;
        }
    }
}
