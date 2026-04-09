<?php
header('Content-Type: application/json');
require_once __DIR__ . '/vendor/autoload.php';
require_once  __DIR__ . '/functions.php';
session_start();

$url = $_POST['url'] ?? "";
$postId = $_POST['postId'] ?? "";
if (empty($url) && empty($postId)) die("URL mancante.");

if (!empty($postId)) {
    $url = 'https://' . $_SESSION['second_lvl_domain'] . '.' . $_SESSION['top_lvl_domain'] . '?page_id=' . $postId;
}

/** @var \classes\Data $app_data */
$app_data = $_SESSION['app_data'];

// Eseguiamo la chiamata tramite cURL (metodo integrato nella classe Data)
$ch = $app_data->getCH();
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    "cmd" => "request.get",
    "url" => $url,
    "session" => $_SESSION['id'],
    "wait" => 3000
]));

$response = json_decode(curl_exec($ch), true);

$data = ['status' => '', 'titolo' =>  '', 'immagine' => '', 'stagioni' => [], 'id' => ''];

if (isset($response['solution'])) {
    $html = $response['solution']['response'];
    $options = $app_data->getOptionsQP();
    try {
        $dom = htmlqp($html, 'body', $options);
        $data['titolo'] = htmlspecialchars(trim($dom->find('h1.entry-title')->first()->text()));
        $data['immagine'] = $dom->find('.entry-content img')->first()->attr('src');
        $data['id'] = slugify($data['titolo']);

        $titles = $dom->find('.su-spoiler-title');
        foreach ($titles as $titleNode) {
            $t = htmlqp($titleNode, null, $options);
            $nomeStagione = htmlspecialchars(trim($t->text()));
            $contentNode = $t->next('.su-spoiler-content');
            $listaEpisodi = [];

            // Pulizia HTML per dividere le righe correttamente
            $rawHtml = $contentNode->html();
            $rawHtml = str_replace(['<br />', '<br>', '</div>', '</p>', '<div>', '<p>'], "\n", $rawHtml);
            $righe = explode("\n", $rawHtml);

            foreach ($righe as $riga) {
                $rigaPura = trim(strip_tags($riga, '<a>')); // Mantieni solo i tag <a> per l'analisi
                if (empty($rigaPura) || strlen(strip_tags($rigaPura)) < 2) continue;

                $rowDom = htmlqp('<div>' . $riga . '</div>', null, $options);
                $links = [];

                $rowDom->find('a')->each(function ($_, $anchor) use (&$links, &$options) {
                    $a = htmlqp($anchor, null, $options);
                    $href = $a->attr('href');
                    $host = trim($a->text());

                    // if ($host === )

                    // Escludiamo link che puntano a pagine interne (come il caso Dexter reboot)
                    // I link degli episodi solitamente vanno su host esterni o hanno pattern specifici
                    /*  && $host !== "DL" */
                    if (!empty($href) && !str_starts_with($href, '/') && $host !== "DL" && $host !== "MixDrop") {
                        $links[] = ['host' => $host, 'url' => $href];
                    }
                });

                if (!empty($links)) {
                    // Pulizia titolo episodio: rimuovi il testo dei link dal testo della riga
                    $titoloEp = trim(strip_tags($riga));
                    foreach ($links as $l) {
                        $titoloEp = str_ireplace($l['host'], '', $titoloEp);
                    }

                    $titoloEp = str_ireplace('DL', '', $titoloEp);
                    $titoloEp = str_ireplace('MixDrop', '', $titoloEp);

                    // $x = array_filter($links, function ($link) {
                    //     if ($link['host'] === "DL") return false;
                    //     return true;
                    // });

                    // file_put_contents(__DIR__ . '/logs/links.log', print_r($x, true), FILE_APPEND);

                    // $links = array_values($x);

                    // Rimuovi caratteri di separazione comuni lasciando lettere e numeri
                    $titoloEp = trim(preg_replace('/^[\s\-\–\—\.\:\?]+|[\s\-\–\—\.\:\?]+$/u', '', $titoloEp));

                    // Se dopo la pulizia il titolo è vuoto (es. c'erano solo i link), metti "Episodio"
                    $finalTitle = $titoloEp ?: "Guarda Episodio";

                    $listaEpisodi[] = [
                        'id' => slugify($finalTitle . "-" . count($listaEpisodi)), // Aggiunto counter per ID univoci
                        'titolo' => htmlspecialchars($finalTitle),
                        'links'  => $links
                    ];
                }
            }

            if (!empty($listaEpisodi)) {
                $data['stagioni'][] = ['nome' => $nomeStagione, 'episodi' => $listaEpisodi];
            }
        }

        if (!empty($data['stagioni'])) {
            $data['status'] = 'success';
        } else {
            $data['status'] = 'error';
            $data['message'] = 'Nessun episodio trovato' . $_SESSION['BREAK_LINE'] . ' <a href="' . $url . '" target="_blank">Vai con questo link</a>';
        }
    } catch (Throwable $th) {
        error_log(date('Y-m-d H:i:s') . " Errore Parsing: " . PHP_EOL . $th->getMessage() . PHP_EOL, 3, __DIR__ . '/logs/errorSiteSerie.log');
    }
}

unset($ch);
echo json_encode($data);
exit;
