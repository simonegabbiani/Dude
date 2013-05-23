<?php
# on-application-start
$EX_MAP = array();
if (!session_id()) session_start();
unset($_SESSION['DBConn.profiles']);
//---------------------------------------------
Piece::executeHandlers($EX_MAP, Piece::$HANDLERS, 'application-start');
?>