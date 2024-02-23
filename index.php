<?php
header('Content-Type: application/json');

// List of allowed origins
$allowed_origins = ['http://localhost:4321', 'https://wp-detector.com', 'https://wp-detector.pages.dev'];

// Check if the Origin header is in the list of allowed origins
if (isset($_SERVER['HTTP_ORIGIN']) && in_array($_SERVER['HTTP_ORIGIN'], $allowed_origins)) {
    // Set the Access-Control-Allow-Origin header to the Origin of the incoming request
    header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
}

$url = $_GET['url'];
$type = $_GET['type'];
$html = get_html($url);

if ($type === 'wp') {
    $wp = detect_wp($html);
    echo json_encode(['wp' => $wp]);

} elseif ($type === 'themes') {
    $themes = detect_themes($html);
    echo json_encode(['themes' => $themes]);

} elseif ($type === 'plugins') {
    $plugins = detect_plugins($html);
    echo json_encode(['plugins' => $plugins]);

} elseif ($type === 'top-themes') {
    $themes = detect_themes($html);
    echo json_encode(['themes' => $themes]);

} elseif ($type === 'top-plugins') {
    $plugins = detect_plugins($html);
    echo json_encode(['plugins' => $plugins]);
}

function get_html($url) {
	$headers = array(
	   "Connection: keep-alive",
	   "Upgrade-Insecure-Requests: 1",
	   "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9",
	   "Accept-Language: en,es-ES;q=0.9,es;q=0.8",
	);
	
	//$agent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/79.0.3945.88 Safari/537.36';
	$agent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:109.0) Gecko/20100101 Firefox/117.0';

	$cookieFilePath = __DIR__ . '/cookie.txt';

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_VERBOSE, true);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_USERAGENT, $agent);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($ch, CURLOPT_ENCODING, '');
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_AUTOREFERER, true);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT , 60); 
	curl_setopt($ch, CURLOPT_TIMEOUT, 60); 
	curl_setopt($ch, CURLOPT_MAXREDIRS, 5); 
	curl_setopt($ch, CURLOPT_COOKIE, 'consent=yes');
	curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFilePath);
	curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFilePath);
	curl_setopt($ch, CURLOPT_URL,$url);
	$result=curl_exec($ch);
	
	 // Check for cURL errors
    if (curl_errno($ch)) {
        $error_message = curl_error($ch);
        error_log('cURL Error: ' . $error_message);
        curl_close($ch);
        return '';
    }
    
	curl_close($ch);
    
	return $result;
}

function detect_wp($html) {
    // Returns true if it finds the html content and false otherwise
    return strpos($html, 'wp-content') !== false;
}

function detect_themes($html) {
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

function detect_plugins() {
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