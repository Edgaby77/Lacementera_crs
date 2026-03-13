<?php
// ============================================================
//  db.php — Configuración de conexión a MySQL (cPanel)
//  Cambia los 4 valores de abajo con los datos de tu cPanel
// ============================================================

define('DB_HOST', 'localhost');          // Casi siempre es "localhost" en cPanel
define('DB_NAME', 'residdb'); // En cPanel: tuusuario_nombrebd
define('DB_USER', 'root');  // Usuario que creaste en MySQL de cPanel
define('DB_PASS', '');  // Contraseña del usuario de BD
define('DB_CHARSET', 'utf8mb4');

// ─── Conexión PDO ────────────────────────────────────────────
function getDB(): PDO {
    static $pdo = null;

    if ($pdo !== null) return $pdo;

    $dsn = sprintf(
        'mysql:host=%s;dbname=%s;charset=%s',
        DB_HOST, DB_NAME, DB_CHARSET
    );

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        // En producción nunca muestres el mensaje real
        error_log('DB Error: ' . $e->getMessage());
        http_response_code(500);
        die(json_encode(['error' => 'No se pudo conectar a la base de datos. Revisa db.php']));
    }

    return $pdo;
}