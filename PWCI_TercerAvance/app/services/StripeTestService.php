<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;

final class StripeTestService
{
    private string $secret;

    public function __construct(string $configPath)
    {
        if (!function_exists('curl_init')) {
            throw new RuntimeException('Habilita cURL de PHP para usar Stripe.');
        }
        $config = is_file($configPath) ? require $configPath : [];
        $key = is_array($config) ? trim((string) ($config['secret_key'] ?? '')) : '';
        if (!str_starts_with($key, 'sk_test_')) {
            throw new RuntimeException('Configura una clave secreta de prueba de Stripe en config/stripe.local.php.');
        }
        $this->secret = $key;
    }

    public static function cents(string $amount): int
    {
        if (!preg_match('/\A([0-9]{1,8})\.([0-9]{2})\z/', $amount, $parts)) {
            throw new RuntimeException('El importe de la compra no es válido.');
        }
        return (int) $parts[1] * 100 + (int) $parts[2];
    }

    public function create(array $item, string $successUrl, string $cancelUrl): array
    {
        return $this->request('POST', '/checkout/sessions', [
            'mode' => 'payment',
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'line_items' => [[
                'quantity' => 1,
                'price_data' => [
                    'currency' => 'mxn',
                    'unit_amount' => self::cents($item['monto']),
                    'product_data' => ['name' => $item['titulo']],
                ],
            ]],
        ]);
    }

    public function verify(string $id, string $amount): void
    {
        $result = $this->request('GET', '/checkout/sessions/' . rawurlencode($id));
        if (($result['id'] ?? null) !== $id
            || ($result['livemode'] ?? null) !== false
            || ($result['mode'] ?? null) !== 'payment'
            || ($result['status'] ?? null) !== 'complete'
            || ($result['payment_status'] ?? null) !== 'paid'
            || ($result['currency'] ?? null) !== 'mxn'
            || ($result['amount_total'] ?? null) !== self::cents($amount)) {
            throw new RuntimeException('Stripe no confirmó el pago por el importe esperado.');
        }
    }

    private function request(string $method, string $path, array $fields = []): array
    {
        $curl = curl_init('https://api.stripe.com/v1' . $path);
        curl_setopt_array($curl, [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->secret,
                'Content-Type: application/x-www-form-urlencoded',
            ],
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 15,
        ]);
        if ($method === 'POST') {
            curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($fields));
        }
        $body = curl_exec($curl);
        $errno = curl_errno($curl);
        $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        curl_close($curl);

        if ($body === false) {
            throw new RuntimeException('XAMPP no pudo conectarse a Stripe (cURL ' . $errno . ').');
        }

        $data = json_decode($body, true);
        if ($status < 200 || $status >= 300 || !is_array($data)) {
            $code = $data['error']['code'] ?? $data['error']['type'] ?? '';
            $param = $data['error']['param'] ?? '';
            $detail = is_string($code) && preg_match('/\A[a-z_]{1,64}\z/', $code) ? ': ' . $code : '';
            if (is_string($param) && preg_match('/\A[a-z0-9_\[\]]{1,100}\z/i', $param)) {
                $detail .= ' — campo ' . $param;
            }
            throw new RuntimeException('Stripe rechazó la solicitud (HTTP ' . $status . $detail . ').');
        }
        return $data;
    }
}
