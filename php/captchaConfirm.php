<?php
// header('Content-Type: application/json');
require_once __DIR__ . '/vendor/autoload.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();                          // Sessione DOPO
}
require_once __DIR__ . '/siteVideo.php';

$codice_utente = $_POST['codice_captcha'] ?? '';
$session_id = $_SESSION['id'] ?? '';
$target_url = $_SESSION['url'] ?? '';
$input_name = $_SESSION['captcha_field_name'] ?? 'captcha';
$hidden_name = $_SESSION['captcha_hidden_name'] ?? '';
$hidden_value = $_SESSION['captcha_hidden_value'] ?? '';
/** @var \classes\Data $app_data */
$app_data = $_SESSION['app_data'];

if (!$codice_utente || !$session_id) die("Dati sessione mancanti.");

$postData = [
    $input_name => $codice_utente,
    $hidden_name => $hidden_value
];

$payload_post = [
    "cmd"       => "request.post",
    "url"       => $target_url,
    "session"   => $session_id,
    "postData"  => http_build_query($postData),
    "maxTimeout" => 60000
];

$ch = $app_data->getCH();

$options = $app_data->getOptionsQP();

curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload_post));
$res = json_decode(curl_exec($ch), true);

if (isset($res['solution'])) {
    $html = $res['solution']['response'];
    file_put_contents(__DIR__ . '/tmp/debug_confirm.html', $html);
    $app_data->setHTML($html);
    getVideo($app_data);
} else error_log(date('Y-m-d H:i:s') . " Errore FlareSolverr: " . PHP_EOL . ($response_get['message'] ?? "Timeout") . PHP_EOL, 3, __DIR__ . '/logs/errorCaptchaConfirm.log');
unset($ch);
exit;
