<?php
require 'find_plugins.php';

function get_plugins($url) {

    //if ($wpContent === null) {
        require 'get_html.php';
        require 'find_wp_content.php';
        $html = get_html($url);
        $wpContent = find_wp_content($html);
    //}

    $plugins = find_plugins($html, $wpContent);
    return $plugins;
}
?>