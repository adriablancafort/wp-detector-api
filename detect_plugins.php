<?php
function detect_plugins($html, $wpContent) {
    // Returns a list of all the plugins detected in the html content

    $plugin1 = [
        'banner' => 'https://ps.w.org/wordpress-seo/assets/banner-772x250.png',
        'icon' => 'https://ps.w.org/wordpress-seo/assets/icon.svg',
        'title' => 'Yoast SEO',
        'author' => 'Team Yoast',
        'version' => '3.4.0',
        'website' => 'https://yoast.com',
        'sanatizedWebsite' => 'yoast.com',
        'reqWpVersion' => '6.3',
        'testedWpVersion' => '6.4.3',
        'reqPhpVersion' => '7.2.5',
        'description' => 'Supercharge your website’s visibility and attract organic traffic with Yoast SEO, the WordPress SEO plugin trusted by millions worldwide. With those millions of users, we’ve definitely helped someone like you! Users of our plugin range from owners of small-town bakeries and local physical stores to some of the world’s largest and most influential organizations. And we’ve done this since 2008!',
        'link' => 'https://yoast.com/?utm_source=wp-detector',
    ];

    return [$plugin1, $plugin1];
}

?>