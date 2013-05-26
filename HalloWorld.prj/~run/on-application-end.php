<?php
# on-application-end
$EX_MAP = array();
require_once 'prova-db.php';
//---------------------------------------------
Piece::executeHandlers($EX_MAP, Piece::$HANDLERS, 'application-end');
?>