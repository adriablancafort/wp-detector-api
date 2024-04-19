<?php

// require 'open_database_connection.php';

// Returns the plugins of a given url
function get_themes($url)
{
    //$conn = open_database_connection();
    //$themes = database_read_themes($conn, $url);
    //if (!empty($themes)) {
        require 'get_html.php';
        require 'find_links.php';
        require 'find_themes.php';
        $html = get_html($url);
        $links = find_links($html);
        $themes = find_themes($links);
    //database_write_themes($conn, $url, $themes);
    //}
    //close_database_connection($conn);
    return $themes;
}

// Reads the themes of a given url in the database
function database_read_themes($conn, $url)
{
    return null;
}

// Writes the themes of a given url in the database
function database_write_themes($conn, $url, $wp)
{
    return null;
}
?>