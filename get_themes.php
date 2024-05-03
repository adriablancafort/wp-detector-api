<?php

require_once 'database_connection.php';

// Returns the plugins of a given url
function get_themes($url)
{
    /*
    $db = new Database();
    $db->connect();

    // Get the column wp from the table websites and the website with PK $url
    $result = $db->query("SELECT themes FROM websites WHERE url = '$url'");
    $row = $result->fetch_assoc();

    if (empty($row)) {
    */
        require_once 'get_content.php';
        require_once 'find_links.php';
        require_once 'find_themes.php';
        $html = get_content($url);
        $links = find_links($html);
        $themes = find_themes($links);

        // Convert the associative array to an indexed array
        $themes = array_values($themes);

    /*
    } else {
        $themes = [];
        $themeSlugs = $row; // Correct to make an iterable array of slugs
        
        foreach ($themeSlugs as $themeSlug) {

            $result = $db->query("SELECT * FROM themes WHERE slug = '$themeSlug'");
            $row = $result->fetch_assoc();

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

            $themes[$themeSlug] = $themeInfo;
        }
    }

    $db->close();
    */

    return $themes;
}
?>