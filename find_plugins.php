<?php
require 'database_read.php';
require 'parse_wordpress.php';
require 'find_version.php';
require 'database_connection.php';
require 'database_write.php';

function find_plugins($html, $wpContent,$url) {
    // Returns a list of all the plugins detected in the html content

    // $plugin1 = [
    //     'banner' => 'https://ps.w.org/wordpress-seo/assets/banner-772x250.png',
    //     'icon' => 'https://ps.w.org/wordpress-seo/assets/icon.svg',
    //     'title' => 'Yoast SEO',
    //     'author' => 'Team Yoast',
    //     'version' => '3.4.0',
    //     'website' => 'https://yoast.com',
    //     'sanatizedWebsite' => 'yoast.com',
    //     'reqWpVersion' => '6.3',
    //     'testedWpVersion' => '6.4.3',
    //     'reqPhpVersion' => '7.2.5',
    //     'description' => 'Supercharge your website’s visibility and attract organic traffic with Yoast SEO, the WordPress SEO plugin trusted by millions worldwide. With those millions of users, we’ve definitely helped someone like you! Users of our plugin range from owners of small-town bakeries and local physical stores to some of the world’s largest and most influential organizations. And we’ve done this since 2008!',
    //     'link' => 'https://yoast.com/?utm_source=wp-detector',
    // ];

    // return [$plugin1, $plugin1];

    if($url===null) {
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        $plugins = [];

        $patterns = [
            'script' => '/<script[^>]*src=[\'"]([^\'"]+)[\'"][^>]*>/i',
            'link' => '/<link[^>]*href=[\'"]([^\'"]+)[\'"][^>]*>/i',
            'img' => '/<img[^>]*src=[\'"]([^\'"]+)[\'"][^>]*>/i',
        ];

        $allMatches = [];
        
        foreach ($patterns as $element => $pattern) {
            if (preg_match_all($pattern, $html, $matches)) {
                $allMatches[$element] = $matches[1];
            }
        }

        foreach ($allMatches as $element => $matches) {
            foreach ($matches as $link) {
                $plugins = process_plugin($link, $wpContent, $plugins);
            }
        }
    }
    else {
        $conn = open_database_connection();
        $stmt = $conn->prepare("SELECT wp FROM websites WHERE url = ?");
        $stmt->bind_param("s", $url);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $slugs = $row['plugins'];
        }
        $slugs = str_replace(' ', '', $slugs);
        $pluginSlugs = explode(',', $slugs);
        $plugins = plugins_from_database($pluginSlugs);
    }

    return $plugins;

}

function plugins_from_database($pluginSlugs){
    foreach ($pluginSlugs as $pluginSlug) {
        $retrievedData = getDataBySlug('plugins', $pluginSlug);
        $description = $retrievedData['pluginDescription'];
        if (strlen($description) > 300) {
            $description = substr($description, 0, 300) . '...';
        }
        $newPlugin = [
            'icon' => $retrievedData['icon'],
            'banner' => $retrievedData['banner'],
            'title' => $retrievedData['title'],
            'author' => $retrievedData['author'],
            'version' => $retrievedData['version'],
            'website' => $retrievedData['website'],
            'sanatizedWebsite' => $retrievedData['sanatizedWebsite'],
            'reqWpVersion' => $retrievedData['reqWpVersion'],
            'testedWpVersion' => $retrievedData['testedWpVersion'],
            'reqPhpVersion' => $retrievedData['reqPhpVersion'],
            'description' => $description,
            'link' => $retrievedData['link'],
        ];
        $plugins[] = $newPlugin;
        $result = updateTimesAnalyzed('plugins',$pluginSlug);
    }
}

function process_plugin($url, $wpContentPath, $plugins) {

    if (strpos($url, 'plugins/') !== false) {
        $pattern = '/plugins\/(.*?)\//';

        if (preg_match($pattern, $url, $matches)) {
            $pluginSlug = $matches[1];

            if (!in_array($pluginSlug, $plugins) && $pluginSlug !== 'src' && $pluginSlug !== 'search') {
                $pluginPath = $wpContentPath . 'plugins/' . $pluginSlug . '/readme.txt';
                
                if (slugExists('plugins', $pluginSlug)) {
                    
                    $retrievedData = getDataBySlug('plugins', $pluginSlug);
                    if ($retrievedData !== null) {
                        $description = $retrievedData['pluginDescription'];
                        if (strlen($description) > 300) {
                            $description = substr($description, 0, 300) . '...';
                        }
                        $newPlugin = [
                            'icon' => $retrievedData['icon'],
                            'banner' => $retrievedData['banner'],
                            'title' => $retrievedData['title'],
                            'author' => $retrievedData['author'],
                            //'version' => find_version($pluginPath, 'plugin'),
                            'version' => $retrievedData['version'],
                            'website' => $retrievedData['website'],
                            'sanatizedWebsite' => $retrievedData['sanatizedWebsite'],
                            'reqWpVersion' => $retrievedData['reqWpVersion'],
                            'testedWpVersion' => $retrievedData['testedWpVersion'],
                            'reqPhpVersion' => $retrievedData['reqPhpVersion'],
                            'description' => $description,
                            'link' => $retrievedData['link'],
                        ];
                        $plugins[] = $newPlugin;
                        $result = updateTimesAnalyzed('plugins',$pluginSlug);
                    }
                } else {
                    $pluginDetails = parse_plugin_info($pluginPath);

                    $wp_html = @file_get_contents('https://wordpress.org/plugins/' . $pluginSlug . '/');
                    $iconUrl = parse_wordpress($wp_html, $pluginSlug, 'icon');
                    $bannerUrl = parse_wordpress($wp_html, $pluginSlug, 'banner');

                    $link = parse_wordpress($wp_html, $pluginSlug, 'url');
                    $parsed_url = parse_url($link);
                    $sanatizedWebsite = isset($parsed_url['host']) ? $parsed_url['host'] : '';
                    $website = isset($parsed_url['scheme']) ? $parsed_url['scheme'] . '://' : '';
                    $website .= isset($parsed_url['host']) ? $parsed_url['host'] : '';

                    $newPlugin = [
                        'icon' => $iconUrl,
                        'banner' => $bannerUrl,
                        'title' => $pluginDetails['title'] ?? null,
                        'author' => $pluginDetails['author'] ?? null,
                        'version' => $pluginDetails['version'] ?? null,
                        'website' => $website,
                        'sanatizedWebsite' => $sanatizedWebsite,
                        'reqWpVersion' => $pluginDetails['reqWpVersion'] ?? null,
                        'testedWpVersion' => $pluginDetails['testedWpVersion'] ?? null,
                        'reqPhpVersion' => $pluginDetails['reqPhpVersion'] ?? null,
                        'description' => $pluginDetails['description'] ?? null,
                        'link' => $link,
                    ];
                    $plugins[] = $newPlugin;

                    $newPlugin['slug'] = $pluginSlug;
                    $newPlugin['times_analyzed'] = 1;
                    $result = setDataBySlug('plugins', $newPlugin);
                }
            }
        }
    }
}

function parse_plugin_info($pluginPath) {
    $pluginContent = @file_get_contents($pluginPath);
    $pluginDetails = [];
                
    if($pluginContent !== false && !empty($pluginContent)) {
        preg_match('/Contributors:\s*(.*?)(?:\n|$)/i', $pluginContent, $matches);
        if (isset($matches[1])) {
            $pluginDetails['author'] = trim($matches[1]);
        } else { $pluginDetails['author'] = null; }
        
        preg_match('/== Description ==\s+(.*?)\s+(^=|\z)/s', $pluginContent, $matches);
        if (isset($matches[1])) {
            $description = trim($matches[1]);
			if (strlen($description) > 300) {
				$pluginDetails['description'] = substr($description, 0, 300) . '...';
			} else {
				$pluginDetails['description'] = $description;
			}    
        } else { $pluginDetails['description'] = null; }
        
        $firstLine = strtok($pluginContent, "\n");
        preg_match('/^===\s*(.*?)\s*===/', $firstLine, $matches);
		if (isset($matches[1])) {
			$pluginDetails['title'] = trim($matches[1]);
		} else {
			$pluginDetails['title'] = null;
		}
		
		preg_match('/== Changelog ==\s+=\s*([\d.]+)\s*=/i', $pluginContent, $matches);
		if (isset($matches[1])) {
			$pluginDetails['version'] = trim($matches[1]);
		} else {
			$pluginDetails['version'] = null;
		}
		
		preg_match('/Requires at least:\s*(.*?)(?:\n|$)/i', $pluginContent, $matches);
		if (isset($matches[1])) {
			$pluginDetails['reqWpVersion'] = trim($matches[1]);
		} else {
			$pluginDetails['reqWpVersion'] = null;
		}

		preg_match('/Tested up to:\s*(.*?)(?:\n|$)/i', $pluginContent, $matches);
		if (isset($matches[1])) {
			$pluginDetails['testedWpVersion'] = trim($matches[1]);
		} else {
			$pluginDetails['testedWpVersion'] = null;
		}

		preg_match('/Requires PHP:\s*(.*?)(?:\n|$)/i', $pluginContent, $matches);
		if (isset($matches[1])) {
			$pluginDetails['reqPhpVersion'] = trim($matches[1]);
		} else {
			$pluginDetails['reqPhpVersion'] = null;
		}

    } 
    return $pluginDetails;
}


?>