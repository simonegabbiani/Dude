<?php

require_once "web-page.php";
require_once "build.php";
require_once "exploder-lib.php";

session_start('BUILD INDEX');
unset($_SESSION['auto-update-calls']);

WebPage::start();
if (!isset($_REQUEST['p'])) {
    if (!isset($_SESSION['project-name']))
        $_SESSION['project-name'] = '';
    echo "<form name='f1'>Confermare progetto: " .
    "<input type='text' name='p' value='{$_SESSION['project-name']}'/><input type<input type='submit'/></form>";
}
else if (!isset($_REQUEST['part'])) {
	echo "<li><a href='context-index.php?p=".urlencode($_REQUEST['p'])."'>Select a context from here and click to 'explode'</a>";
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
		GeneralIndex::load();
		$info = null; $text = '';
		Exploder::part('plain', $_REQUEST['part'], $info, $text);
        Build::end();
    }
}
WebPage::end();

?>