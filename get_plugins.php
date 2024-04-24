<?php

// require_once 'database_connection.php';

// Returns the plugins of a given url
function get_plugins($url)
{
    //if (empty($row)) {
        require_once 'get_content.php';
        require_once 'find_links.php';
        require_once 'find_plugins.php';
        $html = get_content($url);
        $links = find_links($html);
        $plugins = find_plugins($links);
    //}

    // Convert the associative array to an indexed array
    $plugins = array_values($plugins);

    return $plugins;
}
?>