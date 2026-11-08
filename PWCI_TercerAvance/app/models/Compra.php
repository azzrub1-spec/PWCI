<?php

declare(strict_types=1);

namespace App\Models;

use PDO;
use RuntimeException;
use Throwable;

final class Compra
{
    public function __construct(private PDO $db)
    {
    }

    public function item(int $course, ?int $level): ?array
    {
        $sql = $level === null
            ? 'SELECT titulo, costo AS monto FROM curso WHERE id_curso = :curso AND activo = 1'
            : 'SELECT CONCAT(c.titulo, " — ", n.titulo) AS titulo, n.costo_nivel AS monto
               FROM nivel n JOIN curso c ON c.id_curso = n.id_curso
               WHERE c.id_curso = :curso AND n.id_nivel = :nivel AND c.activo = 1 AND n.gratis = 0';

        $query = $this->db->prepare($sql);
        $params = ['curso' => $course];
        if ($level !== null) {
            $params['nivel'] = $level;
        }
        $query->execute($params);
        $item = $query->fetch();

        return $item && (float) $item['monto'] > 0 ? $item : null;
    }

    public function alreadyBought(int $student, int $course, ?int $level): bool
    {
        $sql = 'SELECT 1 FROM pago p JOIN inscripcion i ON i.id_inscripcion = p.id_inscripcion
                WHERE i.id_estudiante = :alumno AND i.id_curso = :curso
                  AND (p.id_nivel IS NULL';
        $params = ['alumno' => $student, 'curso' => $course];
        if ($level !== null) {
            $sql .= ' OR p.id_nivel = :nivel';
            $params['nivel'] = $level;
        }
        $query = $this->db->prepare($sql . ') LIMIT 1');
        $query->execute($params);

        return (bool) $query->fetchColumn();
    }

    public function complete(
        int $student,
        array $order,
        string $reference,
        callable $capture,
        string $method = 'PayPal'
    ): void {
        $this->db->beginTransaction();
        try {
            // Serializa compras del mismo alumno, incluso desde dos sesiones.
            $lock = $this->db->prepare('SELECT activo FROM usuario WHERE id_usuario = ? FOR UPDATE');
            $lock->execute([$student]);
            if (!(bool) $lock->fetchColumn()) {
                throw new RuntimeException('Tu cuenta no está activa.');
            }
            $check = $this->db->prepare(
                'SELECT 1 FROM pago p JOIN inscripcion i ON i.id_inscripcion = p.id_inscripcion
                 WHERE p.referencia_pasarela = ? AND i.id_estudiante = ?'
            );
            $check->execute([$reference, $student]);
            if (!$check->fetchColumn()) {
                if ($this->alreadyBought($student, $order['curso'], $order['nivel'])) {
                    throw new RuntimeException('Ya compraste este contenido.');
                }

                $capture();

                $enroll = $this->db->prepare(
                    'INSERT INTO inscripcion (id_estudiante, id_curso) VALUES (?, ?)
                     ON DUPLICATE KEY UPDATE id_inscripcion = LAST_INSERT_ID(id_inscripcion)'
                );
                $enroll->execute([$student, $order['curso']]);
                $enrollment = (int) $this->db->lastInsertId();
                $insert = $this->db->prepare(
                    'INSERT INTO pago (id_inscripcion, id_nivel, monto, forma_pago, referencia_pasarela)
                     VALUES (?, ?, ?, ?, ?)'
                );
                $insert->execute([$enrollment, $order['nivel'], $order['monto'], $method, $reference]);
            }
            $this->db->commit();
        } catch (Throwable $error) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $error;
        }
    }
}
