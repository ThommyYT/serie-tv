<?php
header('Content-Type: application/json');
include_once __DIR__ . '/vendor/autoload.php';

session_start();

/** @var \classes\Database $db */
$db = $_SESSION['DB'];

$result = $db->getFavorites($_SESSION['user']);
echo json_encode($result);