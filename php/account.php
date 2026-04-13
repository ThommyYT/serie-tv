<?php
header('Content-Type: application/json');
require_once __DIR__ . '/vendor/autoload.php';
session_start();

if (isset($_SESSION['user'])) {
    /** @var \classes\Database */
    $db = $_SESSION['DB'];

    $user = $db->getUser($_SESSION['user']);
    echo json_encode($user);
} else {
    echo json_encode(['status' => 'error']);
}