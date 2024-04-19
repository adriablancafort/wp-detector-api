<?php

// Returns all the themes of the given url
function find_themes($links)
{
    $themes = [];

    foreach ($links as $link) {
        if (preg_match('/.*\/themes\/([^\/]*)/', $link, $matches)) {
            $themeSlug = $matches[1];

            // Parse the URL to get the scheme and host
            $parsedUrl = parse_url($link);
            $rootDomain = $parsedUrl['scheme'] . '://' . $parsedUrl['host'];
            $themePath = $rootDomain . '/wp-content/themes/' . $themeSlug;

            if (!array_key_exists($themeSlug, $themes)) {
                $themeInfo = find_theme_info($themePath);
                $themes[$themeSlug] = $themeInfo;
            }
        }
    }

    // Convert the associative array to an indexed array
    $themes = array_values($themes);

    return $themes;
}

// Returns the theme information given a theme path
function find_theme_info($themePath)
{
    require_once 'get_content.php';
    $styleCssUrl =  $pluginPath . '/readme.txt';
    $styleCssContent = get_content($styleCssUrl);

    preg_match('/Theme Name: (.*)/', $styleCssContent, $matches);
    $title = $matches[1] ?? '';

    preg_match('/Theme URI: (.*)/', $styleCssContent, $matches);
    $website = $matches[1] ?? 'No website specified';

    $sanatizedWebsite = str_replace(['http://', 'https://'], '', $website);

    preg_match('/Author: (.*)/', $styleCssContent, $matches);
    $author = $matches[1] ?? 'No author specified';

    preg_match('/Version: (.*)/', $styleCssContent, $matches);
    $version = $matches[1] ?? '';

    preg_match('/Requires at least: (.*)/', $styleCssContent, $matches);
    $reqWpVersion = $matches[1] . ' or higher' ?? 'Not specified';

    preg_match('/Tested up to: (.*)/', $styleCssContent, $matches);
    $testedWpVersion = $matches[1] ?? 'Not specified';

    preg_match('/Requires PHP: (.*)/', $styleCssContent, $matches);
    $reqPhpVersion = $matches[1] . ' or higher' ?? 'Not specified';

    preg_match('/Description: (.*)Version:/', $styleCssContent, $matches);
    $description = trim($matches[1] ?? 'Not available');

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
        'description' => $styleCssContent,
        // No 'link' since it won't be afiliate
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
        $headers = @get_headers($url);
        if ($headers && strpos($headers[0], '200') !== false) {
            return $url;
        }
    }

    return '/unknown-theme-banner.webp';
}
?>