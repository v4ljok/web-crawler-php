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