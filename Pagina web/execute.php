<?php
// execute.php - maneja single y multi-statement
header('Content-Type: text/plain; charset=utf-8');

$host = "127.0.0.1";
$user = "root";
$password = "123456789";
$database = "dungeon_game";

$conn = new mysqli($host, $user, $password, $database);
if ($conn->connect_error) {
    http_response_code(500);
    die("Error de conexión: " . $conn->connect_error);
}

$query = trim($_POST['query'] ?? '');
if ($query === '') {
    die("No se recibió ninguna query.");
}

// Seguridad básica: bloquear comandos peligrosos
$forbidden = ['drop', 'truncate', 'alter table', 'create user', 'grant', 'revoke'];
$q_upper = strtolower($query);
foreach ($forbidden as $bad) {
    if (strpos($q_upper, $bad) !== false) {
        die("⛔ Comando no permitido: $bad");
    }
}

// Helper para imprimir un result set en JSON lines
function fetch_result_rows($res) {
    $out = "";
    while ($row = $res->fetch_assoc()) {
        $out .= json_encode($row, JSON_UNESCAPED_UNICODE) . "\n";
    }
    return $out;
}

// Si hay múltiples sentencias separadas por ';' usamos multi_query
if (strpos($query, ';') !== false) {
    // Asegurarnos de que la última sentenciase termine sin espacios superfluos
    $query = trim($query);
    // Evitar terminar con ';' extra que genere sentencia vacía
    if (substr($query, -1) === ';') $query = substr($query, 0, -1);

    $output = "";
    if ($conn->multi_query($query)) {
        do {
            if ($result = $conn->store_result()) {
                // fue SELECT o similar que devuelve filas
                $output .= fetch_result_rows($result);
                $result->free();
            } else {
                // no hay result set -> puede ser UPDATE/DELETE/INSERT o error
                if ($conn->errno) {
                    $output .= "❌ Error: " . $conn->error . "\n";
                } else {
                    $output .= "✅ Sentencia ejecutada correctamente. Filas afectadas: " . $conn->affected_rows . "\n";
                }
            }
            // avanzar al siguiente resultado (si hay)
        } while ($conn->more_results() && $conn->next_result());
    } else {
        $output = "❌ multi_query falló: " . $conn->error;
    }

    echo $output;
    $conn->close();
    exit;
}

// Si solo es una sentencia (sin ';'), usamos query normal
// Detectar SELECT
$first_word = strtolower(strtok($query, " \n\t"));
if ($first_word === 'select') {
    $res = $conn->query($query);
    if ($res) {
        echo fetch_result_rows($res);
        $res->free();
    } else {
        echo "❌ Error en SELECT: " . $conn->error;
    }
} else {
    // UPDATE / DELETE / INSERT / CALL
    if ($conn->query($query) === TRUE) {
        echo "✅ Acción ejecutada correctamente\nFilas afectadas: " . $conn->affected_rows;
    } else {
        echo "❌ Error: " . $conn->error;
    }
}

$conn->close();

