<?php

# ~error.php (temporary)
# 
# error messages managment
#

ErrMan::web_page_start();

if (defined('CONTEXT_NAME')) {
	if (ERROR_CODE == 'asset-file-not-found')
		echo "<h1>Asset file not found:</h1>";
	else if (ERROR_CLASS == 'build') 
		echo '<h1>'.ERROR_CODE.'</h1>';
	else
		echo '<h1>On error was occurred with message:</h1>';

	echo '<h3><tt>"'.ERROR_MESSAGE.'"</tt></h3>';
}
else if (!$app_running) {
	echo '<h1>Application Management</h1>';
	echo '<h3>Application "<strong>'.htmlspecialchars(file_get_contents('project.name')).'</strong>" is not running.</h3>';
	echo '<h2><a id="TryStartAppLink" href="~application-manage.php?op=start">click here to start...</a></h2>';
}
ErrMan::web_page_end();

abstract class ErrMan {

 static function error_msg($code) {
	$msg = array('start-no-pid-err' => 'Cannot start application. Cannot create PID file. Check filesystem permissions.');
	echo "<h3 style='color:red'>Error: ".htmlspecialchars($msg[$code])."</h3>";
 }

 static function web_page_start() {
	ob_end_clean();
	echo "<html><body style='text-align:center;margin:auto; width:600px; min-height:200px;" 
		. "height:200px; padding-top:15%'><form name='err'><div style='border:10px solid orange; background-color:red; color:white'>";
 }

 static function web_page_end() {
	if (!defined('ERROR_HIDE_BACK_BUTTON'))
		echo "<p><input type='button' style='font-weight:bold' onClick='history.back()' value='&lt;&lt; B A C K' />";
	echo "</div></form></body></html>";
	@ob_end_flush();
 }
}
?>
