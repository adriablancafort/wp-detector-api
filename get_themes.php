<?php

// require_once 'open_database_connection.php';

// Returns the plugins of a given url
function get_themes($url)
{
    //$conn = open_database_connection();
    //$themes = database_read_themes($conn, $url);
    //if (empty($themes)) {
        require_once 'get_content.php';
        require_once 'find_links.php';
        require_once 'find_themes.php';
        $html = get_content($url);
        $links = find_links($html);
        $themes = find_themes($links);
        //database_write_themes($conn, $url, $themes);
    //}
    //close_database_connection($conn);

    // Convert the associative array to an indexed array
    $themes = array_values($themes);

    return $themes;
}

// Reads the themes of a given url in the database
function database_read_themes($conn, $url)
{
    // Read all the theme slugs of the website
    // Make a joint with the table themes to return all the themeInfo for each one of the slugs
    return null;
}

// Writes the themes of a given url in the database
function database_write_themes($conn, $url, $wp)
{
    // Write a list of theme slugs to the website in the websites table
    return null;
}
?>