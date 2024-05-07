<?php

require_once 'database_connection.php';
require_once 'find_plugins.php';

// Returns the plugins of a given url
function get_plugins($url)
{
    $db = new Database();
    $db->connect();

    // Get the column wp from the table websites and the website with PK $url
    $result = $db->query("SELECT plugins FROM websites WHERE url = '$url'");
    $row = $result->fetch_assoc();

    if (empty($row['plugins'])) {
        require_once 'get_content.php';
        require_once 'find_links.php';
        require_once 'find_plugins.php';
        $html = get_content($url);
        $links = find_links($html);
        $plugins = find_plugins($links);

        write_plugin_slugs_to_database($db, $plugins, $url);
      
    } else {
        // Split the string of plugin slugs into an array
        $pluginSlugs = explode(',', $row['plugins']);

        foreach ($pluginSlugs as $pluginSlug) {
            $pluginSlug = trim($pluginSlug);
            $pluginInfo = get_plugin_info($db, $pluginSlug, null);
            $plugins[$pluginSlug] = $pluginInfo;
        }
    }

    $db->close();

    // Convert the associative array to an indexed array
    $plugins = array_values($plugins);

    return $plugins;
}

// Write plugins to database
function write_plugin_slugs_to_database($db, $plugins, $url)
{
    // Convert the array of plugin slugs to a comma-separated string
    $pluginSlugs = implode(',', array_keys($plugins));

    $db->query("UPDATE websites SET plugins = '$pluginSlugs' WHERE url = '$url'");
}

function get_top_plugins($quantity, $page)
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

    $result = $db->query("SELECT * FROM plugins ORDER BY timesAnalyzed DESC LIMIT $quantity OFFSET $offset");

    while ($row = $result->fetch_assoc()) {
        $pluginInfo = [
            'banner' => $row['banner'],
            'icon' => $row['icon'],
            'title' => $row['title'],
            'contributors' => $row['contributors'],
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

        $plugins[] = $pluginInfo;
    }

    $db->close();

    return $plugins;
}
?>