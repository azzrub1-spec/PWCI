<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\View;
use App\Models\Usuario;

final class AuthController
{
    public function __construct(private Usuario $usuarios)
    {
    }

    public function loginForm(): void
    {
        View::render('auth/login');
    }

    public function registerForm(): void
    {
        View::render('auth/registro');
    }

    public function login(): void
    {
        $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
        $password = $this->input('password');

        if ($email === false || $email === null || $password === '') {
            View::render('auth/login', [
                'error' => 'Captura un correo y una contraseña válidos.',
            ]);
            return;
        }

        $usuario = $this->usuarios->findByEmail($email);

        if ($usuario === null) {
            View::render('auth/login', [
                'error' => 'Las credenciales no son correctas.',
            ]);
            return;
        }

        if (!(bool) $usuario['activo']) {
            View::render('auth/login', [
                'error' => 'La cuenta está bloqueada. Solicita ayuda al administrador.',
            ]);
            return;
        }

        if (!password_verify($password, $usuario['password_hash'])) {
            $this->usuarios->registerFailedAttempt(
                (int) $usuario['id_usuario'],
                (int) $usuario['intentos_fallidos']
            );
            View::render('auth/login', [
                'error' => 'Las credenciales no son correctas.',
            ]);
            return;
        }

        $this->usuarios->resetFailedAttempts((int) $usuario['id_usuario']);
        session_regenerate_id(true);
        $_SESSION['usuario'] = [
            'id' => (int) $usuario['id_usuario'],
            'nombre' => $usuario['nombre'],
            'rol' => $usuario['rol'],
        ];

        header('Location: ' . BASE_URL . '/index.php?r=panel');
        exit;
    }

    public function register(): void
    {
        $data = [
            'nombre' => trim($this->input('nombre')),
            'apellidos' => trim($this->input('apellidos')),
            'genero' => $this->input('genero'),
            'fecha_nacimiento' => $this->input('fecha_nacimiento'),
            'email' => filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL),
            'rol' => $this->input('rol'),
        ];
        $password = $this->input('password');
        $confirmation = $this->input('password_confirmation');
        $errors = $this->validateRegistration($data, $password, $confirmation);

        if ($this->usuarios->findByEmail((string) $data['email']) !== null) {
            $errors[] = 'El correo ya está registrado.';
        }

        if ($errors !== []) {
            View::render('auth/registro', ['errors' => $errors]);
            return;
        }

        $imageErrors = [];
        $image = (new \App\Services\ImagenPerfil())->guardar($imageErrors);
        if ($image === null && $imageErrors === []) {
            $imageErrors[] = 'Selecciona una imagen JPG o PNG para tu perfil.';
        }
        if ($imageErrors !== []) {
            View::render('auth/registro', ['errors' => $imageErrors]);
            return;
        }

        $data['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
        $data['imagen_perfil'] = $image;
        try {
            $this->usuarios->create($data);
        } catch (\Throwable $exception) {
            @unlink(dirname(__DIR__, 2) . '/public/' . $image);
            $message = $exception instanceof \PDOException
                && (int) ($exception->errorInfo[1] ?? 0) === 1062
                ? 'El correo ya está registrado.'
                : 'No se pudo crear la cuenta. Intenta nuevamente.';
            View::render('auth/registro', ['errors' => [$message]]);
            return;
        }
        View::render('auth/login', [
            'success' => 'Cuenta creada correctamente. Ya puedes ingresar.',
        ]);
    }

    public function logout(): void
    {
        $_SESSION = [];
        session_destroy();
        header('Location: ' . BASE_URL . '/index.php?r=login');
        exit;
    }

    private function validateRegistration(
        array $data,
        string $password,
        string $confirmation
    ): array {
        $errors = [];
        $validRoles = ['estudiante', 'instructor'];
        $validGenders = ['Femenino', 'Masculino', 'Otro'];

        if ($data['nombre'] === '' || $data['apellidos'] === '') {
            $errors[] = 'Nombre y apellidos son obligatorios.';
        }

        if ($data['email'] === false || $data['email'] === null) {
            $errors[] = 'El correo electrónico no es válido.';
        }

        if (!in_array($data['genero'], $validGenders, true)) {
            $errors[] = 'Selecciona un género válido.';
        }

        if (!in_array($data['rol'], $validRoles, true)) {
            $errors[] = 'Selecciona un rol válido.';
        }

        $date = preg_match('/\A[1-9][0-9]{3}-[0-9]{2}-[0-9]{2}\z/', $data['fecha_nacimiento'])
            ? \DateTimeImmutable::createFromFormat('!Y-m-d', $data['fecha_nacimiento'])
            : false;
        if ($date === false || $date->format('Y-m-d') !== $data['fecha_nacimiento']
            || $date->format('Y') < '1000'
            || $date > new \DateTimeImmutable('today')) {
            $errors[] = 'La fecha de nacimiento no es válida.';
        }

        if (!preg_match('/^(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}$/', $password)) {
            $errors[] = 'La contraseña no cumple con los requisitos.';
        }

        if ($password !== $confirmation) {
            $errors[] = 'Las contraseñas no coinciden.';
        }

        foreach (['nombre' => 100, 'apellidos' => 150, 'email' => 150] as $field => $limit) {
            if (mb_strlen((string) $data[$field]) > $limit
                || preg_match('/[\x00-\x1F\x7F]/', (string) $data[$field])) {
                $errors[] = 'Revisa el campo ' . $field . ': máximo ' . $limit . ' caracteres.';
            }
        }

        return $errors;
    }

    private function input(string $key): string
    {
        $value = $_POST[$key] ?? '';

        return is_string($value) ? $value : '';
    }
}

