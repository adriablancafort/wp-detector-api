<?php

// Returns all the plugins of the given url
function find_plugins($links)
{
    $plugins = [];

    foreach ($links as $link) {
        if (preg_match('/.*\/plugins\/([^\/]*)/', $link, $matches)) {
            $pluginSlug = $matches[1];

            // Parse the URL to get the scheme and host
            $parsedUrl = parse_url($link);
            $rootDomain = $parsedUrl['scheme'] . '://' . $parsedUrl['host'];
            $pluginPath = $rootDomain . '/wp-content/plugins/' . $pluginSlug;

            if (!array_key_exists($pluginSlug, $plugins)) {
                $pluginInfo = find_plugin_info($pluginSlug, $pluginPath);
                $plugins[$pluginSlug] = $pluginInfo;
            }
        }
    }

    // Convert the associative array to an indexed array
    $plugins = array_values($plugins);

    return $plugins;
}

// Returns the plugin information given a plugin path
function find_plugin_info($pluginSlug, $pluginPath)
{
    require_once 'get_content.php';
    $readmeTxtUrl =  $pluginPath . '/readme.txt';
    $readmeTxtContent = get_content($readmeTxtUrl);
    
    preg_match('/=== (.*) ===/', $readmeTxtContent, $matches);
    // Convert "plugin-slug" to "Plugin Slug"
    $words = explode('-', $pluginSlug);
    $words = array_map('ucfirst', $words);
    $pluginSlug = implode(' ', $words);
    $title = $matches[1] ?? $pluginSlug;

    preg_match('/Contributors: (.*)/', $readmeTxtContent, $matches);
    $author = $matches[1] ?? '';

    preg_match('/Stable tag: (.*)/', $readmeTxtContent, $matches);
    $version = $matches[1] ?? '';

    preg_match('/Donate link: (.*)/', $readmeTxtContent, $matches);
    $website = $matches[1] ?? '';

    $sanatizedWebsite = str_replace(['http://', 'https://'], '', $website);

    preg_match('/Requires at least: (.*)/', $readmeTxtContent, $matches);
    $reqWpVersion = isset($matches[1]) ? $matches[1] . ' or higher' : 'Not specified';

    preg_match('/Tested up to: (.*)/', $readmeTxtContent, $matches);
    $testedWpVersion = $matches[1] ?? '';

    preg_match('/Requires PHP: (.*)/', $readmeTxtContent, $matches);
    $reqPhpVersion = isset($matches[1]) ? $matches[1] . ' or higher' : 'Not specified';

    preg_match('/== Description ==\n\n(.*)/', $readmeTxtContent, $matches);
    $description = $matches[1] ?? '';

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
    ];

    return $plugin;

}

// Returns the banner URL of the plugin
function get_plugin_banner($pluginSlug)
{
    $banner = 'https://ps.w.org/wordpress-seo/assets/banner-772x250.png';
    return $banner;
}

// Returns the icon URL of the plugin
function get_plugin_icon($pluginSlug)
{
    $icon = 'https://ps.w.org/wordpress-seo/assets/icon.svg';
    return $icon;
}
?>