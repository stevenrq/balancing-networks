<?php
// api.php - Controlador de Comunicación (API)
// Este archivo actúa como un "puente" entre lo que ve el usuario (Javascript) y el cerebro del sistema (PHP).
// Recibe los datos, llama al Balanceador, y devuelve la respuesta.

header('Content-Type: application/json');
require_once 'db.php';
require_once 'functions.php';

// Configuración básica de respuesta
$response = ['success' => false, 'message' => '', 'data' => null];

try {
    // Solo aceptamos peticiones POST (envío de datos)
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Método no permitido. Use POST.");
    }

    // 1. Recibir los datos del usuario
    // Los datos vienen en formato JSON desde el navegador
    $inputJSON = file_get_contents('php://input');
    $input = json_decode($inputJSON, true);

    if (!$input) {
        throw new Exception("JSON inválido o vacío.");
    }

    // 2. Validar que tenemos todo lo necesario
    if (!isset($input['tiempo_turno']) || !isset($input['demanda']) || !isset($input['tareas'])) {
        throw new Exception("Faltan datos requeridos (tiempo_turno, demanda, tareas).");
    }

    $tiempoTurno = (int) $input['tiempo_turno'];
    $demanda = (int) $input['demanda'];
    $tareas = $input['tareas'];
    $regla = isset($input['regla']) ? $input['regla'] : 'DEFAULT';

    if (empty($tareas)) {
        throw new Exception("La lista de tareas no puede estar vacía.");
    }

    // 3. Llamar al Cerebro (El Balanceador)
    // Aquí es donde ocurre la magia del cálculo
    $balanceador = new Balanceador();
    $resultado = $balanceador->calcularBalanceo($tiempoTurno, $demanda, $tareas, $regla);

    // 4. Guardar el resultado en la Base de Datos (Historial)
    // Esto es útil para que la empresa tenga un registro de lo que se ha calculado.
    $db = new Database();
    $conn = $db->getConnection();

    // Usamos una transacción para asegurar que se guarde todo o nada
    $conn->beginTransaction();

    // a) Guardamos el proyecto general
    $stmt = $conn->prepare("INSERT INTO proyectos (nombre, tiempo_turno, demanda, takt_time, eficiencia) VALUES (:nombre, :tiempo, :demanda, :takt, :eficiencia)");
    $stmt->execute([
        ':nombre' => 'Balanceo ' . date('Y-m-d H:i:s') . ' (' . $regla . ')',
        ':tiempo' => $tiempoTurno,
        ':demanda' => $demanda,
        ':takt' => $resultado['takt_time'],
        ':eficiencia' => $resultado['eficiencia']
    ]);
    $proyectoId = $conn->lastInsertId();

    // b) Guardamos las tareas asociadas a este proyecto
    $stmtTarea = $conn->prepare("INSERT INTO tareas (proyecto_id, letra_tarea, duracion, precedencias) VALUES (:pid, :letra, :dur, :prec)");

    foreach ($tareas as $t) {
        $stmtTarea->execute([
            ':pid' => $proyectoId,
            ':letra' => $t['letra_tarea'],
            ':dur' => $t['duracion'],
            ':prec' => $t['precedencias']
        ]);
    }

    $conn->commit(); // Confirmar cambios en BD

    // 5. Enviar la respuesta al usuario
    $response['success'] = true;
    $response['data'] = $resultado;
    $response['proyecto_id'] = $proyectoId;

} catch (Exception $e) {
    // Si algo sale mal...
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack(); // Deshacer cambios en BD si hubo error
    }
    
    // Avisar al usuario del error
    http_response_code(400); // Código de error "Bad Request"
    $response['message'] = $e->getMessage();
}

// Imprimir respuesta en formato JSON
echo json_encode($response);
