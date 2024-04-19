<?php
header('Content-Type: application/json');

$allowed_origins = ['http://localhost:4321', 'https://wp-detector.com'];

// Check if the Origin header is in the list of allowed origins
if (isset($_SERVER['HTTP_ORIGIN']) && in_array($_SERVER['HTTP_ORIGIN'], $allowed_origins)) {
    // Set the Access-Control-Allow-Origin header to the Origin of the incoming request
    header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
}
?>