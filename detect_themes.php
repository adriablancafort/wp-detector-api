<?php
function detect_themes($html, $wpContent) {
    // Returns a list of all the themes detected in the html content

    $theme1 = [
        'banner' => 'https://generatepress.com/wp-content/themes/generatepress/screenshot.png',
        'title' => 'GeneratePress',
        'author' => 'Tom Usborne',
        'version' => '3.4.0',
        'website' => 'https://generatepress.com',
        'sanatizedWebsite' => 'generatepress.com',
        'reqWpVersion' => '5.2',
        'testedWpVersion' => '6.3',
        'reqPhpVersion' => '5.6',
        'description' => 'GeneratePress is a lightweight WordPress theme built with a focus on speed and usability. Performance is important to us, which is why a fresh GeneratePress install adds less than 10kb (gzipped) to your page size. We take full advantage of the block editor (Gutenberg), which gives you more control over creating your content. If you use page builders, GeneratePress is the right theme for you. It is completely compatible with all major page builders, including Beaver Builder and Elementor. Thanks to our emphasis on WordPress coding standards, we can boast full compatibility with all well-coded plugins, including WooCommerce. GeneratePress is fully responsive, uses valid HTML/CSS, and is translated into over 25 languages by our amazing community of users. A few of our many features include 60+ color controls, powerful dynamic typography, 5 navigation locations, 5 sidebar layouts, dropdown menus (click or hover), and 9 widget areas. Learn more and check out our powerful premium version at generatepress.com',
        'link' => 'https://generatepress.com/?utm_source=wp-detector',
    ];

    return [$theme1];
}
?>