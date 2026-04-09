<?php
// Recuperiamo l'istanza globale della classe Data

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
/** @var \classes\Database $db */
$db = $_SESSION['DB'];
$dataCards = [];
$dataNavigation = [];
if (isset($response['solution'])) {
    $html = $response['solution']['response'];
    try {
        // Usiamo le opzioni centralizzate della classe Data
        /** @var \QueryPath\DOMQuery $dom */
        $dom = htmlqp($html, 'body', $app_data->getOptionsQP());

        $sectionTitle = $dom->find('.section-title h2')->first()->text() ?: "Risultati";

        if ($classWrapper === "search")
            $sectionTitle = mb_split(",", $sectionTitle)[0];

        /** @var \QueryPath\DOMQuery $post */
        foreach ($dom->find('ul.recent-posts li') as $post) {
            // Estrazione pulita con QueryPath
            if ($_SESSION['user']) $favorite = $db->checkFavorite($_SESSION['user'], $post->attr('id'));

            $dataCards[] = [
                'id' => (string) $post->attr('id'),
                'titolo' => trim($post->find('h2 a')->text()),
                'url' => (string) $post->find('h2 a')->attr('href'),
                'img' => (string) $post->find('.post-thumb img')->attr('src'),
                'favourite' => $favorite ?? false
            ];
        }

        /** @var \QueryPath\DOMQuery $nav */
        $nav = $dom->find('.navigation');
        if ($nav->count() > 0) {
            /** @var \QueryPath\DOMQuery $el */
            foreach ($nav->children() as $el) {
                $pageNum = 0;
                $text = '';
                $active = false;
                $prev = false;
                $next = false;
                $dots = false;

                if ($el->is('a')) {
                    if ($el->hasClass('prev')) {
                        $prev = true;
                    } else if ($el->hasClass('next')) {
                        $next = true;
                    }

                    $text = trim($el->text());

                    if (is_numeric($text)) {
                        $pageNum = (int) $text;
                    } else if ($prev || $next) {
                        $pageNum = $prev ? -1 : +1;
                    }
                } else {
                    if ($el->hasClass('current')) {
                        $text = trim($el->text());
                        $pageNum = (int) $text;
                        $active = true;
                    } else {
                        $dots = true;
                    }
                }

                $dataNavigation[] = [
                    'text' => $text,
                    'pageNum' => $pageNum,
                    'isActive' => $active,
                    'isPrev' => $prev,
                    'isNext' => $next,
                    'isDots' => $dots,
                ];
            }
        }
    } catch (Throwable $th) {
        error_log(date('Y-m-d H:i:s') . " Errore Parsing: " . PHP_EOL . $th->getMessage() . PHP_EOL, 3, __DIR__ . '/logs/errorSite.log');
    }
}

$data = [
    'dataCards' => $dataCards,
    'dataNavigation' => $dataNavigation
];

$log = "[" . date('Y-m-d H:i:s') . "]
FILE: {" . PHP_EOL . $_SERVER['REQUEST_URI'] . PHP_EOL . "},
RESPONSE:" . PHP_EOL . json_encode($response, JSON_PRETTY_PRINT, 4) . PHP_EOL . ",
DATA: " . PHP_EOL . json_encode($data, JSON_PRETTY_PRINT, 4) . "." . PHP_EOL;

file_put_contents(__DIR__ . '/logs/log-' . basename($_SERVER['REQUEST_URI'], '.php') . '-' . date('Y-m-d') . '.log', $log, FILE_APPEND);
unset($ch);
