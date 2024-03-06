<?php

$wpContent = '';

function find_wp_content($html) {
    
    $dom = new DOMDocument();
    libxml_use_internal_errors(true);
    $dom->loadHTML($html);
    libxml_clear_errors();
    
    $elements = array_merge(
        iterator_to_array($dom->getElementsByTagName('link')),
        iterator_to_array($dom->getElementsByTagName('a')),
        iterator_to_array($dom->getElementsByTagName('img')),
        iterator_to_array($dom->getElementsByTagName('script')),
        iterator_to_array($dom->getElementsByTagName('iframe'))
    );
    
    foreach ($elements as $element) {
        $href = $element->getAttribute('href');
        $src = $element->getAttribute('src');
        
        if (strpos($href, '/wp-content/') !== false) {
            $parts = explode('wp-content/', $href);
            $result = $parts[0] . 'wp-content/';
            return $result;
        }

        if (strpos($src, '/wp-content/') !== false) {
            $parts = explode('wp-content/', $href);
            $result = $parts[0] . 'wp-content/';
            return $result;
        }
    }
    
    return false;
}
?>