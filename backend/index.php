<?php

require_once 'env.php';

$apiKey = $_ENV['API_KEY'];

if ($_GET['api_key'] !== $apiKey) {
    echo json_encode(['error' => 'Invalid API key']);
    exit;
}

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

function getHTMLContent($url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $htmlContent = curl_exec($ch);
    curl_close($ch);
    return $htmlContent;
}

$urlFilePath = __DIR__ . '/data/urls.txt';
$scrapingUrl = file_exists($urlFilePath) ? trim(file_get_contents($urlFilePath)) : SCRAPING_URL;

$htmlContent = getHTMLContent($scrapingUrl);
if (!$htmlContent) {
    echo json_encode(['error' => 'Failed to retrieve content from the website']);
    exit;
}