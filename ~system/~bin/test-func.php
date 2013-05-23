<?php

$v = 'pippo {%ciao%} pluto';
$buff ='';
$in_symbol = false;
$inizio_simbolo = 0;
$fine_simbolo = 0;
for ($i=0; $i<strlen($v); $i++) {
  $c = $v[$i]; 
  if ($c == '}' && $prev_c == '%') {
    if (!$in_symbol) die('errore sintassi');
    $buff .= strtoupper(substr($v, $inizio_simbolo, $i - $inizio_simbolo));
    $fine_simbolo = $i;
  }
  else if ($c == '{' && $i < (strlen($v)-1) && $v[$i+1] == '%') {
    if ($in_symbol) die('errore sintassi');
    $buff .= substr($v, $fine_simbolo, $i - $fine_simbolo);
    $inizio_simbolo = $i;
  }
  else if ($i == strlen($v) - 1) {
    if ($in_symbol) die('errore sintassi');
    $buff .= substr($v, $fine_simbolo, $i - $fine_simbolo);
  }
  $prev_c = $c;
}

?>
