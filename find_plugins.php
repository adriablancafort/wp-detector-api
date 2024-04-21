<?php

// Returns all the plugins of the given url
function find_plugins($links)
{
    $plugins = [];

    //$conn = open_database_connection();

    foreach ($links as $link) {
        if (preg_match('/.*\/plugins\/([^\/]*)/', $link, $matches)) {
            $pluginSlug = $matches[1];

            // Parse the URL to get the scheme and host
            $parsedUrl = parse_url($link);
            $rootDomain = $parsedUrl['scheme'] . '://' . $parsedUrl['host'];
            $pluginPath = $rootDomain . '/wp-content/plugins/' . $pluginSlug;

            if (!array_key_exists($pluginSlug, $plugins)) {
                //$pluginInfo = database_read_plugin($conn, $pluginSlug);
                //if (empty($pluginInfo)) {
                    $pluginInfo = find_plugin_info($pluginSlug, $pluginPath);
                    //database_write_plugin($conn, $pluginSlug, $pluginInfo);
                //}
                $plugins[$pluginSlug] = $pluginInfo;
            }
        }
    }

    //close_database_connection($conn);

    return $plugins;
}

// Returns the plugin information given a plugin path
function find_plugin_info($pluginSlug, $pluginPath)
{
    require_once 'get_content.php';
    $readmeTxtUrl =  $pluginPath . '/readme.txt';
    $readmeTxtContent = get_content($readmeTxtUrl);
    
    preg_match('/=== (.*) ===/', $readmeTxtContent, $matches);
    if (!isset($matches[1])) {
        // Convert "plugin-slug" to "Plugin Slug"
        $words = explode('-', $pluginSlug);
        $words = array_map('ucfirst', $words);
        $pluginTitle = implode(' ', $words);
    }
    $title = $matches[1] ?? $pluginTitle;

    preg_match('/Contributors: (.*)/', $readmeTxtContent, $matches);
    $author = $matches[1] ?? "No contributors found";

    preg_match('/Stable tag: (.*)/', $readmeTxtContent, $matches);
    $version = $matches[1] ?? null;

    preg_match('/Donate link: (.*)/', $readmeTxtContent, $matches);
    $website = $matches[1] ?? null;

    $sanatizedWebsite = str_replace(['http://', 'https://'], '', $website);

    preg_match('/Requires at least: (.*)/', $readmeTxtContent, $matches);
    $reqWpVersion = isset($matches[1]) ? $matches[1] . ' or higher' : null;

    preg_match('/Tested up to: (.*)/', $readmeTxtContent, $matches);
    $testedWpVersion = $matches[1] ?? null;

    preg_match('/Requires PHP: (.*)/', $readmeTxtContent, $matches);
    $reqPhpVersion = isset($matches[1]) ? $matches[1] . ' or higher' : null;

    preg_match('/== Description ==\n\n(.*)/', $readmeTxtContent, $matches);
    $description = $matches[1] ?? 'No description provided';

    $banner = get_plugin_banner($pluginSlug);
    $icon = get_plugin_icon($pluginSlug);

    $plugin = [
        'banner' => $banner,
        'icon' => $icon,
        'title' => $title,
        'contributors' => $author,
        'version' => $version,
        'website' => $website,
        'sanatizedWebsite' => $sanatizedWebsite,
        'reqWpVersion' => $reqWpVersion,
        'testedWpVersion' => $testedWpVersion,
        'reqPhpVersion' => $reqPhpVersion,
        'description' => $description,
        'link' => null,
    ];

    // Write plugin to database

    return $plugin;
}

// Returns the banner URL of the plugin
function get_plugin_banner($pluginSlug)
{
    $baseUrl = 'https://ps.w.org/' . $pluginSlug . '/assets/';

    $bannerUrls = [
        $baseUrl . 'banner-1544x500.png',
        $baseUrl . 'banner-1544x500.jpg',
        $baseUrl . 'banner-772x250.png',
        $baseUrl . 'banner-772x250.jpg',
    ];

    foreach ($bannerUrls as $url) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($statusCode == 200) {
            return $url;
        }
    }

    return '/no-plugin-banner.png';
}

// Returns the icon URL of the plugin
function get_plugin_icon($pluginSlug)
{
    $baseUrl = 'https://ps.w.org/' . $pluginSlug . '/assets/';

    $iconUrls = [
        $baseUrl . 'icon.svg',
        $baseUrl . 'icon-128x128.png',
        $baseUrl . 'icon-128x128.gif',
        $baseUrl . 'icon-256x256.png',
        $baseUrl . 'icon-256x256.gif',
    ];

    foreach ($iconUrls as $url) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($statusCode == 200) {
            return $url;
        }
    }

    return '/no-plugin-icon.png';
}

function database_read_plugin($conn, $pluginSlug)
{
    // Read pluginInfo associated with a pluginSlug in the plugins table
    return null;
}

function database_write_plugin($conn, $pluginSlug, $pluginInfo)
{
    // Write pluginInfo associated with a pluginSlug in the plugins table
    return null;
}
?>