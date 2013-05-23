

<?php
# /index_3F2.php
define('PROJECT_NAME', 'HalloWorld.prj');
define('CONTEXT_NAME', '/index-2');
define('CONTEXT_DIR', '/');
define('ROOT_PREFIX', './');
define('AUTO_UPDATE', 1);
define('AUTO_UPDATE_REQUIRES', 1);
define('AUTO_UPDATE_FILES', 1);
//---------------------------------------------
require_once './~runtime.php';
$X_MAP = array();
require_once './hallo-world-2.php';
require_once './lib/~system/www/css-utils.php';
require './~auto_update.php';
//---------------------------------------------
Piece::checkConfigurationReady();
$started = Piece::checkApplicationReady();
Piece::checkSessionReady($started);
include ROOT_PREFIX.'on-context-start.php';
Piece::executeHandlers($X_MAP, Piece::$HANDLERS, 'context-start');
$DEF_ARGS = array();
$p = Piece::executeStartPoint('hallo_3Fworld_3F2_21xml_3A_2Findex_3F2');
if (!Piece::getExecutionStatus()) echo Piece::getBuffer(0);
Piece::resetBuffer();
Piece::executeHandlers($X_MAP, Piece::$HANDLERS, 'context-end');
include ROOT_PREFIX.'on-context-end.php';
Piece::onTerminate();

?>