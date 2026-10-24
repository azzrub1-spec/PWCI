<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;

final class PayPalService
{
    private string $clientId;
    private string $clientSecret;
    private string $baseUrl;

    public function __construct(string $configPath)
    {
        if (!function_exists('curl_init')) {
            throw new RuntimeException('La extensión cURL de PHP no está habilitada.');
        }

        if (!is_file($configPath)) {
            throw new RuntimeException(
                'Falta configurar PayPal Sandbox en config/paypal.local.php.'
            );
        }

        $config = require $configPath;
        $this->clientId = trim((string) ($config['client_id'] ?? ''));
        $this->clientSecret = trim((string) ($config['client_secret'] ?? ''));
        $this->baseUrl = rtrim((string) ($config['base_url'] ?? ''), '/');

        if ($this->clientId === '' || $this->clientSecret === ''
            || $this->baseUrl !== 'https://api-m.sandbox.paypal.com') {
            throw new RuntimeException('La configuración de PayPal Sandbox no es válida.');
        }
    }

    /** @return array<string, mixed> */
    public function createOrder(
        string $amount,
        string $currency,
        string $description,
        string $returnUrl,
        string $cancelUrl
    ): array {
        return $this->request('POST', '/v2/checkout/orders', [
            'intent' => 'CAPTURE',
            'purchase_units' => [[
                'description' => $description,
                'amount' => [
                    'currency_code' => $currency,
                    'value' => $amount,
                ],
            ]],
            'application_context' => [
                'return_url' => $returnUrl,
                'cancel_url' => $cancelUrl,
                'user_action' => 'PAY_NOW',
            ],
        ]);
    }

    /** @return array<string, mixed> */
    public function captureOrder(string $orderId): array
    {
        return $this->request(
            'POST',
            '/v2/checkout/orders/' . rawurlencode($orderId) . '/capture',
            new \stdClass()
        );
    }

    private function accessToken(): string
    {
        $curl = curl_init($this->baseUrl . '/v1/oauth2/token');
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => 'grant_type=client_credentials',
            CURLOPT_USERPWD => $this->clientId . ':' . $this->clientSecret,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Content-Type: application/x-www-form-urlencoded',
            ],
            CURLOPT_TIMEOUT => 20,
        ]);

        $response = curl_exec($curl);
        $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        $error = curl_error($curl);
        curl_close($curl);

        if ($response === false || $error !== '') {
            throw new RuntimeException('No fue posible conectar con PayPal Sandbox.');
        }

        $data = json_decode($response, true);
        if ($status !== 200 || !is_array($data) || empty($data['access_token'])) {
            throw new RuntimeException('PayPal rechazó las credenciales Sandbox.');
        }

        return (string) $data['access_token'];
    }

    /**
     * @param array<string, mixed>|\stdClass $body
     * @return array<string, mixed>
     */
    private function request(string $method, string $path, array|\stdClass $body): array
    {
        $curl = curl_init($this->baseUrl . $path);
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_POSTFIELDS => json_encode($body, JSON_UNESCAPED_UNICODE),
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->accessToken(),
            ],
            CURLOPT_TIMEOUT => 20,
        ]);

        $response = curl_exec($curl);
        $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        $error = curl_error($curl);
        curl_close($curl);

        if ($response === false || $error !== '') {
            throw new RuntimeException('No fue posible completar la solicitud a PayPal.');
        }

        $data = json_decode($response, true);
        if ($status < 200 || $status >= 300 || !is_array($data)) {
            throw new RuntimeException('PayPal no pudo procesar la operación de prueba.');
        }

        return $data;
    }
}
