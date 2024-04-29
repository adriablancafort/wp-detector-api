<?php

require_once 'database_connection.php';

// Returns the plugins of a given url
function get_plugins($url)
{
    $db = new Database();
    $db->connect();

    // Get the column wp from the table websites and the website with PK $url
    $result = $db->query("SELECT plugins FROM websites WHERE url = '$url'");
    $row = $result->fetch_assoc();

    if (empty($row)) {
        require_once 'get_content.php';
        require_once 'find_links.php';
        require_once 'find_plugins.php';
        $html = get_content($url);
        $links = find_links($html);
        $plugins = find_plugins($links);

        // Convert the associative array to an indexed array
        $plugins = array_values($plugins);        
    } else {
        $plugins = [];
        $pluginSlugs = $row; // Correct to make an iterable array of slugs

        foreach ($pluginSlugs as $pluginSlug) {

            $result = $db->query("SELECT * FROM plugins WHERE slug = '$pluginSlug'");
            $row = $result->fetch_assoc();

            $pluginInfo = [
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

            $plugins[$pluginSlug] = $pluginInfo;
        }
    }

    $db->close();

    return $plugins;
}
?>