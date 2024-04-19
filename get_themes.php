<?php
require 'find_themes.php';
require 'database_connection.php';
require 'get_website.php';

function get_themes($url) {

    $conn = open_database_connection();
    $wp = database_read_website($conn, $url);
    if ($wp === null) {
    //if ($wpContent === null) {
        require 'get_html.php';
        require 'find_wp_content.php';
        $html = get_html($url);
        $wpContent = find_wp_content($html);
        $themes = find_themes($html, $wpContent,null);
    }
    else if ($wp === true) {
        $themes = find_themes(null, null, $url);
    }
    // $themes = find_themes($html, $wpContent);
    return $themes;
}
?>
