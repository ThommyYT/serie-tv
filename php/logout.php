<?php
require_once __DIR__ . '/vendor/autoload.php';
session_start();

if (isset($_SESSION['id']) && isset($_SESSION['app_data'])) {
    $ch = $_SESSION['app_data']->getCH();
    
    // Prepariamo i dati
    $payload = json_encode(["cmd" => "sessions.destroy", "session" => $_SESSION['id']]);
    
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    
    // TRUCCO ASINCRONO: Impostiamo un timeout di 500ms (mezzo secondo)
    // In questo modo PHP "spara" la richiesta e chiude la connessione 
    // quasi subito, senza aspettare che l'API remota risponda.
    curl_setopt($ch, CURLOPT_TIMEOUT_MS, 500); 
    curl_setopt($ch, CURLOPT_NOSIGNAL, 1); // Necessario per timeout < 1s su Linux
    
    curl_exec($ch);
    // Non controlliamo curl_error() perché vogliamo che sia "silenzioso"
} 

// Procediamo subito alla distruzione locale (l'utente non percepirà attese)
$_SESSION = array(); // Svuota l'array di sessione
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_unset();
session_destroy();
exit;
