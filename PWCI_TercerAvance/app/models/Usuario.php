<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

final class Usuario
{
    public function __construct(private PDO $database)
    {
    }

    public function findByEmail(string $email): ?array
    {
        $statement = $this->database->prepare(
            'SELECT * FROM usuario WHERE email = :email LIMIT 1'
        );
        $statement->execute(['email' => $email]);
        $usuario = $statement->fetch();

        return $usuario ?: null;
    }

    public function create(array $data): int
    {
        $statement = $this->database->prepare(
            'INSERT INTO usuario
                (nombre, apellidos, genero, fecha_nacimiento, email,
                 password_hash, rol, intentos_fallidos, activo,
                 fecha_registro, fecha_ultimo_cambio)
             VALUES
                (:nombre, :apellidos, :genero, :fecha_nacimiento, :email,
                 :password_hash, :rol, 0, 1, NOW(), NOW())'
        );
        $statement->execute($data);

        return (int) $this->database->lastInsertId();
    }

    public function registerFailedAttempt(int $id, int $attempts): void
    {
        $newAttempts = $attempts + 1;
        $statement = $this->database->prepare(
            'UPDATE usuario
             SET intentos_fallidos = :intentos_fallidos,
                 activo = CASE
                     WHEN :intentos_bloqueo >= 3 THEN 0
                     ELSE activo
                 END
             WHERE id_usuario = :id'
        );
        $statement->execute([
            'intentos_fallidos' => $newAttempts,
            'intentos_bloqueo' => $newAttempts,
            'id' => $id,
        ]);
    }

    public function resetFailedAttempts(int $id): void
    {
        $statement = $this->database->prepare(
            'UPDATE usuario SET intentos_fallidos = 0 WHERE id_usuario = :id'
        );
        $statement->execute(['id' => $id]);
    }

    public function findById(int $id): ?array
    {
        $statement = $this->database->prepare(
            'SELECT id_usuario, nombre, apellidos, genero,
                    fecha_nacimiento, imagen_perfil, email, rol
             FROM usuario WHERE id_usuario = :id LIMIT 1'
        );
        $statement->execute(['id' => $id]);
        $usuario = $statement->fetch();

        return $usuario ?: null;
    }

    public function updateProfile(int $id, array $data): void
    {
        $statement = $this->database->prepare(
            'UPDATE usuario
             SET nombre = :nombre,
                 apellidos = :apellidos,
                 genero = :genero,
                 fecha_nacimiento = :fecha_nacimiento,
                 imagen_perfil = :imagen_perfil,
                 fecha_ultimo_cambio = NOW()
             WHERE id_usuario = :id'
        );
        $statement->execute([
            'nombre' => $data['nombre'],
            'apellidos' => $data['apellidos'],
            'genero' => $data['genero'],
            'fecha_nacimiento' => $data['fecha_nacimiento'],
            'imagen_perfil' => $data['imagen_perfil'],
            'id' => $id,
        ]);
    }
}


