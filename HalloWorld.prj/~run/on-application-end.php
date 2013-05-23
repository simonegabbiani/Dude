<?php
# on-application-end
$EX_MAP = array();
//---------------------------------------------
Piece::executeHandlers($EX_MAP, Piece::$HANDLERS, 'application-end');
?>