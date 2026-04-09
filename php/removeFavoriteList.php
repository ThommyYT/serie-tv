<?php
header('Content-Type: application/json');
include_once __DIR__ . '/vendor/autoload.php';

session_start();

/** @var \classes\Database $db */
$db = $_SESSION['DB'];

if (isset($_POST['data'])) {
    $data = json_decode(base64_decode($_POST['data']), true);
    $id = $data['id'];
    $result = $db->removeFavorite($_SESSION['user'], $id);
    echo json_encode($result);
}