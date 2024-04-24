<?php

// Returns all the plugins of the given url
function find_plugins($links)
{
    $plugins = [];

    $db = new Database();
    $db->connect();

    foreach ($links as $link) {
        if (preg_match('/.*\/plugins\/([^\/]*)/', $link, $matches)) {
            $pluginSlug = $matches[1];

            // Parse the URL to get the scheme and host
            $parsedUrl = parse_url($link);
            $rootDomain = $parsedUrl['scheme'] . '://' . $parsedUrl['host'];
            $pluginPath = $rootDomain . '/wp-content/plugins/' . $pluginSlug;

            if (!array_key_exists($pluginSlug, $plugins)) {

                $result = $db->query("SELECT * FROM plugins WHERE slug = '$pluginSlug'");
                $row = $result->fetch_assoc();

                if (empty($row)) {
                    $pluginInfo = find_plugin_info($pluginSlug, $pluginPath);

                    $banner = $pluginInfo['banner'];
                    $icon = $pluginInfo['icon'];
                    $title = $pluginInfo['title'];
                    $contributors = $pluginInfo['contributors'];
                    $version = $pluginInfo['version'];
                    $website = $pluginInfo['website'];
                    $sanatizedWebsite = $pluginInfo['sanatizedWebsite'];
                    $reqWpVersion = $pluginInfo['reqWpVersion'];
                    $testedWpVersion = $pluginInfo['testedWpVersion'];
                    $reqPhpVersion = $pluginInfo['reqPhpVersion'];
                    $description = $pluginInfo['description'];
                    $link = '';
                    $times_analyzed = 1;

                    // Insert the theme info into the database
                    $db->query("INSERT INTO plugins (slug, banner, icon, title, contributors, version, website, sanatizedWebsite, reqWpVersion, testedWpVersion, reqPhpVersion, description, link, times_analyzed) VALUES ('$pluginSlug', '$banner', '$icon', '$title', '$contributors', '$version', '$website', '$sanatizedWebsite', '$reqWpVersion', '$testedWpVersion', '$reqPhpVersion', '$description', '$link', '$times_analyzed')");

                } else {
                    $pluginInfo = [
                        'banner' => $row['banner'],
                        'icon' => $row['icon'],
                        'title' => $row['title'],
                        'contributors' => $row['contributors'],
                        'version' => $row['version'],
                        'website' => $row['website'],
                        'sanatizedWebsite' => $row['sanatizedWebsite'],
                        'reqWpVersion' => $row['reqWpVersion'],
                        'testedWpVersion' => $row['testedWpVersion'],
                        'reqPhpVersion' => $row['reqPhpVersion'],
                        'description' => $row['description'],
                        'link' => $row['link'],
                    ];
                }

                $plugins[$pluginSlug] = $pluginInfo;
            }
        }
    }

    $db->close();

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

    return '/no-plugin-banner.svg';
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

    return '/no-plugin-icon.svg';
}
?>