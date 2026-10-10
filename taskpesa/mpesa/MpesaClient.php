<?php
/**
 * Minimal client for the Safaricom M-Pesa Daraja API, used to send
 * withdrawal payouts to a user's phone (B2C — Business to Customer).
 *
 * Section 3.5.7: credentials are injected via config/mpesa_config.php
 * (which itself reads environment variables), every request goes over
 * HTTPS, and callback payloads are validated before being trusted.
 */

declare(strict_types=1);

class MpesaClient
{
    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Request a short-lived OAuth access token from Daraja.
     * Throws RuntimeException on failure.
     */
    public function getAccessToken(): string
    {
        $url = $this->config['base_url'] . '/oauth/v1/generate?grant_type=client_credentials';

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPAUTH       => CURLAUTH_BASIC,
            CURLOPT_USERPWD        => $this->config['consumer_key'] . ':' . $this->config['consumer_secret'],
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        if ($response === false || $httpCode !== 200) {
            throw new RuntimeException('Unable to reach M-Pesa (auth): ' . ($curlErr ?: "HTTP {$httpCode}"));
        }

        $data = json_decode($response, true);
        if (empty($data['access_token'])) {
            throw new RuntimeException('M-Pesa did not return an access token.');
        }

        return $data['access_token'];
    }

    /**
     * Initiate a B2C payment (payout) to a customer phone number.
     *
     * @param string $phone       MSISDN in 2547XXXXXXXX format
     * @param float  $amount      Amount in KES
     * @param string $originatorConversationId Your own unique reference for this request
     *
     * @return array Decoded JSON response from Daraja (contains ConversationID etc.)
     */
    public function b2cPayment(string $phone, float $amount, string $originatorConversationId): array
    {
        $token = $this->getAccessToken();
        $url = $this->config['base_url'] . '/mpesa/b2c/v1/paymentrequest';

        $payload = [
            'OriginatorConversationID' => $originatorConversationId,
            'InitiatorName'            => $this->config['initiator_name'],
            'SecurityCredential'       => $this->config['security_cred'],
            'CommandID'                => 'BusinessPayment',
            'Amount'                   => (int) round($amount),
            'PartyA'                   => $this->config['shortcode'],
            'PartyB'                   => $phone,
            'Remarks'                  => 'TaskPesa withdrawal',
            'QueueTimeOutURL'          => $this->config['timeout_url'],
            'ResultURL'                => $this->config['result_url'],
            'Occasion'                 => 'Withdrawal',
        ];

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $token,
            ],
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            throw new RuntimeException('Unable to reach M-Pesa (B2C): ' . $curlErr);
        }

        $data = json_decode($response, true) ?? [];
        if ($httpCode >= 400) {
            $msg = $data['errorMessage'] ?? "HTTP {$httpCode}";
            throw new RuntimeException('M-Pesa rejected the payout request: ' . $msg);
        }

        return $data;
    }
}
