<?php

// require_once 'database_connection.php';

// Returns the plugins of a given url
function get_themes($url)
{
    //if (empty($row)) {
        require_once 'get_content.php';
        require_once 'find_links.php';
        require_once 'find_themes.php';
        $html = get_content($url);
        $links = find_links($html);
        $themes = find_themes($links);
    //}

    // Convert the associative array to an indexed array
    $themes = array_values($themes);

    return $themes;
}
?>