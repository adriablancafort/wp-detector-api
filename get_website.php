<?php

require 'database_connection.php';
require 'database_write.php';

function get_website($url) {
    $conn = open_database_connection();
    $wp = database_read_website($conn, $url);
    if ($wp === null) {
        require 'get_html.php';
        require 'find_wp_content.php';
        $html = get_html($url); // the html is stored in a global variable
        $wpContent = find_wp_content($html);
        $wp = $wpContent !== false;
        database_write_website($conn, $url, $wp);
    }
    else {
        $result = updateTimesAnalyzed('websites',$url);
    }
    close_database_connection($conn);
    return $wp;
}

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

function database_write_website($conn, $url, $wp) {
    $date = date('Y-m-d');
    $stmt = $conn->prepare("INSERT INTO websites (url, wp, themes, plugins, times_analyzed, last_analyzed) VALUES (?, ?, NULL, NULL, 1, ?) ON DUPLICATE KEY UPDATE times_analyzed = times_analyzed + 1, last_analyzed = ?");
    $stmt->bind_param("ssss", $url, $wp, $date);
    $stmt->execute();
}
?>