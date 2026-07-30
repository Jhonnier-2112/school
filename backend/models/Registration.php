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
    }

    /**
     * Obtiene el catálogo de etapas de carrera activas
     */
    public static function getRaceStages(): array {
        try {
            $database = new Database();
            $db = $database->getConnection();
            $stmt = $db->query("SELECT id, name, slug, category_type, distance, price, description, is_active FROM race_stages WHERE is_active = 1 ORDER BY category_type ASC, id ASC");
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
                user_id, categoria_participante, etapas_seleccionadas, nombre_mascota, raza_mascota,
                acudiente_nombre, acudiente_documento, nombres, apellidos, tipo_documento, numero_documento, 
                fecha_nacimiento, edad, genero, eps, grupo_sanguineo, rh, 
                direccion, municipio, departamento, email, telefono, 
                parentesco_emergencia, otro_parentesco, nombre_emergencia, 
                nombre_emergencia_alt, celular_emergencia, acepta_autorizacion, created_at
            ) VALUES (
                :user_id, :categoria_participante, :etapas_seleccionadas, :nombre_mascota, :raza_mascota,
                :acudiente_nombre, :acudiente_documento, :nombres, :apellidos, :tipo_documento, :numero_documento, 
                :fecha_nacimiento, :edad, :genero, :eps, :grupo_sanguineo, :rh, 
                :direccion, :municipio, :departamento, :email, :telefono, 
                :parentesco_emergencia, :otro_parentesco, :nombre_emergencia, 
                :nombre_emergencia_alt, :celular_emergencia, :acepta_autorizacion, NOW()
            )";

            $etapas = is_array($data['etapas_seleccionadas'] ?? null) ? json_encode($data['etapas_seleccionadas']) : ($data['etapas_seleccionadas'] ?? null);

            $insertData = [
                ':user_id' => $data['user_id'] ?? null,
                ':categoria_participante' => $data['categoria_participante'] ?? 'adulto',
                ':etapas_seleccionadas' => $etapas,
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
                ':acepta_autorizacion' => $data['acepta_autorizacion'] ?? 'si'
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
            $query = "SELECT r.*, e.name AS event_name FROM registrations r LEFT JOIN events e ON r.event_id = e.id ORDER BY r.created_at DESC, r.id DESC";
            $stmt = $this->conn->query($query);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
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
        }
        if (empty($data['etapas_seleccionadas'])) {
            $errors[] = 'Debe seleccionar al menos una etapa para inscribirse';
        }

        $categoria = $data['categoria_participante'] ?? 'adulto';
        if ($categoria === 'nino' && empty($data['acudiente_nombre'])) {
            $errors[] = 'El nombre del acudiente es obligatorio para la inscripción infantil';
        }
        if ($categoria === 'mascota' && empty($data['nombre_mascota'])) {
            $errors[] = 'El nombre de la mascota es obligatorio para la categoría Pet Run';
        }

        if (($data['acepta_autorizacion'] ?? '') !== 'si') {
            $errors[] = 'Debe aceptar la autorización para participar';
        }

        return $errors;
    }
}
