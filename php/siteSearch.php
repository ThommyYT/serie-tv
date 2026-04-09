<?php
require_once __DIR__ . '/vendor/autoload.php';
session_start();

// 1. Gestione dinamica della pagina (es: index.php?p=2)
$p = isset($_POST['p']) ? (int)$_POST['p'] : 1;
$s = urlencode($_POST['s'] ?? '');
$pagePath = ($p > 1) ? "page/$p/" : "";

$url = "https://" . $_SESSION['second_lvl_domain'] . "." . $_SESSION['top_lvl_domain'] . "/" . $pagePath . '?s=' . $s;

$classWrapper = 'search';


// Eseguiamo lo scraping (site.php ora deve solo popolare $dataCards e $dataNavigation)
include __DIR__ . '/site.php'; 

// RITORNO JSON
header('Content-Type: application/json');
/** @var string $sectionTitle 
 *  @var array{id: string, titolo: string, url: string, img: string}[] $dataCards
 *  @var array{text: string, pageNum: string|int, isActive: bool, isPrev: bool, isNext: bool, isDots: bool}[] $dataNavigation
*/
echo json_encode([
    'sectionTitle' => $sectionTitle ?? '',
    'dataCards' => $dataCards ?? [],
    'dataNavigation' => $dataNavigation ?? [],
    'status' => 'success'
]);
exit; // Importante per non stampare altro