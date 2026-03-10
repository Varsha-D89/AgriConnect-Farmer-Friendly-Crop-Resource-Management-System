<?php
/**
 * AgriConnect – Database Connection
 * ====================================
 * Establishes a MySQLi connection using config.php settings.
 * Returns $conn (MySQLi connection object) to be used by other scripts.
 */

require_once 'config.php';

/**
 * Create and return a MySQLi database connection.
 * Exits with a JSON error message if connection fails.
 */
function getDBConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    // Check connection
    if ($conn->connect_error) {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Database connection failed: ' . $conn->connect_error
        ]);
        exit();
    }

    // Set charset to UTF-8 (supports Indian language characters)
    $conn->set_charset('utf8mb4');

    return $conn;
}

// Establish connection (available as $conn in all included files)
$conn = getDBConnection();

/**
 * Helper: Sanitize user input to prevent SQL injection.
 * Always use this before inserting any user data into queries.
 *
 * @param mysqli $conn  Active database connection
 * @param string $input Raw user input
 * @return string       Sanitized, safe string
 */
function sanitize($conn, $input) {
    return $conn->real_escape_string(htmlspecialchars(strip_tags(trim($input))));
}

/**
 * Helper: Send a JSON response and exit.
 *
 * @param bool   $success  Whether operation succeeded
 * @param string $message  Message to display to user
 * @param array  $data     Optional extra data to include in response
 */
function sendJSON($success, $message, $data = []) {
    header('Content-Type: application/json');
    echo json_encode(array_merge([
        'success' => $success,
        'message' => $message
    ], $data));
    exit();
}
