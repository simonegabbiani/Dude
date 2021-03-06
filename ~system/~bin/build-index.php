<?php

define('FORCE_REBUILD_LIB', true);

require_once "web-page.php";
require_once "build.php";

//if (!isset($_REQUEST['p'])) $_REQUEST['p'] = (!isset($_SESSION['p'])) ? 'test' : $_SESSION['p'];

// ---------- build-index ----------
//qui lo chiede sempre, poi resta cmq in sessione
session_start('BUILD INDEX');
unset($_SESSION['auto-update-calls']);

WebPage::start();
if (!isset($_REQUEST['p'])) {
    if (!isset($_SESSION['project-name']))
        $_SESSION['project-name'] = '';
    echo "<form name='f1'>Confermare progetto: " .
    "<input type='text' name='p' value='{$_SESSION['project-name']}'/><input type<input type='submit'/></form>";
}
else {
    chdir('../..');
    $_SESSION['project-name'] = $_REQUEST['p'];
    if (!file_exists($_REQUEST['p'])) {
        echo '<p>Ooops, project ' . $_REQUEST['p'] . ' does not exist. <a href="build-index.php">go back</a>';
    } else {
        $_SESSION['project-name'] = $_REQUEST['p'];
        if (!isset($_SESSION['files-to-update']))
            $_SESSION['files-to-update'] = '';
        Build::setup(DirFunctions::realDir(getcwd()));
        Compiler::build(isset($_REQUEST['files-to-update']) ? $_REQUEST['files-to-update'] : $_SESSION['files-to-update'], FORCE_REBUILD_LIB);
        Build::end();
		//echo "<br/><b>Context found:</b>"; 
        //var_dump(GeneralIndex::$index);
        echo "<li><a href='context-index.php?p=".urlencode($_REQUEST['p'])."'>context</a>";
    }
}
WebPage::end();
?>
