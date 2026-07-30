<?php
namespace App\Services;

class BancolombiaPaymentService {
    private $publicKey;
    private $privateKey;
    private $integritySecret;
    private $eventsSecret;
    private $environment;
    private $baseUrl;

    public function __construct() {
        if (!defined('BANCOLOMBIA_WOMPI_PUBLIC_KEY')) {
            require_once __DIR__ . '/../config/config.php';
        }

        $this->publicKey = BANCOLOMBIA_WOMPI_PUBLIC_KEY;
        $this->privateKey = BANCOLOMBIA_WOMPI_PRIVATE_KEY;
        $this->integritySecret = BANCOLOMBIA_WOMPI_INTEGRITY_SECRET;
        $this->eventsSecret = BANCOLOMBIA_WOMPI_EVENTS_SECRET;
        $this->environment = BANCOLOMBIA_WOMPI_ENV;

        if ($this->environment === 'production') {
            $this->baseUrl = 'https://production.wompi.co/v1';
        } else {
            $this->baseUrl = 'https://sandbox.wompi.co/v1';
        }
    }

    /**
     * Genera la firma de integridad SHA-256 para transacciones de Bancolombia / Wompi
     * Fórmula: SHA256(Referencia + MontoEnCentavos + Moneda + SecretoDeIntegridad)
     */
    public function generateIntegritySignature(string $reference, float $amount, string $currency = 'COP'): string {
        $amountInCents = (int)round($amount * 100);
        $concatenated = $reference . $amountInCents . $currency . $this->integritySecret;
        return hash('sha256', $concatenated);
    }

    /**
     * Prepara la configuración del checkout y widget de pago de Bancolombia
     */
    public function prepareCheckoutPayload(array $orderData): array {
        $reference = $orderData['order_number'];
        $amount = floatval($orderData['total']);
        $amountInCents = (int)round($amount * 100);
        $currency = 'COP';
        $signature = $this->generateIntegritySignature($reference, $amount, $currency);

        $baseUrl = defined('BASE_URL') ? BASE_URL : 'http://localhost:8000';
        $redirectUrl = $baseUrl . '/payment/response';

        return [
            'publicKey' => $this->publicKey,
            'currency' => $currency,
            'amountInCents' => $amountInCents,
            'amountFormatted' => number_format($amount, 2, ',', '.'),
            'reference' => $reference,
            'signature' => $signature,
            'redirectUrl' => $redirectUrl,
            'environment' => $this->environment,
            'customerData' => [
                'email' => $orderData['customer_email'],
                'fullName' => $orderData['customer_name'],
                'phoneNumber' => $orderData['customer_phone'],
                'legalId' => $orderData['customer_document'],
                'legalIdType' => 'CC'
            ]
        ];
    }

    /**
     * Consulta el estado de una transacción mediante su ID en la API de Bancolombia / Wompi
     */
    public function getTransactionStatus(string $transactionId): ?array {
        $url = $this->baseUrl . '/transactions/' . urlencode($transactionId);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->publicKey,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200 && $response) {
            $data = json_decode($response, true);
            return $data['data'] ?? null;
        }

        return null;
    }

    /**
     * Valida la firma checksum recibida en el Webhook de eventos asíncronos
     */
    public function isValidWebhookChecksum(array $eventPayload, string $checksumHeader): bool {
        if (empty($eventPayload['data']['transaction']) || empty($eventPayload['timestamp'])) {
            return false;
        }

        $tx = $eventPayload['data']['transaction'];
        $timestamp = $eventPayload['timestamp'];

        // Estructura: transaction.id + transaction.status + transaction.amount_in_cents + timestamp + events_secret
        $concatenated = $tx['id'] . $tx['status'] . $tx['amount_in_cents'] . $timestamp . $this->eventsSecret;
        $calculatedHash = hash('sha256', $concatenated);

        return hash_equals($calculatedHash, strtolower($checksumHeader));
    }
}
