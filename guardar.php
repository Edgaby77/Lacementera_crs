<?php
// guardar.php — Recibe el formulario y lo guarda en MySQL

require_once __DIR__ . '/db.php';

// ─── Carpeta para fotos de INE ────────────────────────────────
$uploadDir = __DIR__ . '/uploads/ine/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// ─── Helpers ─────────────────────────────────────────────────
function str($key): string {
    return trim($_POST[$key] ?? '');
}

function subirINE(string $fileKey, string $dir): string {
    if (empty($_FILES[$fileKey]['name']) || $_FILES[$fileKey]['error'] !== UPLOAD_ERR_OK) {
        return '';
    }
    $ext     = strtolower(pathinfo($_FILES[$fileKey]['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if (!in_array($ext, $allowed, true)) return '';

    // Límite 5 MB
    if ($_FILES[$fileKey]['size'] > 5 * 1024 * 1024) return '';

    $nombre  = uniqid('ine_', true) . '.' . $ext;
    $destino = $dir . $nombre;

    return move_uploaded_file($_FILES[$fileKey]['tmp_name'], $destino)
        ? 'uploads/ine/' . $nombre
        : '';
}

// ─── Validar tipo ─────────────────────────────────────────────
$tipo = str('tipo');
if (!in_array($tipo, ['visita', 'proveedor'], true)) {
    header('Location: index.html?error=tipo');
    exit;
}

// ─── Reunir campos según tipo ─────────────────────────────────
if ($tipo === 'visita') {
    $nombre      = str('nombre');
    $empresa     = null;
    $fecha       = str('fecha');
    $horaIngreso = str('hora_ingreso');
    $motivo      = str('motivo');
    $casaVisita  = str('casa_visita');
    $residente   = str('residente');
    $vehiculo    = str('vehiculo') === 'si' ? 'si' : 'no';
    $placas      = strtoupper(str('placas'));
    $modeloVeh   = str('modelo_vehiculo');
    $ineKey      = 'ine_foto';
} else {
    $nombre      = str('nombre_prov');
    $empresa     = str('empresa');
    $fecha       = str('fecha_prov');
    $horaIngreso = str('hora_ingreso_prov');
    $motivo      = str('motivo_prov');
    $casaVisita  = str('casa_visita_prov');
    $residente   = str('residente_prov');
    $vehiculo    = str('vehiculo_prov') === 'si' ? 'si' : 'no';
    $placas      = strtoupper(str('placas_prov'));
    $modeloVeh   = str('modelo_vehiculo_prov');
    $ineKey      = 'ine_foto_prov';
}

// Validación mínima
if (empty($nombre) || empty($fecha) || empty($horaIngreso)) {
    header('Location: index.html?error=campos');
    exit;
}

$ineFoto = subirINE($ineKey, $uploadDir);

// ─── Insertar en MySQL ────────────────────────────────────────
try {
    $pdo = getDB();

    $sql = "INSERT INTO registros
              (tipo, nombre, empresa, fecha, hora_ingreso,
               motivo, casa_visita, residente,
               vehiculo, placas, modelo_vehiculo, ine_foto)
            VALUES
              (:tipo, :nombre, :empresa, :fecha, :hora_ingreso,
               :motivo, :casa_visita, :residente,
               :vehiculo, :placas, :modelo_vehiculo, :ine_foto)";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':tipo'            => $tipo,
        ':nombre'          => $nombre,
        ':empresa'         => $empresa ?: null,
        ':fecha'           => $fecha,
        ':hora_ingreso'    => $horaIngreso,
        ':motivo'          => $motivo ?: null,
        ':casa_visita'     => $casaVisita ?: null,
        ':residente'       => $residente ?: null,
        ':vehiculo'        => $vehiculo,
        ':placas'          => $vehiculo === 'si' ? ($placas ?: null) : null,
        ':modelo_vehiculo' => $vehiculo === 'si' ? ($modeloVeh ?: null) : null,
        ':ine_foto'        => $ineFoto ?: null,
    ]);

    header('Location: index.html?ok=1');

} catch (PDOException $e) {
    error_log('Insert error: ' . $e->getMessage());
    header('Location: index.html?error=db');
}

exit;