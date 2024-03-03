<?php
function wp_content_path($html) {
    
    $dom = new DOMDocument();
    libxml_use_internal_errors(true);
    $dom->loadHTML($html); 
    
    $elements = array_merge(
        iterator_to_array($dom->getElementsByTagName('link')),
        iterator_to_array($dom->getElementsByTagName('a')),
        iterator_to_array($dom->getElementsByTagName('img'))
    );
    
    foreach ($elements as $link) {
        $href = $link->getAttribute('href');
        $src = $link->getAttribute('src');
        
        if (strpos($href, '/wp-content/') !== false) {
            $parts = explode('wp-content/', $href);
            $result = $parts[0] . 'wp-content/';
            return $result;
        }
        
        if (strpos($src, '/wp-content/') !== false) {
            $parts = explode('wp-content/', $src);
            $result = $parts[0] . 'wp-content/';
            return $result;
        }
    }
    
    return false;
}
?>