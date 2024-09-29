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

$allProducts = [];
foreach ($categories as $category) {
    $categoryName = $category['name'];
    $categoryUrl = $category['url'];

    $categoryContent = getHTMLContent($categoryUrl);
    if (!$categoryContent) {
        continue;
    }

    $categoryDom = new DOMDocument();
    libxml_use_internal_errors(true);
    $categoryDom->loadHTML($categoryContent);
    libxml_clear_errors();

    $categoryXPath = new DOMXPath($categoryDom);
    $productNodes = $categoryXPath->query("//div[contains(@class, 'product')]");

    foreach ($productNodes as $node) {
        $titleNode = $categoryXPath->query(".//h4", $node);
        $priceNode = $categoryXPath->query(".//span[contains(@class, 'price')]", $node);
        $productLinkNode = $categoryXPath->query(".//a", $node);

        $title = $titleNode->length > 0 ? trim($titleNode->item(0)->nodeValue) : 'No title';
        $price = $priceNode->length > 0 ? trim($priceNode->item(0)->nodeValue) : 'No price';
        $productLink = $productLinkNode->length > 0 ? $productLinkNode->item(0)->getAttribute('href') : '';

        if (strpos($productLink, 'http') === false) {
            $baseUrl = parse_url($scrapingUrl, PHP_URL_SCHEME) . '://' . parse_url($scrapingUrl, PHP_URL_HOST);
            $productLink = $baseUrl . '/' . ltrim(preg_replace('#^/et/catalog/[^/]+#', '', $productLink), '/');
        }

        $productContent = getHTMLContent($productLink);
        $rating = 'No rating';
        $availability = 'In Stock';

        if ($productContent) {
            $productDom = new DOMDocument();
            libxml_use_internal_errors(true);
            $productDom->loadHTML($productContent);
            libxml_clear_errors();

            $productXPath = new DOMXPath($productDom);
            
            $ratingNode = $productXPath->query("//span[contains(@class, 'rating') or contains(@class, 'star-rating')]");
            if ($ratingNode->length > 0) {
                $rating = trim($ratingNode->item(0)->nodeValue);
            }

            $availabilityNode = $productXPath->query("//*[contains(text(), 'Нет в наличии') or contains(text(), 'Out of stock') or contains(text(), 'Ei ole laos')]");
            if ($availabilityNode->length > 0) {
                $availability = 'Out of Stock';
            }
        }

        $allProducts[] = [
            'title' => $title,
            'price' => $price,
            'category' => $categoryName,
            'link' => $productLink,
            'rating' => $rating,
            'availability' => $availability
        ];
    }
}