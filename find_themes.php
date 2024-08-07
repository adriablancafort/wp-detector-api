<?php

require_once 'database_connection.php';

// Returns all the themes of the given url
function find_themes($links, $url)
{
    $themes = [];

    $parsedUrl = parse_url($url);
    $rootDomain = $parsedUrl['scheme'] . '://' . $parsedUrl['host'] . '/wp-content/themes/'; // Todo: search wp content in other paths. Example: example.com/w/wp-content/

    $db = new Database();
    $db->connect();

    foreach ($links as $link) {
        if (preg_match('/.*\/themes\/([^\/]*)/', $link, $matches)) {
            $themeSlug = $matches[1];

            if (!array_key_exists($themeSlug, $themes) && preg_match('/^[a-z\-_]+$/', $themeSlug)) {

                $themePath = $rootDomain . $themeSlug;
                $themeInfo = get_theme_info($db, $themeSlug, $themePath);

                if (!empty($themeInfo)) {
                    $themes[$themeSlug] = $themeInfo;
                }
            }
        }
    }

    // Check the domain name as theme slug candidate
    $host = $parsedUrl['host'];
    if (preg_match('/(?:www\.)?(.*?)\.\w+$/', $host, $matches)) {
        if (!array_key_exists($matches[1], $themes)) {

            $themePath =  $rootDomain . $matches[1];

            // Check if the plugin exists in the website
            if (find_theme_info_in_website($themePath, true)) {
                $themeInfo = get_theme_info($db, $matches[1], $themePath);

                if (!empty($themeInfo)) {
                    $themes[$themeSlug] = $themeInfo;
                }
            }
        }
    };

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
            $themeInfo = find_theme_info_in_website($themePath);
        }
        if (empty($themeInfo)) {
            return null; // False positive
        }

        // Replace with NULL if the value is null or transform into strings
        $screenshot = isset($themeInfo['screenshot']) ? "'" . $themeInfo['screenshot'] . "'" : "NULL";
        $title = isset($themeInfo['title']) ? "'" . $themeInfo['title'] . "'" : "NULL";
        $author = isset($themeInfo['author']) ? "'" . $themeInfo['author'] . "'" : "NULL";
        $version = isset($themeInfo['version']) ? "'" . $themeInfo['version'] . "'" : "NULL";
        $website = isset($themeInfo['website']) ? "'" . $themeInfo['website'] . "'" : "NULL";
        $sanatizedWebsite = isset($themeInfo['sanatizedWebsite']) ? "'" . $themeInfo['sanatizedWebsite'] . "'" : "NULL";
        $lastUpdated = isset($themeInfo['lastUpdated']) ? "'" . $themeInfo['lastUpdated'] . "'" : "NULL";
        $activeInstallations = isset($themeInfo['activeInstallations']) ? "'" . $themeInfo['activeInstallations'] . "'" : "NULL";
        $reqWpVersion = isset($themeInfo['reqWpVersion']) ? "'" . $themeInfo['reqWpVersion'] . "'" : "NULL";
        $testedWpVersion = isset($themeInfo['testedWpVersion']) ? "'" . $themeInfo['testedWpVersion'] . "'" : "NULL";
        $reqPhpVersion = isset($themeInfo['reqPhpVersion']) ? "'" . $themeInfo['reqPhpVersion'] . "'" : "NULL";
        $description = isset($themeInfo['description']) ? "'" . $themeInfo['description'] . "'" : "NULL";
        $link = isset($themeInfo['link']) ? "'" . $themeInfo['link'] . "'" : "NULL";

        // Insert the theme info into the database
        $db->query("INSERT INTO themes (slug, screenshot, title, author, version, website, sanatizedWebsite, lastUpdated, activeInstallations, reqWpVersion, testedWpVersion, reqPhpVersion, description, link, timesAnalyzed, lastAnalyzed) VALUES ('$themeSlug', $screenshot, $title, $author, $version, $website, $sanatizedWebsite, $lastUpdated, $activeInstallations, $reqWpVersion, $testedWpVersion, $reqPhpVersion, $description, $link, 1, NOW())");
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

    // Overide null fields with the desired values
    $themeInfo['screenshot'] = $themeInfo['screenshot'] ?? "/no-theme-screenshot.svg";
    $themeInfo['description'] = $themeInfo['description'] ?? "No description provided";
    $themeInfo['author'] = $themeInfo['author'] ?? "No author found";

    return $themeInfo;
}

// Returns the plugin information in the wordpress directory given a plugin slug
function find_theme_info_in_api($themeSlug)
{
    $url = "https://api.wordpress.org/themes/info/1.2/?action=theme_information&request[slug]=" . $themeSlug;

    $json = file_get_contents($url);

    // Decode the JSON response into an associative array
    $data = json_decode($json, true);

    // Return if the theme is not available in the wordpress directory
    if (isset($data['error'])) {
        return null;
    }

    $fullDescription = $data['sections']['description'] ?? '';
    $description = substr($fullDescription, 0, 1000);

    $theme = [
        'screenshot' => $data['screenshot_url'] ?? '',
        'title' => $data['name'] ?? '',
        'author' => $data['author']['author'] ?? '',
        'version' => $data['version'] ?? '',
        'website' => $data['homepage'] ?? '',
        'sanatizedWebsite' => filter_var($data['homepage'] ?? '', FILTER_SANITIZE_URL),
        'lastUpdated' => $data['last_updated'] ?? '',
        'activeInstallations' => $data['downloaded'] ?? 0,
        'reqWpVersion' => $data['requires'] ?? '',
        'testedWpVersion' => $data['tested'] ?? '',
        'reqPhpVersion' => $data['requires_php'] ?? '',
        'description' => $description,
        'link' => $data['download_link'] ?? '',
    ];

    return $theme;
}

// Returns the theme information in the wordpress directory given a theme slug
function find_theme_info_in_directory($themeSlug)
{
    require_once 'get_content.php';

    $url = "https://wordpress.org/themes/" . $themeSlug;
    $html = get_content($url);

    // Return if the page didn't return content
    if (empty($html)) {
        return null;
    }

    $doc = new DOMDocument();
    @$doc->loadHTML($html);
    $xpath = new DOMXPath($doc);

    $nodes = $xpath->query('//title');
    $pageTitle = trim($nodes->item(0)->nodeValue);

    // Returns null if the theme page doesen't exist in worpdress directory (the title will be "All themes ...")
    if (strpos($pageTitle, "All themes") !== false) {
        return null;
    }

    $nodes = $xpath->query('//figure[@class="wp-block-post-featured-image"]/img');
    $screenshot = $nodes->length > 0 ? $nodes->item(0)->getAttribute('src') : null;

    $nodes = $xpath->query('//h1');
    if ($nodes->length > 0) {
        $title = trim($nodes->item(0)->nodeValue);
    } else {
        // Format the title from the slug
        $words = explode('-', $themeSlug);
        $words = array_map('ucfirst', $words);
        $title = implode(' ', $words);
    };

    $nodes = $xpath->query('//a[@class="wp-block-post-author-name__link"]');
    $author = $nodes->length > 0 ? trim(trim($nodes->item(0)->nodeValue)) : null;

    $nodes = $xpath->query('//li[@class="is-meta-version"]/span[2]');
    $version = $nodes->length > 0 ? trim($nodes->item(0)->nodeValue) : null;

    $nodes = $xpath->query('//li[@class="is-meta-last-updated"]/span[2]');
    $lastUpdated = $nodes->length > 0 ? trim($nodes->item(0)->nodeValue) : null;

    $nodes = $xpath->query('//li[@class="is-meta-active-installs"]/span[2]');
    $activeInstallations = $nodes->length > 0 ? trim($nodes->item(0)->nodeValue) : null;

    $nodes = $xpath->query('//li[@class="is-meta-requires-wp"]/span[2]');
    $reqWpVersion = $nodes->length > 0 ? trim($nodes->item(0)->nodeValue) : null;

    $nodes = $xpath->query('//li[@class="is-meta-requires-php"]/span[2]');
    $reqPhpVersion = $nodes->length > 0 ? trim($nodes->item(0)->nodeValue) : null;

    $website = null;
    $sanatizedWebsite = null;

    $nodes = $xpath->query('//li[@class="is-meta-theme-link"]/a');

    // If a website is found, sanatize it
    if ($nodes->length > 0) {
        $websiteUrl = $nodes->item(0)->getAttribute('href');
        $parsedUrl = parse_url($websiteUrl);
        $sanatizedWebsite = $parsedUrl['host'] ?? null;
        $website = $sanatizedWebsite ? $parsedUrl['scheme'] . '://' . $sanatizedWebsite : null;
    }

    $nodes = $xpath->query('//div[contains(@class, "entry-content") and contains(@class, "wp-block-post-content")]//p');
    $description = $nodes->length > 0 ? trim($nodes->item(0)->nodeValue) : null;
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
function find_theme_info_in_website($themePath, $returnBool = false)
{
    require_once 'get_content.php';

    $styleCssUrl =  $themePath . '/style.css';
    $styleCssContent = get_content($styleCssUrl);

    // Every wordpress theme must have a style.css
    if (empty($styleCssContent)) {
        return null;
    }

    preg_match('/Theme Name: (.*)/', $styleCssContent, $matches);
    if (isset($matches[1])) {
        // Theme found in website
        if ($returnBool) {
            return true;
        }
        $title = trim($matches[1]);
    } else {
        return null; // The title should exist
    }

    preg_match('/Theme URI: (.*)/', $styleCssContent, $matches);
    if (isset($matches[1])) {
        $parsedUrl = parse_url($matches[1]);
        $sanatizedWebsite = $parsedUrl['host'] ?? null;
        $website = $sanatizedWebsite ? $parsedUrl['scheme'] . '://' . $sanatizedWebsite : null;
    } else {
        $website = null;
        $sanatizedWebsite = null;
    }

    preg_match('/Author: (.*)/', $styleCssContent, $matches);
    $author = isset($matches[1]) ? trim($matches[1]) : null;

    preg_match('/Version: (.*)/', $styleCssContent, $matches);
    $version = isset($matches[1]) ? trim($matches[1]) : null;

    preg_match('/Requires at least: (.*)/', $styleCssContent, $matches);
    $reqWpVersion = isset($matches[1]) ? trim($matches[1]) . ' or higher' : null;

    preg_match('/Tested up to: (.*)/', $styleCssContent, $matches);
    $testedWpVersion = isset($matches[1]) ? trim($matches[1]) : null;

    preg_match('/Requires PHP: (.*)/', $styleCssContent, $matches);
    $reqPhpVersion = isset($matches[1]) ? trim($matches[1]) . ' or higher' : null;

    preg_match('/Description: (.*)/', $styleCssContent, $matches);
    $description = isset($matches[1]) ? trim($matches[1]) : null;
    $description = substr($description, 0, 1000); // Limit the description to 1000 characters

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

    return null;
}
