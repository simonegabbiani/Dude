

<?php
# /test_3Fupload.php
define('PROJECT_NAME', 'HalloWorld.prj');
define('CONTEXT_NAME', '/test-upload');
define('CONTEXT_DIR', '/');
define('ROOT_PREFIX', './');
define('AUTO_UPDATE', 1);
define('AUTO_UPDATE_REQUIRES', 1);
define('AUTO_UPDATE_FILES', 1);
//---------------------------------------------
require_once './~runtime.php';
$X_MAP = array();
require_once './prova-upload.php';
require_once './lib/~system/WebMasterTools/ajax-file-upload.php';
require './~auto_update.php';
//---------------------------------------------
Piece::checkConfigurationReady();
$started = Piece::checkApplicationReady();
Piece::checkSessionReady($started);
include ROOT_PREFIX.'on-context-start.php';
Piece::executeHandlers($X_MAP, Piece::$HANDLERS, 'context-start');
$DEF_ARGS = array();
$p = Piece::executeStartPoint('prova_3Fupload_21xml_3A_2Ftest_3Fupload');
if (!Piece::getExecutionStatus()) echo Piece::getBuffer(0);
Piece::resetBuffer();
Piece::executeHandlers($X_MAP, Piece::$HANDLERS, 'context-end');
include ROOT_PREFIX.'on-context-end.php';
Piece::onTerminate();

?>