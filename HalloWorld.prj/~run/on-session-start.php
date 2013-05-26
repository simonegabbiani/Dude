<?php
# on-session-start
$EX_MAP = array();
$EX_MAP['prova_3Fdb_21xml']['~system/Base/D.xml:dbconn-profile'] = 'dbconn-profile';
require_once 'prova-db.php';
require_once 'lib/~system/Base/D.php';
//---------------------------------------------
Piece::executeHandlers($EX_MAP, Piece::$HANDLERS, 'session-start');
?>