<?php

// require_once 'open_database_connection.php';

// Returns the plugins of a given url
function get_plugins($url)
{
    //$conn = open_database_connection();
    //$plugins = database_read_plugins($conn, $url);
    //if (empty($plugins)) {
        require_once 'get_content.php';
        require_once 'find_links.php';
        require_once 'find_plugins.php';
        $html = get_content($url);
        $links = find_links($html);
        $plugins = find_plugins($links);
        //database_write_plugins($conn, $url, $plugins);
    //}
    //close_database_connection($conn);

    // Convert the associative array to an indexed array
    $plugins = array_values($plugins);

    return $plugins;
}

// Reads the plugins of a given url in the database
function database_read_plugins($conn, $url)
{
    // Read all the plugin slugs of the website
    // Make a joint with the table plugins to return all the pluginInfo for each one of the slugs
    return null;
}

// Writes the plugins of a given url in the database
function database_write_plugins($conn, $url, $wp)
{
    // Write a list of plugin slugs to the website in the websites table
    return null;
}
?>