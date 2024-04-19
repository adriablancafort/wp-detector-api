<?php
require 'find_plugins.php';
require 'database_connection.php';
require 'get_website.php';

function get_plugins($url) {

    $conn = open_database_connection();
    $wp = database_read_website($conn, $url);
    if ($wp === null) {
    //if ($wpContent === null) {
        require 'get_html.php';
        require 'find_wp_content.php';
        $html = get_html($url);
        $wpContent = find_wp_content($html);
        $plugins = find_plugins($html, $wpContent,null);
    }
    else if ($wp === true) {
        $plugins = find_plugins(null, null, $url);
    }
    // $plugins = find_plugins($html, $wpContent);
    return $plugins;
}
?>