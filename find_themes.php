<?php

require_once 'database_connection.php';

// Returns all the themes of the given url
function find_themes($links)
{
    $themes = [];

    $db = new Database();
    $db->connect();

    foreach ($links as $link) {
        if (preg_match('/.*\/themes\/([^\/]*)/', $link, $matches)) {
            $themeSlug = $matches[1];

            // Parse the URL to get the scheme and host
            $parsedUrl = parse_url($link);
            $rootDomain = $parsedUrl['scheme'] . '://' . $parsedUrl['host'];
            $themePath = $rootDomain . '/wp-content/themes/' . $themeSlug;

            if (!array_key_exists($themeSlug, $themes) && preg_match('/^[a-z\-]+$/', $themeSlug)) {
                $themeInfo = get_theme_info($db, $themeSlug, $themePath);
                if (!empty($themeInfo)) {
                    $themes[$themeSlug] = $themeInfo;
                }
            }
        }
    }

    $db->close();

    return $themes;
}

// Returns the theme information of a given theme slug
function get_theme_info($db, $themeSlug, $themePath)
{
    $result = $db->query("SELECT * FROM themes WHERE slug = '$themeSlug'");
    $row = $result->fetch_assoc();

    if (empty($row)) {
        //$themeInfo = find_theme_info_in_directory($themeSlug);
        //if (empty($themeInfo)) {
        $themeInfo = find_theme_info_in_website($themeSlug, $themePath);
        //}
        //if (empty($themeInfo)) {
        //    return null;
        //}

        $screenshot = $themeInfo['screenshot'];
        $title = $themeInfo['title'];
        $author = $themeInfo['author'];
        $version = $themeInfo['version'];
        $website = $themeInfo['website'];
        $sanatizedWebsite = $themeInfo['sanatizedWebsite'];
        $lastUpdated = $themeInfo['lastUpdated'];
        $activeInstallations = $themeInfo['activeInstallations'];
        $reqWpVersion = $themeInfo['reqWpVersion'];
        $testedWpVersion = $themeInfo['testedWpVersion'];
        $reqPhpVersion = $themeInfo['reqPhpVersion'];
        $description = $themeInfo['description'];
        $link = '';

        // Insert the theme info into the database
        $db->query("INSERT INTO themes (slug, screenshot, title, author, version, website, sanatizedWebsite, lastUpdated, activeInstallations, reqWpVersion, testedWpVersion, reqPhpVersion, description, link, timesAnalyzed, lastAnalyzed) VALUES ('$themeSlug', '$screenshot', '$title', '$author', '$version', '$website', '$sanatizedWebsite', '$lastUpdated', '$activeInstallations', '$reqWpVersion', '$testedWpVersion', '$reqPhpVersion', '$description', '$link', 1, NOW())");

    } else {
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

        // Update timesAnalyzed and lastAnalyzed
        $db->query("UPDATE themes SET timesAnalyzed = timesAnalyzed + 1, lastAnalyzed = NOW() WHERE slug = '$themeSlug'");    
    }

    return $themeInfo;
}

// Returns the theme information in the wordpress directory given a theme slug
function find_theme_info_in_directory($themeSlug)
{
    require_once 'get_content.php';
    $directoryUrl = 'https://wordpress.org/themes/' . $themeSlug;
    $directoryContent = get_content($directoryUrl);

    if (empty($directoryContent)) {
        return null;
    }

    preg_match('/<h2 class="theme-name entry-title">(.*?)<\/h2>/', $directoryContent, $matches);
    $title = $matches[1] ?? $themeTitle;

    preg_match('/<p class="theme_homepage"><a href="(.*?)">Theme Homepage<\/a><\/p>/', $directoryContent, $matches);
    $website = $matches[1] ?? null;

    $sanatizedWebsite = str_replace(['http://', 'https://'], '', $website);

    preg_match('/<div class="theme-author">By <a href="\/themes\/author\/.*?\/"><span class="author">(.*?)<\/span><\/a><\/div>/', $directoryContent, $matches);
    $author = $matches[1] ?? "No author found";

    preg_match('/<p class="version">Version: <strong>(.*?)<\/strong><\/p>/', $directoryContent, $matches);
    $version = $matches[1] ?? null;

    preg_match('/<p class="updated">Last updated: <strong>(.*?)<\/strong><\/p>/', $directoryContent, $matches);
    $lastUpdated = $matches[1] ?? null;

    preg_match('/<p class="active_installs">Active Installations: <strong>(.*?)<\/strong><\/p>/', $directoryContent, $matches);
    $activeInstallations = $matches[1] ?? null;

    preg_match('/<p class="requires">WordPress Version: <strong>(.*?) or higher<\/strong><\/p>/', $directoryContent, $matches);
    $reqWpVersion = isset($matches[1]) ? $matches[1] . ' or higher' : null;

    preg_match('/<p class="requires_php">PHP Version: <strong>(.*?) or higher<\/strong><\/p>/', $directoryContent, $matches);
    $reqPhpVersion = isset($matches[1]) ? $matches[1] . ' or higher' : null;

    preg_match('/<div class="theme-description entry-summary"><p>(.*?)<\/p><\/div>/', $directoryContent, $matches);
    $description = trim($matches[1] ?? "No description provided");

    preg_match('/<img src="(.*?)" srcset/', $directoryContent, $matches);
    $screenshot = $matches[1] ?? "";

    $theme = [
        'screenshot' => $screenshot,
        'title' => $title,
        'author' => $author,
        'version' => $version,
        'website' => $website,
        'sanatizedWebsite' => $sanatizedWebsite,
        'lastUpdated' => $lastUpdated,
        'activeInstallations' => $activeInstallations,
        'reqWpVersion' => $reqWpVersion,
        'testedWpVersion' => null,
        'reqPhpVersion' => $reqPhpVersion,
        'description' => $description,
        'link' => $directoryUrl,
    ];

    return $theme;
}

// Returns the theme information in the website given a theme path
function find_theme_info_in_website($themeSlug, $themePath)
{
    require_once 'get_content.php';
    $styleCssUrl =  $themePath . '/style.css';
    $styleCssContent = get_content($styleCssUrl);

    preg_match('/Theme Name: (.*)/', $styleCssContent, $matches);
    if (!isset($matches[1])) {
        // Convert "plugin-slug" to "Plugin Slug"
        $words = explode('-', $themeSlug);
        $words = array_map('ucfirst', $words);
        $themeTitle = implode(' ', $words);
    }
    $title = $matches[1] ?? $themeTitle;

    preg_match('/Theme URI: (.*)/', $styleCssContent, $matches);
    $website = $matches[1] ?? null;

    $sanatizedWebsite = str_replace(['http://', 'https://'], '', $website);

    preg_match('/Author: (.*)/', $styleCssContent, $matches);
    $author = $matches[1] ?? "No author found";

    preg_match('/Version: (.*)/', $styleCssContent, $matches);
    $version = $matches[1] ?? null;

    preg_match('/Requires at least: (.*)/', $styleCssContent, $matches);
    $reqWpVersion = isset($matches[1]) ? $matches[1] . ' or higher' : null;

    preg_match('/Tested up to: (.*)/', $styleCssContent, $matches);
    $testedWpVersion = $matches[1] ?? null;

    preg_match('/Requires PHP: (.*)/', $styleCssContent, $matches);
    $reqPhpVersion = isset($matches[1]) ? $matches[1] . ' or higher' : null;

    preg_match('/Description: (.*)Version:/', $styleCssContent, $matches);
    $description = trim($matches[1] ?? "No description provided");

    $screenshot = get_theme_screenshot_in_website($themePath);

    $theme = [
        'screenshot' => $screenshot,
        'title' => $title,
        'author' => $author,
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

    return $theme;
}

// Returns the screenshot URL of the theme
function get_theme_screenshot_in_website($themePath)
{
    $screenshotUrls = [
        $themePath . '/screenshot.png',
        $themePath . '/screenshot.jpg'
    ];

    foreach ($screenshotUrls as $url) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($statusCode == 200) {
            return $url;
        }
    }

    return '/no-theme-screenshot.svg';
}
