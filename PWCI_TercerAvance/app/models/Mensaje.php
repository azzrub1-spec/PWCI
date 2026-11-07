<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

final class Mensaje
{
    public function __construct(private PDO $pdo)
    {
    }

    public function conversations(int $user, bool $isStudent): array
    {
        $student = 'm.id_remitente';
        $peer = $isStudent ? 'm.id_destinatario' : 'm.id_remitente';
        $query = $this->pdo->prepare(
            "SELECT c.id_curso, c.titulo, $student AS id_estudiante,
                    u.nombre, u.apellidos, MAX(m.fecha_envio) AS ultima_fecha
             FROM mensaje m
             JOIN curso c ON c.id_curso = m.id_curso
             JOIN usuario u ON u.id_usuario = $peer
             WHERE $student <> c.id_instructor
               AND m.id_destinatario = c.id_instructor
               AND " . ($isStudent ? 'm.id_remitente' : 'c.id_instructor') . " = :usuario
             GROUP BY c.id_curso, c.titulo, $student, u.nombre, u.apellidos
             ORDER BY ultima_fecha DESC, c.titulo, id_estudiante"
        );
        $query->execute(['usuario' => $user]);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function thread(int $course, int $user, bool $isStudent, ?int $student): ?array
    {
        $condition = $isStudent
            ? 'a.id_usuario = :usuario AND a.rol = \'estudiante\''
            : 'c.id_instructor = :usuario AND a.rol = \'estudiante\'';
        $query = $this->pdo->prepare(
            "SELECT c.id_curso, c.titulo, c.activo AS curso_activo, c.id_instructor,
                    a.id_usuario AS id_estudiante,
                    a.activo AS estudiante_activo, i.activo AS instructor_activo,
                    a.nombre AS estudiante_nombre, a.apellidos AS estudiante_apellidos,
                    i.nombre AS instructor_nombre, i.apellidos AS instructor_apellidos,
                    EXISTS (
                        SELECT 1 FROM mensaje m
                        WHERE m.id_curso = c.id_curso
                          AND m.id_remitente = a.id_usuario
                          AND m.id_destinatario = c.id_instructor
                    ) AS iniciada
             FROM curso c
             JOIN usuario i ON i.id_usuario = c.id_instructor AND i.rol = 'instructor'
             JOIN usuario a ON a.id_usuario = :estudiante
             WHERE c.id_curso = :curso AND $condition"
        );
        $query->execute([
            'estudiante' => $isStudent ? $user : $student,
            'curso' => $course,
            'usuario' => $user,
        ]);
        $row = $query->fetch(PDO::FETCH_ASSOC);
        if (!$row || (!$row['iniciada'] && (!$isStudent || !$row['curso_activo']))) {
            return null;
        }

        return $row;
    }

    public function history(int $course, int $student, int $instructor): array
    {
        $query = $this->pdo->prepare(
            'SELECT m.id_remitente, m.texto, m.fecha_envio,
                    u.nombre, u.apellidos, u.imagen_perfil
             FROM mensaje m
             JOIN usuario u ON u.id_usuario = m.id_remitente
             WHERE m.id_curso = :curso
               AND ((m.id_remitente = :alumno1 AND m.id_destinatario = :docente1)
                 OR (m.id_remitente = :docente2 AND m.id_destinatario = :alumno2))
             ORDER BY m.fecha_envio, m.id_mensaje'
        );
        $query->execute([
            'curso' => $course,
            'alumno1' => $student,
            'docente1' => $instructor,
            'docente2' => $instructor,
            'alumno2' => $student,
        ]);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function send(int $course, int $sender, int $recipient, string $message): void
    {
        $query = $this->pdo->prepare(
            'INSERT INTO mensaje (id_curso, id_remitente, id_destinatario, texto)
             VALUES (:curso, :remitente, :destinatario, :texto)'
        );
        $query->execute([
            'curso' => $course,
            'remitente' => $sender,
            'destinatario' => $recipient,
            'texto' => $message,
        ]);
    }
}
