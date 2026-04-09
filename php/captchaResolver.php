<?php
header('Content-Type: application/json');
require_once __DIR__ . '/vendor/autoload.php';

use thiagoalessio\TesseractOCR\TesseractOCR;

if (session_status() === PHP_SESSION_NONE) {
    session_start();                          // Sessione DOPO
}

$target_url = $_POST['url'];

$app_data = $_SESSION['app_data'];

$ch = $app_data->getCH();

$session_id = $_SESSION['id'];
$_SESSION['url'] = $target_url;

// STEP 2: Richiesta GET usando la sessione per ottenere l'immagine
$payload_get = [
    "cmd" => "request.get",
    "url" => $target_url,
    "session" => $session_id,
];

$options = $app_data->getOptionsQP();
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload_get));
$response_get = json_decode(curl_exec($ch), true);

if (isset($response_get['solution'])) {
    $target_url = $response_get['solution']['url'];

    $_SESSION['url'] = $target_url;

    $html = $response_get['solution']['response'];

    file_put_contents(__DIR__ . '/tmp/debug_resolver.html', $html);

    // Carichiamo l'HTML in QueryPath
    /** @var \QueryPath\DOMQuery $qp */
    $qp = htmlqp($html, null, $options);

    // Cerca QUALSIASI immagine che abbia un src base64
    $captcha_src = $qp->find('img[src*="data:image"]')->attr('src');

    // Cerca il nome dell'input basandoti sulla label 'Enter the code'
    $input_name = $qp->find('label:contains("code")')->nextAll('input')->attr('name');

    $hidden_name = null;
    $hidden_value = null;

    if ($qp->find('input[type="hidden"]')->attr('name')) {
        $hidden_name = $qp->find('input[type="hidden"]')->attr('name');
        $hidden_value = $qp->find('input[type="hidden"]')->attr('value');
    }

    // Se il precedente fallisce, prendi il primo input text che trovi
    if (!$input_name) {
        $input_name = $qp->find('input[type="text"]')->attr('name');
    }

    // 2. PROVA OCR AUTOMATICO
    if ($captcha_src) {
        $auto_code = $auto_code = tryAutoSolve($captcha_src);
        unlink(__DIR__ . '/tmp/captcha_raw.png');
        unlink(__DIR__ . '/tmp/captcha_clean.png');
    }


    if (isset($auto_code) && strlen($auto_code) >= 3) {
        $postData = [
            $input_name => $auto_code,
            $hidden_name => $hidden_value
        ];

        $payload_post = [
            "cmd"       => "request.post",
            "url"       => $target_url,
            "session"   => $session_id,
            "postData"  => http_build_query($postData),
            "maxTimeout" => 60000
        ];

        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload_post));
        $response_post = json_decode(curl_exec($ch), true);

        if (isset($response_post['solution'])) {
            $app_data->setHTML($response_post['solution']['response']);
            $_SESSION['app_data'] = $app_data;
            echo json_encode([
                'status' => 'redirect',
            ]);
        }
    } else {
        if ($captcha_src) {
            $_SESSION['captcha_field_name'] = $input_name;
            if ($hidden_name) {
                $_SESSION['captcha_hidden_name'] = $hidden_name;
                $_SESSION['captcha_hidden_value'] = $hidden_value;
            }
            echo json_encode([
                'status' => 'success',
                'captcha_src' => $captcha_src
            ]);
        } else {
            $app_data->setHTML($html);
            $_SESSION['app_data'] = $app_data;
            echo json_encode([
                'status' => 'redirect',
            ]);
        }
    }
} else error_log(date('Y-m-d H:i:s') . " Errore FlareSolverr: " . PHP_EOL . ($response_get['message'] ?? "Impossibile connettersi") . PHP_EOL, 3, __DIR__ . '/logs/errorCaptchaResolver.log');

unset($ch);
exit;

function tryAutoSolve($base64_data)
{
    $log_file = __DIR__ . '/logs/ocr_debug.log';
    $temp_path = __DIR__ . '/tmp/';

    try {
        if (empty($base64_data)) throw new Exception('Base64 data is empty');

        $data = explode(',', $base64_data);
        $img_data = base64_decode($data[1]);

        if (!$img_data) throw new Exception('Image data is empty');

        $original_file = $temp_path . 'captcha_raw.png';
        $cleaned_file = $temp_path . 'captcha_clean.png';

        file_put_contents($original_file, $img_data);

        $img = imagecreatefromstring($img_data);
        if ($img) {
            // 1. Ingrandisci subito (almeno 3x o 4x)
            $width = imagesx($img);
            $height = imagesy($img);
            $scaled = imagecreatetruecolor($width * 3, $height * 3);
            imagecopyresampled($scaled, $img, 0, 0, 0, 0, $width * 3, $height * 3, $width, $height);

            // 2. Converti in scala di grigi
            imagefilter($scaled, IMG_FILTER_GRAYSCALE);

            // 3. BINARIZZAZIONE MANUALE (Soglia)
            // Trasformiamo ogni pixel o in bianco puro o in nero puro
            for ($x = 0; $x < $width * 3; $x++) {
                for ($y = 0; $y < $height * 3; $y++) {
                    $rgb = imagecolorat($scaled, $x, $y);
                    $cols = imagecolorsforindex($scaled, $rgb);
                    // Se il pixel è "chiaro" (grigio/disturbo), fallo diventare bianco (255)
                    // Se è "scuro" (testo), fallo diventare nero (0)
                    $threshold = 130; // Regola questo valore tra 100 e 160 se non legge bene
                    $grey = ($cols['red'] + $cols['green'] + $cols['blue']) / 3;
                    $color = ($grey > $threshold) ? 255 : 0;
                    $new_col = imagecolorallocate($scaled, $color, $color, $color);
                    imagesetpixel($scaled, $x, $y, $new_col);
                }
            }

            imagepng($scaled, $cleaned_file);
            unset($scaled);
            unset($img);
        } else {
            throw new Exception('Image creation failed');
        }

        $ocr = new TesseractOCR($cleaned_file);
        $ocr->executable('C:\Program Files\Tesseract-OCR\tesseract.exe');
        $ocr->tempDir($temp_path);
        $ocr->allowlist(range(0, 9));
        $ocr->psm(6);

        $code = $ocr->run();

        file_put_contents($log_file, "RISULTATO OCR: [" . $code . "]" . PHP_EOL, FILE_APPEND);

        return trim($code);
    } catch (Throwable $e) {
        file_put_contents($log_file, "ECCEZIONE: " . $e->getMessage() . PHP_EOL, FILE_APPEND);
        return null;
    }
}
