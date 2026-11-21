<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Model;
use PDO;

/** Persistencia de compras y conciliación de resultados confirmados por Mercado Pago. */
final class Compra extends Model
{
    public function crearPendiente(int $estudianteId, int $cursoId, float $monto, string $referencia): int
    {
        $st = $this->db->prepare(
            'INSERT INTO compras (estudiante_id, curso_id, monto, estado, referencia_externa, fecha_creacion)
             VALUES (:estudiante, :curso, :monto, \'pendiente\', :referencia, NOW())'
        );
        $st->execute([
            ':estudiante' => $estudianteId,
            ':curso' => $cursoId,
            ':monto' => number_format($monto, 2, '.', ''),
            ':referencia' => $referencia,
        ]);
        $id = (int)$this->db->lastInsertId();
        return $id;
    }

    public function asociarOrden(int $id, string $orderId, string $status, string $detail = ''): void
    {
        $st = $this->db->prepare(
            'UPDATE compras SET mp_order_id = :orden, mp_status = :status, mp_status_detail = :detalle,
                                fecha_actualizacion = NOW() WHERE id = :id'
        );
        $st->execute([':orden' => $orderId, ':status' => $status, ':detalle' => $detail, ':id' => $id]);
    }

    public function marcarError(int $id): void
    {
        $st = $this->db->prepare("UPDATE compras SET estado = 'error', fecha_actualizacion = NOW() WHERE id = :id AND estado = 'pendiente'");
        $st->execute([':id' => $id]);
    }

    public function porId(int $id): ?array
    {
        $st = $this->db->prepare(
            'SELECT co.id, co.estudiante_id, co.curso_id, co.monto, co.estado, co.referencia_externa,
                    co.mp_order_id, co.mp_status, co.mp_status_detail, co.fecha_creacion, co.fecha_actualizacion,
                    c.titulo AS curso
               FROM compras co JOIN cursos c ON c.id = co.curso_id WHERE co.id = :id'
        );
        $st->execute([':id' => $id]);
        return $st->fetch() ?: null;
    }

    public function porIdDeEstudiante(int $id, int $estudianteId): ?array
    {
        $st = $this->db->prepare(
            'SELECT co.id, co.estudiante_id, co.curso_id, co.monto, co.estado, co.referencia_externa,
                    co.mp_order_id, co.mp_status, co.mp_status_detail, co.fecha_creacion, co.fecha_actualizacion,
                    c.titulo AS curso
               FROM compras co JOIN cursos c ON c.id = co.curso_id
              WHERE co.id = :id AND co.estudiante_id = :estudiante'
        );
        $st->execute([':id' => $id, ':estudiante' => $estudianteId]);
        return $st->fetch() ?: null;
    }

    public function cursoYaInscrito(int $estudianteId, int $cursoId): bool
    {
        $st = $this->db->prepare('SELECT 1 FROM inscripciones WHERE estudiante_id = :estudiante AND curso_id = :curso');
        $st->execute([':estudiante' => $estudianteId, ':curso' => $cursoId]);
        return (bool)$st->fetchColumn();
    }

    /** Aplica una respuesta consultada directamente a Mercado Pago. Nunca confía en un estado enviado por el cliente. */
    public function sincronizarEstado(int $compraId, array $orden): void
    {
        $status = strtolower((string)($orden['status'] ?? ''));
        $detail = strtolower((string)($orden['status_detail'] ?? ''));
        $compra = $this->porId($compraId);
        if (!$compra) return;
        $referenciaRecibida = (string)($orden['external_reference'] ?? '');
        if ($referenciaRecibida === '' || !hash_equals((string)$compra['referencia_externa'], $referenciaRecibida)) {
            error_log('[Polycraft] Mercado Pago devolvio una referencia externa que no coincide con la compra local #' . $compraId);
            return;
        }
        $monto = (float)$compra['monto'];
        if (isset($orden['total_amount']) && is_numeric($orden['total_amount'])
            && abs((float)$orden['total_amount'] - $monto) > 0.01) {
            error_log('[Polycraft] Mercado Pago devolvio un monto que no coincide con la compra local #' . $compraId);
            return;
        }
        $estado = 'pendiente';
        $paid = isset($orden['total_paid_amount']) && is_numeric($orden['total_paid_amount'])
            ? (float)$orden['total_paid_amount'] : 0.0;

        if (in_array($status, ['processed', 'approved'], true) && ($detail === 'accredited' || $paid >= $monto)) {
            $estado = 'aprobada';
        } elseif (in_array($status, ['cancelled', 'canceled'], true)) {
            $estado = 'cancelada';
        } elseif (str_contains($detail, 'reject') || $status === 'rejected') {
            $estado = 'rechazada';
        }

        if ($estado === 'aprobada') {
            $this->confirmarPago($compraId, $status, $detail);
            return;
        }
        $st = $this->db->prepare(
            'UPDATE compras SET estado = CASE WHEN estado = \'aprobada\' THEN estado ELSE :estado END,
                                mp_status = :status, mp_status_detail = :detalle, fecha_actualizacion = NOW()
              WHERE id = :id'
        );
        $st->execute([':estado' => $estado, ':status' => $status, ':detalle' => $detail, ':id' => $compraId]);
    }

    /**
     * Segunda forma de pago exclusivamente académica. No realiza cargos,
     * transferencias bancarias ni comunicación con proveedores externos.
     */
    public function confirmarTransferenciaDemo(int $compraId, int $estudianteId): void
    {
        $compra = $this->porIdDeEstudiante($compraId, $estudianteId);
        if (!$compra || $compra['estado'] !== 'pendiente' || $compra['mp_order_id'] !== null) {
            throw new \DomainException('Compra no válida para simulación');
        }
        $this->confirmarPago($compraId, 'simulada', 'aprobacion_demo', 'Transferencia');
    }

    private function confirmarPago(int $compraId, string $status, string $detail, string $formaNombre = 'Mercado Pago'): void
    {
        $this->db->beginTransaction();
        try {
            $st = $this->db->prepare('SELECT * FROM compras WHERE id = :id FOR UPDATE');
            $st->execute([':id' => $compraId]);
            $compra = $st->fetch();
            if (!$compra) throw new \RuntimeException('Compra no encontrada durante la conciliación');

            $st = $this->db->prepare("UPDATE compras SET estado = 'aprobada', mp_status = :status, mp_status_detail = :detalle, fecha_actualizacion = NOW() WHERE id = :id");
            $st->execute([':status' => $status, ':detalle' => $detail, ':id' => $compraId]);

            $st = $this->db->prepare(
                'INSERT INTO inscripciones (estudiante_id, curso_id, fecha_inscripcion)
                 VALUES (:estudiante, :curso, NOW()) ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id)'
            );
            $st->execute([':estudiante' => (int)$compra['estudiante_id'], ':curso' => (int)$compra['curso_id']]);
            $inscripcionId = (int)$this->db->lastInsertId();
            if ($inscripcionId < 1) {
                $q = $this->db->prepare('SELECT id FROM inscripciones WHERE estudiante_id = :estudiante AND curso_id = :curso');
                $q->execute([':estudiante' => (int)$compra['estudiante_id'], ':curso' => (int)$compra['curso_id']]);
                $inscripcionId = (int)$q->fetchColumn();
            }

            $qForma = $this->db->prepare('SELECT id FROM formas_pago WHERE nombre = ? LIMIT 1');
            $qForma->execute([$formaNombre]);
            $forma = $qForma->fetchColumn();
            if (!$forma) throw new \RuntimeException('Falta registrar Mercado Pago en formas_pago');
            $pago = $this->db->prepare(
                'INSERT IGNORE INTO pagos (compra_id, inscripcion_id, nivel_id, forma_pago_id, monto, fecha_pago)
                 VALUES (:compra, :inscripcion, NULL, :forma, :monto, NOW())'
            );
            $pago->execute([
                ':compra' => $compraId,
                ':inscripcion' => $inscripcionId,
                ':forma' => (int)$forma,
                ':monto' => $compra['monto'],
            ]);
            $this->db->commit();
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $e;
        }
    }
}
