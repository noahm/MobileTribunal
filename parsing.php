<?php
function getNumberOfGames($tribunalHTML) {

	// grab the element from the html
	$doc = new DOMDocument();
	$doc->loadHTML($tribunalHTML);
	$gamecount = $doc->getElementById('h_gamecount');

	// now, grab the contents of that element
	// (this seems like a stupid limitation of PHP's DOM parsing library)

	// create a document becase the element itself can't saveHTML
	$newdoc = new DomDocument();
	$newdoc->appendChild($newdoc->importNode($gamecount, TRUE));
	$html = trim($newdoc->saveHTML());

	// get the name of whatever tag happened to have the id "h_gamecount" (I expect it's a DIV)
	$tag = $gamecount->nodeName;

	// strip out the surrounding html
	$pattern = '@^<' . $tag . '[^>]*>|</' . $tag . '>$@';
	return (int) preg_replace($pattern, '', $html);

}
