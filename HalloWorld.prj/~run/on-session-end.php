<?php
# on-session-end
$EX_MAP = array();
//---------------------------------------------
Piece::executeHandlers($EX_MAP, Piece::$HANDLERS, 'session-end');
?>