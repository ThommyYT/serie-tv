<?php

use classes\videoException;

header('Content-Type: application/json');

require_once __DIR__ . '/vendor/autoload.php';
// require_once __DIR__ . '/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (basename($_SERVER['PHP_SELF']) == 'siteVideo.php') {
    if (isset($_SESSION['app_data'])) {
        getVideo($_SESSION['app_data']);
    } else {
        error_log(date('Y-m-d H:i:s') . " Errore: Sessione scaduta o dati mancanti." . PHP_EOL, 3, __DIR__ . '/logs/errorVideo.log');
    }
}

/**
 * Estrae il video finale dal link finale.
 *
 * @param Data $data
 * @param string $final_link
 * @return string La sorgente video o una stringa vuota.
 *
 * La funzione effettua le seguenti operazioni:
 * 1. Verifica se il link finale contiene "maxstream" e se s, estrae il link video finale con getVideoMaxstream.
 * 2. Verifica se il link finale contiene "adelta" e se s, estrae il link video finale con getVideoAdelta.
 * 3. Se non riesce a trovare la sorgente video finale, stampa un messaggio di errore.
 */
function getVideo($data)
{
    // 1. VERIFICA SE IL LINK FINALE CONTIENE "MAXSTREAM"
    // 2. VERIFICA SE IL LINK FINALE CONTIENE "ADELTA"
    $qp = htmlqp($data->getHTML(), null, $data->getOptionsQP());
    $final_link = $qp->find('button#buttok, button')->parent()->attr('href');
    $session_id = $_SESSION['id'];
    $video_src = "";

    if (!empty($final_link)) {
        try {
            // 1. Determiniamo quale funzione usare
            $handler = match (true) {
                str_contains($final_link, 'maxstream') => 'getVideoMaxstream',
                str_contains($final_link, 'adelta') => 'getVideoAdelta',
                str_contains($final_link, 'tva') => 'getVideoTurboVid',
                // str_contains($final_link, 'mixdrop') => 'getVideoMixdrop',
                default => null
            };

            if ($handler === null) {
                throw new Exception("Servizio streaming non supportato.");
            }

            // 2. Chiamata dinamica alla funzione (DRY: Don't Repeat Yourself)
            $video_src = $handler($data, $final_link, $session_id);

            // 3. Risposta di successo
            if (!empty($video_src)) {
                echo json_encode([
                    'status' => 'success',
                    'video_src' => trim(htmlspecialchars_decode($video_src))
                ]);
            } else {
                throw new Exception("Nessun video trovato.");
            }
        } catch (videoException $e) {
            if ($e->getMapperErrorCode() == 1) {
                echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            }
        } catch (Exception $e) {
            error_log(date('Y-m-d H:i:s') . " Errore Scraping Video: " . $e->getMessage() . PHP_EOL, 3, __DIR__ . '/logs/errorVideo.log');
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Nessun link trovato.']);
    }
    exit;
}

/**
 * Recupera il video dal link finale Maxstream.
 *
 * @param Data $data
 * @param string $final_link
 * @param string $session_id
 * @return string La sorgente video o una stringa vuota.
 * @throws Exception Se non riesce a trovare la sorgente video finale.
 */
function getVideoMaxstream($data, $final_link, $session_id)
{
    /**
     * Inizializza una nuova richiesta GET per ottenere l'HTML finale.
     * @var array $payload_get
     */
    $ch = $data->getCH();
    $options = $data->getOptionsQP();

    $payload_get = [
        "cmd" => "request.get",
        "url" => $final_link,
        "session" => $session_id,
        "wait" => 3000
    ];

    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload_get));
    $res = json_decode(curl_exec($ch), true);
    $html = $res['solution']['response'] ?? '';

    file_put_contents(__DIR__ . '/tmp/debug_video.html', $html);

    /** @var \QueryPath\DOMQuery $qp */
    $qp = htmlqp($html, null, $options);

    $video_src = "";

    /**
     * Cerca il primo <iframe> con un attributo src.
     * @var string $iframe_src
     */
    $iframe_src = $qp->find("div[id*='iframes']")->firstChild()->attr('src');
    if (!empty($iframe_src)) {
        /**
         * Inizializza una nuova richiesta GET per ottenere l'HTML all'interno dell'iframe.
         * @var array $payload_iframe
         */
        $payload_iframe = [
            "cmd" => "request.get",
            "url" => $iframe_src,
            "session" => $session_id,
            "wait" => 5000
        ];

        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload_iframe));
        $res_video = json_decode(curl_exec($ch), true);
        $html_video = $res_video['solution']['response'] ?? '';

        file_put_contents(__DIR__ . '/tmp/debug_video_final.html', $html_video);
        /** @var \QueryPath\DOMQuery $qp */
        $qp = htmlqp($html_video, null, $options);

        getVideoFromScriptJS($video_src, $qp);
    }

    unset($ch);

    return $video_src;
}

/**
 * Recupera il video dal link finale DeltaBit.
 *
 * @param Data $data
 * @param string $final_link
 * @param string $session_id
 * @return string La sorgente video o una stringa vuota.
 * @throws Exception Se non riesce a trovare la sorgente video finale.
 */
function getVideoAdelta($data, $final_link, $session_id)
{
    $ch = $data->getCH();
    $options = $data->getOptionsQP();

    // 1. Prima richiesta GET
    $payload_get = ["cmd" => "request.get", "url" => $final_link, "session" => $session_id, "wait" => 3000];
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload_get));
    $res = json_decode(curl_exec($ch), true);
    $html = $res['solution']['response'] ?? '';

    file_put_contents(__DIR__ . '/tmp/debug_video.html', $html);

    /** @var \QueryPath\DOMQuery $qp */
    $qp = htmlqp($html, null, $options);
    $form = $qp->find("form");

    if ($form->length == 0) {
        throw new videoException("Video non trovato il link è scaduto", 1);
    }

    $postDataArray = [];
    // Estraiamo i campi hidden (op, id, fname, hash, ecc.)
    foreach ($form->find("input[type='hidden']") as $input) {
        $name = $input->attr('name');
        $value = $input->attr('value') ?? '';
        $postDataArray[$name] = $value;
    }

    // Fondamentale: DeltaBit vuole il name del bottone submit
    $postDataArray['imhuman'] = '';

    // Gestione dell'Action URL
    $action_url = $form->attr('action') ?: $final_link;

    /** * TRUCCO CRUCIALE: 
     * DeltaBit ha un timer. Se facciamo il POST troppo presto, fallisce.
     * Usiamo una richiesta FlareSolverr intermedia o aumentiamo il 'wait' nel POST.
     */

    // 2. Invio POST con attesa maggiorata (6 secondi per coprire il countdown)
    $payload_final = [
        "cmd" => "request.post",
        "url" => $action_url,
        "session" => $session_id,
        "postData" => http_build_query($postDataArray),
        "wait" => 7000 // Aspettiamo 7 secondi per dare tempo al server di accettare il POST dopo il countdown
    ];

    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload_final));
    $res_final = json_decode(curl_exec($ch), true);
    $html_final = $res_final['solution']['response'] ?? '';

    // Salviamo per debug
    file_put_contents(__DIR__ . '/tmp/debug_video_final.html', $html_final);

    $video_src = "";

    // Regex migliorata per DeltaBit (cerca il pattern JwPlayer o simili)
    // Di solito il link è dentro un blocco script tipo: sources: ["https://..."]

    /** @var \QueryPath\DOMQuery $qp */
    $qp = htmlqp($html_final, null, $options);

    getVideoFromScriptJS($video_src, $qp);

    if (empty($video_src)) {
        // Se ancora non lo trovi, controlliamo se c'è un errore specifico nell'HTML
        if (strpos($html_final, "Wrong IP") !== false) {
            throw new Exception("Errore: Il server video vede l'IP di FlareSolverr differente dal tuo.");
        }
        throw new Exception("Non riesco a trovare la sorgente video. Controlla debug_video_final.html");
    }

    unset($ch);

    return $video_src;
}

/**
 * Recupera il video dal link finale TurboVid.
 * @param string $final_link
 * @param string $session_id
 * @return string La sorgente video o una stringa vuota.
 * @throws Exception Se non riesce a trovare la sorgente video finale.
 */
function getVideoTurboVid($data, $final_link, $session_id)
{
    $ch = $data->getCH();
    $options = $data->getOptionsQP();

    $payload_get = [
        "cmd" => "request.get",
        "url" => $final_link,
        "session" => $session_id,
        "wait" => 5000
    ];

    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload_get));
    $res = json_decode(curl_exec($ch), true);
    $html = $res['solution']['response'] ?? '';
    $url = $res['solution']['url'] ?? '';

    file_put_contents(__DIR__ . '/tmp/debug_video.html', $html);

    /** @var \QueryPath\DOMQuery $qp */
    $qp = htmlqp($html, null, $options);

    $form = $qp->find('form');

    if ($form->length == 0) {
        throw new videoException("Video non trovato il link è scaduto", 1);
    }


    $postDataArray = [];
    // Estraiamo i campi hidden (op, id, fname, hash, ecc.)
    foreach ($form->find("input[type='hidden']") as $input) {
        $name = $input->attr('name');
        $value = $input->attr('value') ?? '';
        $postDataArray[$name] = $value;
    }

    // Fondamentale: DeltaBit vuole il name del bottone submit
    $postDataArray['imhuman'] = 'Proceed to video';

    // Gestione dell'Action URL
    $action_url = $form->attr('action') ?: $final_link;
    $action_url = str_contains($action_url, '://') ? $action_url : (str_contains($url, $action_url) ? $url : $final_link);

    /** * TRUCCO CRUCIALE: 
     * DeltaBit ha un timer. Se facciamo il POST troppo presto, fallisce.
     * Usiamo una richiesta FlareSolverr intermedia o aumentiamo il 'wait' nel POST.
     */

    // 2. Invio POST con attesa maggiorata (6 secondi per coprire il countdown)
    $payload_final = [
        "cmd" => "request.post",
        "url" => $action_url,
        "session" => $session_id,
        "postData" => http_build_query($postDataArray),
        "wait" => 9000 // Aspettiamo 10 secondi per dare tempo al server di accettare il POST dopo il countdown
    ];

    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload_final));
    $res_final = json_decode(curl_exec($ch), true);
    $html_final = $res_final['solution']['response'] ?? '';

    file_put_contents(__DIR__ . '/tmp/debug_video_final.html', $html_final);

    // Se compare il countdown, dobbiamo fare un altro POST
    $maxAttempts = 10;
    $attempt = 0;

    do {

        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload_final));
        $res_final2 = json_decode(curl_exec($ch), true);
        $html_final = $res_final2['solution']['response'] ?? '';

        file_put_contents(__DIR__ . '/tmp/debug_video_final.html', $html_final);

        $attempt++;

        if ($attempt < $maxAttempts) {
            usleep(500000); // 0.5 secondi
        }
    } while (
        $attempt < $maxAttempts &&
        (str_contains($html_final, 'countdown_str') || str_contains($html_final, 'Wait'))
    );
    // --- STEP 3: estrai il video dallo script Clappr ---

    $video_src = '';

    $packed = '';

    if (preg_match('/eval\(function\(p,a,c,k,e,d\).*?split\(\'\|\'\)\)\)/s', $html_final, $m)) {
        $packed = $m[0];
        file_put_contents(__DIR__ . '/tmp/packed.js', $packed);
    }

    if (!$packed) {
        throw new videoException("TurboVid: impossibile trovare il codice packed", 1);
    }

    file_put_contents(__DIR__ . '/tmp/packed.js', $packed);

    $unpacked = unpack_packer($packed);

    file_put_contents(__DIR__ . '/tmp/unpacked.js', $unpacked);

    getVideoFromScriptJS($video_src, null, $unpacked, false);

    if (!$video_src) {
        throw new videoException("TurboVid: impossibile trovare la sorgente video", 1);
    }

    unset($ch);

    return $video_src;
}

// function getVideoMixdrop($data, $final_link, $session_id)
// {
//     $ch = $data->getCH();
//     $options = $data->getOptionsQP();

//     // 1. Prima richiesta GET
//     $payload_get = ["cmd" => "request.get", "url" => $final_link, "session" => $session_id, "wait" => 3000];
//     curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload_get));
//     $res = json_decode(curl_exec($ch), true);
//     $html = $res['solution']['response'] ?? '';

//     file_put_contents(__DIR__ . '/tmp/debug_video.html', $html);

//     /** @var \QueryPath\DOMQuery $qp */
//     $qp = htmlqp($html, null, $options);
//     $src = $qp->find(".panel iframe")->attr('src');

//     $payload_get = [
//         "cmd" => "request.get",
//         "url" => $src,
//         "session" => $session_id,
//         "wait" => 3000
//     ];

//     curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload_get));
//     $res = json_decode(curl_exec($ch), true);
//     $html = $res['solution']['response'] ?? '';

//     file_put_contents(__DIR__ . '/tmp/debug_video_final.html', $html);

//     $video_src = '';

//     $packed = '';

//     // Cambia la regex per essere meno rigida sulle parentesi finali e gli spazi
//     if (preg_match("/eval\(function\(p,a,c,k,e,d\).*?\.split\('\|'\).*?\)\)/s", $html, $m)) {
//         $packed = $m[0];
//         file_put_contents(__DIR__ . '/tmp/packed.js', $packed);
//     }

//     if (!$packed) {
//         throw new videoException("Mixdrop: impossibile trovare il codice packed", 1);
//     }

//     $unpacked = unpack_packer($packed);

//     file_put_contents(__DIR__ . '/tmp/unpacked.js', $unpacked);

//     getVideoFromScriptJS($video_src, null, $unpacked, false);

//     if (!$video_src) {
//         throw new videoException("Mixdrop: impossibile trovare la sorgente video", 1);
//     }

//     unset($ch);

//     return $video_src;
// }

function unpack_packer($packed)
{
    if (!preg_match("/}\('(.*)',\s*(\d+),\s*(\d+),\s*'(.*?)'\.split\('\|'\)/s", $packed, $out)) {
        return '';
    }

    $payload = $out[1];
    $radix   = (int)$out[2];
    $count   = (int)$out[3];
    $symtab  = explode('|', $out[4]);

    if ($count != count($symtab)) {
        return '';
    }

    for ($i = $count - 1; $i >= 0; $i--) {
        if ($symtab[$i] != '') {
            $payload = preg_replace(
                '/\b' . base_convert($i, 10, $radix) . '\b/',
                $symtab[$i],
                $payload
            );
        }
    }

    return $payload;
}

function getVideoFromScriptJS(string &$video_src, ?\QueryPath\DOMQuery $qp = null, ?string $unpacked = null, $isScriptJS = true)
{
    // Regex migliorata: cattura link con o senza protocollo e gestisce i parametri di query (token)
    $regex = '/(?:sources|src|MDCore\.wurl)\s*[:=]\s*\[?\s*["\']([^"\']+\.(?:m3u8|mp4)[^"\']*)["\']/i';
    if ($isScriptJS) {
        $scripts = $qp->find('script');
        $scripts->each(function ($_, $script) use (&$video_src, $regex) {
            $content = $script->textContent;
            if (str_contains($content, 'sources')) {
                if (preg_match($regex, $content, $matches)) {
                    $video_src = fixVideoProtocol($matches[1]);
                    return false; // Stop al primo trovato
                }
            }
        });
    } else {
        // var_dump($video_src, $unpacked, $isScriptJS, $regex, $qp);
        if (preg_match($regex, $unpacked, $matches)) {
            // var_dump($matches);
            $video_src = fixVideoProtocol($matches[1]);
        }
    }
}

/**
 * Funzione di utility per garantire che l'URL abbia il protocollo https
 * fondamentale per il fetching via cURL e il rendering frontend
 */
function fixVideoProtocol($url)
{
    if (empty($url)) return "";
    $url = trim($url);
    if (strpos($url, '//') === 0) {
        return "https:" . $url;
    }
    return $url;
}
