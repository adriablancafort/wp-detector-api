<?php
require 'set_header.php';
require 'get_html.php';
require 'wp_content_path.php';
require 'detect_themes.php';
require 'detect_plugins.php';

$url = $_GET['url'];
$type = $_GET['type'];
$html = get_html($url);
$wpContent = wp_content_path($html);

if ($type === 'wp') {
    $wp = $wpContent !== false;
    echo json_encode(['wp' => $wp]);

} elseif ($type === 'themes') {
    $themes = detect_themes($html, $wpContent);
    echo json_encode(['themes' => $themes]);

} elseif ($type === 'plugins') {
    $plugins = detect_plugins($html, $wpContent);
    echo json_encode(['plugins' => $plugins]);

} elseif ($type === 'top-themes') {
    $themes = detect_themes($html, $wpContent);
    echo json_encode(['themes' => $themes]);

} elseif ($type === 'top-plugins') {
    $plugins = detect_plugins($html, $wpContent);
    echo json_encode(['plugins' => $plugins]);
}
?>