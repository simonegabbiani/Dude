<?php
# on-configuration
$EX_MAP = array();
require_once 'prova-db.php';
//---------------------------------------------
Piece::executeHandlers($EX_MAP, Piece::$HANDLERS, 'configuration');
?>