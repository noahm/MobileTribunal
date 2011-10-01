<?php phpinfo();

function getNumberOfGames($tribunalHTML) {
  $doc = new DOMDocument();
  $doc->loadHTML($tribunalHTML);
  $gamecount = $doc->getElementById('h_gamecount');
  var_export($gamecount);
}

include('sample_page.php');
getNumberOfGames($page);
