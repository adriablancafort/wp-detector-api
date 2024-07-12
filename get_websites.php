<?php

require_once 'database_connection.php';
require_once 'get_themes.php';
require_once 'get_plugins.php';

function get_websites_themes()
{
    $db = new Database();
    $db->connect();

    // Select the required data from the database
    $result = $db->query("SELECT url, wp, timesAnalyzed, lastAnalyzed FROM websites");

    $websitesThemes = [];

    while ($row = $result->fetch_assoc()) {
        // Check if the website uses WordPress
        if ($row['wp']) {
            // Get themes for the website
            $themes = get_themes($row['url']);

            // Store the website data along with themes
            $websitesThemes[] = [
                'url' => $row['url'],
                'timesAnalyzed' => $row['timesAnalyzed'],
                'lastAnalyzed' => $row['lastAnalyzed'],
                'themes' => $themes
            ];
        }
    }

    $db->close();

    return $websitesThemes;
}

function get_websites_plugins()
{
    $db = new Database();
    $db->connect();

    // Select the required data from the database
    $result = $db->query("SELECT url, wp, timesAnalyzed, lastAnalyzed FROM websites");

    $websitesPlugins = [];

    while ($row = $result->fetch_assoc()) {
        // Check if the website uses WordPress
        if ($row['wp']) {
            // Get plugins for the website
            $plugins = get_plugins($row['url']);

            $websitesPlugins[] = [
                'url' => $row['url'],
                'timesAnalyzed' => $row['timesAnalyzed'],
                'lastAnalyzed' => $row['lastAnalyzed'],
                'plugins' => $plugins
            ];
        }
    }

    $db->close();

    return $websitesPlugins;
}
?>