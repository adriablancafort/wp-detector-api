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
            $pluginPath = $rootDomain . '/wp-content/plugins/' . $pluginSlug; // Todo: search wp content in other paths. Example: example.com/w/wp-content/

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
        $pluginInfo = find_plugin_info_in_directory($pluginSlug);
        if (empty($pluginInfo)) {
            $pluginInfo = find_plugin_info_in_website($pluginSlug, $pluginPath);
        }
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
function find_plugin_info_in_directory($pluginSlug)
{
    require_once 'get_content.php';

    $url = "https://wordpress.org/plugins/" . $pluginSlug;
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
    if (strpos($pageTitle, "Page not found") !== false) {
        return null;
    }

    $nodes = $xpath->query('//div[@class="plugin-banner"]/img/@src');
    $banner = $nodes->length > 0 ? $nodes->item(0)->nodeValue : '/no-plugin-banner.svg';

    $nodes = $xpath->query('//div[@class="entry-thumbnail"]/img[@class="plugin-icon"]/@src');
    $icon = $nodes->length > 0 ? $nodes->item(0)->nodeValue : '/no-plugin-icon.svg';

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

    $nodes = $xpath->query('//span[@class="author vcard"]/a/@href');
    $rawUrl = $nodes->length > 0 ? $nodes->item(0)->nodeValue : null;
    $parsedUrl = parse_url($rawUrl);
    $sanatizedWebsite = $parsedUrl['host'] ?? null;
    $website = $sanatizedWebsite ? "https://" . $sanatizedWebsite : null;

    $descriptionNodes = $xpath->query('//div[@id="tab-description"]/*[not(self::h2)]//text()');
    $description = '';
    foreach ($descriptionNodes as $node) {
        $description .= trim($node->nodeValue) . ' ';
    }
    $description = trim($description); // Remove any leading/trailing whitespace
    $description = substr($description, 0, 1000); // Limit the description to 1000 characters

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

    preg_match('/=== (.*) ===/', $readmeTxtContent, $matches);
    if (!isset($matches[1])) {
        // Convert "plugin-slug" to "Plugin Slug"
        $words = explode('-', $pluginSlug);
        $words = array_map('ucfirst', $words);
        $pluginTitle = implode(' ', $words);
    }
    $title = $matches[1] ?? $pluginTitle;

    preg_match('/Contributors: (.*)/', $readmeTxtContent, $matches);
    $contributors = $matches[1] ?? "No contributors found";

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
?>