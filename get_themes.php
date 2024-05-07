<?php

require_once 'database_connection.php';
require_once 'find_themes.php';

// Returns the plugins of a given url
function get_themes($url)
{
    $db = new Database();
    $db->connect();

    // Get the column wp from the table websites and the website with PK $url
    $result = $db->query("SELECT themes FROM websites WHERE url = '$url'");
    $row = $result->fetch_assoc();

    if (empty($row['themes'])) {
        require_once 'get_content.php';
        require_once 'find_links.php';
        $html = get_content($url);
        $links = find_links($html);
        $themes = find_themes($links);
        
        write_theme_slugs_to_database($db, $themes, $url);

    } else {
        // Split the string of theme slugs into an array
        $themeSlugs = explode(',', $row['themes']);

        foreach ($themeSlugs as $themeSlug) {
            $themeSlug = trim($themeSlug);
            $themeInfo = get_theme_info($db, $themeSlug, null);
            $themes[$themeSlug] = $themeInfo;
        }
    }

    $db->close();

    // Convert the associative array to an indexed array
    $themes = array_values($themes);

    return $themes;
}

// Write themes to database
function write_theme_slugs_to_database($db, $themes, $url)
{
    // Convert the array of theme slugs to a comma-separated string
    $themeSlugs = implode(',', array_keys($themes));

    $db->query("UPDATE websites SET themes = '$themeSlugs' WHERE url = '$url'");
}

function get_top_themes($quantity, $page)
{
    // Sanitize and validate the quantity
    $quantity = filter_var($quantity, FILTER_VALIDATE_INT);

    if ($quantity === false) {
        $quantity = 5;
    }

    // Calculate the offset
    $offset = ($page - 1) * $quantity;

    $db = new Database();
    $db->connect();

    $result = $db->query("SELECT * FROM themes ORDER BY timesAnalyzed DESC LIMIT $quantity OFFSET $offset");

    while ($row = $result->fetch_assoc()) {
        $themeInfo = [
            'screenshot' => $row['screenshot'],
            'title' => $row['title'],
            'author' => $row['author'],
            'version' => $row['version'],
            'website' => $row['website'],
            'sanatizedWebsite' => $row['sanatizedWebsite'],
            'lastUpdated' => $row['lastUpdated'],
            'activeInstallations' => $row['activeInstallations'],
            'reqWpVersion' => $row['reqWpVersion'],
            'testedWpVersion' => $row['testedWpVersion'],
            'reqPhpVersion' => $row['reqPhpVersion'],
            'description' => $row['description'],
            'link' => $row['link'],
        ];

        $themes[] = $themeInfo;
    }

    $db->close();

    return $themes;
}
?>