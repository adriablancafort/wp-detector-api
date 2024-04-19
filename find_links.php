<?php

// Returns all the links inside the html content
function find_links($html) 
{
    $links = [];

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

        if (!empty($href)) {
            $links[] = $href;
        }

        if (!empty($src)) {
            $links[] = $src;
        }
    }
    
    return $links;
}
?>