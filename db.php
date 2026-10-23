<?php
/**
 * Campus Canteen Management System - Database Connection
 * MySQLi with prepared statements for security
 */

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'canteen_db');

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to handle special characters
$conn->set_charset("utf8mb4");

// Helper function to get connection
function getDB() {
    global $conn;
    return $conn;
}

// Helper function to sanitize input
function sanitize($data) {
    global $conn;
    return htmlspecialchars(strip_tags(trim($conn->real_escape_string($data))));
}

// Helper function to execute prepared statement
function executeQuery($sql, $params = [], $types = "") {
    global $conn;
    $stmt = $conn->prepare($sql);
    
    if ($stmt === false) {
        error_log("Prepare failed: " . $conn->error);
        return false;
    }
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    return $stmt;
}

// Helper function to fetch all results
function fetchAll($sql, $params = [], $types = "") {
    $stmt = executeQuery($sql, $params, $types);
    if ($stmt === false) return [];
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $data;
}

// Helper function to fetch single row
function fetchOne($sql, $params = [], $types = "") {
    $stmt = executeQuery($sql, $params, $types);
    if ($stmt === false) return null;
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    $stmt->close();
    return $data;
}

// Helper function to get count
function getCount($sql, $params = [], $types = "") {
    $stmt = executeQuery($sql, $params, $types);
    if ($stmt === false) return 0;
    $result = $stmt->get_result();
    $row = $result->fetch_row();
    $stmt->close();
    return $row[0] ?? 0;
}

// Helper function for insert/update/delete
function execute($sql, $params = [], $types = "") {
    $stmt = executeQuery($sql, $params, $types);
    if ($stmt === false) return false;
    $affected = $stmt->affected_rows;
    $insert_id = $stmt->insert_id;
    $stmt->close();
    return $insert_id ?: $affected;
}
?>
