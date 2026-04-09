<?php
// 1. Gestione parametri CLI vs Web
if (php_sapi_name() === 'cli') {
    if (isset($argv[1])) session_id($argv[1]);
    $isCreate = (isset($argv[2]) && $argv[2] === 'create');
} else {
    $isCreate = isset($_GET['create']);
}

require_once __DIR__ . '/vendor/autoload.php';

// 2. Avvio sessione unico
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($isCreate) {
    // --- LOGICA DI CREAZIONE (Asincrona) ---
    $url = "https://" . $_SESSION['second_lvl_domain'] . "." . $_SESSION['top_lvl_domain'] . "/elenco-serie-tv/";
    $ch = $_SESSION['app_data']->getCH();

    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(["cmd" => "request.get", "url" => $url, "session" => $_SESSION['id'], "wait" => 3000]));
    $response = json_decode(curl_exec($ch), true);
    
    $listSearch = [];
    if (isset($response['solution'])) {
        $html = $response['solution']['response'];
        try {
            $dom = htmlqp($html, 'body', $_SESSION['app_data']->getOptionsQP());
            foreach ($dom->find('.entry > ul > li > a') as $a) {
                $listSearch[] = $a->text();
            }
        } catch (Throwable $th) {
            error_log(date('Y-m-d H:i:s') . " Errore Parsing: " . $th->getMessage() . PHP_EOL, 3, __DIR__ . '/logs/error.log');
        }
    }

    $_SESSION['listSearch'] = $listSearch;
    session_write_close(); // Salva e chiude
    exit;

} else {
    // --- LOGICA DI LETTURA (Polling AJAX) ---
    header('Content-Type: application/json');

    // Se i dati ci sono già, rispondi subito
    if (isset($_SESSION['listSearch'])) {
        echo json_encode($_SESSION['listSearch']);
        exit;
    }

    // Altrimenti aspetta che il processo CLI scriva i dati
    while (true) {
        session_write_close(); // Rilascia il lock per permettere la scrittura al processo CLI
        sleep(1);              // Attendi
        session_start();       // Riapri per leggere i nuovi dati
        
        if (isset($_SESSION['listSearch'])) {
            echo json_encode($_SESSION['listSearch']);
            break;
        }
    }
    exit;
}
