<?php

require_once "web-page.php";
require_once "build.php"; //unico file da includere

// ---------- build-index ----------
session_start();
WebPage::start('CONTEXT INDEX');
if (!isset($_REQUEST['p'])) {
    if (!isset($_SESSION['project-name']))
        $_SESSION['project-name'] = '';
    echo "<form name='f1'>Confermare progetto: " .
    "<input type='text' value='{$_SESSION['project-name']}' name='p'/><input type='submit'/></form>";
}
else {
    chdir('../..');
    $_SESSION['project-name'] = $_REQUEST['p'];
    if (!file_exists($_REQUEST['p'])) {
        echo '<p>Ooops, project ' . $_REQUEST['p'] . ' does not exist. <a href="context-index.php">go back</a></p>';
    } else {
        $_SESSION['project-name'] = $_REQUEST['p'];
        Build::setup(DirFunctions::realDir(getcwd()));
        GeneralIndex::load();
        if (isset($_REQUEST['c'])) {
			$i = 1;
			if ($_REQUEST['c'] == '!all') {
				foreach(GeneralIndex::$index->context as $c)
					{ ContextFactory::createRunFile($c); $i++; }
				$_REQUEST['c'] = '!events';
			}
			if ($_REQUEST['c'] == '!events')
				ContextFactory::createEventFiles();
			else {
				$x = GeneralIndex::$index->context[$_REQUEST['c']];
				/*if (isset($_REQUEST['re-build-sources'])) {
					$f_list = array(); $map = array();
					self::buildFileDependencies($x->partStartPoint->df, $x->partStartPoint, $f_list, $map, false);
				}*/
	            ContextFactory::createRunFile($x);
			}
			echo "<span style='padding:5px; background-color:#fcc;'>N. $i context generated</span>";
        }
        // else {
			echo "<p><li><a style='color:red' href='context-index.php?p=".htmlspecialchars($_SESSION['project-name'])."&c=!all'>build all contexts</a></li></p>";
            foreach (GeneralIndex::$index->context as $c) {
                if ($c->tmpDeleted) {
                    echo "<li style='color:#999'>".$c->df->fileName . ": " . htmlspecialchars($c->name)." (deleted)</li>";
                }
                else {
                    echo "<li>" . /*$c->df->fileName . ": " .*/ htmlspecialchars($c->name);
                    //supports only projects under ~run
					echo " &nbsp; <a style='color:red' target='_blank' href='context-index.php?p=".htmlspecialchars($_SESSION['project-name'])."&c=".htmlspecialchars($c->name)."'>build</a> | ";
                    echo " &nbsp; <a style='color:blue' target='_blank' href='../../".urlencode($_SESSION['project-name'])."/~run/".Utils::encode($c->name, true, true).".php?--reset-auto-update-check--'>execute</a> &nbsp;|&nbsp; <a target='_blank' href='view-source.php?file=".urlencode("../../".urlencode($_SESSION['project-name'])."/~run/".Utils::encode($c->name, true)).".php'>source</a>";
                    echo " &nbsp;|&nbsp; <a target='_blank' href='exploder-index.php?part=".urlencode($c->partStartPoint->fullName)."&p=".urlencode($_SESSION['project-name'])."'>explode</a>";
                    echo " &nbsp;|&nbsp; <a target='_blank' href='info-index.php?part=".urlencode($c->partStartPoint->fullName)."&p=".urlencode($_SESSION['project-name'])."'>info/test</a>";
                } 
                echo "</li>";
            }
			echo "<p><li>on-application-start &nbsp; <a target='_blank' href='view-source.php?file=".urlencode("../../".urlencode($_SESSION['project-name'])."/~run/on-application-start.php")."'>source</a> &nbsp;|&nbsp; <a style='color:red' href='context-index.php?p=".htmlspecialchars($_SESSION['project-name'])."&c=!events'>build event files</a></li>";
			echo "<li>on-application-end &nbsp; <a target='_blank' href='view-source.php?file=".urlencode("../../".urlencode($_SESSION['project-name'])."/~run/on-application-end.php")."'>source</a>";
			echo "<li>on-session-start &nbsp; <a target='_blank' href='view-source.php?file=".urlencode("../../".urlencode($_SESSION['project-name'])."/~run/on-session-start.php")."'>source</a>";
			//echo "<li>on-session-end &nbsp; <a target='_blank' href='view-source.php?file=".urlencode("../../".urlencode($_SESSION['project-name'])."/~run/on-session-end.php")."'>source</a>";
			echo "<li>on-context-stasrt &nbsp; <a target='_blank' href='view-source.php?file=".urlencode("../../".urlencode($_SESSION['project-name'])."/~run/on-context-start.php")."'>source</a>";
			echo "<li>on-context-end &nbsp; <a target='_blank' href='view-source.php?file=".urlencode("../../".urlencode($_SESSION['project-name'])."/~run/on-context-end.php")."'>source</a>";
        // }
    }
}
WebPage::end();
?>
