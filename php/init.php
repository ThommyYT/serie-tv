<?php

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/functions.php';

$required_dirs = [__DIR__ . '/tmp', __DIR__ . '/logs'];
foreach ($required_dirs as $dir) {
    if (!is_dir($dir)) {
        if (!mkdir($dir, 0777, true)) {
            die("Errore: Impossibile creare la cartella $dir. Verifica i permessi.");
        }
    }
}

session_start();

use \classes\Data;
use \classes\Database;

if (!isset($_SESSION['BREAK_LINE'])) $_SESSION['BREAK_LINE'] = "<br>";

if (!isset($_SESSION['DB'])) $_SESSION['DB'] = new Database();

// Inizializzazione FlareSolverr Session ID se non esiste

// $x = url_exists("https://eurostreamings.xyz", -1);
// if ($x["exist"]){
//     echo "Apposto";
// } else {
//     die("Code: {$x["code"]};{$_SESSION["BREAK_LINE"]}Exists: {$x["existStr"]};");
// }

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
$response = curl_exec($ch);
$res = json_decode($response, true);
if (isset($res) && is_array($res) && isset($res['sessions']) && is_array($res['sessions'])) {
    if (count($res['sessions']) > 0) {
        if ($_SESSION['id'] != $res['sessions'][0]) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(["cmd" => "sessions.destroy", "session" => $res['sessions'][0]]));
            curl_exec($ch);

            // curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(["cmd" => "sessions.create", "session" => $_SESSION['id']]));
        }
    } else {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(["cmd" => "sessions.create", "session" => $_SESSION['id']]));
        curl_exec($ch);
    }
}

if (!isset($_SESSION['user'])) $_SESSION['user'] = 0;

if (!isset($_SESSION['listSearch'])) {
    session_write_close();

    // 1. In XAMPP su Linux l'eseguibile è quasi sempre in questo percorso
    $phpPath = '/opt/lampp/bin/php';

    // Fallback se non lo trova (percorso standard Linux)
    if (!file_exists($phpPath)) {
        $phpPath = 'php';
    }

    $scriptPath = __DIR__ . "/getSearchItems.php";
    $sessionId = $_SESSION['id'];

    /**
     * Spiegazione comando Linux:
     * > /dev/null 2>&1  => Redirige output ed errori nel nulla (evita blocchi)
     * &                 => Mette il processo in background
     */
    $comando = "$phpPath $scriptPath " . escapeshellarg($sessionId) . " create > /dev/null 2>&1 &";

    exec($comando);
}

// print_r($_SESSION);
// print_r($_SERVER);
exit;
