<?php

function find_version($path, $type) {
    $content = @file_get_contents($path);
    $version = '';
                
    if($content !== false && !empty($content)) {
		if($type == 'plugin')
		{
			preg_match('/== Changelog ==\s+=\s*([\d.]+)\s*=/i', $content, $matches);
			if (isset($matches[1])) {
				$version = trim($matches[1]);
			}
		}else {
			preg_match('/Version:\s+([\d.]+\d)/', $content, $matches);
			if (isset($matches[1])) {
				$version = trim($matches[1]);
			}
		}
    }
    return $version; 
}

?>