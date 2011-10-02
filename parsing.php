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

?>
