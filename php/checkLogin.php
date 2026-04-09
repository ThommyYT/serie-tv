<?php
header('Content-Type: application/json');
require_once __DIR__ . '/vendor/autoload.php';
session_start();

if (!isset($_POST['id'])) {
    echo json_encode(['status' => 'error', 'message' => 'ID mancante']);
    exit;
} else {
    $id = $_POST['id'];
    // echo "POST['id']: " . $id . "\n";
}

if (isset($_SESSION['user'])) {
    // echo "SESSION['user']: " . $_SESSION['user'] . "\n";
    if ($_SESSION['user'] == $id) {
        echo json_encode(['status' => 'success']);
    } else if ($_SESSION['user'] === 0) {
        $_SESSION['user'] = $id;
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Utente non loggato: post_id = ' . $id]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Errore sessione non trovata']);
}

// echo "DEBUG: Exiting\n";
exit;