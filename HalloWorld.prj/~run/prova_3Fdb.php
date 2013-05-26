

<?php
# /prova_3Fdb.php
define('PROJECT_NAME', 'HalloWorld.prj');
define('CONTEXT_NAME', '/prova-db');
define('CONTEXT_DIR', '/');
define('ROOT_PREFIX', './');
define('AUTO_UPDATE', 1);
define('AUTO_UPDATE_REQUIRES', 1);
define('AUTO_UPDATE_FILES', 1);
//---------------------------------------------
require_once './~runtime.php';
$X_TOUCH['prova_3Fdb_21xml'] = 1369412560;
$X_MAP['prova_3Fdb_21xml']['~system/Base/D.xml:dbconn-profile'] = 'dbconn-profile';
require_once './prova-db.php';
require_once './lib/~system/Base/D.php';
require './~auto_update.php';
//---------------------------------------------
Piece::checkConfigurationReady();
$started = Piece::checkApplicationReady();
Piece::checkSessionReady($started);
include ROOT_PREFIX.'on-context-start.php';
Piece::executeHandlers($X_MAP, Piece::$HANDLERS, 'context-start');
$DEF_ARGS = array();
$p = Piece::executeStartPoint('prova_3Fdb_21xml_3A_2Fprova_3Fdb');
if (!Piece::getExecutionStatus()) echo Piece::getBuffer(0);
Piece::resetBuffer();
Piece::executeHandlers($X_MAP, Piece::$HANDLERS, 'context-end');
include ROOT_PREFIX.'on-context-end.php';
Piece::onTerminate();

?>