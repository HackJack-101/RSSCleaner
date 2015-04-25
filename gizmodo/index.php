<?php

//error_reporting(E_ALL);
//ini_set('display_errors', TRUE);
//ini_set('display_startup_errors', TRUE);
header('Content-Type: application/xml; charset=utf-8');

$url	 = 'http://www.gizmodo.fr/feed';
$curl	 = curl_init();
curl_setopt($curl, CURLOPT_URL, $url);
curl_setopt($curl, CURLOPT_TIMEOUT, 10);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
$content = curl_exec($curl);

$xml	 = new DomDocument();
$xml->loadXML($content);
$items	 = $xml->getElementsByTagName('item');

for ($k = 0; $k < $items->length; $k++)
{
	$descriptions	 = $items->item($k)->getElementsByTagName('description');
	$enclosures		 = $items->item($k)->getElementsByTagName('enclosure');
	$urls			 = $items->item($k)->getElementsByTagName('link');
	$title			 = $items->item($k)->getElementsByTagName('title')->item(0)->nodeValue;
	$url			 = $urls->item(0)->nodeValue;
	$urlInfo		 = parse_url($url);
	$urlCleaned		 = $urlInfo['scheme'] . '://' . $urlInfo['host'] . $urlInfo['path'];
	$urlDivided		 = explode('/', $urlInfo['path']);
	$name			 = $urlDivided[count($urlDivided) - 1];
	$cache			 = 'cache/' . $name;
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

	$scripts = $html->getElementsByTagName('script');
	while ($script	 = $scripts->item(0))
		$script->parentNode->removeChild($script);

	$article = $html->getElementsByTagName('article')->item(0);
	if (!empty($article))
	{
		$title		 = $html->getElementsByTagName('h1')->item(0)->nodeValue;
		$img		 = $html->saveHTML($article->getElementsByTagName('img')->item(0));
		$desc		 = '';
		$sections	 = $article->getElementsByTagName('section');
		foreach ($sections as $section)
		{
			if ($section->attributes->getNamedItem("class")->nodeValue == 'article-content')
				$desc = $section;
		}
		$footer	 = $desc->getElementsByTagName('footer')->item(0);
		$desc->removeChild($footer);
		$desc	 = $html->saveHTML($desc);
		$items->item($k)->removeChild($descriptions->item(0));
		$items->item($k)->removeChild($enclosures->item(0));

		$new = $xml->createDocumentFragment();
		$new->appendXML('<description><![CDATA[<h2>' . $title . '</h2><div>' . $img . '</div><div>' . $desc . '</div>]]></description>');
		$items->item($k)->appendChild($new);
	}
}
echo $xml->saveXML();
