<?php
require_once __DIR__ . '/vendor/autoload.php';
session_start();

use classes\Data;

// Recuperiamo l'istanza dalla sessione (assumiamo esista già come da tua richiesta)

/** @var Data $app_data */
$app_data = $_SESSION['app_data'];

$url = "https://" . $_SESSION['second_lvl_domain'] . "." . $_SESSION['top_lvl_domain'] . "/nuovi-ep-aggiornamento/";
$data = [];

// Usiamo il cURL della classe Data per coerenza con siteHome.php
$ch = $app_data->getCH();
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    "cmd" => "request.get",
    "url" => $url,
    "session" => $_SESSION['id'],
    "wait" => 3000
]));
$response = json_decode(curl_exec($ch), true);

if (isset($response['solution'])) {
    $html = $response['solution']['response'];
    try {
        // Usiamo le opzioni di QueryPath centralizzate nella classe Data
        $dom = htmlqp($html, 'body', $app_data->getOptionsQP());
        $entries = $dom->find("#content .post-entry div");

        if ($entries->count() > 0) {
            foreach ($entries->find("h4") as $h4) {
                $giorno = trim($h4->text());
                $serieDelGiorno = [];

                // Isola il blocco tra questo h4 e il prossimo
                $blocco = $h4->nextUntil('h4');

                foreach ($blocco->find('.serieTitle') as $serieTitle) {
                    $nodoTesto = $serieTitle->contents()->get(0);
                    $titoloRaw = ($nodoTesto) ? $nodoTesto->textContent : '';
                    $titoloPulito = preg_replace('/[\?\–\—\-\s]+$/u', '', trim($titoloRaw));

                    $link = $serieTitle->find('a');
                    $episodio = trim($link->text());
                    $href = (string)$link->attr('href');

                    $extraInfo = '';
                    $elemNext = $serieTitle->next('span');
                    if ($elemNext->count() > 0 && !$elemNext->hasClass('serieTitle')) {
                        $extraInfo = trim($elemNext->text());
                    }

                    if (!empty($titoloPulito)) {
                        $serieDelGiorno[] = [
                            'titolo' => $titoloPulito,
                            'episodio' => $episodio,
                            'url' => $href,
                            'extra' => $extraInfo
                        ];
                    }
                }

                if (!empty($serieDelGiorno)) {
                    $data[] = [
                        'giorno' => $giorno,
                        'serie' => $serieDelGiorno
                    ];
                }
            }
        }
    } catch (Throwable $e) {
        error_log(date('Y-m-d H:i:s') . " Errore Scraping Updates: " . PHP_EOL . $e->getMessage() . PHP_EOL, 3, __DIR__ . '/logs/errorUpdate.log');
    }
}

// RITORNO JSON per il frontend TypeScript
header('Content-Type: application/json');
echo json_encode([
    'status' => 'success',
    'dataUpdates' => $data
]);
exit;
