<?php
declare(strict_types=1);
namespace App\Services;

/** Cliente de servidor para Mercado Pago Checkout Pro mediante Orders API. */
final class MercadoPagoService
{
    private string $accessToken;

    public function __construct()
    {
        // APP_USR puede corresponder a prueba o producción. El entorno se confirma
        // deliberadamente, no se infiere del prefijo del Access Token.
        if (getenv('MP_HABILITAR_CHECKOUT_PRUEBA') !== '1') {
            throw new MercadoPagoException(
                'Checkout desactivado: confirma ambiente Prueba y habilita MP_HABILITAR_CHECKOUT_PRUEBA=1.', 503);
        }
        $this->accessToken = trim((string)(getenv('MP_ACCESS_TOKEN') ?: ''));
        if ($this->accessToken === '') {
            throw new MercadoPagoException('Mercado Pago no está configurado: define MP_ACCESS_TOKEN en el entorno del servidor.', 503);
        }
        if (!function_exists('curl_init')) {
            throw new MercadoPagoException('La extensión cURL de PHP no está habilitada en el servidor.', 503);
        }
    }

    /** Crea una orden real y devuelve su ID y checkout_url. No recibe montos del navegador. */
    public function crearOrden(array $compra, array $curso, array $comprador): array
    {
        $monto = number_format((float)$compra['monto'], 2, '.', '');
        $payload = [
            'type' => 'online',
            'processing_mode' => 'manual',
            'capture_mode' => 'automatic_async',
            'total_amount' => $monto,
            'external_reference' => (string)$compra['referencia_externa'],
            'description' => 'Polycraft - ' . poly_mb_substr((string)$curso['titulo'], 0, 180),
            'payer' => ['email' => (string)$comprador['email']],
            'items' => [[
                'title' => poly_mb_substr((string)$curso['titulo'], 0, 250),
                'quantity' => 1,
                'unit_price' => $monto,
            ]],
        ];
        $key = bin2hex(random_bytes(16));
        $respuesta = $this->request('POST', '/v1/orders', $payload, $key);
        if (empty($respuesta['id']) || empty($respuesta['checkout_url'])) {
            error_log('[Polycraft] Mercado Pago respondió sin id o checkout_url al crear la orden.');
            throw new MercadoPagoException('Mercado Pago no devolvió los datos de checkout esperados.');
        }
        return [
            'id' => (string)$respuesta['id'],
            'checkout_url' => (string)$respuesta['checkout_url'],
            'status' => (string)($respuesta['status'] ?? 'created'),
            'status_detail' => (string)($respuesta['status_detail'] ?? ''),
        ];
    }

    public function obtenerOrden(string $orderId): array
    {
        if (!preg_match('/^[A-Za-z0-9_-]{6,100}$/', $orderId)) {
            throw new MercadoPagoException('El identificador de orden no tiene un formato válido.', 400);
        }
        return $this->request('GET', '/v1/orders/' . rawurlencode($orderId));
    }

    private function request(string $method, string $path, ?array $payload = null, ?string $idempotencyKey = null): array
    {
        $headers = [
            'Authorization: Bearer ' . $this->accessToken,
            'Accept: application/json',
            'Content-Type: application/json',
        ];
        if ($idempotencyKey !== null) $headers[] = 'X-Idempotency-Key: ' . $idempotencyKey;

        $ch = curl_init('https://api.mercadopago.com' . $path);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_TIMEOUT => 25,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);
        if ($payload !== null) {
            $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if ($json === false) throw new MercadoPagoException('No se pudo preparar la solicitud de pago.', 500);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        }
        $body = curl_exec($ch);
        $curlError = curl_error($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($body === false) {
            error_log('[Polycraft] Error de conexión con Mercado Pago: ' . $curlError);
            throw new MercadoPagoException('No fue posible conectar con Mercado Pago. Intenta de nuevo.');
        }
        $data = json_decode((string)$body, true);
        if (!is_array($data)) {
            error_log('[Polycraft] Respuesta no JSON de Mercado Pago. HTTP ' . $status);
            throw new MercadoPagoException('Mercado Pago devolvió una respuesta inesperada.');
        }
        if ($status < 200 || $status >= 300) {
            // El detalle completo se guarda solo en el log del servidor; no se filtran credenciales ni trazas.
            error_log('[Polycraft] Mercado Pago HTTP ' . $status . ': ' . substr((string)$body, 0, 1200));
            $safeStatus = ($status === 401 || $status === 403) ? 503 : 502;
            throw new MercadoPagoException('Mercado Pago rechazó la solicitud. Verifica credenciales, cuenta y datos de prueba.', $safeStatus);
        }
        return $data;
    }
}
