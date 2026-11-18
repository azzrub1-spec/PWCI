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
                (nombre, apellidos, genero, fecha_nacimiento, email, imagen_perfil,
                 password_hash, rol, intentos_fallidos, activo,
                 fecha_registro, fecha_ultimo_cambio)
             VALUES
                (:nombre, :apellidos, :genero, :fecha_nacimiento, :email, :imagen_perfil,
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

    public function listForAdministration(): array
    {
        return $this->database->query(
            'SELECT id_usuario, nombre, apellidos, email, rol, activo
             FROM usuario ORDER BY apellidos, nombre, id_usuario'
        )->fetchAll();
    }

    public function accessState(int $id): ?array
    {
        $statement = $this->database->prepare(
            'SELECT rol, activo FROM usuario WHERE id_usuario = :id'
        );
        $statement->execute(['id' => $id]);

        return $statement->fetch() ?: null;
    }

    public function activeInstructorReport(): array
    {
        return $this->database->query(
            "SELECT u.email, u.nombre, u.apellidos, u.fecha_registro,
                    (SELECT COUNT(*) FROM curso c WHERE c.id_instructor = u.id_usuario) AS cursos,
                    COALESCE((SELECT SUM(p.monto) FROM curso c
                              JOIN inscripcion i ON i.id_curso = c.id_curso
                              JOIN pago p ON p.id_inscripcion = i.id_inscripcion
                              WHERE c.id_instructor = u.id_usuario), 0) AS ganancias
             FROM usuario u
             WHERE u.activo = 1 AND u.rol = 'instructor'
             ORDER BY u.apellidos, u.nombre, u.id_usuario"
        )->fetchAll();
    }

    public function activeStudentReport(): array
    {
        return $this->database->query(
            "SELECT u.email, u.nombre, u.apellidos, u.fecha_registro,
                    (SELECT COUNT(*) FROM inscripcion i WHERE i.id_estudiante = u.id_usuario) AS cursos,
                    (SELECT COUNT(*) FROM inscripcion i
                     WHERE i.id_estudiante = u.id_usuario
                       AND EXISTS (SELECT 1 FROM nivel n WHERE n.id_curso = i.id_curso)
                       AND NOT EXISTS (
                           SELECT 1 FROM nivel n WHERE n.id_curso = i.id_curso
                             AND NOT EXISTS (
                                 SELECT 1 FROM progreso_nivel p
                                 WHERE p.id_inscripcion = i.id_inscripcion
                                   AND p.id_nivel = n.id_nivel AND p.completado = 1
                             )
                       )) AS terminados
             FROM usuario u
             WHERE u.activo = 1 AND u.rol = 'estudiante'
             ORDER BY u.apellidos, u.nombre, u.id_usuario"
        )->fetchAll();
    }

    public function setActive(int $id, int $administrator, bool $active): bool
    {
        $value = (int) $active;
        $statement = $this->database->prepare(
            'UPDATE usuario
             SET activo = :nuevo,
                 intentos_fallidos = CASE WHEN :reiniciar = 1 THEN 0 ELSE intentos_fallidos END
             WHERE id_usuario = :id AND id_usuario <> :administrador AND activo <> :estado'
        );
        $statement->execute([
            'nuevo' => $value,
            'reiniciar' => $value,
            'id' => $id,
            'administrador' => $administrator,
            'estado' => $value,
        ]);

        return $statement->rowCount() === 1;
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


