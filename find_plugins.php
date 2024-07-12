<?php

require_once 'database_connection.php';

// Returns all the plugins of the given url
function find_plugins($links, $url)
{
    $plugins = [];

    $parsedUrl = parse_url($url);
    $rootDomain = $parsedUrl['scheme'] . '://' . $parsedUrl['host'] . '/wp-content/plugins/'; // Todo: search wp content in other paths. Example: example.com/w/wp-content/

    $db = new Database();
    $db->connect();

    foreach ($links as $link) {
        if (preg_match('/.*\/plugins\/([^\/]*)/', $link, $matches)) {
            $pluginSlug = $matches[1];

            if (!array_key_exists($pluginSlug, $plugins) && preg_match('/^[a-z0-9\-]+$/', $pluginSlug)) {

                $pluginPath = $rootDomain . $pluginSlug;
                $pluginInfo = get_plugin_info($db, $pluginSlug, $pluginPath);

                if (!empty($pluginInfo)) {
                    $plugins[$pluginSlug] = $pluginInfo;
                }
            }
        }
    }

    // Check popular plugins only in website
    $pluginSlugs = ['seo', 'seo-by-rank-math', 'wp-mail-smtp'];
    foreach ($pluginSlugs as $pluginSlug) {

        $pluginPath = $rootDomain . $pluginSlug;
        $pluginInfo = get_plugin_info($db, $pluginSlug, $pluginPath, false);

        if (!empty($pluginInfo) && !array_key_exists($pluginSlug, $plugins)) {
            $plugins[$pluginSlug] = $pluginInfo;
        }
    }

    $db->close();

    return $plugins;
}

// Returns the plugin information of a given plugin slug
function get_plugin_info($db, $pluginSlug, $pluginPath, $checkPublicDirectory = true)
{
    $result = $db->query("SELECT * FROM plugins WHERE slug = '$pluginSlug'");
    $row = $result->fetch_assoc();

    if (empty($row)) {

        if ($checkPublicDirectory) {
            $pluginInfo = find_plugin_info_in_directory($pluginSlug);
        }
        if (empty($pluginInfo)) {
            $pluginInfo = find_plugin_info_in_website($pluginSlug, $pluginPath);
        }
        if (empty($pluginInfo)) {
            return null; // False positive
        }

        $banner = isset($pluginInfo['banner']) ? "'" . $pluginInfo['banner'] . "'" : "NULL";
        $icon = isset($pluginInfo['icon']) ? "'" . $pluginInfo['icon'] . "'" : "NULL";
        $title = isset($pluginInfo['title']) ? "'" . $pluginInfo['title'] . "'" : "NULL";
        $contributors = isset($pluginInfo['contributors']) ? "'" . $pluginInfo['contributors'] . "'" : "NULL";
        $version = isset($pluginInfo['version']) ? "'" . $pluginInfo['version'] . "'" : "NULL";
        $website = isset($pluginInfo['website']) ? "'" . $pluginInfo['website'] . "'" : "NULL";
        $sanatizedWebsite = isset($pluginInfo['sanatizedWebsite']) ? "'" . $pluginInfo['sanatizedWebsite'] . "'" : "NULL";
        $lastUpdated = isset($pluginInfo['lastUpdated']) ? "'" . $pluginInfo['lastUpdated'] . "'" : "NULL";
        $activeInstallations = isset($pluginInfo['activeInstallations']) ? "'" . $pluginInfo['activeInstallations'] . "'" : "NULL";
        $reqWpVersion = isset($pluginInfo['reqWpVersion']) ? "'" . $pluginInfo['reqWpVersion'] . "'" : "NULL";
        $testedWpVersion = isset($pluginInfo['testedWpVersion']) ? "'" . $pluginInfo['testedWpVersion'] . "'" : "NULL";
        $reqPhpVersion = isset($pluginInfo['reqPhpVersion']) ? "'" . $pluginInfo['reqPhpVersion'] . "'" : "NULL";
        $description = isset($pluginInfo['description']) ? "'" . $pluginInfo['description'] . "'" : "NULL";
        $link = isset($pluginInfo['link']) ? "'" . $pluginInfo['link'] . "'" : "NULL";

        // Insert the plugin info into the database
        //$db->query("INSERT INTO plugins (slug, banner, icon, title, contributors, version, website, sanatizedWebsite, lastUpdated, activeInstallations, reqWpVersion, testedWpVersion, reqPhpVersion, description, link, timesAnalyzed, lastAnalyzed) VALUES ('$pluginSlug', $banner, $icon, $title, $contributors, $version, $website, $sanatizedWebsite, $lastUpdated, $activeInstallations, $reqWpVersion, $testedWpVersion, $reqPhpVersion, $description, $link, 1, NOW())");

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

    // Overide null fields with the desired values
    $pluginInfo['banner'] = $pluginInfo['banner'] ?? "/no-plugin-banner.svg";
    $pluginInfo['icon'] = $pluginInfo['icon'] ?? "/no-plugin-icon.svg";
    $pluginInfo['description'] = $pluginInfo['description'] ?? "No description provided";
    $pluginInfo['contributors'] = $pluginInfo['author'] ?? "No contributors found";

    return $pluginInfo;
}

// Returns the plugin information in the wordpress directory given a plugin slug
function find_plugin_info_in_directory($pluginSlug)
{
    require_once 'get_content.php';

    $url = "https://wordpress.org/plugins/" . $pluginSlug;
    $html = get_content($url);

    // Return if the page didn't return content
    if (empty($html)) {
        return null;
    }

    $doc = new DOMDocument();
    @$doc->loadHTML($html);
    $xpath = new DOMXPath($doc);

    $nodes = $xpath->query('//title');
    $pageTitle = $nodes->item(0)->nodeValue;

    // Returns null if the theme page doesen't exist in worpdress directory (The title will be "Search Results ...")
    if (strpos($pageTitle, "Search Results") !== false) {
        return null;
    }

    $nodes = $xpath->query('//div[@class="plugin-banner"]/img/@src');
    $banner = $nodes->length > 0 ? $nodes->item(0)->nodeValue : null;

    $nodes = $xpath->query('//div[@class="entry-thumbnail"]/img[@class="plugin-icon"]/@src');
    $icon = $nodes->length > 0 ? $nodes->item(0)->nodeValue : null;

    $nodes = $xpath->query('//h1[@class="plugin-title"]');
    if ($nodes->length > 0) {
        $title = $nodes->item(0)->nodeValue;
    } else {
        // Format the title from the slug
        $words = explode('-', $pluginSlug);
        $words = array_map('ucfirst', $words);
        $title = implode(' ', $words);
    };

    $nodes = $xpath->query('//ul[@id="contributors-list"]/li/a');
    $contributorsList = [];
    foreach ($nodes as $node) {
        $contributorName = trim($node->nodeValue);
        $contributorsList[$contributorName] = $contributorName; // Use the name as the key to prevent duplicates
    }
    $contributors = implode(', ', $contributorsList); // Transform to comma separated string

    $nodes = $xpath->query('//li[contains(text(), "Version")]/strong');
    $version = $nodes->length > 0 ? $nodes->item(0)->nodeValue : null;

    $nodes = $xpath->query('//li[contains(text(), "Last updated")]/strong/span');
    $lastUpdated = $nodes->length > 0 ? $nodes->item(0)->nodeValue : null;

    $nodes = $xpath->query('//li[contains(text(), "Active installations")]/strong');
    $activeInstallations = $nodes->length > 0 ? $nodes->item(0)->nodeValue : null;

    $nodes = $xpath->query('//li[contains(text(), "WordPress version")]/strong');
    $reqWpVersion = $nodes->length > 0 ? $nodes->item(0)->nodeValue : null;

    $nodes = $xpath->query('//li[contains(text(), "Tested up to")]/strong');
    $testedWpVersion = $nodes->length > 0 ? $nodes->item(0)->nodeValue : null;

    $nodes = $xpath->query('//li[contains(text(), "PHP version")]/strong');
    $reqPhpVersion = $nodes->length > 0 ? $nodes->item(0)->nodeValue : null;

    $website = null;
    $sanatizedWebsite = null;

    $nodes = $xpath->query('//span[@class="author vcard"]/a/@href');

    // If a website is found, sanatize it
    if ($nodes->length > 0) {
        $websiteUrl = $nodes->item(0)->nodeValue;
        $parsedUrl = parse_url($websiteUrl);
        $sanatizedWebsite = $parsedUrl['host'] ?? null;
        $website = $sanatizedWebsite ? $parsedUrl['scheme'] . '://' . $sanatizedWebsite : null;
    }

    $nodes = $xpath->query('//div[@id="tab-description"]/*[not(self::h2)]//text()');
    $description = null;
    if ($nodes->length > 0) { // Check if there are any nodes
        $description = '';
        foreach ($nodes as $node) {
            $description .= trim($node->nodeValue) . ' ';
        }
        $description = trim($description); // Remove any leading/trailing whitespace
        $description = substr($description, 0, 800); // Limit the description to 800 characters
    }

    $plugin = [
        'banner' => $banner,
        'icon' => $icon,
        'title' => $title,
        'contributors' => $contributors,
        'version' => $version,
        'website' => $website,
        'sanatizedWebsite' => $sanatizedWebsite,
        'lastUpdated' => $lastUpdated,
        'activeInstallations' => $activeInstallations,
        'reqWpVersion' => $reqWpVersion,
        'testedWpVersion' => $testedWpVersion,
        'reqPhpVersion' => $reqPhpVersion,
        'description' => $description,
        'link' => $url,
    ];

    return $plugin;
}

// Returns the plugin information given a plugin path
function find_plugin_info_in_website($pluginSlug, $pluginPath)
{
    require_once 'get_content.php';

    $readmeTxtUrl =  $pluginPath . '/readme.txt';


    $readmeTxtContent = get_content($readmeTxtUrl);

    // Return if there is no readme ?
    if (empty($readmeTxtContent)) {
        return null;
    }

    preg_match('/=== (.*) ===/', $readmeTxtContent, $matches);
    if (isset($matches[1])) {
        $title = trim($matches[1]);
    } else {
        return null; // The title should exist

        /*
        // Convert "plugin-slug" to "Plugin Slug"
        $words = explode('-', $pluginSlug);
        $words = array_map('ucfirst', $words);
        $title = implode(' ', $words);
        */
    }

    preg_match('/Contributors: (.*)/', $readmeTxtContent, $matches);
    $contributors = isset($matches[1]) ? trim($matches[1]) : null;

    preg_match('/Stable tag: (.*)/', $readmeTxtContent, $matches);
    $version = isset($matches[1]) ? trim($matches[1]) : null;

    preg_match('/Donate link: (.*)/', $readmeTxtContent, $matches);

    $website = null;
    $sanatizedWebsite = null;

    if (!empty($matches[1])) {
        // The donate link exists and is not PayPal
        if (strpos($matches[1], 'paypal') === false) {
            $parsedUrl = parse_url($matches[1]);
            $sanatizedWebsite = $parsedUrl['host'] ?? null;
            $website = $sanatizedWebsite ? $parsedUrl['scheme'] . '://' . $sanatizedWebsite : null;
        }
    }

    preg_match('/Requires at least: (.*)/', $readmeTxtContent, $matches);
    $reqWpVersion = isset($matches[1]) ? $matches[1] . ' or higher' : null;

    preg_match('/Tested up to: (.*)/', $readmeTxtContent, $matches);
    $testedWpVersion = isset($matches[1]) ? trim($matches[1]) : null;

    preg_match('/Requires PHP: (.*)/', $readmeTxtContent, $matches);
    $reqPhpVersion = isset($matches[1]) ? $matches[1] . ' or higher' : null;

    preg_match('/== Description ==\n\n(.*?)\n==/s', $readmeTxtContent, $matches); // Description until the next "=="
    $description = $matches[1] ?? null;
    $description = substr($description, 0, 800); // Limit the description to 800 characters

    $plugin = [
        'banner' => null,
        'icon' => null,
        'title' => $title,
        'contributors' => $contributors,
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
