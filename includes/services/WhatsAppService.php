<?php

$twilioAutoload = __DIR__ . '/../../vendor/autoload.php';
if (file_exists($twilioAutoload)) {
    require_once $twilioAutoload;
}

use Twilio\Rest\Client;

class WhatsAppService
{
    private string $sid;
    private string $token;
    private string $from;

    public function __construct(array $config = [])
    {
        $twilio = $config['twilio'] ?? [];

        $this->sid = (string) ($twilio['account_sid'] ?? '');
        $this->token = (string) ($twilio['auth_token'] ?? '');
        $this->from = (string) ($twilio['from_number'] ?? 'whatsapp:+14155238886');
    }

    public function formatWhatsAppPhone(string $phone, string $defaultCountryCode = '+91'): string
    {
        $digits = preg_replace('/\D/', '', trim($phone));

        if ($digits === '') {
            return '';
        }

        if (strlen($digits) === 12 && strpos($digits, '91') === 0) {
            $digits = substr($digits, 2);
        }

        if (strlen($digits) === 11 && strpos($digits, '0') === 0) {
            $digits = substr($digits, 1);
        }

        if (!preg_match('/^[6-9]\d{9}$/', $digits)) {
            return '';
        }

        return 'whatsapp:' . $defaultCountryCode . $digits;
    }

    public function buildOrderPlacedMessage(string $name, int $orderId, float $amount): string
    {
        $customerName = trim($name) !== '' ? trim($name) : 'Customer';

        return implode("\n", [
            "Hello {$customerName},",
            "Your order #{$orderId} has been placed successfully.",
            'Total Amount: ₹' . number_format($amount, 2),
            'Thank you for shopping with us!',
        ]);
    }

    public function sendWhatsApp(string $phone, string $message): bool
    {
        if (
            $phone === '' ||
            $message === '' ||
            $this->sid === '' ||
            $this->token === '' ||
            $this->sid === 'YOUR_TWILIO_SID' ||
            $this->sid === 'YOUR_TWILIO_ACCOUNT_SID' ||
            $this->token === 'YOUR_TWILIO_AUTH_TOKEN'
        ) {
            error_log('Twilio WhatsApp send skipped because configuration or payload is incomplete.');
            return false;
        }

        if (!class_exists(Client::class)) {
            error_log('Twilio SDK is not installed. Run composer require twilio/sdk');
            return false;
        }

        try {
            $client = new Client($this->sid, $this->token);
            $client->messages->create($phone, [
                'from' => $this->from,
                'body' => $message,
            ]);

            return true;
        } catch (Throwable $e) {
            error_log('Twilio WhatsApp send failed: ' . $e->getMessage());
            return false;
        }
    }
}
