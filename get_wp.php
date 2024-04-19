<?php

require 'database_connection.php';

// Returns if wordpress is detected given a url
function get_wp($url) 
{
    //$conn = open_database_connection();
    //$wp = database_read_website($conn, $url);
    //if (!empty($wp)) {
        require 'get_html.php';
        require 'find_links.php';
        require 'find_wp.php';
        $html = get_html($url);
        $links = find_links($html);
        $wp = find_wp($links);
        //database_write_website($conn, $url, $wp);
    //}
    //close_database_connection($conn);
    return $wp;
}

// Reads if wordpress is detected given url in the database
function database_read_website($conn, $url) {
    $stmt = $conn->prepare("SELECT wp FROM websites WHERE url = ?");
    $stmt->bind_param("s", $url);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['wp'] ? true : false;
    }
    return null;
}

// Writes if wordpress is detected given url in the database
function database_write_website($conn, $url, $wp) {
    $date = date('Y-m-d');
    $stmt = $conn->prepare("INSERT INTO websites (url, wp, themes, plugins, times_analyzed, last_analyzed) VALUES (?, ?, NULL, NULL, 1, ?) ON DUPLICATE KEY UPDATE times_analyzed = times_analyzed + 1, last_analyzed = ?");
    $stmt->bind_param("ssss", $url, $wp, $date, $date);
    $stmt->execute();
}
?>