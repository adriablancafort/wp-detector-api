<?php

// Returns true if any of the links contain '/wp-content/'
function find_wp($links, $url) 
{
    $urlName = get_url_name($url);

    foreach ($links as $link) {
        if (strpos($link, '/wp-content/') !== false) {

            $linkName = get_url_name($link);

            if ($urlName === $linkName) {
                return true;
            }
        }
    }
    
    return false;
}

// Returns the name of the website from the URL. https://www.example.com -> example.com
function get_url_name($url)
{
    $parsedUrl = parse_url($url);
    $urlHost = $parsedUrl['host'];

    // Remove 'www.' from the host if present
    $urlName = preg_replace('/^www\./', '', $urlHost);

    return $urlName;
}
?>