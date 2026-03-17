<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * MailProviderService
 *
 * Central service for sending emails through any configured provider.
 * Supports automatic fallback chaining when a provider fails.
 *
 * Usage:
 *   app(MailProviderService::class)->send([
 *       'to_email'  => 'user@example.com',
 *       'to_name'   => 'John',
 *       'subject'   => 'Hello',
 *       'html'      => '<p>...</p>',
 *       'text'      => 'plain fallback',
 *       'from_email'=> 'noreply@domain.com',   // optional override
 *       'from_name' => 'My App',               // optional override
 *   ], 'ses');   // optional provider override
 *
 * Returns: ['success' => bool, 'provider' => string, 'message_id' => string|null, 'error' => string|null]
 */
class MailProviderService
{
    /** @var array<string, array> */
    private array $providerConfig;

    public function __construct()
    {
        $this->providerConfig = config('providers.providers', []);
    }

    // ═══════════════════════════════════════════════════
    // PUBLIC: Send with automatic fallback
    // ═══════════════════════════════════════════════════

    public function send(array $message, ?string $provider = null): array
    {
        $primary   = $provider ?? config('providers.default', 'resend');
        $fallbacks = config('providers.fallback', []);

        // Build ordered list: [primary, ...fallbacks] — deduplicated
        $chain = array_unique(array_filter(
            array_merge([$primary], (array) $fallbacks),
            fn($p) => !empty($p)
        ));

        $lastError = 'No providers configured.';

        foreach ($chain as $providerKey) {
            $cfg = $this->providerConfig[$providerKey] ?? null;

            if (!$cfg || !($cfg['enabled'] ?? false)) {
                Log::warning("MailProvider: '{$providerKey}' is not enabled or configured — skipping.");
                $lastError = "Provider '{$providerKey}' is not configured.";
                continue;
            }

            try {
                $result = $this->sendVia($providerKey, $message, $cfg);
                $result['provider'] = $providerKey;
                return $result;

            } catch (\Throwable $e) {
                $lastError = $e->getMessage();
                Log::warning("MailProvider: '{$providerKey}' failed — {$lastError}. Trying next.");
            }
        }

        return [
            'success'    => false,
            'provider'   => $primary,
            'message_id' => null,
            'error'      => $lastError,
        ];
    }

    // ═══════════════════════════════════════════════════
    // PUBLIC: Test a provider's credentials with a ping
    // ═══════════════════════════════════════════════════

    public function test(string $providerKey): array
    {
        $cfg = $this->providerConfig[$providerKey] ?? null;

        if (!$cfg) {
            return ['success' => false, 'error' => 'Provider not found in config.'];
        }

        if (!($cfg['enabled'] ?? false)) {
            return ['success' => false, 'error' => 'Provider credentials not set in .env.'];
        }

        try {
            return match ($providerKey) {
                'resend'   => $this->testResend($cfg),
                'ses'      => $this->testSes($cfg),
                'mailtrap' => $this->testMailtrap($cfg),
                default    => ['success' => false, 'error' => "Unknown provider: {$providerKey}"],
            };
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // ═══════════════════════════════════════════════════
    // PUBLIC: Get status of all providers
    // ═══════════════════════════════════════════════════

    public function status(): array
    {
        $result = [];
        foreach ($this->providerConfig as $key => $cfg) {
            $result[$key] = [
                'label'   => $cfg['label'],
                'icon'    => $cfg['icon'],
                'color'   => $cfg['color'],
                'enabled' => $cfg['enabled'] ?? false,
                'default' => config('providers.default') === $key,
            ];
        }
        return $result;
    }

    // ═══════════════════════════════════════════════════
    // PRIVATE: Route to correct provider
    // ═══════════════════════════════════════════════════

    private function sendVia(string $key, array $msg, array $cfg): array
    {
        return match ($key) {
            'resend'   => $this->sendResend($msg, $cfg),
            'ses'      => $this->sendSes($msg, $cfg),
            'mailtrap' => $this->sendMailtrap($msg, $cfg),
            default    => throw new \InvalidArgumentException("Unknown provider: {$key}"),
        };
    }

    // ═══════════════════════════════════════════════════
    // RESEND — HTTP API
    // ═══════════════════════════════════════════════════

    private function sendResend(array $msg, array $cfg): array
    {
        $fromEmail = $msg['from_email'] ?? config('mail.from.address');
        $fromName  = $msg['from_name']  ?? config('mail.from.name', config('app.name'));
        $from      = $fromName ? "{$fromName} <{$fromEmail}>" : $fromEmail;

        $body = [
            'from'    => $from,
            'to'      => [$msg['to_email']],
            'subject' => $msg['subject'],
            'html'    => $msg['html'] ?? '',
        ];

        if (!empty($msg['text'])) {
            $body['text'] = $msg['text'];
        }

        $response = $this->httpPost(
            'https://api.resend.com/emails',
            $body,
            ['Authorization: Bearer ' . $cfg['api_key'], 'Content-Type: application/json']
        );

        if (!isset($response['id'])) {
            throw new \RuntimeException('Resend error: ' . ($response['message'] ?? json_encode($response)));
        }

        return ['success' => true, 'message_id' => $response['id'], 'error' => null];
    }

    private function testResend(array $cfg): array
    {
        $response = $this->httpGet(
            'https://api.resend.com/domains',
            ['Authorization: Bearer ' . $cfg['api_key']]
        );

        if (isset($response['data']) || isset($response['name'])) {
            return ['success' => true, 'message' => 'Resend credentials are valid ✅'];
        }

        return ['success' => false, 'error' => $response['message'] ?? 'Invalid API key'];
    }

    // ═══════════════════════════════════════════════════
    // AMAZON SES — HTTP API (Signature Version 4)
    // ═══════════════════════════════════════════════════

    private function sendSes(array $msg, array $cfg): array
    {
        $fromEmail = $msg['from_email'] ?? $cfg['from_email'] ?? config('mail.from.address');
        $fromName  = $msg['from_name']  ?? config('mail.from.name', config('app.name'));
        $from      = $fromName ? "{$fromName} <{$fromEmail}>" : $fromEmail;

        $toName = $msg['to_name'] ?? '';
        $to     = $toName ? "{$toName} <{$msg['to_email']}>" : $msg['to_email'];

        $payload = [
            'Content' => [
                'Simple' => [
                    'Subject' => ['Data' => $msg['subject'], 'Charset' => 'UTF-8'],
                    'Body'    => [
                        'Html' => ['Data' => $msg['html'] ?? '', 'Charset' => 'UTF-8'],
                        'Text' => ['Data' => $msg['text'] ?? strip_tags($msg['html'] ?? ''), 'Charset' => 'UTF-8'],
                    ],
                ],
            ],
            'Destination' => ['ToAddresses' => [$to]],
            'FromEmailAddress' => $from,
        ];

        $region   = $cfg['region'];
        $endpoint = "https://email.{$region}.amazonaws.com/v2/email/outbound-emails";

        $headers  = $this->sesSignedHeaders('POST', $endpoint, json_encode($payload), $cfg);
        $response = $this->httpPost($endpoint, $payload, $headers);

        if (!isset($response['MessageId'])) {
            throw new \RuntimeException('SES error: ' . ($response['message'] ?? json_encode($response)));
        }

        return ['success' => true, 'message_id' => $response['MessageId'], 'error' => null];
    }

    private function testSes(array $cfg): array
    {
        // Call SES GetAccount to verify credentials
        $region   = $cfg['region'];
        $endpoint = "https://email.{$region}.amazonaws.com/v2/email/account";
        $headers  = $this->sesSignedHeaders('GET', $endpoint, '', $cfg);
        $response = $this->httpGet($endpoint, $headers);

        if (isset($response['SendingEnabled'])) {
            $enabled = $response['SendingEnabled'] ? 'enabled' : 'disabled';
            return ['success' => true, 'message' => "SES credentials valid. Sending is {$enabled} ✅"];
        }

        return ['success' => false, 'error' => $response['message'] ?? json_encode($response)];
    }

    /**
     * AWS Signature Version 4 signing for SES v2 API.
     * Does NOT require the AWS SDK — pure PHP.
     */
    private function sesSignedHeaders(string $method, string $url, string $body, array $cfg): array
    {
        $key    = $cfg['key'];
        $secret = $cfg['secret'];
        $region = $cfg['region'];
        $service = 'ses';

        $parsed = parse_url($url);
        $host   = $parsed['host'];
        $path   = $parsed['path'] ?? '/';
        $query  = $parsed['query'] ?? '';

        $amzDate  = gmdate('Ymd\THis\Z');
        $dateStamp = gmdate('Ymd');

        $contentType = 'application/json';
        $payloadHash = hash('sha256', $body);

        // Canonical headers (must be sorted)
        $canonicalHeaders = "content-type:{$contentType}\nhost:{$host}\nx-amz-date:{$amzDate}\n";
        $signedHeaders    = 'content-type;host;x-amz-date';

        $canonicalRequest = implode("\n", [
            $method,
            $path,
            $query,
            $canonicalHeaders,
            $signedHeaders,
            $payloadHash,
        ]);

        $credentialScope = "{$dateStamp}/{$region}/{$service}/aws4_request";
        $stringToSign    = implode("\n", [
            'AWS4-HMAC-SHA256',
            $amzDate,
            $credentialScope,
            hash('sha256', $canonicalRequest),
        ]);

        // Derive signing key
        $signingKey = hash_hmac('sha256', 'aws4_request',
            hash_hmac('sha256', $service,
                hash_hmac('sha256', $region,
                    hash_hmac('sha256', $dateStamp, 'AWS4' . $secret, true),
                    true),
                true),
            true);

        $signature = hash_hmac('sha256', $stringToSign, $signingKey);

        $authHeader = "AWS4-HMAC-SHA256 Credential={$key}/{$credentialScope}, "
                    . "SignedHeaders={$signedHeaders}, "
                    . "Signature={$signature}";

        return [
            "Authorization: {$authHeader}",
            "Content-Type: {$contentType}",
            "X-Amz-Date: {$amzDate}",
            "Host: {$host}",
        ];
    }

    // ═══════════════════════════════════════════════════
    // MAILTRAP — API sending
    // ═══════════════════════════════════════════════════

    private function sendMailtrap(array $msg, array $cfg): array
    {
        $fromEmail = $msg['from_email'] ?? $cfg['from_email'] ?? config('mail.from.address');
        $fromName  = $msg['from_name']  ?? config('mail.from.name', config('app.name'));

        $body = [
            'from'    => ['email' => $fromEmail, 'name' => $fromName],
            'to'      => [['email' => $msg['to_email'], 'name' => $msg['to_name'] ?? '']],
            'subject' => $msg['subject'],
            'html'    => $msg['html'] ?? '',
            'text'    => $msg['text'] ?? strip_tags($msg['html'] ?? ''),
        ];

        $response = $this->httpPost(
            'https://send.api.mailtrap.io/api/send',
            $body,
            [
                'Authorization: Bearer ' . $cfg['api_token'],
                'Content-Type: application/json',
            ]
        );

        if (!($response['success'] ?? false)) {
            throw new \RuntimeException('Mailtrap error: ' . json_encode($response['errors'] ?? $response));
        }

        return [
            'success'    => true,
            'message_id' => $response['message_ids'][0] ?? null,
            'error'      => null,
        ];
    }

    private function testMailtrap(array $cfg): array
    {
        $response = $this->httpGet(
            'https://mailtrap.io/api/accounts',
            [
                'Authorization: Bearer ' . $cfg['api_token'],
                'Content-Type: application/json',
            ]
        );

        // Mailtrap returns array of accounts on success
        if (is_array($response) && !isset($response['error'])) {
            return ['success' => true, 'message' => 'Mailtrap credentials are valid ✅'];
        }

        return ['success' => false, 'error' => $response['error'] ?? json_encode($response)];
    }

    // ═══════════════════════════════════════════════════
    // HTTP Helpers — pure cURL, no Guzzle required
    // ═══════════════════════════════════════════════════

    private function httpPost(string $url, array $body, array $headers): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($body),
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $raw  = curl_exec($ch);
        $err  = curl_error($ch);
        curl_close($ch);

        if ($err) throw new \RuntimeException("cURL error: {$err}");
        return json_decode($raw, true) ?? [];
    }

    private function httpGet(string $url, array $headers): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $raw  = curl_exec($ch);
        $err  = curl_error($ch);
        curl_close($ch);

        if ($err) throw new \RuntimeException("cURL error: {$err}");
        return json_decode($raw, true) ?? [];
    }
}
