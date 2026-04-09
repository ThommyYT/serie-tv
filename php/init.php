<?php

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/functions.php';
session_start();

use \classes\Data;
use \classes\Database;

if (!isset($_SESSION['BREAK_LINE'])) $_SESSION['BREAK_LINE'] = "<br>";

if (!isset($_SESSION['DB'])) $_SESSION['DB'] = new Database();

// Inizializzazione FlareSolverr Session ID se non esiste

initDomain();


if (!verifyDomain()) {
    header('HTTP/1.1 404 Not Found');
    header('Status: 404 Not Found');
    exit;
}

if (!isset($_SESSION['app_data'])) {
    $_SESSION['app_data'] = new Data();
}

if (!isset($_SESSION['id'])) $_SESSION['id'] = session_id();

$ch = $_SESSION['app_data']->getCH();

curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(["cmd" => "sessions.list"]));
$res = json_decode(curl_exec($ch), true);

if (count($res['sessions']) > 0) {
    if ($_SESSION['id'] != $res['sessions'][0]) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(["cmd" => "sessions.destroy", "session" => $res['sessions'][0]]));
        curl_exec($ch);

        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(["cmd" => "sessions.create", "session" => $_SESSION['id']]));
    }
} else {
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(["cmd" => "sessions.create", "session" => $_SESSION['id']]));
    curl_exec($ch);
}

if (!isset($_SESSION['user'])) $_SESSION['user'] = 0;

if (!isset($_SESSION['listSearch'])) {
    // richiama il file php che costruisce la lista di ricerca in maniera asincrona senza ritorno di risposta mantenendo la sessione creata in precedenza quindi con i dati aggiornati
    session_write_close();
    $phpPath = PHP_BINDIR . DIRECTORY_SEPARATOR . 'php.exe';

    // 2. Se non esiste (ritorna C:\php\php.exe ma non c'è nulla), cerchiamolo nei posti comuni
    if (!file_exists($phpPath)) {
        $paths = [
            'C:\xampp\php\php.exe',
            'C:\wamp64\bin\php\php' . PHP_VERSION . '\php.exe',
            'C:\laragon\bin\php\php' . PHP_VERSION . '\php.exe'
        ];
        foreach ($paths as $path) {
            if (file_exists($path)) {
                $phpPath = $path;
                break;
            }
        }
    }

    // Verifica finale per te (rimuovi dopo il test)
    if (!file_exists($phpPath)) {
        die("Errore: Impossibile trovare l'eseguibile php.exe. Percorso tentato: " . $phpPath);
    }

    $scriptPath = __DIR__ . "/getSearchItems.php";
    $comando = 'start /B "" "' . $phpPath . '" "' . $scriptPath . '" ' . $_SESSION['id'] . ' create';
    // echo $comando . "\n";
    pclose(popen($comando, "r"));
}

// print_r($_SESSION);
// print_r($_SERVER);
exit;
