<?php
require 'find_themes.php';

function get_themes($url) {

    //if ($wpContent === null) {
        require 'get_html.php';
        require 'find_wp_content.php';
        $html = get_html($url);
        $wpContent = find_wp_content($html);
    //}

    $themes = find_themes($html, $wpContent);
    return $themes;
}
?>
