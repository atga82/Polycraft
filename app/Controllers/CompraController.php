<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\Controller;
use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Models\Compra;
use App\Models\Curso;
use App\Services\MercadoPagoException;
use App\Services\MercadoPagoService;

/** Crea compras locales, solicita el checkout y consulta el estado contra el servidor de Mercado Pago. */
final class CompraController extends Controller
{
    public function crear(Request $req, array $params): Response
    {
        $usuario = $req->atributo('usuario');
        $d = $req->json();
        $this->exigir($d, ['curso_id']);
        $cursoId = filter_var($d['curso_id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if (!$cursoId) throw new HttpException(422, 'curso_id debe ser un entero positivo');

        $cursos = new Curso();
        $curso = $cursos->cursoPagado((int)$cursoId);
        if (!$curso) throw new HttpException(404, 'Curso activo no encontrado');
        if ((float)$curso['precio'] <= 0) throw new HttpException(400, 'El curso es gratuito y no necesita checkout de Mercado Pago');

        $metodo = $d['metodo_pago'] ?? 'mercado_pago';
        if (!in_array($metodo, ['mercado_pago', 'transferencia_demo'], true)) {
            throw new HttpException(422, 'Método de pago inválido. Usa mercado_pago o transferencia_demo');
        }
        $compras = new Compra();
        if ($compras->cursoYaInscrito((int)$usuario['id'], (int)$cursoId)) {
            throw new HttpException(409, 'Ya tienes una inscripción a este curso');
        }
        $ref = 'polycraft-' . (int)$usuario['id'] . '-' . bin2hex(random_bytes(12));
        $id = $compras->crearPendiente((int)$usuario['id'], (int)$cursoId, (float)$curso['precio'], $ref);
        $compra = $compras->porId($id);

        if ($metodo === 'transferencia_demo') {
            $compras->confirmarTransferenciaDemo($id, (int)$usuario['id']);
            return Response::creado([
                'id_compra' => $id, 'curso_id' => (int)$cursoId,
                'curso' => $curso['titulo'], 'monto' => (float)$curso['precio'],
                'estado' => 'aprobada', 'metodo_pago' => 'transferencia_demo',
                'mensaje' => 'Transferencia simulada para demostración. No hubo movimiento de dinero.',
            ], '/api/compras/' . $id);
        }

        try {
            $mp = new MercadoPagoService();
            $orden = $mp->crearOrden($compra, $curso, $usuario);
            $compras->asociarOrden($id, $orden['id'], $orden['status'], $orden['status_detail']);
        } catch (MercadoPagoException $e) {
            $compras->marcarError($id);
            throw new HttpException($e->httpStatus(), $e->httpStatus() === 503
                ? 'El servicio de pagos no está listo. Revisa la configuración del servidor.'
                : 'No se pudo iniciar el pago en Mercado Pago. Intenta de nuevo o revisa el log del servidor.');
        }

        return Response::creado([
            'id_compra' => $id,
            'curso_id' => (int)$cursoId,
            'curso' => $curso['titulo'],
            'monto' => (float)$curso['precio'],
            'estado' => 'pendiente',
            'order_id' => $orden['id'],
            'checkout_url' => $orden['checkout_url'],
            'referencia_externa' => $ref,
        ], '/api/compras/' . $id);
    }

    public function mostrar(Request $req, array $params): Response
    {
        $usuario = $req->atributo('usuario');
        $id = filter_var($params['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if (!$id) throw new HttpException(400, 'El id de compra debe ser un entero positivo');
        $compras = new Compra();
        $compra = $compras->porIdDeEstudiante((int)$id, (int)$usuario['id']);
        if (!$compra) throw new HttpException(404, 'Compra no encontrada');

        if ($compra['estado'] === 'pendiente' && !empty($compra['mp_order_id'])) {
            try {
                $orden = (new MercadoPagoService())->obtenerOrden((string)$compra['mp_order_id']);
                $compras->sincronizarEstado((int)$id, $orden);
                $compra = $compras->porIdDeEstudiante((int)$id, (int)$usuario['id']);
            } catch (MercadoPagoException $e) {
                throw new HttpException($e->httpStatus(), 'No se pudo consultar el estado de Mercado Pago. Intenta de nuevo más tarde.');
            }
        }
        unset($compra['estudiante_id']);
        return Response::json($compra);
    }
}
