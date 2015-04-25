<?php

//error_reporting(E_ALL);
//ini_set('display_errors', TRUE);
//ini_set('display_startup_errors', TRUE);
header('Content-Type: application/xml; charset=utf-8');

$url	 = 'http://www.begeek.fr/feed';
$curl	 = curl_init();
curl_setopt($curl, CURLOPT_URL, $url);
curl_setopt($curl, CURLOPT_TIMEOUT, 10);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
$content = curl_exec($curl);
$content = preg_replace('#<content:encoded>.*</content:encoded>#isU', '', $content);

$xml	 = new DomDocument();
$xml->loadXML($content);
$items	 = $xml->getElementsByTagName('item');

for ($k = 0; $k < $items->length; $k++)
{
	$descriptions	 = $items->item($k)->getElementsByTagName('description');
	$urls			 = $items->item($k)->getElementsByTagName('link');
	$url			 = $urls->item(0)->nodeValue;
	$urlInfo		 = parse_url($url);
	$urlCleaned		 = $urlInfo['scheme'] . '://' . $urlInfo['host'] . $urlInfo['path'];
	$urlDivided		 = explode('/', $urlInfo['path']);
	$name			 = $urlDivided[count($urlDivided) - 1];
	$cache			 = 'cache/' . $name . '.html';
	if (!file_exists($cache))
	{
		$curl	 = curl_init();
		curl_setopt($curl, CURLOPT_URL, $urlCleaned);
		curl_setopt($curl, CURLOPT_TIMEOUT, 10);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		$content = curl_exec($curl);
		file_put_contents($cache, $content);
	}
	$content = file_get_contents($cache);
	$html	 = new DomDocument();
	$html->loadHTML($content);

	$scripts	 = $html->getElementsByTagName('script');
	while ($script		 = $scripts->item(0))
		$script->parentNode->removeChild($script);
	$share_post	 = $html->getElementById('share_post');
	$share_post->parentNode->removeChild($share_post);


	$single		 = $html->getElementById('single');
	$title		 = $single->getElementsByTagName('h1')->item(0)->nodeValue;
	$subtitle	 = $single->getElementsByTagName('h2')->item(0)->nodeValue;
	$img		 = $single->getElementsByTagName('img')->item(0);
	$htmlContent = $html->getElementById('boc_content');

	$img	 = $html->saveHTML($img);
	$desc	 = $html->saveHTML($htmlContent);

	$items->item($k)->removeChild($descriptions->item(0));

	$new = $xml->createDocumentFragment();
	$new->appendXML('<description><![CDATA[<h2>' . $title . '</h2><div>' . $img . '</div><h3>' . $subtitle . '</h3><div>' . $desc . '</div>]]></description>');
	$items->item($k)->appendChild($new);
}
echo $xml->saveXML();
