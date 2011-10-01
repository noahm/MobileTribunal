<?php

function getNumberOfGames($tribunalHTML) {
  $doc = new DOMDocument();
  $doc->loadHTML($tribunalHTML);
  $gamecount = $doc->getElementById('h_gamecount');
  $newdoc = new DomDocument();
  $newdoc->appendChild($newdoc->importNode($gamecount, TRUE));
  $html = trim($newdoc->saveHTML());
  $tag = $gamecount->nodeName;
  return (int) preg_replace('@^<' . $tag . '[^>]*>|</' . $tag . '>$@', '', $html);
}

error_reporting(0);
echo getNumberOfGames(file_get_contents('sample_page.html'));
