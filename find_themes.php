<?php

require_once 'database_connection.php';

// Returns all the themes of the given url
function find_themes($links, $url)
{
    $themes = [];

    /*
    $parsedUrl = parse_url($url);
    $host = $parsedUrl['host'];
    if (preg_match('/(?:www\.)?(.*?)\.\w+$/', $host, $matches)) { 
        $themes[] = $matches[1]; // Add "example" for www.example.com in the themes slugs array
    };
    */

    $db = new Database();
    $db->connect();

    foreach ($links as $link) {
        if (preg_match('/.*\/themes\/([^\/]*)/', $link, $matches)) {
            $themeSlug = $matches[1];

            // Parse the URL to get the scheme and host
            $parsedUrl = parse_url($link);
            $rootDomain = $parsedUrl['scheme'] . '://' . $parsedUrl['host'];
            $themePath = $rootDomain . '/wp-content/themes/' . $themeSlug; // Todo: search wp content in other paths. Example: example.com/w/wp-content/

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
        $themeInfo = find_theme_info_in_directory($themeSlug);
        if (empty($themeInfo)) {
            $themeInfo = find_theme_info_in_website($themeSlug, $themePath);
        }
        if (empty($themeInfo)) {
            return null;
        }

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

    $url = "https://wordpress.org/themes/" . $themeSlug;
    $html = get_content($url);

    // Return if the page didn't return content
    if ($html === null) {
        return null;
    }

    $doc = new DOMDocument();
    @$doc->loadHTML($html);
    $xpath = new DOMXPath($doc);

    $nodes = $xpath->query('//title');
    $pageTitle = $nodes->item(0)->nodeValue;

    // Returns null if the theme page doesen't exist in worpdress directory
    if (strpos($pageTitle, "All themes") !== false) {
        return null;
    }

    $nodes = $xpath->query('//figure[@class="wp-block-post-featured-image"]/img');
    $screenshot = $nodes->length > 0 ? $nodes->item(0)->getAttribute('src') : "/no-theme-image.svg"; // Return no-theme-image if screenshot is not found.

    $nodes = $xpath->query('//h1');
    if ($nodes->length > 0) {
        $title = $nodes->item(0)->nodeValue;
    } else {
        // Format the title from the slug
        $words = explode('-', $themeSlug);
        $words = array_map('ucfirst', $words);
        $title = implode(' ', $words);
    };

    $nodes = $xpath->query('//a[@class="wp-block-post-author-name__link"]');
    $author = $nodes->length > 0 ? $nodes->item(0)->nodeValue : null;

    $nodes = $xpath->query('//li[@class="is-meta-version"]/span[2]');
    $version = $nodes->length > 0 ? $nodes->item(0)->nodeValue : null;

    $nodes = $xpath->query('//li[@class="is-meta-last-updated"]/span[2]');
    $lastUpdated = $nodes->length > 0 ? $nodes->item(0)->nodeValue : null;

    $nodes = $xpath->query('//li[@class="is-meta-active-installs"]/span[2]');
    $activeInstallations = $nodes->length > 0 ? $nodes->item(0)->nodeValue : null;

    $nodes = $xpath->query('//li[@class="is-meta-requires-wp"]/span[2]');
    $reqWpVersion = $nodes->length > 0 ? $nodes->item(0)->nodeValue : null;

    $nodes = $xpath->query('//li[@class="is-meta-requires-php"]/span[2]');
    $reqPhpVersion = $nodes->length > 0 ? $nodes->item(0)->nodeValue : null;

    $nodes = $xpath->query('//li[@class="is-meta-theme-link"]/a');
    $website = $nodes->length > 0 ? $nodes->item(0)->getAttribute('href') : null;

    $sanatizedWebsite = str_replace(['http://', 'https://'], '', $website);

    $nodes = $xpath->query('//div[contains(@class, "entry-content") and contains(@class, "wp-block-post-content")]//p');
    $description = $nodes->length > 0 ? $nodes->item(0)->nodeValue : "No description provided";
    $description = substr($description, 0, 1000); // Limit the description to 1000 characters

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
        'link' => $url,
    ];

    return $theme;
}

// Returns the theme information in the website given a theme path
function find_theme_info_in_website($themeSlug, $themePath, )
{
    require_once 'get_content.php';

    $styleCssUrl =  $themePath . '/style.css';
    $styleCssContent = get_content($styleCssUrl);

    // Every wordpress theme must have a style.css
    if ($styleCssContent === null) {
        return null;
    }

    preg_match('/Theme Name: (.*)/', $styleCssContent, $matches);
    if (!isset($matches[1])) {
        // Convert "plugin-slug" to "Plugin Slug"
        $words = explode('-', $themeSlug);
        $words = array_map('ucfirst', $words);
        $themeTitle = implode(' ', $words);
    }
    $title = $matches[1] ?? $themeTitle;

    preg_match('/Theme URI: (.*)/', $styleCssContent, $matches);
    
    if (empty($matches[1])) {
        $websiteUrl = $themePath; // Return the url of the analyzed website
    } else {
        $websiteUrl = $matches[1];
    }

    $sanatizedWebsite = $websiteUrl['host'] ?? null;
    $website = $sanatizedWebsite ? $websiteUrl['scheme'] . '://' . $sanatizedWebsite : null;

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

    preg_match('/Description: (.*)/', $styleCssContent, $matches);
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
?>