<?php
// Disable error reporting
ini_set('display_errors', '0');

require_once 'set_header.php';
require_once 'get_wp.php';
require_once 'get_themes.php';
require_once 'get_plugins.php';
require_once 'get_websites.php';

function cleanUrl($url)
{
    $parsedUrl = parse_url($url);
    $cleanUrl = $parsedUrl['scheme'] . '://' . $parsedUrl['host'];
    // Append the path to the url if it is not empty
    $path = $parsedUrl['path'];
    if (isset($path)) {
        $cleanUrl .= $path;
    }
    return $cleanUrl;
}

$type = $_GET['type'];
$url = $_GET['url']; // Store multiple pages of the same website as a single website in the database. Analyze multiple pages of the same website for every query?
if (isset($url)) {
    $cleanUrl = cleanUrl($url);
}

if ($type === 'wp') {
    $wp = get_wp($cleanUrl);    
    echo json_encode(['wp' => $wp]);    

} elseif ($type === 'themes') {
    $themes = get_themes($cleanUrl);
    echo json_encode(['themes' => $themes]);

} elseif ($type === 'plugins') {
    $plugins = get_plugins($cleanUrl);
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
/*
elseif ($type === 'websites-themes') {
    $websitesThemes = get_websites_themes();
    echo json_encode(['websitesThemes' => $websitesThemes]);
}

elseif ($type === 'websites-plugins') {
    $websitesPlugins = get_websites_plugins();
    echo json_encode(['websitesPlugins' => $websitesPlugins]);
}
*/
?>