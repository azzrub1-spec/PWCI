<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\View;
use App\Middleware\AuthMiddleware;
use App\Services\PayPalService;
use RuntimeException;

final class PaymentController
{
    public function __construct(private readonly string $configPath)
    {
    }

    public function show(): void
    {
        AuthMiddleware::requireRole(['estudiante']);
        View::render('pago/index');
    }

    public function create(): void
    {
        AuthMiddleware::requireRole(['estudiante']);

        try {
            $paypal = new PayPalService($this->configPath);
            $order = $paypal->createOrder(
                '10.00',
                'MXN',
                'Pago de prueba de curso KONNEKT',
                $this->absoluteUrl('paypal-capturar'),
                $this->absoluteUrl('paypal-cancelar')
            );

            foreach (($order['links'] ?? []) as $link) {
                if (($link['rel'] ?? '') === 'approve' && !empty($link['href'])) {
                    header('Location: ' . $link['href']);
                    exit;
                }
            }

            throw new RuntimeException('PayPal no devolvió el enlace de aprobación.');
        } catch (RuntimeException $exception) {
            View::render('pago/index', ['error' => $exception->getMessage()]);
        }
    }

    public function capture(): void
    {
        AuthMiddleware::requireRole(['estudiante']);
        $orderId = trim((string) ($_GET['token'] ?? ''));

        if ($orderId === '' || !preg_match('/^[A-Z0-9]+$/', $orderId)) {
            View::render('pago/index', ['error' => 'La orden de PayPal no es válida.']);
            return;
        }

        try {
            $paypal = new PayPalService($this->configPath);
            $capture = $paypal->captureOrder($orderId);
            $completed = ($capture['status'] ?? '') === 'COMPLETED';

            View::render('pago/index', [
                'success' => $completed
                    ? 'Pago de prueba completado correctamente en PayPal Sandbox.'
                    : 'PayPal devolvió la orden sin completar.',
                'orderId' => $orderId,
            ]);
        } catch (RuntimeException $exception) {
            View::render('pago/index', ['error' => $exception->getMessage()]);
        }
    }

    public function cancel(): void
    {
        AuthMiddleware::requireRole(['estudiante']);
        View::render('pago/index', [
            'error' => 'El pago de prueba fue cancelado. No se realizó ningún cobro.',
        ]);
    }

    private function absoluteUrl(string $route): string
    {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            ? 'https'
            : 'http';
        $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost:8080');

        return $scheme . '://' . $host . BASE_URL . '/index.php?r=' . $route;
    }
}
