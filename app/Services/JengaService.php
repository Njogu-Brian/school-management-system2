<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class JengaService
{
    public function getAccessToken(bool $forceRefresh = false): string
    {
        $cacheKey = $this->tokenCacheKey();
        if (! $forceRefresh) {
            $cached = Cache::get($cacheKey);
            if (is_string($cached) && $cached !== '') {
                return $cached;
            }
        }

        $apiKey = (string) config('jenga.auth.api_key');
        $merchantCode = (string) config('jenga.auth.merchant_code');
        $consumerSecret = (string) config('jenga.auth.consumer_secret');

        if ($apiKey === '' || $merchantCode === '' || $consumerSecret === '') {
            throw new RuntimeException('Jenga credentials are missing. Set JENGA_API_KEY, JENGA_MERCHANT_CODE, and JENGA_CONSUMER_SECRET.');
        }

        $response = Http::timeout((int) config('jenga.auth.timeout', 30))
            ->withHeaders([
                'Api-Key' => $apiKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])
            ->post($this->url((string) config('jenga.endpoints.authenticate_merchant')), [
                'merchantCode' => $merchantCode,
                'consumerSecret' => $consumerSecret,
            ]);

        if (! $response->successful()) {
            Log::error('Jenga auth failed', [
                'status' => $response->status(),
                'response' => $response->json(),
            ]);
            throw new RuntimeException('Failed to authenticate with Jenga API.');
        }

        $token = (string) ($response->json('data.accessToken') ?? $response->json('accessToken') ?? '');
        if ($token === '') {
            throw new RuntimeException('Jenga token response did not include accessToken.');
        }

        Cache::put($cacheKey, $token, now()->addSeconds((int) config('jenga.auth.token_cache_ttl_seconds', 3300)));

        return $token;
    }

    public function accountBalance(string $countryCode, string $accountId): array
    {
        $signatureString = $countryCode.$accountId;
        $endpoint = str_replace(
            ['{countryCode}', '{accountId}'],
            [$countryCode, $accountId],
            (string) config('jenga.endpoints.account_balance')
        );

        return $this->request('GET', $endpoint, [], $signatureString);
    }

    public function accountInquiry(string $countryCode, string $accountNumber): array
    {
        $signatureString = $countryCode.$accountNumber;
        $endpoint = str_replace(
            ['{countryCode}', '{accountNumber}'],
            [$countryCode, $accountNumber],
            (string) config('jenga.endpoints.account_inquiry')
        );

        return $this->request('GET', $endpoint, [], $signatureString);
    }

    public function accountMiniStatement(string $countryCode, string $accountNumber): array
    {
        $signatureString = $countryCode.$accountNumber;
        $endpoint = str_replace(
            ['{countryCode}', '{accountNumber}'],
            [$countryCode, $accountNumber],
            (string) config('jenga.endpoints.account_mini_statement')
        );

        return $this->request('GET', $endpoint, [], $signatureString);
    }

    public function accountFullStatement(array $payload): array
    {
        $signatureString = data_get($payload, 'accountNumber')
            .data_get($payload, 'countryCode')
            .data_get($payload, 'toDate');

        return $this->request('POST', (string) config('jenga.endpoints.account_full_statement'), $payload, $signatureString);
    }

    public function disburseToMobileWallet(array $payload): array
    {
        $signatureString = data_get($payload, 'transfer.amount')
            .data_get($payload, 'transfer.currencyCode')
            .data_get($payload, 'transfer.reference')
            .data_get($payload, 'source.accountNumber');

        return $this->request('POST', (string) config('jenga.endpoints.send_mobile'), $payload, $signatureString);
    }

    public function disburseWithinEquity(array $payload): array
    {
        $signatureString = data_get($payload, 'source.accountNumber')
            .data_get($payload, 'transfer.amount')
            .data_get($payload, 'transfer.currencyCode')
            .data_get($payload, 'transfer.reference');

        return $this->request('POST', (string) config('jenga.endpoints.internal_bank_transfer'), $payload, $signatureString);
    }

    public function disburseRtgs(array $payload): array
    {
        $signatureString = data_get($payload, 'transfer.reference')
            .data_get($payload, 'transfer.date')
            .data_get($payload, 'source.accountNumber')
            .data_get($payload, 'destination.accountNumber')
            .data_get($payload, 'transfer.amount');

        return $this->request('POST', (string) config('jenga.endpoints.rtgs'), $payload, $signatureString);
    }

    public function getRtgsPaymentPurposes(?string $signatureString = null): array
    {
        return $this->request(
            'GET',
            (string) config('jenga.endpoints.rtgs_payment_purposes'),
            [],
            $signatureString
        );
    }

    public function initiateStkUssdPush(array $payload): array
    {
        $signatureString = data_get($payload, 'merchant.accountNumber')
            .data_get($payload, 'payment.ref')
            .data_get($payload, 'payment.mobileNumber')
            .data_get($payload, 'payment.telco')
            .data_get($payload, 'payment.amount')
            .data_get($payload, 'payment.currency');

        return $this->request('POST', (string) config('jenga.endpoints.stk_ussd_push_initiate'), $payload, $signatureString);
    }

    public function queryTransactionDetails(string $reference): array
    {
        $endpoint = str_replace('{ref}', $reference, (string) config('jenga.endpoints.query_transaction_details'));

        return $this->request('GET', $endpoint, [], $reference);
    }

    public function getBillers(?int $perPage = null, ?int $page = null, ?string $signatureString = null): array
    {
        $query = [];
        if ($perPage !== null) {
            $query['per_page'] = $perPage;
        }
        if ($page !== null) {
            $query['page'] = $page;
        }

        return $this->request('GET', (string) config('jenga.endpoints.billers'), $query, $signatureString, true);
    }

    public function getMerchants(?int $perPage = null, ?int $page = null, ?string $signatureString = null): array
    {
        $query = [];
        if ($perPage !== null) {
            $query['per_page'] = $perPage;
        }
        if ($page !== null) {
            $query['page'] = $page;
        }

        return $this->request('GET', (string) config('jenga.endpoints.merchants'), $query, $signatureString, true);
    }

    public function signedRequest(string $method, string $endpointPath, string $signatureString, array $payload = []): array
    {
        return $this->request($method, $endpointPath, $payload, $signatureString);
    }

    protected function request(
        string $method,
        string $endpointPath,
        array $payload,
        ?string $signatureString = null,
        bool $asQueryParams = false
    ): array
    {
        $token = $this->getAccessToken();
        $headers = ['Accept' => 'application/json'];
        if (is_string($signatureString) && $signatureString !== '') {
            $headers['Signature'] = $this->sign($signatureString);
        }

        $sendOptions = $asQueryParams ? ['query' => $payload] : ['json' => $payload];

        $response = Http::timeout((int) config('jenga.timeouts.default', 60))
            ->withToken($token)
            ->withHeaders($headers)
            ->send(strtoupper($method), $this->url($endpointPath), $sendOptions);

        if ($response->status() === 401) {
            // Token may have expired earlier than expected; refresh once.
            $token = $this->getAccessToken(true);
            $response = Http::timeout((int) config('jenga.timeouts.default', 60))
                ->withToken($token)
                ->withHeaders($headers)
                ->send(strtoupper($method), $this->url($endpointPath), $sendOptions);
        }

        return $this->formatResponse($response);
    }

    protected function sign(string $data): string
    {
        $privateKeyPath = (string) config('jenga.security.private_key_path');
        if ($privateKeyPath === '' || ! is_file($privateKeyPath)) {
            throw new RuntimeException('Jenga private key not found. Set JENGA_PRIVATE_KEY_PATH to a valid file path.');
        }

        $keyContents = file_get_contents($privateKeyPath);
        if ($keyContents === false) {
            throw new RuntimeException('Unable to read Jenga private key file.');
        }

        $privateKey = openssl_pkey_get_private($keyContents);
        if (! $privateKey) {
            throw new RuntimeException('Invalid Jenga private key format.');
        }

        $signature = '';
        $signed = openssl_sign($data, $signature, $privateKey, OPENSSL_ALGO_SHA256);
        openssl_pkey_free($privateKey);

        if (! $signed) {
            throw new RuntimeException('Failed to sign Jenga request.');
        }

        return base64_encode($signature);
    }

    protected function url(string $endpointPath): string
    {
        $env = (string) config('jenga.environment', 'sandbox');
        $base = (string) config("jenga.base_urls.{$env}");

        if ($base === '') {
            throw new RuntimeException("Jenga base URL not configured for environment [{$env}].");
        }

        return rtrim($base, '/').'/'.ltrim($endpointPath, '/');
    }

    protected function tokenCacheKey(): string
    {
        $env = (string) config('jenga.environment', 'sandbox');
        $merchantCode = (string) config('jenga.auth.merchant_code', '');

        return 'jenga_token_'.$env.'_'.md5($merchantCode);
    }

    protected function formatResponse(Response $response): array
    {
        return [
            'ok' => $response->successful(),
            'status' => $response->status(),
            'data' => $response->json(),
            'raw' => $response->body(),
        ];
    }
}

