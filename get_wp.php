<?php

require_once 'database_connection.php';

// Returns if wordpress is detected given a url
function get_wp($url) 
{
    $db = new Database();
    $db->connect();

    // Get the column wp from the table websites and the website with PK $url
    $result = $db->query("SELECT wp FROM websites WHERE url = '$url'");
    $row = $result->fetch_assoc();

    if (empty($row)) {
        require_once 'get_content.php';
        require_once 'find_links.php';
        require_once 'find_wp.php';
        $html = get_content($url);
        $links = find_links($html);
        $wp = find_wp($links);

        // Write the result in the column wp in the table websites for the website $url
        $wpbool = $wp ? '1' : '0';
        $db->query("INSERT INTO websites (url, wp) VALUES ('$url', '$wpbool')");

        echo "The website is not in the database\n";

    } else {
        $wp = $row['wp'] === '1';

        echo "The website is already in the database\n";
    }

    $db->close();

    return $wp;
}
?>