<?php

/**
 * Verifica se l'URL esiste.
 *
 * La funzione utilizza una cache basata su file per evitare di dover effettuare
 * troppe volte la stessa verifica. La cache viene salvata per $cache_seconds secondi.
 *
 * @param string $url L'URL da verificare.
 * @param int $cache_seconds Il numero di secondi per cui la cache deve essere
 * valida. Il valore di default è 60.
 *
 * @return bool True se l'URL esiste, false altrimenti.
 */
function url_exists($url, $cache_seconds = 30): bool
{
    $tempDir = sys_get_temp_dir();
    $cache_file = "";
    if (str_contains($url, 'nuovo-indirizzo')) {
        $tmp = md5("indirizzoNuovo");
        $cache_file = "$tempDir\url_check_$tmp.txt";
    } else {
        $tmp = md5("eurostreaming");
        $cache_file = "$tempDir\url_check_$tmp.txt";
    }

    file_put_contents(__DIR__ . '/logs/logFunctions.log',  date('Y-m-d H:i:s') .  " url_exists: " . $url . " - " . $cache_file . PHP_EOL, FILE_APPEND);

    // 1. Controlliamo se la cache esiste ed è ancora valida
    if (file_exists($cache_file) && (time() - file_get_contents($cache_file)) < $cache_seconds) {
        return true;
    }

    // 2. Se la cache è scaduta, facciamo il controllo reale con cURL
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 3);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)');

    curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    unset($ch);

    $exists = ($httpCode >= 200 && $httpCode < 400);

    // 3. Salviamo il risultato nella cache se esiste gli mettiamo un timestamp
    if ($exists) {
        file_put_contents($cache_file, time());
    }

    return $exists;
}

/**
 * Stampa una stringa con un carattere di newline alla fine.
 *
 * La funzione utilizza la variabile di sessione $_SESSION['BREAK_LINE'] per
 * aggiungere un carattere di newline alla fine della stringa.
 *
 * @param string $arg La stringa da stampare.
 *
 * @return int Il numero di bytes stampati.
 */
function println(string $arg): int
{
    return print($arg . $_SESSION['BREAK_LINE']);
}

/**
 * Stampa il valore di una variabile con print_r e aggiunge un carattere di
 * newline alla fine.
 *
 * La funzione utilizza la variabile di sessione $_SESSION['BREAK_LINE'] per
 * aggiungere un carattere di newline alla fine della stringa.
 *
 * @param mixed $value La variabile da stampare.
 * @param bool $return Se vero, restituisce la stringa stampata anziché
 * stamparla.
 *
 * @return string|true La stringa stampata se $return è true, altrimenti true.
 */
function println_r(mixed $value, bool $return = false): string|true
{
    $_return = print_r($value, $return);
    println("");
    return $_return;
}

/**
 * Inizializza le variabili di sessione per il dominio.
 *
 * Prende una stringa contenente il dominio come argomento e
 * lo divide in due parti utilizzando il carattere di punteggiatura
 * '.'. Le due parti vengono quindi salvate nelle variabili di
 * sessione $_SESSION['recent_second_lvl_domain'] e
 * $_SESSION['recent_top_lvl_domain'].
 *
 * Successivamente, vengono copiate le variabili di sessione
 * $_SESSION['top_lvl_domain'] e $_SESSION['second_lvl_domain'] con
 * i valori appena inizializzati.
 */
function initDomain()
{
    /** @var classes\Database $db */
    $db = $_SESSION['DB'];
    $defaultDomain = $db->getSetting('streaming_domain');

    // Inizializziamo solo se non abbiamo già nulla in sessione
    if (!isset($_SESSION['recent_second_lvl_domain']) || empty($_SESSION['recent_second_lvl_domain'])) {
        $domain = mb_split('\\.', $defaultDomain);
        $_SESSION['recent_second_lvl_domain'] = $domain[0] ?? "";
        $_SESSION['recent_top_lvl_domain'] = $domain[1] ?? "";

        $_SESSION['second_lvl_domain'] = $_SESSION['recent_second_lvl_domain'];
        $_SESSION['top_lvl_domain'] = $_SESSION['recent_top_lvl_domain'];
    }
}

/**
 * Verifica se il dominio è cambiato e aggiorna la sessione se necessario.
 *
 * La funzione utilizza le variabili di sessione $_SESSION['recent_second_lvl_domain'] e
 * $_SESSION['recent_top_lvl_domain'] per verificare se il TLD è cambiato.
 *
 * Se il TLD è cambiato, vengono aggiornati le variabili di sessione
 * $_SESSION['second_lvl_domain'], $_SESSION['top_lvl_domain'] e
 * $_SESSION['recent_second_lvl_domain'] con i valori appena ottenuti.
 *
 * La funzione utilizza QueryPath per estrarre il testo dal dominio.
 * In caso di errore, stampa un messaggio di errore.
 */
function verifyDomain(): bool
{
    $url = 'https://' . $_SESSION['recent_second_lvl_domain'] . '.' . $_SESSION['recent_top_lvl_domain'];

    if (url_exists($url)) {
        // println("Apposto non c'è nulla da fare");
        file_put_contents(__DIR__ . '/logs/logFunctions.log',  date('Y-m-d H:i:s') . " Apposto non c'è nulla da fare" . PHP_EOL, FILE_APPEND);
        return true;
    }

    $targetUrl = "https://eurostreaming-nuovo-indirizzo.com/";

    if (url_exists($targetUrl)) {
        // Opzioni per QueryPath: forza UTF-8 e ignora i warning del parser XML
        $options = [
            'use_parser' => 'html', // Fondamentale: usa il parser HTML di DOMDocument
            'ignore_parser_warnings' => true,
            'convert_from_encoding' => 'UTF-8'
        ];


        // Carichiamo prima l'HTML manualmente per avere più controllo
        $html = @file_get_contents($targetUrl);

        // Usiamo htmlqp() che è la versione specifica per HTML di QueryPath
        $dom = htmlqp($html, null, $options);

        // Cerchiamo l'elemento con la sintassi CSS di jQuery
        $elem = $dom->find("span.Stile6 a b");

        if ($elem->count() == 0) {
            file_put_contents(__DIR__ . '/logs/logFunctions.log',  date('Y-m-d H:i:s') . " Elemento non trovato: pagina cambiata?" . PHP_EOL, FILE_APPEND);
            // println("Elemento non trovato: pagina cambiata?");
            return false;
        }

        // Otteniamo il testo e lo puliamo
        $text = trim($elem->text());

        // Rimuoviamo il protocollo (http/https)
        $text = preg_replace('#^https?://#', '', $text);

        // Dividiamo il dominio (es: eurostreaming.red -> ['eurostreaming', 'red'])
        $parts = explode(".", $text);
        
        if (count($parts) < 2) {
            file_put_contents(__DIR__ . '/logs/logFunctions.log',  date('Y-m-d H:i:s') . " Formato dominio non valido: " . $text . PHP_EOL, FILE_APPEND);
            // println("Formato dominio non valido: " . $text);
            return false;
        }

        $newSecond = $parts[0];
        $newTop = $parts[1];

        // Se il TLD è cambiato, aggiorniamo la sessione
        if ($_SESSION['recent_top_lvl_domain'] !== $newTop) {
            $_SESSION['top_lvl_domain'] = $newTop;
            $_SESSION['second_lvl_domain'] = $newSecond;
            // Invece di stampare a video, usiamo un log o carichiamo silenziosamente
            /** @var classes\Database $db */
            $db = $_SESSION['DB'];
            // $db->saveSetting('streaming_domain', $_SESSION['second_lvl_domain'] . '.' . $_SESSION['top_lvl_domain']);
            return $db->saveSetting('streaming_domain', $_SESSION['second_lvl_domain'] . '.' . $_SESSION['top_lvl_domain']);;
        }
    }

    file_put_contents(__DIR__ . '/logs/logFunctions.log',  date('Y-m-d H:i:s') . " Errore: sia l'url del sito e sia l'url di aggiornamento non sono disponibili" . PHP_EOL, FILE_APPEND);
    return false;
}

function slugify(string $text): string
{
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    return strtolower(trim($text, '-'));
}


function generateToken(): string {
    return bin2hex(random_bytes(32)); // 256 bit
}

function generateCode() {
    return random_int(100000, 999999); // 6 cifre
}