<?php

// require_once 'open_database_connection.php';

// Returns the plugins of a given url
function get_plugins($url)
{
    //$conn = open_database_connection();
    //$plugins = database_read_plugins($conn, $url);
    //if (!empty($plugins)) {
        require_once 'get_content.php';
        require_once 'find_links.php';
        require_once 'find_plugins.php';
        $html = get_content($url);
        $links = find_links($html);
        $plugins = find_plugins($links);
    //database_write_plugins($conn, $url, $plugins);
    //}
    //close_database_connection($conn);
    return $plugins;
}

// Reads the plugins of a given url in the database
function database_read_plugins($conn, $url)
{
    return null;
}

// Writes the plugins of a given url in the database
function database_write_plugins($conn, $url, $wp)
{
    return null;
}
?>