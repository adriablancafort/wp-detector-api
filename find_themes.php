<?php
require 'database_read.php';
require 'find_version.php';
require 'find_theme_banner.php';

function find_themes($html, $wpContent) {
    // Returns a list of all the themes detected in the html content

    // $theme1 = [
    //     'banner' => 'https://generatepress.com/wp-content/themes/generatepress/screenshot.png',
    //     'title' => 'GeneratePress',
    //     'author' => 'Tom Usborne',
    //     'version' => '3.4.0',
    //     'website' => 'https://generatepress.com',
    //     'sanatizedWebsite' => 'generatepress.com',
    //     'reqWpVersion' => '5.2',
    //     'testedWpVersion' => '6.3',
    //     'reqPhpVersion' => '5.6',
    //     'description' => 'GeneratePress is a lightweight WordPress theme built with a focus on speed and usability. Performance is important to us, which is why a fresh GeneratePress install adds less than 10kb (gzipped) to your page size. We take full advantage of the block editor (Gutenberg), which gives you more control over creating your content. If you use page builders, GeneratePress is the right theme for you. It is completely compatible with all major page builders, including Beaver Builder and Elementor. Thanks to our emphasis on WordPress coding standards, we can boast full compatibility with all well-coded plugins, including WooCommerce. GeneratePress is fully responsive, uses valid HTML/CSS, and is translated into over 25 languages by our amazing community of users. A few of our many features include 60+ color controls, powerful dynamic typography, 5 navigation locations, 5 sidebar layouts, dropdown menus (click or hover), and 9 widget areas. Learn more and check out our powerful premium version at generatepress.com',
    //     'link' => 'https://generatepress.com/?utm_source=wp-detector',
    // ];

    // return [$theme1];

    $dom = new DOMDocument();
	libxml_use_internal_errors(true);
    $dom->loadHTML($html); 
    $themes = [];

    $elements = array_merge(
        iterator_to_array($dom->getElementsByTagName('link')),
        iterator_to_array($dom->getElementsByTagName('script')),
        iterator_to_array($dom->getElementsByTagName('meta'))
    );

    foreach ($elements as $element) {
        $themes = process_element($element, $wpContentPath, $themes);
    }

    return $themes;
}

function process_element($element, $wpContentPath, $themes) {
    $href = $element->getAttribute('href');
    $src = $element->getAttribute('src');
    $content = $element->getAttribute('content');
    $matches = [];

    if (strpos($href, '/wp-content/themes/') !== false || strpos($src, '/wp-content/themes/') !== false || strpos($content, '/wp-content/themes/') !== false) {
        $pattern = '/\/wp-content\/themes\/(.*?)\//';

        if (preg_match($pattern, $href, $matches) || preg_match($pattern, $src, $matches) || preg_match($pattern, $content, $matches)) {
            $themeSlug = $matches[1];

            if (!in_array($themeSlug, $themes)) {
                $themePath = $wpContentPath . 'themes/' . $themeSlug . '/style.css';
                
                if (slugExists('theme', $themeSlug)) {
                        
                        $retrievedData = getDataBySlug('theme', $themeSlug);
                        if ($retrievedData !== null) {
                            if($retrievedData['banner'] === null) {
                                $themeImage = find_theme_banner($wpContentPath . 'themes/' . $themeSlug, $themeSlug);
                            }
                            $newTheme = [
                                'author' => $retrievedData['author'],
                                'link' => $retrievedData['link'],
                                'website' => $retrievedData['website'],
                                'sanatizedWebsite' => $retrievedData['sanatizedWebsite'],
                                'description' => $retrievedData['description'],
                                'title' => $retrievedData['title'],
                                'reqWpVersion' => $retrievedData['reqWpVersion'],
                                'testedWpVersion' => $retrievedData['testedWpVersion'],
                                'reqPhpVersion' => $retrievedData['reqPhpVersion'],
                                'version' => find_version($themePath, 'theme'),
                                'banner' => $retrievedData['banner'] ?? $themeImage
                            ];
                            $themes[] = $newTheme;
                        }
                } else {
                    $themeDetails = parse_theme_info($themePath);
                    $themeImage = find_theme_banner($wpContentPath . 'themes/' . $themeSlug, $themeSlug);
                    $link = $themeDetails['link'];
                    $parsed_url = parse_url($link);
                    $sanatizedWebsite = isset($parsed_url['host']) ? $parsed_url['host'] : '';
                    $website = isset($parsed_url['scheme']) ? $parsed_url['scheme'] . '://' : '';
                    $website .= isset($parsed_url['host']) ? $parsed_url['host'] : '';

                    $newTheme = [
                        'author' => $themeDetails['author'],
                        'link' => $link,
                        'website' => $website,
                        'sanatizedWebsite' => $sanatizedWebsite,
                        'description' => $themeDetails['description'],
                        'title' => $themeDetails['title'],
                        'reqWpVersion' => $themeDetails['reqWpVersion'],
                        'testedWpVersion' => $themeDetails['testedWpVersion'],
                        'reqPhpVersion' => $themeDetails['reqPhpVersion'],
                        'version' => $themeDetails['version'],
                        'banner' => $themeImage
                    ];
                    $themes[] = $newTheme;

                    // TO DO: write the theme to db except version + slug + times_analyzed
                }
            }
        }
    }

    return $themes;
}

function parse_theme_info($themePath) {
    $styleContent = @file_get_contents($themePath);
    $themeDetails = [];
                
    if($styleContent !== false && !empty($styleContent)) {
		preg_match('/Theme Name:(.*)/i', $styleContent, $matches);
        if (isset($matches[1])) {
            $themeDetails['title'] = trim($matches[1]);
        } else { $themeDetails['title'] = null; }
        
        preg_match('/Theme URI:(.*)/i', $styleContent, $matches);
        if (isset($matches[1])) {
            $themeDetails['link'] = trim($matches[1]);
        } else { $themeDetails['link'] = null; }

        preg_match('/Author:(.*)/i', $styleContent, $matches);
        if (isset($matches[1])) {
            $themeDetails['author'] = trim($matches[1]);
        } else { $themeDetails['author'] = null; }

        preg_match('/Description:(.*)/i', $styleContent, $matches);
        if (isset($matches[1])) {
            $themeDetails['description'] = trim($matches[1]);
        } else { $themeDetails['description'] = null; }

        preg_match('/Version:\s+([\d.]+\d)/', $styleContent, $matches);
        if (isset($matches[1])) {
            $themeDetails['version'] = trim($matches[1]);
        } else { $themeDetails['version'] = null; }
        
        preg_match('/Requires at least:\s+([\d.]+\d)/', $styleContent, $matches);
		if (isset($matches[1])) {
			$themeDetails['reqWpVersion'] = trim($matches[1]);
		} else {
			$themeDetails['reqWpVersion'] = null;
		}

		preg_match('/Tested up to:\s+([\d.]+\d)/', $styleContent, $matches);
		if (isset($matches[1])) {
			$themeDetails['testedWpVersion'] = trim($matches[1]);
		} else {
			$themeDetails['testedWpVersion'] = null;
		}

		preg_match('/Requires PHP:\s+([\d.]+\d)/', $styleContent, $matches);
		if (isset($matches[1])) {
			$themeDetails['reqPhpVersion'] = trim($matches[1]);
		} else {
			$themeDetails['reqPhpVersion'] = null;
		}
    }
    return $themeDetails;
}
?>