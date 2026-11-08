<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\View;
use App\Middleware\AuthMiddleware;
use App\Models\Compra;
use App\Services\PayPalService;
use PDOException;
use RuntimeException;

final class PaymentController
{
    public function __construct(private Compra $compras, private string $configPath)
    {
    }

    public function show(): void
    {
        AuthMiddleware::requireRole(['estudiante']);
        $selection = $this->selection($_GET);
        $this->render($selection);
    }

    public function create(): void
    {
        AuthMiddleware::requireRole(['estudiante']);
        $csrf = $_POST['csrf'] ?? '';
        if (!is_string($csrf) || !hash_equals(AuthMiddleware::csrfToken(), $csrf)) {
            http_response_code(403);
            View::render('errors/403');
            return;
        }

        $selection = $this->selection($_POST);
        if ($selection === null) {
            $this->render(null, 'Este contenido no está disponible para compra.');
            return;
        }

        try {
            if ($this->compras->alreadyBought($this->student(), $selection['curso'], $selection['nivel'])) {
                throw new RuntimeException('Ya compraste este contenido.');
            }
            $state = bin2hex(random_bytes(16));
            $paypal = new PayPalService($this->configPath);
            $order = $paypal->createOrder(
                $selection['monto'],
                'MXN',
                $selection['titulo'],
                $this->absoluteUrl('paypal-capturar', $state),
                $this->absoluteUrl('paypal-cancelar', $state)
            );
            $id = $order['id'] ?? '';
            foreach (($order['links'] ?? []) as $link) {
                $url = $link['href'] ?? '';
                if (is_string($id) && preg_match('/\A[A-Z0-9]{1,40}\z/', $id)
                    && in_array($link['rel'] ?? '', ['approve', 'payer-action'], true)
                    && is_string($url) && parse_url($url, PHP_URL_SCHEME) === 'https'
                    && parse_url($url, PHP_URL_HOST) === 'www.sandbox.paypal.com') {
                    $_SESSION['compra_paypal'] = [
                        'alumno' => $this->student(), 'orden' => $id, 'estado' => $state, 'item' => $selection,
                    ];
                    header('Location: ' . $url, true, 303);
                    exit;
                }
            }
            throw new RuntimeException('PayPal no devolvió un enlace de aprobación válido.');
        } catch (RuntimeException $error) {
            $this->render($selection, $error instanceof PDOException ? 'No se pudo iniciar la compra.' : $error->getMessage());
        }
    }

    public function capture(): void
    {
        AuthMiddleware::requireRole(['estudiante']);
        $order = $this->pending();
        if ($order === null || ($_GET['token'] ?? null) !== $order['orden']) {
            $this->render(null, 'La orden no corresponde a esta sesión.');
            return;
        }

        $item = $order['item'];
        try {
            $paypal = new PayPalService($this->configPath);
            $this->compras->complete($this->student(), $item, 'paypal:' . $order['orden'], static function () use ($paypal, $order, $item): void {
                $result = $paypal->captureOrder($order['orden']);
                $capture = $result['purchase_units'][0]['payments']['captures'][0] ?? [];
                if (($result['id'] ?? null) !== $order['orden']
                    || ($capture['status'] ?? '') !== 'COMPLETED'
                    || ($capture['amount']['currency_code'] ?? '') !== 'MXN'
                    || ($capture['amount']['value'] ?? '') !== $item['monto']) {
                    throw new RuntimeException('PayPal todavía no confirma el importe completo. Puedes volver a cargar esta página para consultar la orden.');
                }
            });
            $this->render($item, null, 'Compra de prueba confirmada y registrada.');
        } catch (RuntimeException $error) {
            $this->render($item, $error instanceof PDOException
                ? 'No se pudo registrar la compra. Recarga esta página para reintentar la misma orden.'
                : $error->getMessage());
        }
    }

    public function cancel(): void
    {
        AuthMiddleware::requireRole(['estudiante']);
        $order = $this->pending();
        if ($order !== null) {
            unset($_SESSION['compra_paypal']);
        }
        $this->render($order['item'] ?? null, 'Se canceló el proceso de aprobación.');
    }

    private function pending(): ?array
    {
        $order = $_SESSION['compra_paypal'] ?? null;
        $state = $_GET['estado'] ?? '';
        if ($order === null || $order['alumno'] !== $this->student()
            || !is_string($state) || !hash_equals($order['estado'], $state)) {
            return null;
        }
        return $order;
    }

    private function selection(array $input): ?array
    {
        $options = ['options' => ['min_range' => 1]];
        $course = filter_var($input['curso'] ?? null, FILTER_VALIDATE_INT, $options);
        $level = isset($input['nivel']) ? filter_var($input['nivel'], FILTER_VALIDATE_INT, $options) : null;
        if (!$course || $level === false) {
            return null;
        }
        $item = $this->compras->item($course, $level);
        return $item === null ? null : $item + ['curso' => $course, 'nivel' => $level];
    }

    private function student(): int
    {
        return (int) $_SESSION['usuario']['id'];
    }

    private function render(?array $item, ?string $error = null, ?string $success = null): void
    {
        View::render('pago/index', [
            'item' => $item,
            'error' => $error,
            'success' => $success,
            'csrf' => AuthMiddleware::csrfToken(),
        ]);
    }

    private function absoluteUrl(string $route, string $state): string
    {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost:8080');

        return $scheme . '://' . $host . BASE_URL . '/index.php?r=' . $route . '&estado=' . $state;
    }
}
