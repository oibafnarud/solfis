<?php
/**
 * Script para inicializar la base de datos del sistema de blog SolFis
 * Este script debe ser ejecutado una sola vez para configurar la base de datos.
 */

// Configuración de la base de datos
$host = 'localhost';
$user = 'root';
$password = '';

// Conectar a MySQL (sin seleccionar una base de datos)
$mysqli = new mysqli($host, $user, $password);

// Verificar conexión
if ($mysqli->connect_error) {
    die("Error de conexión: " . $mysqli->connect_error);
}

// Leer el archivo SQL
$sql_file = file_get_contents('crear_tablas.sql');

// Dividir las consultas
$queries = explode(';', $sql_file);

// Ejecutar cada consulta
$success = true;
foreach ($queries as $query) {
    $query = trim($query);
    if (!empty($query)) {
        if (!$mysqli->query($query)) {
            echo "Error al ejecutar consulta: " . $mysqli->error . "<br>";
            echo "Consulta: " . $query . "<br><br>";
            $success = false;
        }
    }
}

// Cerrar conexión
$mysqli->close();

if ($success) {
    echo "<h1>Inicialización de base de datos completada exitosamente</h1>";
    echo "<p>La base de datos ha sido creada y configurada correctamente con las tablas necesarias.</p>";
    echo "<p>Se ha creado un usuario administrador predeterminado:</p>";
    echo "<ul>";
    echo "<li>Email: admin@solfis.com</li>";
    echo "<li>Contraseña: admin123</li>";
    echo "</ul>";
    echo "<p>También se han creado algunas categorías y un post de ejemplo.</p>";
    echo "<p><a href='admin/login.php'>Ir al panel de administración</a></p>";
} else {
    echo "<h1>Error durante la inicialización</h1>";
    echo "<p>Ocurrieron errores durante la inicialización de la base de datos. Por favor revise los mensajes anteriores.</p>";
}