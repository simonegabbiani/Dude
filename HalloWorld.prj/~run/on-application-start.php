<?php
# on-application-start
$EX_MAP = array();
$EX_MAP['prova_3Fdb_21xml']['~system/Base/D.xml:dbconn-profile'] = 'dbconn-profile';
if (!session_id()) session_start();
unset($_SESSION['DBConn.profiles']);
require_once 'prova-db.php';
require_once 'lib/~system/Base/D.php';
//---------------------------------------------
Piece::executeHandlers($EX_MAP, Piece::$HANDLERS, 'application-start');
?>