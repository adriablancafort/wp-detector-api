<?php

require_once 'database_connection.php';

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

            if (!array_key_exists($pluginSlug, $plugins) && preg_match('/^[a-z\-]+$/', $pluginSlug)) {
                $pluginInfo = get_plugin_info($db, $pluginSlug, $pluginPath);
                if (!empty($pluginInfo)) {
                    $plugins[$pluginSlug] = $pluginInfo;
                }
            }
        }
    }

    $db->close();

    return $plugins;
}

// Returns the plugin information of a given plugin slug
function get_plugin_info($db, $pluginSlug, $pluginPath)
{
    $result = $db->query("SELECT * FROM plugins WHERE slug = '$pluginSlug'");
    $row = $result->fetch_assoc();

    if (empty($row)) {
        //$pluginInfo = find_plugin_info_in_directory($pluginSlug);
        //if (empty($pluginInfo)) {
        $pluginInfo = find_plugin_info_in_website($pluginSlug, $pluginPath);
        //}
        //if (empty($pluginInfo)) {
        //    return null;
        //}


        $banner = $pluginInfo['banner'];
        $icon = $pluginInfo['icon'];
        $title = $pluginInfo['title'];
        $contributors = $pluginInfo['contributors'];
        $version = $pluginInfo['version'];
        $website = $pluginInfo['website'];
        $sanatizedWebsite = $pluginInfo['sanatizedWebsite'];
        $lastUpdated = $pluginInfo['lastUpdated'];
        $activeInstallations = $pluginInfo['activeInstallations'];
        $reqWpVersion = $pluginInfo['reqWpVersion'];
        $testedWpVersion = $pluginInfo['testedWpVersion'];
        $reqPhpVersion = $pluginInfo['reqPhpVersion'];
        $description = $pluginInfo['description'];
        $link = '';

        // Insert the plugin info into the database
        $db->query("INSERT INTO plugins (slug, banner, icon, title, contributors, version, website, sanatizedWebsite, lastUpdated, activeInstallations, reqWpVersion, testedWpVersion, reqPhpVersion, description, link, timesAnalyzed, lastAnalyzed) VALUES ('$pluginSlug', '$banner', '$icon', '$title', '$contributors', '$version', '$website', '$sanatizedWebsite', '$lastUpdated', '$activeInstallations', '$reqWpVersion', '$testedWpVersion', '$reqPhpVersion', '$description', '$link',  1, NOW())");
    
    } else {
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

        // Update timesAnalyzed and lastAnalyzed
        $db->query("UPDATE plugins SET timesAnalyzed = timesAnalyzed + 1, lastAnalyzed = NOW() WHERE slug = '$pluginSlug'");
    }

    return $pluginInfo;
}

// Returns the plugin information in the wordpress directory given a plugin slug
function find_plugin_info_in_directory($pluginsSlug)
{
    require_once 'get_content.php';
    $directoryUrl = 'https://wordpress.org/plugins/' . $pluginsSlug;
    $directoryContent = get_content($directoryUrl);

    if (empty($directoryContent)) {
        return null;
    }

    preg_match('/<h1 class="plugin-title">(.*?)<\/h1>/', $directoryContent, $matches);
    $title = $matches[1] ?? null;

    preg_match('/<span class="byline">By <span class="author vcard"><a class="url fn n" rel="nofollow" href=".*?">(.*?)<\/a><\/span><\/span>/', $readmeTxtContent, $matches);
    $author = $matches[1] ?? "No contributors found";

    preg_match('/<li>\s*Version: <strong>(.*?)<\/strong>\s*<\/li>/', $readmeTxtContent, $matches);
    $version = $matches[1] ?? null;

    preg_match('/<li>\s*Last updated: <strong><span>(.*?)<\/span><\/strong>\s*<\/li>/', $directoryContent, $matches);
    $lastUpdated = $matches[1] ?? null;

    preg_match('/<li>\s*Active installations: <strong>(.*?)<\/strong>\s*<\/li>/', $directoryContent, $matches);
    $activeInstallations = $matches[1] ?? null;

    preg_match('/<a href="(.*?)" rel="nofollow">Support<\/a>/', $readmeTxtContent, $matches);
    $website = $matches[1] ?? null;

    $sanatizedWebsite = str_replace(['http://', 'https://'], '', $website);

    preg_match('/<li>\s*WordPress Version:\s*<strong>\s*(.*?) or higher\s*<\/strong>\s*<\/li>/', $readmeTxtContent, $matches);
    $reqWpVersion = isset($matches[1]) ? $matches[1] . ' or higher' : null;

    preg_match('/<li>\s*Tested up to: <strong>(.*?)<\/strong>\s*<\/li>/', $readmeTxtContent, $matches);
    $testedWpVersion = $matches[1] ?? null;

    preg_match('/<li>\s*PHP Version:\s*<strong>\s*(.*?) or higher\s*<\/strong>\s*<\/li>/', $readmeTxtContent, $matches);
    $reqPhpVersion = isset($matches[1]) ? $matches[1] . ' or higher' : null;

    preg_match('/== Description ==\n\n(.*)/', $readmeTxtContent, $matches);
    $description = $matches[1] ?? 'No description provided';

    preg_match('/<img class="plugin-icon" src="(.*?)">/', $directoryContent, $matches);
    $icon = $matches[1] ?? '/no-plugin-icon.svg';

    preg_match("/background-image: url\('(.*?)'\);/", $directoryContent, $matches);
    $banner = $matches[1] ?? '/no-plugin-banner.svg';

    $plugin = [
        'banner' => $banner,
        'icon' => $icon,
        'title' => $title,
        'contributors' => $author,
        'version' => $version,
        'website' => $website,
        'sanatizedWebsite' => $sanatizedWebsite,
        'lastUpdated' => $lastUpdated,
        'activeInstallations' => $activeInstallations,
        'reqWpVersion' => $reqWpVersion,
        'testedWpVersion' => $testedWpVersion,
        'reqPhpVersion' => $reqPhpVersion,
        'description' => $description,
        'link' => $directoryUrl,
    ];

    return $plugin;
}

// Returns the plugin information given a plugin path
function find_plugin_info_in_website($pluginSlug, $pluginPath)
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

    $banner = '/no-plugin-banner.svg';
    $icon = '/no-plugin-icon.svg';

    $plugin = [
        'banner' => $banner,
        'icon' => $icon,
        'title' => $title,
        'contributors' => $author,
        'version' => $version,
        'website' => $website,
        'sanatizedWebsite' => $sanatizedWebsite,
        'lastUpdated' => null,
        'activeInstallations' => null,
        'reqWpVersion' => $reqWpVersion,
        'testedWpVersion' => $testedWpVersion,
        'reqPhpVersion' => $reqPhpVersion,
        'description' => $description,
        'link' => null,
    ];

    return $plugin;
}
?>