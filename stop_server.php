<?php 
require_once __DIR__ . '/php/vendor/autoload.php';
session_start();

if (isset($_SESSION['id'])) {
    echo $_SESSION['id'];
    curl_setopt($_SESSION['app_data']->getCH(), CURLOPT_POSTFIELDS, json_encode(["cmd" => "sessions.destroy", "session" => $_SESSION['id']]));
    curl_exec($_SESSION['app_data']->getCH());
    session_destroy();
} else {
    echo "Sessione non trovata.";
}
exit;