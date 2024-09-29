<?php

require_once 'env.php';

$apiKey = $_ENV['API_KEY'];

if ($_GET['api_key'] !== $apiKey) {
    echo json_encode(['error' => 'Invalid API key']);
    exit;
}