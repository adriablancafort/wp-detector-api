<?php

function find_theme_banner($theme_path, $themeSlug) {
    $screenshotPaths = [
        $theme_path . '/screenshot.jpg',
        $wp_path . '/screenshot.png',
    ];
    $themeImage = false;

    foreach ($screenshotPaths as $screenshotPath) {
        $headers = get_headers($screenshotPath);
        if (strpos($headers[0], '200 OK') !== false) {
            $themeImage = true;
            $bannerLink = $screenshotPath;
            break;
        }
    }

    if ($themeImage) {
        // TO DO: write the found image url to the database
        return $bannerLink;
    }
    else return null;
}

?>