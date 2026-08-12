<?php
namespace App\Models;

use App\Config\Database;
use PDO;
use PDOException;

class Event {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    /**
     * Obtiene el evento principal activo con métricas de inscritos
     */
    public static function getPrimaryEvent(): ?array {
        try {
            $database = new Database();
            $db = $database->getConnection();

            $stmt = $db->query("SELECT * FROM events WHERE is_active = 1 ORDER BY id ASC LIMIT 1");
            $event = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$event) {
                return null;
            }

            // Contar inscripciones totales (solo las pagadas)
            $countStmt = $db->query("SELECT COUNT(*) AS total FROM registrations WHERE payment_status = 'paid'");
            $registeredCount = (int)($countStmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

            $event['registered_count'] = $registeredCount;
            $event['available_slots'] = max(0, (int)$event['total_slots'] - $registeredCount);
            $event['is_presale_active'] = self::isPresaleActive($event);

            return $event;
        } catch (PDOException $e) {
            error_log("Event::getPrimaryEvent() Error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Comprueba si la preventa está activa según las fechas configuradas
     */
    public static function isPresaleActive(?array $event): bool {
        if (!$event || empty($event['presale_start_date']) || empty($event['presale_end_date'])) {
            return false;
        }

        $now = time();
        $start = strtotime($event['presale_start_date']);
        $end = strtotime($event['presale_end_date']);

        return ($now >= $start && $now <= $end);
    }

    /**
     * Actualiza la información general, cupos y fechas del evento
     */
    public function updateEvent(int $id, array $data): bool {
        try {
            $sql = "UPDATE events SET 
                        title = :title,
                        location = :location,
                        total_slots = :total_slots,
                        presale_start_date = :presale_start_date,
                        presale_end_date = :presale_end_date,
                        event_end_date = :event_end_date,
                        updated_at = NOW()
                    WHERE id = :id";

            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                ':title' => $data['title'] ?? 'Carrera Corre Con FemTribe',
                ':location' => $data['location'] ?? 'Cali, Valle del Cauca',
                ':total_slots' => (int)($data['total_slots'] ?? 600),
                ':presale_start_date' => !empty($data['presale_start_date']) ? $data['presale_start_date'] : null,
                ':presale_end_date' => !empty($data['presale_end_date']) ? $data['presale_end_date'] : null,
                ':event_end_date' => !empty($data['event_end_date']) ? $data['event_end_date'] : null,
                ':id' => $id
            ]);
        } catch (PDOException $e) {
            error_log("Event::updateEvent() Error: " . $e->getMessage());
            return false;
        }
    }

    public static function getStageRegistrationCounts(int $stageId): array {
        try {
            $database = new Database();
            $db = $database->getConnection();
            
            // Solo contar las inscripciones que estén confirmadas (pagadas)
            $stmt = $db->query("SELECT etapas_seleccionadas, etapas_preventa FROM registrations WHERE payment_status = 'paid'");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            
            $totalCount = 0;
            $presaleCount = 0;
            
            foreach ($rows as $row) {
                $sel = $row['etapas_seleccionadas'];
                $ids = [];
                if (!empty($sel)) {
                    if (is_string($sel)) {
                        $decoded = json_decode($sel, true);
                        $ids = is_array($decoded) ? $decoded : [(int)$sel];
                    } elseif (is_array($sel)) {
                        $ids = $sel;
                    }
                }
                
                if (in_array($stageId, $ids)) {
                    $totalCount++;
                }
                
                $ep = $row['etapas_preventa'];
                if (!empty($ep)) {
                    $pIds = json_decode($ep, true);
                    if (is_array($pIds) && in_array($stageId, $pIds)) {
                        $presaleCount++;
                    }
                }
            }
            
            return [
                'total' => $totalCount,
                'presale' => $presaleCount,
                'normal' => max(0, $totalCount - $presaleCount)
            ];
        } catch (PDOException $e) {
            return ['total' => 0, 'presale' => 0, 'normal' => 0];
        }
    }

    public static function isStageInPresale(array $stage, ?array $event = null): bool {
        if (!$event) {
            $event = self::getPrimaryEvent();
        }

        $datePresaleActive = self::isPresaleActive($event);
        if (!$datePresaleActive) {
            return false;
        }

        if (empty($stage['presale_slots_limit'])) {
            return true;
        }

        $counts = self::getStageRegistrationCounts((int)$stage['id']);
        return ($counts['presale'] < (int)$stage['presale_slots_limit']);
    }

    /**
     * Obtiene las etapas / kilometrajes de un evento
     */
    public static function getStages(int $eventId = 1): array {
        try {
            $database = new Database();
            $db = $database->getConnection();

            // Auto-migración para agregar la columna presale_slots_limit si no existe
            try {
                $db->exec("ALTER TABLE race_stages ADD COLUMN presale_slots_limit INT(11) DEFAULT NULL");
            } catch (PDOException $e) {}

            $stmt = $db->prepare("SELECT *, COALESCE(slots_limit, NULL) AS slots_limit, COALESCE(presale_slots_limit, NULL) AS presale_slots_limit FROM race_stages WHERE event_id = :event_id ORDER BY id ASC");
            $stmt->execute([':event_id' => $eventId]);
            $stages = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            $event = self::getPrimaryEvent();

            foreach ($stages as &$stg) {
                $counts = self::getStageRegistrationCounts((int)$stg['id']);
                $stg['registered_count'] = $counts['total'];
                $stg['presale_registered_count'] = $counts['presale'];
                
                $stgPresaleLimit = isset($stg['presale_slots_limit']) ? (int)$stg['presale_slots_limit'] : 0;
                $stgNormalLimit = isset($stg['slots_limit']) ? (int)$stg['slots_limit'] : 0;
                $stgTotalLimit = $stgPresaleLimit + $stgNormalLimit;
                
                $stg['is_stage_presale_active'] = self::isStageInPresale($stg, $event);
                $stg['active_price'] = $stg['is_stage_presale_active'] ? (float)$stg['presale_price'] : (float)$stg['price'];
                
                $stg['is_sold_out'] = ($stgTotalLimit > 0 && $counts['total'] >= $stgTotalLimit);
                $stg['available_slots'] = $stgTotalLimit > 0 ? max(0, $stgTotalLimit - $counts['total']) : 9999;
            }

            return $stages;
        } catch (PDOException $e) {
            error_log("Event::getStages() Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Actualiza los costos y datos de una etapa/kilometraje específica
     */
    public function updateStage(int $stageId, array $data): bool {
        try {
            $slotsLimit = isset($data['slots_limit']) && $data['slots_limit'] !== '' && $data['slots_limit'] !== null
                ? max(1, (int)$data['slots_limit'])
                : null;

            $presaleSlotsLimit = isset($data['presale_slots_limit']) && $data['presale_slots_limit'] !== '' && $data['presale_slots_limit'] !== null
                ? max(1, (int)$data['presale_slots_limit'])
                : null;

            $sql = "UPDATE race_stages SET 
                        name = :name,
                        distance = :distance,
                        category_type = :category_type,
                        presale_price = :presale_price,
                        price = :price,
                        is_active = :is_active,
                        slots_limit = :slots_limit,
                        presale_slots_limit = :presale_slots_limit
                    WHERE id = :id";

            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                ':name'           => $data['name'],
                ':distance'       => $data['distance'],
                ':category_type'  => $data['category_type'],
                ':presale_price'  => (float)($data['presale_price'] ?? 0),
                ':price'          => (float)($data['price'] ?? 0),
                ':is_active'      => isset($data['is_active']) ? (int)$data['is_active'] : 1,
                ':slots_limit'    => $slotsLimit,
                ':presale_slots_limit' => $presaleSlotsLimit,
                ':id'             => $stageId
            ]);
        } catch (PDOException $e) {
            error_log("Event::updateStage() Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Actualiza solo el límite de cupos de una etapa
     */
    public function updateStageSlots(int $stageId, ?int $slotsLimit, ?int $presaleSlotsLimit = null): bool {
        try {
            $stmt = $this->db->prepare("UPDATE race_stages SET slots_limit = :slots_limit, presale_slots_limit = :presale_slots_limit WHERE id = :id");
            return $stmt->execute([
                ':slots_limit' => $slotsLimit, 
                ':presale_slots_limit' => $presaleSlotsLimit, 
                ':id' => $stageId
            ]);
        } catch (PDOException $e) {
            error_log("Event::updateStageSlots() Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Crea un nuevo kilometraje / etapa de carrera
     */
    public function createStage(array $data): bool {
        try {
            $name = trim($data['name'] ?? '');
            if ($name === '') return false;

            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name))) . '-' . rand(10, 99);
            $sql = "INSERT INTO race_stages 
                        (event_id, name, slug, category_type, distance, presale_price, price, description, is_active, created_at)
                    VALUES 
                        (:event_id, :name, :slug, :category_type, :distance, :presale_price, :price, :description, 1, NOW())";

            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                ':event_id' => (int)($data['event_id'] ?? 1),
                ':name' => $name,
                ':slug' => $slug,
                ':category_type' => $data['category_type'] ?? 'adulto',
                ':distance' => trim($data['distance'] ?? '5K'),
                ':presale_price' => (float)($data['presale_price'] ?? 0),
                ':price' => (float)($data['price'] ?? 0),
                ':description' => trim($data['description'] ?? '')
            ]);
        } catch (PDOException $e) {
            error_log("Event::createStage() Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Elimina un kilometraje / etapa por su ID
     */
    public function deleteStage(int $stageId): bool {
        try {
            $stmt = $this->db->prepare("DELETE FROM race_stages WHERE id = :id");
            return $stmt->execute([':id' => $stageId]);
        } catch (PDOException $e) {
            error_log("Event::deleteStage() Error: " . $e->getMessage());
            return false;
        }
    }
}
