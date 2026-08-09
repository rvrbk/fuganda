<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * MTN Uganda MoMoPay API Service
 * 
 * Documentation: momodeveloper.mtn.com
 * Sandbox: sandbox.momodeveloper.mtn.com
 * Production: momodeveloper.mtn.com
 */
class MtnMomoService
{
    // API Endpoints
    private const TOKEN_PATH = '/collection/token/';
    private const REQUEST_TO_PAY_PATH = '/collection/v1_0/requesttopay';
    private const DEPOSIT_PATH = '/disbursement/v1_0/deposit';
    private const BALANCE_PATH = '/collection/v1_0/account/balance';
    private const TRANSACTION_STATUS_PATH = '/collection/v1_0/requesttopay/';

    // Provider constant
    public const PROVIDER_MTN = 'mtn';

    /**
     * Get authentication token from MTN MoMo API
     */
    public function getToken(bool $production = false): string
    {
        // In local/testing with placeholder credentials, return a mock token
        if (app()->environment('local') || app()->environment('testing')) {
            if ((string) config('services.mtn_momo.api_user') === 'placeholder_api_user') {
                return 'mock_token_' . time();
            }
        }

        $baseUrl = $this->getBaseUrl($production);
        $apiUser = (string) config('services.mtn_momo.api_user');
        $apiKey = (string) config('services.mtn_momo.api_key');
        $subscriptionKey = (string) config('services.mtn_momo.subscription_key');

        $response = Http::withBasicAuth($apiUser, $apiKey)
            ->withHeaders([
                'Ocp-Apim-Subscription-Key' => $subscriptionKey,
                'Content-Type' => 'application/json',
            ])
            ->post($baseUrl . self::TOKEN_PATH);

        if ($response->failed()) {
            $error = $response->json();
            $errorMessage = data_get($error, 'message', data_get($error, 'error_description', 'Failed to authenticate with MTN MoMo API'));
            
            Log::error('MTN MoMo token request failed', [
                'response' => $response->body(),
                'status' => $response->status(),
            ]);

            throw ValidationException::withMessages([
                'status' => ['MTN MoMo authentication failed: ' . $errorMessage],
            ]);
        }

        return (string) data_get($response->json(), 'access_token', '');
    }

    /**
     * Request payment from customer (Request to Pay)
     * 
     * @param string $phoneNumber Customer phone number in international format (e.g., 256772123456)
     * @param int $amount Amount in UGX
     * @param string $reference Unique merchant reference
     * @param string $description Payment description
     * @param bool $production Whether to use production environment
     */
    public function requestToPay(
        string $phoneNumber,
        int $amount,
        string $reference,
        string $description,
        bool $production = false,
        ?string $callbackUrl = null
    ): array {
        // In local/testing with placeholder credentials, return a mock response
        if (app()->environment('local') || app()->environment('testing')) {
            if ((string) config('services.mtn_momo.api_user') === 'placeholder_api_user') {
                Log::debug('MTN MoMo mock request in local environment', [
                    'reference' => $reference,
                    'phone' => $phoneNumber,
                    'amount' => $amount,
                ]);

                return [
                    'provider' => self::PROVIDER_MTN,
                    'provider_transaction_id' => 'mock_' . $reference . '_' . time(),
                    'reference' => $reference,
                    'status' => 'PENDING',
                    'message' => 'Mock MTN MoMo request in local environment',
                ];
            }
        }

        $baseUrl = $this->getBaseUrl($production);
        $token = $this->getToken($production);
        $merchantCode = (string) config('services.mtn_momo.merchant_code');
        
        if ($callbackUrl === null) {
            $callbackUrl = (string) config('services.mtn_momo.callback_url');
        }

        $payload = [
            'amount' => $amount,
            'currency' => 'UGX',
            'externalId' => $reference,
            'payer' => [
                'partyIdType' => 'MSISDN',
                'partyId' => $this->normalizePhoneNumber($phoneNumber),
            ],
            'payerMessage' => $description,
            'payeeNote' => $description,
            'callbackurl' => $callbackUrl,
        ];

        $response = Http::withToken($token)
            ->withHeaders([
                'Ocp-Apim-Subscription-Key' => (string) config('services.mtn_momo.subscription_key'),
                'X-Target-Environment' => $production ? 'mtn-uganda' : 'sandbox',
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])
            ->post($baseUrl . self::REQUEST_TO_PAY_PATH, $payload);

        if ($response->failed()) {
            $error = $response->json();
            $errorMessage = data_get($error, 'message', data_get($error, 'error_description', 'Request to Pay failed'));
            
            Log::error('MTN MoMo Request to Pay failed', [
                'reference' => $reference,
                'phone' => $phoneNumber,
                'amount' => $amount,
                'response' => $response->body(),
            ]);

            throw ValidationException::withMessages([
                'status' => ['MTN MoMo Request to Pay failed: ' . $errorMessage],
            ]);
        }

        $data = $response->json();
        
        // Extract the transaction reference from the response
        $transactionId = data_get($data, 'referenceId', data_get($data, 'transactionId', ''));
        $status = data_get($data, 'status', 'PENDING');

        return [
            'provider' => self::PROVIDER_MTN,
            'provider_transaction_id' => $transactionId,
            'reference' => $reference,
            'status' => $status,
            'raw_response' => $data,
        ];
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
                'Ocp-Apim-Subscription-Key' => (string) config('services.mtn_momo.subscription_key'),
                'X-Target-Environment' => $production ? 'mtn-uganda' : 'sandbox',
                'Content-Type' => 'application/json',
            ])
            ->get($baseUrl . self::TRANSACTION_STATUS_PATH . $reference);

        if ($response->failed()) {
            Log::warning('MTN MoMo transaction status check failed', [
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
        $merchantCode = (string) config('services.mtn_momo.merchant_code');

        $response = Http::withToken($token)
            ->withHeaders([
                'Ocp-Apim-Subscription-Key' => (string) config('services.mtn_momo.subscription_key'),
                'X-Target-Environment' => $production ? 'mtn-uganda' : 'sandbox',
                'Content-Type' => 'application/json',
            ])
            ->get($baseUrl . self::BALANCE_PATH);

        if ($response->failed()) {
            Log::warning('MTN MoMo balance check failed', [
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
     * Deposit/Disburse funds (from collection account to another)
     */
    public function deposit(
        string $recipientPhone,
        int $amount,
        string $reference,
        string $description = '',
        bool $production = false
    ): array {
        $baseUrl = $this->getBaseUrl($production);
        $token = $this->getToken($production);

        $payload = [
            'amount' => $amount,
            'currency' => 'UGX',
            'externalId' => $reference,
            'payee' => [
                'partyIdType' => 'MSISDN',
                'partyId' => $this->normalizePhoneNumber($recipientPhone),
            ],
            'payerMessage' => $description,
            'payeeNote' => $description,
        ];

        $response = Http::withToken($token)
            ->withHeaders([
                'Ocp-Apim-Subscription-Key' => (string) config('services.mtn_momo.subscription_key'),
                'X-Target-Environment' => $production ? 'mtn-uganda' : 'sandbox',
                'Content-Type' => 'application/json',
            ])
            ->post($baseUrl . self::DEPOSIT_PATH, $payload);

        if ($response->failed()) {
            $error = $response->json();
            $errorMessage = data_get($error, 'message', 'Deposit failed');
            
            Log::error('MTN MoMo deposit failed', [
                'reference' => $reference,
                'recipient' => $recipientPhone,
                'amount' => $amount,
                'response' => $response->body(),
            ]);

            throw ValidationException::withMessages([
                'status' => ['MTN MoMo deposit failed: ' . $errorMessage],
            ]);
        }

        return $response->json();
    }

    /**
     * Verify webhook signature
     * Note: MTN MoMo uses API key in Authorization header for webhook verification
     */
    public function verifyWebhookSignature(string $payload, string $signatureHeader): bool
    {
        $webhookSecret = (string) config('services.mtn_momo.webhook_secret');
        
        // If no secret is configured, accept the webhook (for development)
        if ($webhookSecret === '') {
            Log::warning('MTN MoMo webhook secret not configured, accepting all webhooks');
            return true;
        }

        // MTN MoMo webhook signature verification
        // The signature is typically HMAC-SHA256 of the payload with the API key
        $expectedSignature = hash_hmac('sha256', $payload, $webhookSecret);
        $providedSignature = trim($signatureHeader);

        return hash_equals($expectedSignature, $providedSignature);
    }

    /**
     * Handle incoming webhook from MTN MoMo
     * 
     * MTN MoMo sends webhook notifications for payment status changes
     */
    public function handleWebhook(array $data): array
    {
        $reference = data_get($data, 'externalId', data_get($data, 'reference', ''));
        $status = data_get($data, 'status', data_get($data, 'Status', ''));
        $transactionId = data_get($data, 'referenceId', data_get($data, 'transactionId', ''));

        return [
            'provider' => self::PROVIDER_MTN,
            'reference' => $reference,
            'provider_transaction_id' => $transactionId,
            'status' => $this->normalizeStatus($status),
            'raw_data' => $data,
        ];
    }

    /**
     * Normalize phone number to international format without +
     * MTN expects format like: 256772123456 (no + prefix)
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
        if (strlen($normalized) === 9 && in_array(substr($normalized, 0, 1), ['7', '3', '4'])) {
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
        
        if (in_array($status, ['successful', 'completed', 'paid', 'success'])) {
            return 'paid';
        }
        
        if (in_array($status, ['failed', 'declined', 'rejected', 'invalid'])) {
            return 'failed';
        }
        
        if (in_array($status, ['pending', 'initiated', 'processing'])) {
            return 'pending';
        }
        
        if (in_array($status, ['expired', 'timeout', 'timedout'])) {
            return 'expired';
        }
        
        return 'unknown';
    }

    /**
     * Get the appropriate base URL based on environment
     */
    private function getBaseUrl(bool $production): string
    {
        if ($production) {
            return (string) config('services.mtn_momo.production_base_url');
        }
        
        return (string) config('services.mtn_momo.base_url');
    }

    /**
     * Validate configuration
     */
    public function validateConfiguration(bool $production = false): array
    {
        $missing = [];
        
        $baseUrl = $this->getBaseUrl($production);
        if ($baseUrl === '' || $baseUrl === 'https://sandbox.momodeveloper.mtn.com' && !app()->environment('local')) {
            $missing[] = 'MTN_MOMO_' . ($production ? 'PRODUCTION_' : '') . 'BASE_URL';
        }
        
        if ((string) config('services.mtn_momo.api_user') === 'placeholder_api_user') {
            $missing[] = 'MTN_MOMO_API_USER';
        }
        
        if ((string) config('services.mtn_momo.api_key') === 'placeholder_api_key') {
            $missing[] = 'MTN_MOMO_API_KEY';
        }
        
        if ((string) config('services.mtn_momo.merchant_code') === 'placeholder_merchant_code') {
            $missing[] = 'MTN_MOMO_MERCHANT_CODE';
        }
        
        if ((string) config('services.mtn_momo.subscription_key') === 'placeholder_subscription_key') {
            $missing[] = 'MTN_MOMO_SUBSCRIPTION_KEY';
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
