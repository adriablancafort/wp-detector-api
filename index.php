<?php
// Disable error reporting
ini_set('display_errors', '0');

require_once 'set_header.php';
require_once 'get_wp.php';
require_once 'get_themes.php';
require_once 'get_plugins.php';

$type = $_GET['type'];

if ($type === 'wp') {
    $url = $_GET['url'];
    $wp = get_wp($url);    
    echo json_encode(['wp' => $wp]);    

} elseif ($type === 'themes') {
    $url = $_GET['url'];
    $themes = get_themes($url);
    echo json_encode(['themes' => $themes]);

} elseif ($type === 'plugins') {
    $url = $_GET['url'];
    $plugins = get_plugins($url);
    echo json_encode(['plugins' => $plugins]);
    
} elseif ($type === 'top-themes') {
    $quantity = $_GET['quantity'];
    $page = $_GET['page'];
    $themes = get_top_themes($quantity, $page);
    echo json_encode(['themes' => $themes]);

} elseif ($type === 'top-plugins') {
    $quantity = $_GET['quantity'];
    $page = $_GET['page'];
    $plugins = get_top_plugins($quantity, $page);
    echo json_encode(['plugins' => $plugins]);
}
?>