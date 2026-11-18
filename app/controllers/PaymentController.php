<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\View;
use App\Middleware\AuthMiddleware;
use App\Models\Compra;
use App\Services\PayPalService;
use App\Services\StripeTestService;
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

    public function stripeCreate(): void
    {
        AuthMiddleware::requireRole(['estudiante']);
        $csrf = $_POST['csrf'] ?? '';
        if (!is_string($csrf) || !hash_equals(AuthMiddleware::csrfToken(), $csrf)) {
            http_response_code(403);
            View::render('errors/403');
            return;
        }

        $item = $this->selection($_POST);
        if ($item === null) {
            $this->render(null, 'Este contenido no está disponible para compra.');
            return;
        }

        try {
            if ($this->compras->alreadyBought($this->student(), $item['curso'], $item['nivel'])) {
                throw new RuntimeException('Ya compraste este contenido.');
            }
            $state = bin2hex(random_bytes(16));
            $return = $this->absoluteUrl('stripe-confirmar', $state);
            $cancel = $this->absoluteUrl('pago', $state) . '&curso=' . $item['curso']
                . ($item['nivel'] === null ? '' : '&nivel=' . $item['nivel']);
            $stripe = new StripeTestService(dirname($this->configPath) . '/stripe.local.php');
            $checkout = $stripe->create($item, $return . '&session_id={CHECKOUT_SESSION_ID}', $cancel);
            $id = $checkout['id'] ?? '';
            $url = $checkout['url'] ?? '';
            if (!is_string($id) || !preg_match('/\Acs_test_[A-Za-z0-9]{1,110}\z/', $id)
                || ($checkout['livemode'] ?? null) !== false
                || !is_string($url) || parse_url($url, PHP_URL_SCHEME) !== 'https'
                || parse_url($url, PHP_URL_HOST) !== 'checkout.stripe.com') {
                throw new RuntimeException('Stripe no devolvió una sesión de prueba válida.');
            }
            $_SESSION['compras_stripe'][$id] = [
                'alumno' => $this->student(), 'estado' => $state, 'item' => $item,
            ];
            header('Location: ' . $url, true, 303);
            exit;
        } catch (RuntimeException $error) {
            $this->render($item, $error instanceof PDOException
                ? 'No se pudo iniciar la compra.'
                : $error->getMessage());
        }
    }

    public function stripeConfirm(): void
    {
        AuthMiddleware::requireRole(['estudiante']);
        $id = $_GET['session_id'] ?? '';
        $state = $_GET['estado'] ?? '';
        $order = is_string($id) ? ($_SESSION['compras_stripe'][$id] ?? null) : null;
        if ($order === null || $order['alumno'] !== $this->student()
            || !is_string($state) || !hash_equals($order['estado'], $state)) {
            $this->render(null, 'La orden de Stripe no corresponde a esta sesión.');
            return;
        }

        $item = $order['item'];
        try {
            $stripe = new StripeTestService(dirname($this->configPath) . '/stripe.local.php');
            $this->compras->complete(
                $this->student(),
                $item,
                'stripe:' . $id,
                static function () use ($stripe, $id, $item): void {
                    $stripe->verify($id, $item['monto']);
                },
                'Tarjeta (Stripe)'
            );
            $this->render($item, null, 'Tu compra se registró correctamente.');
        } catch (RuntimeException $error) {
            $this->render($item, $error instanceof PDOException
                ? 'No se pudo registrar la compra. Recarga esta página para reintentar.'
                : $error->getMessage());
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
            $this->render($item, null, 'Tu compra se registró correctamente.');
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
        // El detalle técnico queda en el registro, no en la pantalla de compra.
        if ($error !== null) {
            $mensajes = [
                'La extensión cURL de PHP no está habilitada.' => 'El pago no está disponible en este momento.',
                'Falta configurar PayPal Sandbox en config/paypal.local.php.' => 'El pago con PayPal no está disponible en este momento.',
                'La configuración de PayPal Sandbox no es válida.' => 'El pago con PayPal no está disponible en este momento.',
                'PayPal rechazó las credenciales Sandbox.' => 'El pago con PayPal no está disponible en este momento.',
                'Habilita cURL de PHP para usar Stripe.' => 'El pago con tarjeta no está disponible en este momento.',
                'Configura una clave secreta de prueba de Stripe en config/stripe.local.php.' => 'El pago con tarjeta no está disponible en este momento.',
                'No fue posible conectar con PayPal Sandbox.' => 'No fue posible conectar con PayPal. Intenta nuevamente.',
                'No fue posible completar la solicitud a PayPal.' => 'No fue posible completar la operación con PayPal. Intenta nuevamente.',
                'PayPal no pudo procesar la operación de prueba.' => 'PayPal no pudo procesar la operación. Intenta nuevamente.',
                'PayPal no devolvió un enlace de aprobación válido.' => 'No fue posible iniciar el pago con PayPal. Intenta nuevamente.',
                'Stripe no devolvió una sesión de prueba válida.' => 'No fue posible iniciar el pago con tarjeta. Intenta nuevamente.',
                'Stripe no confirmó el pago por el importe esperado.' => 'No se pudo verificar el pago. No se ha registrado la compra.',
                'La orden de Stripe no corresponde a esta sesión.' => 'No se pudo identificar tu compra. Regresa al curso e inténtalo nuevamente.',
            ];
            $mensajePublico = $mensajes[$error] ?? null;
            if (str_starts_with($error, 'XAMPP no pudo conectarse a Stripe (cURL ')) {
                $mensajePublico = 'No fue posible conectar con el servicio de pago. Intenta nuevamente.';
            } elseif (str_starts_with($error, 'Stripe rechazó la solicitud (HTTP ')) {
                $mensajePublico = 'No fue posible completar la operación con tarjeta. Intenta nuevamente.';
            }
            if ($mensajePublico !== null) {
                error_log('[KONNEKT pagos] ' . str_replace(["\r", "\n"], ' ', $error));
                $error = $mensajePublico;
            }
        }

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
