<?php
/**
 * Conexión a la base de datos con PDO.
 * Este archivo se incluye en todas las páginas que usan la base de datos.
 */

// Datos de conexión (valores por defecto de XAMPP)
define('DB_HOST', 'localhost');
define('DB_NAME', 'tecnostock');
define('DB_USER', 'root');
define('DB_PASS', '');

// Ruta base del proyecto (para armar los enlaces)
define('BASE_URL', '/tecnostock/');

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // los errores lanzan excepciones
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // resultados como arreglos asociativos
            PDO::ATTR_EMULATE_PREPARES   => false,                  // consultas preparadas reales
        ]
    );
} catch (PDOException $e) {
    // No se muestra el error técnico al usuario, solo un mensaje claro
    http_response_code(500);
    exit('No se pudo conectar a la base de datos. Verifique que MySQL esté iniciado en XAMPP.');
}