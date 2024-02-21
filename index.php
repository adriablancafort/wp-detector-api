<?php
header('Content-Type: application/json');

// List of allowed origins
$allowed_origins = ['http://localhost:4321', 'https://wp-detector.com', 'https://wp-detector.pages.dev'];

// Check if the Origin header is in the list of allowed origins
if (isset($_SERVER['HTTP_ORIGIN']) && in_array($_SERVER['HTTP_ORIGIN'], $allowed_origins)) {
    // Set the Access-Control-Allow-Origin header to the Origin of the incoming request
    header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
}

$url = $_GET['url'];

// Fetch the HTML content of the webpage
$html = file_get_contents($url);

// Check if 'wp-content' is present in the HTML
if (strpos($html, 'wp-content') !== false) {
    echo json_encode(['wp' => 'yes']);
} else {
    echo json_encode(['wp' => 'no']);
}
?>