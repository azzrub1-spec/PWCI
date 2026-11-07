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
        $password = (string) ($_POST['password'] ?? '');

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
            'nombre' => trim((string) ($_POST['nombre'] ?? '')),
            'apellidos' => trim((string) ($_POST['apellidos'] ?? '')),
            'genero' => (string) ($_POST['genero'] ?? ''),
            'fecha_nacimiento' => (string) ($_POST['fecha_nacimiento'] ?? ''),
            'email' => filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL),
            'rol' => (string) ($_POST['rol'] ?? ''),
        ];
        $password = (string) ($_POST['password'] ?? '');
        $confirmation = (string) ($_POST['password_confirmation'] ?? '');
        $errors = $this->validateRegistration($data, $password, $confirmation);

        if ($this->usuarios->findByEmail((string) $data['email']) !== null) {
            $errors[] = 'El correo ya está registrado.';
        }

        if ($errors !== []) {
            View::render('auth/registro', ['errors' => $errors]);
            return;
        }

        $data['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
        $this->usuarios->create($data);
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

        $date = \DateTimeImmutable::createFromFormat(
            'Y-m-d',
            $data['fecha_nacimiento']
        );
        if ($date === false || $date > new \DateTimeImmutable('today')) {
            $errors[] = 'La fecha de nacimiento no es válida.';
        }

        if (!preg_match('/^(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}$/', $password)) {
            $errors[] = 'La contraseña no cumple con los requisitos.';
        }

        if ($password !== $confirmation) {
            $errors[] = 'Las contraseñas no coinciden.';
        }

        return $errors;
    }
}

