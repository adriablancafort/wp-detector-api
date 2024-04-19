<?php 

function parse_wordpress($wp_html, $pluginSlug, $assetType) {
	
	if($wp_html !== false && !empty($wp_html)) {
		$dom = new DOMDocument();
		libxml_use_internal_errors(true);
		$dom->loadHTML($wp_html);

		$xpath = new DOMXPath($dom);
		
		if($assetType === 'icon') {
			$srcQuery = '//img[@class="plugin-icon"]/@src';
			$src = $xpath->evaluate($srcQuery);

			if ($src->length > 0) {
				return $src->item(0)->nodeValue; // Output the src attribute value
			}
		}
		
		if($assetType === 'banner') {
			$pattern = "/#plugin-banner-[\w-]+\s*\{ background-image: url\('([^']+)'\)/"; // Regular expression pattern

			if (preg_match($pattern, $wp_html, $matches)) {
				return $matches[1]; // URL is captured in the first capture group
			}
		}
		
		if($assetType === 'url') {
			$xpath = new DOMXPath($dom);
			$authorSpan = $xpath->query('//span[@class="author vcard"]')->item(0);

			if ($authorSpan) {
				$authorLink = $authorSpan->getElementsByTagName('a')->item(0);

				if ($authorLink) {
					$url = $authorLink->getAttribute('href');
					if (preg_match('/^[^?]+/', $url, $matches)) {
						$url = $matches[0];
					}
				}
				return $url;
			}
		}
		
	}
	return null;
}

?>