<?php

// Returns true if any of the links contain '/wp-content/'
function find_wp($links, $url) 
{
    $parsedUrl = parse_url($url);
    $urlHost = $parsedUrl['host'];

    foreach ($links as $link) {
        if (strpos($link, '/wp-content/') !== false) {

            $parsedLink = parse_url($link);
            $linkHost = $parsedLink['host'];

            if ($urlHost === $linkHost) {
                return true;
            }
        }
    }
    
    return false;
}
?>