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

$dom = new DOMDocument();
libxml_use_internal_errors(true);
$dom->loadHTML($htmlContent);
libxml_clear_errors();

$xpath = new DOMXPath($dom);
$categoryNodes = $xpath->query("//div[@id='tea-cats']//a");

$categories = [];
foreach ($categoryNodes as $categoryNode) {
    $categoryName = trim($categoryNode->nodeValue);
    $categoryUrl = $categoryNode->getAttribute('href');
    if (strpos($categoryUrl, 'http') === false) {
        $categoryUrl = rtrim($scrapingUrl, '/') . '/' . ltrim($categoryUrl, '/');
    }
    $categories[] = [
        'name' => $categoryName,
        'url' => $categoryUrl
    ];
}

if (empty($categories)) {
    echo json_encode(['error' => 'No categories found on the website']);
    exit;
}