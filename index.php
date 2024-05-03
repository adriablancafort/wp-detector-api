<?php
// Disable error reporting
ini_set('display_errors', '0');

require_once 'set_header.php';
require_once 'get_wp.php';
require_once 'get_themes.php';
require_once 'get_plugins.php';

$url = $_GET['url'];
$type = $_GET['type'];

if ($type === 'wp') {
    $wp = get_wp($url);    
    echo json_encode(['wp' => $wp]);    

} elseif ($type === 'themes') {
    $themes = get_themes($url);
    echo json_encode(['themes' => $themes]);

} elseif ($type === 'plugins') {
    $plugins = get_plugins($url);
    echo json_encode(['plugins' => $plugins]);
    
} elseif ($type === 'top-themes') {
    $themes = get_top_themes();
    echo json_encode(['themes' => $themes]);

} elseif ($type === 'top-plugins') {
    $plugins = get_top_plugins();
    echo json_encode(['plugins' => $plugins]);
}
?>