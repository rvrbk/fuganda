<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * Airtel Uganda Airtel Money API Service
 * 
 * Documentation: developers.airtel.africa
 * API Version: v1
 */
class AirtelMoneyService
{
    // API Endpoints
    private const TOKEN_PATH = '/auth/oauth2/token';
    private const COLLECT_PATH = '/merchant/v1/payments/';
    private const DISBURSE_PATH = '/merchant/v1/disbursements/';
    private const BALANCE_PATH = '/merchant/v1/balance';
    private const TRANSACTION_STATUS_PATH = '/merchant/v1/payments/';

    // Provider constant
    public const PROVIDER_AIRTEL = 'airtel';

    /**
     * Get authentication token from Airtel Money API
     */
    public function getToken(bool $production = false): string
    {
        // In local/testing with placeholder credentials, return a mock token
        if (app()->environment('local') || app()->environment('testing')) {
            if ((string) config('services.airtel_money.client_id') === 'placeholder_client_id') {
                return 'mock_token_' . time();
            }
        }

        $baseUrl = $this->getBaseUrl($production);
        $clientId = (string) config('services.airtel_money.client_id');
        $clientSecret = (string) config('services.airtel_money.client_secret');

        $response = Http::asForm()
            ->post($baseUrl . self::TOKEN_PATH, [
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'grant_type' => 'client_credentials',
            ]);

        if ($response->failed()) {
            $error = $response->json();
            $errorMessage = data_get($error, 'error_description', data_get($error, 'message', 'Failed to authenticate with Airtel Money API'));
            
            Log::error('Airtel Money token request failed', [
                'response' => $response->body(),
                'status' => $response->status(),
            ]);

            throw ValidationException::withMessages([
                'status' => ['Airtel Money authentication failed: ' . $errorMessage],
            ]);
        }

        return (string) data_get($response->json(), 'access_token', '');
    }

    /**
     * Collect payment from customer
     * 
     * @param string $phoneNumber Customer phone number in international format (e.g., 256701234567)
     * @param int $amount Amount in UGX
     * @param string $reference Unique merchant reference
     * @param string $description Payment description
     * @param bool $production Whether to use production environment
     */
    public function collect(
        string $phoneNumber,
        int $amount,
        string $reference,
        string $description,
        bool $production = false,
        ?string $callbackUrl = null
    ): array {
        // In local/testing with placeholder credentials, return a mock response
        if (app()->environment('local') || app()->environment('testing')) {
            if ((string) config('services.airtel_money.client_id') === 'placeholder_client_id') {
                Log::debug('Airtel Money mock request in local environment', [
                    'reference' => $reference,
                    'phone' => $phoneNumber,
                    'amount' => $amount,
                ]);

                return [
                    'provider' => self::PROVIDER_AIRTEL,
                    'provider_transaction_id' => 'mock_' . $reference . '_' . time(),
                    'reference' => $reference,
                    'status' => 'PENDING',
                    'message' => 'Mock Airtel Money request in local environment',
                ];
            }
        }

        $baseUrl = $this->getBaseUrl($production);
        $token = $this->getToken($production);
        $merchantCode = (string) config('services.airtel_money.merchant_code');
        
        if ($callbackUrl === null) {
            $callbackUrl = (string) config('services.airtel_money.callback_url');
        }

        $payload = [
            'merchantId' => $merchantCode,
            'reference' => $reference,
            'subscriber' => [
                'country' => 'UG',
                'currency' => 'UGX',
                'msisdn' => $this->normalizePhoneNumber($phoneNumber),
            ],
            'transaction' => [
                'amount' => $amount,
                'country' => 'UG',
                'currency' => 'UGX',
                'id' => $reference,
            ],
            'notifyUrl' => $callbackUrl,
            'description' => $description,
        ];

        $response = Http::withToken($token)
            ->withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'X-Country' => 'UG',
                'X-Currency' => 'UGX',
            ])
            ->post($baseUrl . self::COLLECT_PATH, $payload);

        if ($response->failed()) {
            $error = $response->json();
            $errorMessage = data_get($error, 'message', data_get($error, 'error_description', 'Payment collection failed'));
            
            Log::error('Airtel Money collection failed', [
                'reference' => $reference,
                'phone' => $phoneNumber,
                'amount' => $amount,
                'response' => $response->body(),
            ]);

            throw ValidationException::withMessages([
                'status' => ['Airtel Money collection failed: ' . $errorMessage],
            ]);
        }

        $data = $response->json();
        
        // Extract the transaction reference from the response
        $transactionId = data_get($data, 'transactionId', data_get($data, 'id', ''));
        $status = data_get($data, 'status', data_get($data, 'Status', 'PENDING'));

        return [
            'provider' => self::PROVIDER_AIRTEL,
            'provider_transaction_id' => $transactionId,
            'reference' => $reference,
            'status' => $status,
            'raw_response' => $data,
        ];
    }

    /**
     * Disburse funds to a recipient
     */
    public function disburse(
        string $recipientPhone,
        int $amount,
        string $reference,
        string $description = '',
        bool $production = false
    ): array {
        $baseUrl = $this->getBaseUrl($production);
        $token = $this->getToken($production);
        $merchantCode = (string) config('services.airtel_money.merchant_code');

        $payload = [
            'merchantId' => $merchantCode,
            'reference' => $reference,
            'recipient' => [
                'country' => 'UG',
                'currency' => 'UGX',
                'msisdn' => $this->normalizePhoneNumber($recipientPhone),
            ],
            'transaction' => [
                'amount' => $amount,
                'country' => 'UG',
                'currency' => 'UGX',
                'id' => $reference,
            ],
            'description' => $description,
        ];

        $response = Http::withToken($token)
            ->withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'X-Country' => 'UG',
                'X-Currency' => 'UGX',
            ])
            ->post($baseUrl . self::DISBURSE_PATH, $payload);

        if ($response->failed()) {
            $error = $response->json();
            $errorMessage = data_get($error, 'message', 'Disbursement failed');
            
            Log::error('Airtel Money disbursement failed', [
                'reference' => $reference,
                'recipient' => $recipientPhone,
                'amount' => $amount,
                'response' => $response->body(),
            ]);

            throw ValidationException::withMessages([
                'status' => ['Airtel Money disbursement failed: ' . $errorMessage],
            ]);
        }

        return $response->json();
    }

    /**
     * Check transaction status
     */
    public function checkTransactionStatus(string $reference, bool $production = false): array
    {
        $baseUrl = $this->getBaseUrl($production);
        $token = $this->getToken($production);

        $response = Http::withToken($token)
            ->withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])
            ->get($baseUrl . self::TRANSACTION_STATUS_PATH . $reference);

        if ($response->failed()) {
            Log::warning('Airtel Money transaction status check failed', [
                'reference' => $reference,
                'response' => $response->body(),
            ]);

            return [
                'status' => 'UNKNOWN',
                'message' => 'Failed to check transaction status',
            ];
        }

        return $response->json();
    }

    /**
     * Check account balance
     */
    public function checkBalance(bool $production = false): array
    {
        $baseUrl = $this->getBaseUrl($production);
        $token = $this->getToken($production);

        $response = Http::withToken($token)
            ->withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])
            ->get($baseUrl . self::BALANCE_PATH);

        if ($response->failed()) {
            Log::warning('Airtel Money balance check failed', [
                'response' => $response->body(),
            ]);

            return [
                'balance' => 0,
                'currency' => 'UGX',
            ];
        }

        $data = $response->json();
        
        return [
            'balance' => data_get($data, 'availableBalance', 0),
            'currency' => data_get($data, 'currency', 'UGX'),
            'raw_response' => $data,
        ];
    }

    /**
     * Verify webhook signature
     */
    public function verifyWebhookSignature(string $payload, string $signatureHeader): bool
    {
        $webhookSecret = (string) config('services.airtel_money.webhook_secret');
        
        // If no secret is configured, accept the webhook (for development)
        if ($webhookSecret === '') {
            Log::warning('Airtel Money webhook secret not configured, accepting all webhooks');
            return true;
        }

        // Airtel Money webhook signature verification
        // The signature is typically HMAC-SHA256 of the payload with the client secret
        $expectedSignature = hash_hmac('sha256', $payload, $webhookSecret);
        $providedSignature = trim($signatureHeader);

        return hash_equals($expectedSignature, $providedSignature);
    }

    /**
     * Handle incoming webhook from Airtel Money
     * 
     * Airtel Money sends webhook notifications for payment status changes
     */
    public function handleWebhook(array $data): array
    {
        $reference = data_get($data, 'reference', data_get($data, 'Reference', ''));
        $status = data_get($data, 'status', data_get($data, 'Status', ''));
        $transactionId = data_get($data, 'transactionId', data_get($data, 'TransactionId', ''));

        return [
            'provider' => self::PROVIDER_AIRTEL,
            'reference' => $reference,
            'provider_transaction_id' => $transactionId,
            'status' => $this->normalizeStatus($status),
            'raw_data' => $data,
        ];
    }

    /**
     * Normalize phone number to international format without +
     * Airtel expects format like: 256701234567 (no + prefix)
     */
    public function normalizePhoneNumber(string $phone): string
    {
        // Remove all non-digit characters
        $normalized = preg_replace('/[^0-9]/', '', $phone);
        
        // If it starts with 0 (local format), replace with 256
        if (str_starts_with($normalized, '0')) {
            $normalized = '256' . substr($normalized, 1);
        }
        
        // If it starts with 7, 3, or 4 (Uganda mobile), ensure it has country code
        if (strlen($normalized) === 9 && in_array(substr($normalized, 0, 1), ['7', '3', '4', '2'])) {
            $normalized = '256' . $normalized;
        }
        
        // Airtel numbers typically start with 70, 75, 76, 77, 78
        if (strlen($normalized) === 12 && !str_starts_with($normalized, '256')) {
            $normalized = '256' . $normalized;
        }
        
        return $normalized;
    }

    /**
     * Normalize status to our internal format
     */
    public function normalizeStatus(string $status): string
    {
        $status = strtolower(trim($status));
        
        if (in_array($status, ['successful', 'completed', 'paid', 'success', 'processed'])) {
            return 'paid';
        }
        
        if (in_array($status, ['failed', 'declined', 'rejected', 'invalid', 'reversed'])) {
            return 'failed';
        }
        
        if (in_array($status, ['pending', 'initiated', 'processing', 'in-progress', 'inprogress'])) {
            return 'pending';
        }
        
        if (in_array($status, ['expired', 'timeout', 'timedout', 'cancelled'])) {
            return 'expired';
        }
        
        return 'unknown';
    }

    /**
     * Get the appropriate base URL based on environment
     */
    private function getBaseUrl(bool $production): string
    {
        // Airtel uses the same base URL for sandbox and production
        // The environment is determined by the credentials used
        return (string) config('services.airtel_money.base_url');
    }

    /**
     * Validate configuration
     */
    public function validateConfiguration(bool $production = false): array
    {
        $missing = [];
        
        $baseUrl = $this->getBaseUrl($production);
        if ($baseUrl === '') {
            $missing[] = 'AIRTEL_MONEY_BASE_URL';
        }
        
        if ((string) config('services.airtel_money.client_id') === 'placeholder_client_id') {
            $missing[] = 'AIRTEL_MONEY_CLIENT_ID';
        }
        
        if ((string) config('services.airtel_money.client_secret') === 'placeholder_client_secret') {
            $missing[] = 'AIRTEL_MONEY_CLIENT_SECRET';
        }
        
        if ((string) config('services.airtel_money.merchant_code') === 'placeholder_merchant_code') {
            $missing[] = 'AIRTEL_MONEY_MERCHANT_CODE';
        }
        
        return $missing;
    }

    /**
     * Check if service is configured
     */
    public function isConfigured(bool $production = false): bool
    {
        return empty($this->validateConfiguration($production));
    }
}
