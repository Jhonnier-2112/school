<?php
namespace App\Models;

use App\Config\Database;
use PDO;
use PDOException;

class Registration {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
        self::checkPaymentColumns();
    }

    /**
     * Obtiene el catálogo de etapas de carrera activas con precios vigentes (preventa/normal)
     */
    public static function getRaceStages(): array {
        if (class_exists('App\Models\Event')) {
            $stages = \App\Models\Event::getStages(1);
            if (!empty($stages)) {
                return $stages;
            }
        }
        try {
            $database = new Database();
            $db = $database->getConnection();
            $stmt = $db->query("SELECT id, name, slug, category_type, distance, presale_price, price, description, is_active FROM race_stages WHERE is_active = 1 ORDER BY category_type ASC, id ASC");
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Registra un participante a una o varias etapas de carrera
     */
    public static function create($data) {
        try {
            $database = new Database();
            $db = $database->getConnection();
            
            $sql = "INSERT INTO registrations (
                user_id, categoria_participante, etapas_seleccionadas, etapas_preventa, nombre_mascota, raza_mascota,
                acudiente_nombre, acudiente_documento, nombres, apellidos, tipo_documento, numero_documento, 
                fecha_nacimiento, edad, genero, eps, grupo_sanguineo, rh, talla_camiseta_adulto, talla_camiseta_nino,
                direccion, municipio, departamento, email, telefono, 
                parentesco_emergencia, otro_parentesco, nombre_emergencia, 
                nombre_emergencia_alt, celular_emergencia, acepta_autorizacion, created_at,
                payment_status, payment_amount, order_number
            ) VALUES (
                :user_id, :categoria_participante, :etapas_seleccionadas, :etapas_preventa, :nombre_mascota, :raza_mascota,
                :acudiente_nombre, :acudiente_documento, :nombres, :apellidos, :tipo_documento, :numero_documento, 
                :fecha_nacimiento, :edad, :genero, :eps, :grupo_sanguineo, :rh, :talla_camiseta_adulto, :talla_camiseta_nino,
                :direccion, :municipio, :departamento, :email, :telefono, 
                :parentesco_emergencia, :otro_parentesco, :nombre_emergencia, 
                :nombre_emergencia_alt, :celular_emergencia, :acepta_autorizacion, NOW(),
                :payment_status, :payment_amount, :order_number
            )";

            $etapas = is_array($data['etapas_seleccionadas'] ?? null) ? json_encode($data['etapas_seleccionadas']) : ($data['etapas_seleccionadas'] ?? null);

            // Determinar qué etapas están en preventa en el momento de la inscripción
            $etapasIds = is_array($data['etapas_seleccionadas'] ?? null) ? $data['etapas_seleccionadas'] : json_decode($data['etapas_seleccionadas'] ?? '[]', true);
            if (!is_array($etapasIds)) {
                $etapasIds = [$etapasIds];
            }
            
            $etapasPreventaIds = [];
            if (class_exists('App\Models\Event')) {
                $allStages = \App\Models\Event::getStages(1);
                $event = \App\Models\Event::getPrimaryEvent();
                foreach ($allStages as $stg) {
                    if (in_array((int)$stg['id'], $etapasIds)) {
                        if (\App\Models\Event::isStageInPresale($stg, $event)) {
                            $etapasPreventaIds[] = (int)$stg['id'];
                        }
                    }
                }
            }
            $etapasPreventa = json_encode($etapasPreventaIds);

            $insertData = [
                ':user_id' => $data['user_id'] ?? null,
                ':categoria_participante' => $data['categoria_participante'] ?? 'adulto',
                ':etapas_seleccionadas' => $etapas,
                ':etapas_preventa' => $etapasPreventa,
                ':nombre_mascota' => $data['nombre_mascota'] ?? null,
                ':raza_mascota' => $data['raza_mascota'] ?? null,
                ':acudiente_nombre' => $data['acudiente_nombre'] ?? null,
                ':acudiente_documento' => $data['acudiente_documento'] ?? null,
                ':nombres' => $data['nombres'],
                ':apellidos' => $data['apellidos'],
                ':tipo_documento' => $data['tipo_documento'] ?? 'CC',
                ':numero_documento' => $data['numero_documento'],
                ':fecha_nacimiento' => $data['fecha_nacimiento'] ?? null,
                ':edad' => $data['edad'] ?? 0,
                ':genero' => $data['genero'] ?? null,
                ':eps' => $data['eps'] ?? null,
                ':grupo_sanguineo' => $data['grupo_sanguineo'] ?? null,
                ':rh' => $data['rh'] ?? null,
                ':talla_camiseta_adulto' => $data['talla_camiseta_adulto'] ?? null,
                ':talla_camiseta_nino' => $data['talla_camiseta_nino'] ?? null,
                ':direccion' => $data['direccion'] ?? '',
                ':municipio' => $data['municipio'] ?? 'Cali',
                ':departamento' => $data['departamento'] ?? 'Valle del Cauca',
                ':email' => $data['email'],
                ':telefono' => $data['telefono'],
                ':parentesco_emergencia' => $data['parentesco_emergencia'] ?? 'familiar',
                ':otro_parentesco' => $data['otro_parentesco'] ?? null,
                ':nombre_emergencia' => $data['nombre_emergencia'] ?? null,
                ':nombre_emergencia_alt' => $data['nombre_emergencia_alt'] ?? null,
                ':celular_emergencia' => $data['celular_emergencia'] ?? null,
                ':acepta_autorizacion' => $data['acepta_autorizacion'] ?? 'si',
                ':payment_status' => $data['payment_status'] ?? 'pending',
                ':payment_amount' => $data['payment_amount'] ?? 0.00,
                ':order_number' => $data['order_number'] ?? null
            ];

            $stmt = $db->prepare($sql);
            if ($stmt->execute($insertData)) {
                return $db->lastInsertId();
            } else {
                return false;
            }
            
        } catch (PDOException $e) {
            error_log("Registration::create() Error: " . $e->getMessage());
            return false;
        }
    }

    public function delete($id) {
        try {
            $stmt = $this->conn->prepare("DELETE FROM registrations WHERE id = :id");
            return $stmt->execute([':id' => $id]);
        } catch (PDOException $e) {
            return false;
        }
    }

    public function getAll() {
        try {
            // No se hace JOIN con events para evitar fallos cuando event_id es NULL
            $query = "SELECT r.* FROM registrations r ORDER BY r.created_at DESC, r.id DESC";
            $stmt = $this->conn->query($query);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $rows ?: [];
        } catch (PDOException $e) {
            error_log("Registration::getAll() Error: " . $e->getMessage());
            return [];
        }
    }

    public function countAll() {
        try {
            $stmt = $this->conn->query("SELECT COUNT(*) AS total FROM registrations");
            return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        } catch (PDOException $e) {
            return 0;
        }
    }

    public static function findByDocument($numero_documento) {
        try {
            $database = new Database();
            $db = $database->getConnection();
            
            $stmt = $db->prepare("SELECT * FROM registrations WHERE numero_documento = :numero_documento ORDER BY id DESC LIMIT 1");
            $stmt->execute([':numero_documento' => $numero_documento]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return false;
        }
    }

    public static function findAllByDocument($numero_documento) {
        try {
            $database = new Database();
            $db = $database->getConnection();
            
            $stmt = $db->prepare("SELECT * FROM registrations WHERE numero_documento = :numero_documento");
            $stmt->execute([':numero_documento' => $numero_documento]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            return [];
        }
    }

    public function validateData($data) {
        $errors = [];

        if (empty($data['nombres'])) {
            $errors[] = 'Los nombres son requeridos';
        }
        if (empty($data['apellidos'])) {
            $errors[] = 'Los apellidos son requeridos';
        }
        if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Email válido es requerido';
        }
        if (empty($data['numero_documento'])) {
            $errors[] = 'El número de documento es requerido';
        }
        
        if (empty($data['telefono'])) {
            $errors[] = 'El teléfono es requerido';
        } else {
            $telefonoClean = preg_replace('/[^0-9]/', '', $data['telefono']);
            if (strlen($telefonoClean) !== 10 || strpos($telefonoClean, '3') !== 0) {
                $errors[] = 'El celular de contacto debe iniciar con 3 y tener 10 dígitos';
            }
        }

        if (empty($data['etapas_seleccionadas'])) {
            $errors[] = 'Debe seleccionar al menos una etapa para inscribirse';
        } else {
            $categoria = $data['categoria_participante'] ?? 'adulto';
            $selectedStages = $data['etapas_seleccionadas'] ?? [];
            if (!is_array($selectedStages)) {
                $selectedStages = [$selectedStages];
            }
            
            if ($categoria === 'adulto') {
                $allStages = self::getRaceStages();
                $adultStageIds = [];
                foreach ($allStages as $stg) {
                    if (($stg['category_type'] ?? '') === 'adulto') {
                        $adultStageIds[] = (int)$stg['id'];
                    }
                }
                
                $selectedAdultCount = 0;
                foreach ($selectedStages as $sid) {
                    if (in_array((int)$sid, $adultStageIds)) {
                        $selectedAdultCount++;
                    }
                }
                
                if ($selectedAdultCount === 0) {
                    $errors[] = 'Debe seleccionar una etapa para la categoría Adulto (5K o 10K)';
                } elseif ($selectedAdultCount > 1) {
                    $errors[] = 'Un adulto solo se puede inscribir a 10K o 5K, pero no a los dos';
                }
            }

            // Validar si los cupos de las etapas seleccionadas están agotados
            if (class_exists('App\Models\Event')) {
                $allStages = \App\Models\Event::getStages(1);
                $stagesMap = [];
                foreach ($allStages as $stg) {
                    $stagesMap[(int)$stg['id']] = $stg;
                }

                foreach ($selectedStages as $sid) {
                    if (isset($stagesMap[(int)$sid])) {
                        $stg = $stagesMap[(int)$sid];
                        if (!empty($stg['is_sold_out'])) {
                            $errors[] = 'Los cupos para la etapa "' . $stg['name'] . '" se han agotado.';
                        }
                    }
                }
            }
        }

        // Validar que no se inscriba a 5K y 10K (ya sea en esta inscripción o combinada con previas)
        if (!empty($data['numero_documento']) && !empty($data['etapas_seleccionadas'])) {
            $currentStages = $data['etapas_seleccionadas'];
            if (!is_array($currentStages)) {
                $currentStages = [$currentStages];
            }
            
            $existingRegistrations = self::findAllByDocument($data['numero_documento']);
            $existingStageIds = [];
            foreach ($existingRegistrations as $reg) {
                if (($reg['payment_status'] ?? 'pending') === 'paid') {
                    $etapas = $reg['etapas_seleccionadas'];
                    if (!empty($etapas)) {
                        if (is_string($etapas)) {
                            $decoded = json_decode($etapas, true);
                            if (is_array($decoded)) {
                                foreach ($decoded as $id) {
                                    $existingStageIds[] = (int)$id;
                                }
                            } else {
                                $existingStageIds[] = (int)$etapas;
                            }
                        } elseif (is_array($etapas)) {
                            foreach ($etapas as $id) {
                                $existingStageIds[] = (int)$id;
                            }
                        }
                    }
                }
            }

            $allStages = self::getRaceStages();
            $stageDistances = [];
            $stageNames = [];
            foreach ($allStages as $stg) {
                $stageDistances[(int)$stg['id']] = $stg['distance'];
                $stageNames[(int)$stg['id']] = $stg['name'];
            }

            // Validar si ya se encuentra inscrito en exactamente la misma etapa
            foreach ($currentStages as $sid) {
                if (in_array((int)$sid, $existingStageIds)) {
                    $errors[] = "Ya existe una inscripción registrada con este documento para la etapa: " . ($stageNames[(int)$sid] ?? $sid);
                }
            }

            // Validar choque entre 5K y 10K
            $has5K = false;
            $has10K = false;
            $totalStageIds = array_unique(array_merge($currentStages, $existingStageIds));
            
            foreach ($totalStageIds as $sid) {
                $dist = $stageDistances[(int)$sid] ?? '';
                if ($dist === '5K') {
                    $has5K = true;
                } elseif ($dist === '10K') {
                    $has10K = true;
                }
            }

            if ($has5K && $has10K) {
                $errors[] = 'Un participante solo se puede inscribir a 10K o 5K, pero no a las dos distancias (se corren el mismo día). Sin embargo, sí te puedes inscribir a 3K y también a 5K o 10K.';
            }
        }

        $categoria = $data['categoria_participante'] ?? 'adulto';
        
        if (!empty($data['fecha_nacimiento'])) {
            try {
                $birthDate = new \DateTime($data['fecha_nacimiento']);
                $today = new \DateTime('now');
                $age = $today->diff($birthDate)->y;

                if ($categoria === 'adulto') {
                    if ($age < 18) {
                        $errors[] = 'Como adulto debes ser mayor de 18 años';
                    } elseif ($age >= 90) {
                        $errors[] = 'La edad para adulto debe ser menor a 90 años';
                    }
                } else {
                    if ($age < 8) {
                        $errors[] = 'Debes tener al menos 8 años para participar';
                    }
                }

                // Validar consistencia de tipo de documento y edad
                $docType = $data['tipo_documento'] ?? '';
                if ($docType === 'tarjeta_identidad' && $age >= 18) {
                    $errors[] = 'La Tarjeta de Identidad es para menores de 18 años';
                } elseif ($docType === 'cedula_ciudadania' && $age < 18) {
                    $errors[] = 'La Cédula de Ciudadanía es para mayores de 18 años';
                }
            } catch (\Exception $e) {
                $errors[] = 'La fecha de nacimiento no tiene un formato válido';
            }
        } else {
            $errors[] = 'La fecha de nacimiento es requerida';
        }

        if ($categoria === 'nino' && empty($data['acudiente_nombre'])) {
            $errors[] = 'El nombre del acudiente es obligatorio para la inscripción infantil';
        }
        if ($categoria === 'nino' && empty($data['talla_camiseta_nino'])) {
            $errors[] = 'La talla de camiseta para el niño es obligatoria';
        }
        if ($categoria !== 'nino' && empty($data['talla_camiseta_adulto'])) {
            $errors[] = 'La talla de camiseta de adulto es obligatoria';
        }
        if ($categoria === 'mascota' && empty($data['nombre_mascota'])) {
            $errors[] = 'El nombre de la mascota es obligatorio para la categoría Pet Run';
        }

        if (($data['acepta_autorizacion'] ?? '') !== 'si') {
            $errors[] = 'Debe aceptar la autorización para participar';
        }

        return $errors;
    }

    /**
     * Asegura que existan las columnas de pago en la tabla registrations
     */
    public static function checkPaymentColumns() {
        try {
            $database = new Database();
            $db = $database->getConnection();
            
            // Comprobar si existe la columna order_number
            $dbname = defined('DB_NAME') ? DB_NAME : 'runner_db';
            $check = $db->query("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '{$dbname}' AND TABLE_NAME = 'registrations' AND COLUMN_NAME = 'order_number'");
            $exists = (int)$check->fetchColumn() > 0;
            
            if (!$exists) {
                $db->exec("ALTER TABLE registrations ADD COLUMN payment_status VARCHAR(30) NOT NULL DEFAULT 'pending', ADD COLUMN payment_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00, ADD COLUMN order_number VARCHAR(50) NULL");
            }
        } catch (PDOException $e) {
            error_log("Registration::checkPaymentColumns() Error: " . $e->getMessage());
        }
    }

    /**
     * Actualiza el estado de pago de una inscripción por su número de orden
     */
    public static function updatePaymentStatusByOrder(string $orderNumber, string $paymentStatus): bool {
        try {
            $database = new Database();
            $db = $database->getConnection();
            $stmt = $db->prepare("UPDATE registrations SET payment_status = :status WHERE order_number = :order_num");
            return $stmt->execute([
                ':status' => $paymentStatus,
                ':order_num' => $orderNumber
            ]);
        } catch (PDOException $e) {
            error_log("Registration::updatePaymentStatusByOrder() Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Busca la inscripción asociada a un número de orden
     */
    public static function findByOrderNumber(string $orderNumber): ?array {
        try {
            $database = new Database();
            $db = $database->getConnection();
            $stmt = $db->prepare("SELECT * FROM registrations WHERE order_number = :order_number LIMIT 1");
            $stmt->execute([':order_number' => $orderNumber]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (PDOException $e) {
            return null;
        }
    }
}
