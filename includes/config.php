<?php
// ============================================================
// DATABASE CONFIGURATION
// ============================================================
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'admission_predictor');

function getDB() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        die(json_encode(['error' => 'Database connection failed: ' . $conn->connect_error]));
    }
    $conn->set_charset('utf8mb4');
    return $conn;
}

session_start();

function isLoggedIn() {
    return isset($_SESSION['student_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ../index.php');
        exit;
    }
}

$EXAMS = [
    'JEE Main' => 300,
    'JEE Advanced' => 360,
    'IPU CET' => 400,
    'CUET' => 800,
    'BITSAT' => 390,
    'VITEEE' => 125,
    'General/Other' => 100
];

$COURSES = [
    'B.Tech CSE', 'B.Tech CSAI', 'B.Tech IT', 'B.Tech ECE', 'B.Tech EEE', 
    'B.Tech Mechanical', 'B.Tech Civil', 'B.Tech Chemical',
    'BCA', 'MCA', 'BBA', 'MBA', 'BA Economics'
];
?>
