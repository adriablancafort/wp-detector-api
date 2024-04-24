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

            if (!array_key_exists($themeSlug, $themes)) {

                $result = $db->query("SELECT * FROM themes WHERE slug = '$themeSlug'");
                $row = $result->fetch_assoc();

                if (empty($row)) {
                    $themeInfo = find_theme_info($themeSlug, $themePath);

                    $banner = $themeInfo['banner'];
                    $title = $themeInfo['title'];
                    $author = $themeInfo['author'];
                    $version = $themeInfo['version'];
                    $website = $themeInfo['website'];
                    $sanatizedWebsite = $themeInfo['sanatizedWebsite'];
                    $reqWpVersion = $themeInfo['reqWpVersion'];
                    $testedWpVersion = $themeInfo['testedWpVersion'];
                    $reqPhpVersion = $themeInfo['reqPhpVersion'];
                    $description = $themeInfo['description'];
                    $link = '';
                    $times_analyzed = 1;

                    // Insert the theme info into the database
                    $db->query("INSERT INTO themes (slug, banner, title, author, version, website, sanatizedWebsite, reqWpVersion, testedWpVersion, reqPhpVersion, description, link, times_analyzed) VALUES ('$themeSlug', '$banner', '$title', '$author', '$version', '$website', '$sanatizedWebsite', '$reqWpVersion', '$testedWpVersion', '$reqPhpVersion', '$description', '$link', '$times_analyzed')");

                } else {
                    $themeInfo = [
                        'banner' => $row['banner'],
                        'title' => $row['title'],
                        'author' => $row['author'],
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

                $themes[$themeSlug] = $themeInfo;
            }
        }
    }

    $db->close();

    return $themes;
}

// Returns the theme information given a theme path
function find_theme_info($themeSlug, $themePath)
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

    $banner = get_theme_banner($themePath);
    
    $theme = [
        'banner' => $banner,
        'title' => $title,
        'author' => $author,
        'version' => $version,
        'website' => $website,
        'sanatizedWebsite' => $sanatizedWebsite,
        'reqWpVersion' => $reqWpVersion,
        'testedWpVersion' => $testedWpVersion,
        'reqPhpVersion' => $reqPhpVersion,
        'description' => $description,
        'link' => null,
    ];

    return $theme;
}

// Returns the banner URL of the theme
function get_theme_banner($themePath)
{
    $bannerUrls = [
        $themePath . '/screenshot.png',
        $themePath . '/screenshot.jpg'
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

    return '/no-theme-image.svg';
}
?>