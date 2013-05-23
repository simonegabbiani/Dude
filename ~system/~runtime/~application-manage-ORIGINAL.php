<?php

# ~application-manage.php
# 
# application starting/stopping management utility
#

if (!defined('ROOT_PREFIX'))
	define('ROOT_PREFIX', '');

$app_running = file_exists(ROOT_PREFIX.'application.pid');
	
# This script can be work in standalone mode or included by runtime method
# Piece::checkApplicationStart(). The AUTO_UPDATE variable says in which
# context it is running.
if (defined('CONTEXT_NAME')) {
	// 1. si rifiuta di continuare
	// 2. se c'è il cookie admin, chiede all'utente
	// 3. fa partire in automatico
	// (non facendo niente, includendo direttamente on-application-start.php
	if (!$app_running) {
		AppMan::try_start_application();
		include "on-application-start.php";
		if (!Piece::getExecutionStatus()) {
			AppMan::finalize_start_application();
			define('APPLICATION_STARTED', 'true');
		}
	}
}
else if (!$app_running) {
	AppMan::app_manage_web_page_start();
	echo '<h1>Application Management</h1>';
	echo '<h3>Application "<strong>'.htmlspecialchars(file_get_contents('project.name')).'</strong>" is not running.</h3>';
	echo '<h2><a id="TryStartAppLink" href="~application-manage.php?op=start">click here to start...</a></h2>';
	AppMan::app_manage_web_page_end();
}

abstract class AppMan {
 static function try_start_application() {
	@touch(ROOT_PREFIX.'application.pid');
	if (!file_exists(ROOT_PREFIX.'application.pid')) {
		AppMan::app_manage_web_page_start();
		AppMan::error_msg('start-no-pid-err');
		AppMan::app_manage_web_page_end();
		die();
	}
	unlink(ROOT_PREFIX.'application.pid');
	return true;
 }
 static function finalize_start_application() {
	 touch(ROOT_PREFIX.'application.pid');
 }

 static function error_msg($code) {
	$msg = array('start-no-pid-err' => 'Cannot start application. Cannot create PID file. Check filesystem permissions.');
	echo "<h3 style='color:red'>Error: ".htmlspecialchars($msg[$code])."</h3>";
 }

 static function app_manage_web_page_start() {
	//ob_end_clean();
	echo "<html><body>";
 }

 static function app_manage_web_page_end() {
	echo "</body></html>";
	//ob_end_flush();
	//die();
 }
}
?>
