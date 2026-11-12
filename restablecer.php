<?php
require_once 'includes/conexionBD.php';

$mensaje = '';
$tipo_alerta = '';
$token_valido = false;
$token = trim($_GET['token'] ?? $_POST['token'] ?? '');

if (empty($token)) {
    $mensaje = "Token de recuperación no proporcionado o no válido.";
    $tipo_alerta = "error";
} else {
    $sql = "SELECT id_empleado, correo FROM empleados 
            WHERE token_recuperacion = ? AND token_expiracion >= NOW()";
    $stmt = mysqli_prepare($conexion, $sql);

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "s", $token);
        mysqli_stmt_execute($stmt);
        $resultado = mysqli_stmt_get_result($stmt);

        if ($empleado = mysqli_fetch_assoc($resultado)) {
            $token_valido = true;
        } else {
            $mensaje = "El enlace de recuperación es inválido o ya ha expirado.";
            $tipo_alerta = "error";
        }
        mysqli_stmt_close($stmt);
    } else {
        $mensaje = "Error al verificar el token de recuperación.";
        $tipo_alerta = "error";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $token_valido) {
    $nueva_contrasena = $_POST['contrasena'] ?? '';
    $confirmar_contrasena = $_POST['confirmar_contrasena'] ?? '';

    if (strlen($nueva_contrasena) < 8) {
        $mensaje = "La contraseña debe tener al menos 8 caracteres.";
        $tipo_alerta = "error";
    } elseif ($nueva_contrasena !== $confirmar_contrasena) {
        $mensaje = "Las contraseñas no coinciden.";
        $tipo_alerta = "error";
    } else {
        $hash = password_hash($nueva_contrasena, PASSWORD_DEFAULT);

        $sql_update = "UPDATE empleados 
                       SET contraseña = ?, token_recuperacion = NULL, token_expiracion = NULL 
                       WHERE token_recuperacion = ?";
        $stmt_update = mysqli_prepare($conexion, $sql_update);

        if ($stmt_update) {
            mysqli_stmt_bind_param($stmt_update, "ss", $hash, $token);
            if (mysqli_stmt_execute($stmt_update)) {
                mysqli_stmt_close($stmt_update);
                header("Location: iniciar-sesion.php?exito=1");
                exit();
            } else {
                $mensaje = "Error al actualizar la contraseña.";
                $tipo_alerta = "error";
            }
            mysqli_stmt_close($stmt_update);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lo de Torres - Restablecer Contraseña</title>
    <link rel="stylesheet" href="includes/style.css">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
</head>
<body style="display: flex; align-items: center; justify-content: center; min-height: 100vh;">

    <div class="tarjeta-login" style="width: 100%; max-width: 420px; padding: 30px; box-sizing: border-box;">
        <h2 style="text-align: center; margin-bottom: 5px;">Lo de Torres</h2>
        <p style="color: var(--color-text-muted); font-size: 0.9rem; margin-bottom: 20px; text-align: center;">Crear nueva contraseña</p>

        <?php if (!empty($mensaje)): ?>
            <div style="padding: 10px 14px; border-radius: 8px; font-size: 0.85rem; margin-bottom: 16px; text-align: center; border: 1px solid <?= $tipo_alerta === 'exito' ? 'var(--accent-green)' : 'var(--accent-red)' ?>; background: <?= $tipo_alerta === 'exito' ? 'rgba(74, 222, 128, 0.1)' : 'rgba(255, 74, 90, 0.1)' ?>; color: <?= $tipo_alerta === 'exito' ? 'var(--accent-green)' : 'var(--accent-red)' ?>;">
                <?= htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <?php if ($token_valido): ?>
            <form action="restablecer.php?token=<?= urlencode($token) ?>" method="POST">
                <input type="hidden" name="token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">

                <div class="grupo-input" style="margin-bottom: 16px;">
                    <label for="contrasena" style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: var(--color-text-main);">Nueva Contraseña (mínimo 8 caracteres)</label>
                    <input type="password" name="contrasena" id="contrasena" required minlength="8" placeholder="••••••••" style="width: 100%; padding: 10px 15px; background: var(--bg-input); border: 1px solid var(--border-subtle); border-radius: 6px; color: #fff; font-size: 14px; outline: none;">
                </div>

                <div class="grupo-input" style="margin-bottom: 20px;">
                    <label for="confirmar_contrasena" style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: var(--color-text-main);">Confirmar Nueva Contraseña</label>
                    <input type="password" name="confirmar_contrasena" id="confirmar_contrasena" required minlength="8" placeholder="••••••••" style="width: 100%; padding: 10px 15px; background: var(--bg-input); border: 1px solid var(--border-subtle); border-radius: 6px; color: #fff; font-size: 14px; outline: none;">
                </div>
                
                <button type="submit" class="btn-publish" style="width: 100%; padding: 12px; cursor: pointer; font-size: 14px; font-weight: bold; border-radius: 6px; border: none; background-color: var(--accent-green); color: #000; transition: background 0.2s;">Guardar Contraseña</button>
            </form>
        <?php else: ?>
            <div style="text-align: center; margin-top: 15px;">
                <a href="recuperar.php" style="font-size: 0.85rem; color: var(--accent-cyan); text-decoration: none;">Solicitar un nuevo enlace de recuperación</a>
            </div>
        <?php endif; ?>

        <div style="text-align: center; margin-top: 20px;">
            <a href="iniciar-sesion.php" style="font-size: 0.85rem; color: var(--color-text-muted); text-decoration: none;">Volver al Inicio de Sesión</a>
        </div>
    </div>

</body>
</html>