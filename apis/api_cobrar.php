<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include_once("../includes/conexionBD.php");
include_once("../includes/funciones.php");

if (ob_get_length()) {
    ob_clean();
}

header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$id_empleado_sesion = $_SESSION['empleado_id'] ?? $_SESSION['id_empleado'] ?? $_SESSION['id_usuario'] ?? null;
if (!$id_empleado_sesion) {
    echo json_encode(array("success" => false, "error" => "No hay sesión activa."), JSON_UNESCAPED_UNICODE);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $idPedido = filter_var($_POST["id_pedido"] ?? 0, FILTER_VALIDATE_INT);

    if (!$idPedido) {
        echo json_encode(array("success" => false, "error" => "ID de pedido inválido."), JSON_UNESCAPED_UNICODE);
        exit();
    }

    $sqlTurno = "SELECT id_turno FROM turno_caja WHERE fecha_hora_cierre IS NULL LIMIT 1";
    $resTurno = mysqli_query($conexion, $sqlTurno);
    if (!$resTurno) {
        echo json_encode(array("success" => false, "error" => "Error SQL turno: " . mysqli_error($conexion)), JSON_UNESCAPED_UNICODE);
        exit();
    }
    
    $turnoActivo = mysqli_fetch_assoc($resTurno);
    if (!$turnoActivo) {
        echo json_encode(array("success" => false, "error" => "No hay ninguna caja abierta."), JSON_UNESCAPED_UNICODE);
        exit();
    }

    $idTurno = (int)$turnoActivo['id_turno'];

    $idEstadoCobrado = 7; 
    $sqlEstado = "SELECT id_estado FROM estado_pedido WHERE LOWER(nombre) = 'cobrado' LIMIT 1";
    $resEst = mysqli_query($conexion, $sqlEstado);
    if ($resEst && $rowEst = mysqli_fetch_assoc($resEst)) {
        $idEstadoCobrado = (int)$rowEst['id_estado'];
    }

    $sqlUpdate = "UPDATE pedido SET id_estado = ?, id_turno = ? WHERE id_pedido = ?";
    $stmt = mysqli_prepare($conexion, $sqlUpdate);
    if (!$stmt) {
        echo json_encode(array("success" => false, "error" => "Error al preparar update: " . mysqli_error($conexion)), JSON_UNESCAPED_UNICODE);
        exit();
    }

    mysqli_stmt_bind_param($stmt, "iii", $idEstadoCobrado, $idTurno, $idPedido);
    $exito = mysqli_stmt_execute($stmt);
    
    if ($exito) {
        mysqli_stmt_close($stmt);
        echo json_encode(array("success" => true, "mensaje" => "Cobro realizado con éxito."), JSON_UNESCAPED_UNICODE);
        exit();
    } else {
        $error = mysqli_stmt_error($stmt);
        mysqli_stmt_close($stmt);
        echo json_encode(array("success" => false, "error" => "Error al ejecutar: " . $error), JSON_UNESCAPED_UNICODE);
        exit();
    }
}

echo json_encode(array("success" => false, "error" => "Método no permitido."), JSON_UNESCAPED_UNICODE);