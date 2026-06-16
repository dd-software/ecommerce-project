<?php
// ============================================================
// Conexión a la Base de Datos con PDO
// ============================================================
// [PEDAGÓGICO] PDO (PHP Data Objects) es una capa de abstracción
// que permite trabajar con diferentes motores de base de datos
// (MySQL, PostgreSQL, SQLite, etc.) usando la misma API.
// Además, prepara consultas con parámetros, lo que previene
// Inyección SQL automáticamente.

/**
 * Obtiene una instancia única (Singleton) de conexión PDO.
 *
 * [PEDAGÓGICO] Usamos Singleton para reutilizar la misma conexión
 * a lo largo de toda la petición. Esto evita abrir múltiples
 * conexiones innecesarias y mejora el rendimiento.
 *
 * @return PDO Instancia de conexión a la base de datos
 */
function getDB()
{
    // Variable estática: conserva su valor entre llamadas
    // dentro de la misma petición HTTP
    static $pdo = null;

    // Si ya existe la conexión, la reutilizamos
    if ($pdo !== null) {
        return $pdo;
    }

    // Cadena DSN (Data Source Name): describe dónde y cómo conectar
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';

    try {
        // Crear la conexión PDO
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            // [PEDAGÓGICO] utf8mb4 soporta emojis y caracteres
            // especiales (acentos, ñ, etc.). Es la codificación
            // recomendada para aplicaciones web modernas.
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",

            // [PEDAGÓGICO] Lanzar excepciones en vez de errores
            // silenciosos. Así podemos usar try/catch para manejar
            // errores de BD de forma controlada.
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,

            // [PEDAGÓGICO] Los resultados se devuelven como arrays
            // asociativos ($fila['columna']) en vez de numéricos
            // ($fila[0]), lo que hace el código más legible.
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,

            // [PEDAGÓGICO] Desactivar emulación de consultas preparadas.
            // Con esto usamos consultas preparadas REALES del motor
            // MySQL, que son más seguras que la emulación de PHP.
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);

        return $pdo;

    } catch (PDOException $e) {
        // [PEDAGÓGICO] En producción NO mostrar detalles del error
        // al usuario. Registrar en log y mostrar mensaje genérico.
        error_log('Error de conexión BD: ' . $e->getMessage());
        die('Error de conexión a la base de datos. Contacte al administrador.');
    }
}

// [PEDAGÓGICO] NO es necesario cerrar la conexión explícitamente.
// PDO la cierra automáticamente cuando el script termina o cuando
// el objeto PDO se destruye (pierde todas las referencias).
// Llamar a $pdo = null; es opcional y solo forzaría el cierre
// inmediato si se necesita liberar recursos antes de tiempo.
