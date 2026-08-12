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

    // Guarda la inscripción en la BD y redirecciona al pago
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

            // Calcular el total a pagar por las etapas seleccionadas
            $total = 0;
            $allStages = Event::getStages(1);
            $stagesMap = [];
            foreach ($allStages as $stg) {
                $stagesMap[(int)$stg['id']] = $stg;
            }
            foreach ($etapas as $sid) {
                if (isset($stagesMap[(int)$sid])) {
                    $total += $stagesMap[(int)$sid]['active_price'];
                }
            }

            // Generar número de orden único
            $orderNumber = \App\Models\Order::generateOrderNumber();
            $data['payment_status'] = 'pending';
            $data['payment_amount'] = $total;
            $data['order_number'] = $orderNumber;

            // Crear la inscripción en BD
            $registration = Registration::create($data);
            if ($registration) {
                // Registrar log de auditoría
                \App\Services\AuditLogService::log('REGISTRATION_CREATE', 'Inscripción pre-registrada (Pendiente de pago) para ' . $data['nombres'] . ' ' . $data['apellidos'] . ' - Orden: ' . $orderNumber, ['order_number' => $orderNumber, 'total' => $total], $user_id);

                // Crear la orden de compra asociada
                $orderModel = new \App\Models\Order();
                
                $orderData = [
                    'order_number' => $orderNumber,
                    'user_id' => $user_id,
                    'customer_name' => $data['nombres'] . ' ' . $data['apellidos'],
                    'customer_email' => $data['email'],
                    'customer_phone' => $data['telefono'],
                    'customer_document' => $data['numero_documento'],
                    'shipping_address' => $data['direccion'],
                    'city' => $data['municipio'],
                    'department' => $data['departamento'],
                    'subtotal' => $total,
                    'total' => $total,
                    'payment_method' => 'bancolombia_wompi'
                ];
                
                $orderItems = [];
                foreach ($etapas as $sid) {
                    if (isset($stagesMap[(int)$sid])) {
                        $orderItems[] = [
                            'product_id' => null,
                            'name' => 'Inscripción Carrera - ' . $stagesMap[(int)$sid]['name'],
                            'price' => $stagesMap[(int)$sid]['active_price'],
                            'quantity' => 1
                        ];
                    }
                }
                
                $createdOrder = $orderModel->createOrder($orderData, $orderItems);
                
                if ($createdOrder) {
                    // Guardar registro inicial de pago en estado PENDING
                    $orderModel->addPayment([
                        'order_id' => $createdOrder['id'],
                        'payment_gateway' => 'bancolombia_wompi',
                        'transaction_reference' => $orderNumber,
                        'amount' => $total,
                        'currency' => 'COP',
                        'status' => 'PENDING',
                        'raw_response' => json_encode(['registration_id' => $registration])
                    ]);
                }

                // Redireccionar al usuario a la página de pago seguro
                if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                    $_SESSION['registration_success'] = true;
                    $_SESSION['participant_data'] = $data;
                    
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => true, 
                        'message' => 'Inscripción registrada exitosamente. Redirigiendo al pago...',
                        'redirect' => '/payment/pay?order=' . $orderNumber
                    ]);
                    exit;
                } else {
                    $_SESSION['registration_success'] = true;
                    $_SESSION['participant_data'] = $data;
                    
                    header('Location: /payment/pay?order=' . $orderNumber);
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

            $registrations = Registration::findAllByDocument($numeroDocumento);

            if (!empty($registrations)) {
                $allStages = Registration::getRaceStages();
                $stagesMap = [];
                foreach ($allStages as $stg) {
                    $stagesMap[(int)$stg['id']] = $stg['name'] . ' (' . $stg['distance'] . ')';
                }

                $formattedList = [];
                foreach ($registrations as $reg) {
                    $stgIds = [];
                    $sel = $reg['etapas_seleccionadas'];
                    if (!empty($sel)) {
                        if (is_string($sel)) {
                            $decoded = json_decode($sel, true);
                            $stgIds = is_array($decoded) ? $decoded : [(int)$sel];
                        } elseif (is_array($sel)) {
                            $stgIds = $sel;
                        }
                    }

                    $stageNames = [];
                    foreach ($stgIds as $sid) {
                        if (isset($stagesMap[(int)$sid])) {
                            $stageNames[] = $stagesMap[(int)$sid];
                        }
                    }

                    $formattedList[] = [
                        'nombres' => $reg['nombres'],
                        'apellidos' => $reg['apellidos'],
                        'tipo_documento' => $reg['tipo_documento'],
                        'numero_documento' => $reg['numero_documento'],
                        'email' => $reg['email'],
                        'telefono' => $reg['telefono'],
                        'created_at' => $reg['created_at'],
                        'payment_status' => $reg['payment_status'] ?? 'pending',
                        'order_number' => $reg['order_number'] ?? '',
                        'payment_amount' => $reg['payment_amount'] ?? 0,
                        'etapas' => implode(', ', $stageNames)
                    ];
                }

                $response = [
                    'success' => true,
                    'registrations' => $formattedList
                ];
            } else {
                $response = [
                    'success' => false,
                    'message' => 'No se encontró ninguna inscripción con ese número de documento.'
                ];
            }

            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($response);
            
        } catch (Exception $e) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
        }
    }

    public function checkDocumentStages() {
        try {
            $numeroDocumento = $_POST['numero_documento'] ?? '';
            if (empty($numeroDocumento)) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Número de documento requerido']);
                exit;
            }

            $existingRegistrations = Registration::findAllByDocument($numeroDocumento);
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

            // Get stages info to know name and distance for each registered stage
            $allStages = Registration::getRaceStages();
            $stagesInfo = [];
            foreach ($allStages as $stg) {
                if (in_array((int)$stg['id'], $existingStageIds)) {
                    $stagesInfo[] = [
                        'id' => (int)$stg['id'],
                        'name' => $stg['name'],
                        'distance' => $stg['distance']
                    ];
                }
            }

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'registered_stages' => $stagesInfo
            ]);
            exit;
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
            exit;
        }
    }
}
