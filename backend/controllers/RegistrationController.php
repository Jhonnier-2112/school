<?php
namespace App\Controllers;

use App\Models\Registration;
use App\Models\Event;
use App\Services\EmailService;
use Exception;

// Incluir la configuración de inscripciones
require_once __DIR__ . '/../config/RegistrationConfig.php';
use RegistrationConfig;
use App\Core\Controller;

class RegistrationController extends Controller {
    // Muestra el formulario de inscripción con etapas dinámicas
    public function create() {
        $event = Event::getPrimaryEvent();
        $availableSlots = $event['available_slots'] ?? 600;

        if (!RegistrationConfig::inscripcionesAbiertas() || $availableSlots <= 0) {
            $this->view('registration_closed', ['event' => $event]);
            return;
        }

        $userModel = new \App\Models\User();
        $currentUser = null;
        if (!empty($_SESSION['user_id'])) {
            $currentUser = $userModel->findById((int)$_SESSION['user_id']);
        }

        $stages = Registration::getRaceStages();
        $this->view('registration_form', ['currentUser' => $currentUser, 'stages' => $stages, 'event' => $event]);
    }

    // Guarda la inscripción en la BD y muestra la pantalla de éxito
    public function store() {
        $event = Event::getPrimaryEvent();
        $availableSlots = $event['available_slots'] ?? 600;

        if (!RegistrationConfig::inscripcionesAbiertas() || $availableSlots <= 0) {
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false, 
                    'message' => 'Las inscripciones están temporalmente cerradas o los cupos se han agotado.'
                ]);
                exit;
            } else {
                $this->view('registration_closed', ['event' => $event]);
                return;
            }
        }
        
        try {
            $user_id = $_SESSION['user_id'] ?? null;
            $categoria = $_POST['categoria_participante'] ?? 'adulto';
            $etapas = $_POST['etapas_seleccionadas'] ?? [];
            if (is_string($etapas)) {
                $etapas = json_decode($etapas, true) ?: [$etapas];
            }

            // Recopilar todos los datos del formulario
            $data = [
                'user_id' => $user_id,
                'categoria_participante' => $categoria,
                'etapas_seleccionadas' => $etapas,
                'nombre_mascota' => $_POST['nombre_mascota'] ?? '',
                'raza_mascota' => $_POST['raza_mascota'] ?? '',
                'acudiente_nombre' => $_POST['acudiente_nombre'] ?? '',
                'acudiente_documento' => $_POST['acudiente_documento'] ?? '',
                'nombres' => $_POST['nombres'] ?? '',
                'apellidos' => $_POST['apellidos'] ?? '',
                'tipo_documento' => $_POST['tipo_documento'] ?? '',
                'numero_documento' => $_POST['numero_documento'] ?? '',
                'fecha_nacimiento' => $_POST['fecha_nacimiento'] ?? '',
                'edad' => $_POST['edad'] ?? '',
                'genero' => $_POST['genero'] ?? '',
                'eps' => $_POST['eps'] ?? '',
                'grupo_sanguineo' => $_POST['grupo_sanguineo'] ?? '',
                'rh' => $_POST['rh'] ?? '',
                'talla_camiseta_adulto' => $_POST['talla_camiseta_adulto'] ?? '',
                'talla_camiseta_nino' => $_POST['talla_camiseta_nino'] ?? '',
                'direccion' => $_POST['direccion'] ?? '',
                'municipio' => $_POST['municipio'] ?? '',
                'departamento' => $_POST['departamento'] ?? '',
                'email' => $_POST['email'] ?? '',
                'telefono' => $_POST['telefono'] ?? '',
                'parentesco_emergencia' => $_POST['parentesco_emergencia'] ?? '',
                'otro_parentesco' => $_POST['otro_parentesco'] ?? '',
                'nombre_emergencia' => $_POST['nombre_emergencia'] ?? '',
                'nombre_emergencia_alt' => $_POST['nombre_emergencia_alt'] ?? '',
                'celular_emergencia' => $_POST['celular_emergencia'] ?? '',
                'acepta_autorizacion' => $_POST['acepta_autorizacion'] ?? ''
            ];
            
            // Validar datos
            $model = new Registration();
            $errors = $model->validateData($data);
            if (!empty($errors)) {
                return $this->showError($errors);
            }

            // Crear la inscripción
            $registration = Registration::create($data);
            if ($registration) {
                try {
                    $emailService = new EmailService();
                    $emailService->sendWelcomeEmail($data);
                } catch (Exception $e) {
                    error_log("Error al enviar email de bienvenida: " . $e->getMessage());
                }
                
                if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                    $_SESSION['registration_success'] = true;
                    $_SESSION['participant_data'] = $data;
                    
                    header('Content-Type: application/json');
                    echo json_encode(['success' => true, 'message' => 'Inscripción registrada exitosamente']);
                    exit;
                } else {
                    $_SESSION['registration_success'] = true;
                    $_SESSION['participant_data'] = $data;
                    
                    header('Location: /registration_success');
                    exit;
                }
            } else {
                return $this->showError(['Error al crear la inscripción. Por favor, inténtalo de nuevo.']);
            }
            
        } catch (Exception $e) {
            return $this->showError(['Error interno del servidor. Por favor, inténtalo de nuevo.']);
        }
    }

    private function showError($errors) {
        $isAjax = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                   strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
                  (isset($_POST['ajax']) && $_POST['ajax'] === '1');

        if ($isAjax) {
            header('Content-Type: application/json');
            $errorMessage = '';
            if (is_array($errors)) {
                $errorMessage = '<ul class="list-unstyled mb-0">';
                foreach ($errors as $error) {
                    $errorMessage .= '<li><i class="fas fa-exclamation-circle text-danger me-2"></i>' . htmlspecialchars($error) . '</li>';
                }
                $errorMessage .= '</ul>';
            } else {
                $errorMessage = '<i class="fas fa-exclamation-circle text-danger me-2"></i>' . htmlspecialchars($errors);
            }
            
            echo json_encode(['success' => false, 'message' => $errorMessage]);
            exit;
            $errors = is_array($errors) ? $errors : [$errors];
            $this->view('error', ['title' => $title, 'errors' => $errors]);
        }
    }

    public function success() {
        if (!isset($_SESSION['registration_success']) || !$_SESSION['registration_success']) {
            header('Location: /inscribirse');
            exit;
        }
        $participantData = $_SESSION['participant_data'] ?? [];
        unset($_SESSION['registration_success']);
        unset($_SESSION['participant_data']);
        $this->view('registration_success', ['participantData' => $participantData]);
    }

    public function createWithData($data = [], $errors = []) {
        $stages = Registration::getRaceStages();
        $this->view('registration_form', ['formData' => $formData, 'formErrors' => $formErrors, 'stages' => $stages]);
    }

    public function consultaForm() {
        $this->view('consulta_inscripcion');
    }

    public function consultarInscripcion() {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'Método no permitido']);
                return;
            }

            $numeroDocumento = $_POST['numero_documento'] ?? '';
            if (empty($numeroDocumento)) {
                echo json_encode(['success' => false, 'message' => 'Número de documento requerido']);
                return;
            }

            $participant = Registration::findByDocument($numeroDocumento);

            if ($participant) {
                $response = [
                    'success' => true,
                    'participant' => [
                        'nombres' => $participant['nombres'],
                        'apellidos' => $participant['apellidos'],
                        'tipo_documento' => $participant['tipo_documento'],
                        'numero_documento' => $participant['numero_documento'],
                        'email' => $participant['email'],
                        'telefono' => $participant['telefono'],
                        'created_at' => $participant['created_at']
                    ]
                ];
            } else {
                $response = [
                    'success' => false,
                    'message' => 'No se encontró ninguna inscripción con ese número de documento.'
                ];
            }

            header('Content-Type: application/json');
            echo json_encode($response);
            
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
        }
    }
}
