<?php
# on-session-start
$EX_MAP = array();
//---------------------------------------------
Piece::executeHandlers($EX_MAP, Piece::$HANDLERS, 'session-start');
?>