<?php

require_once "web-page.php";
require_once "build.php";

session_start();
WebPage::start('GENERAL-INDEX');
if (!isset($_REQUEST['p'])) {
    if (!isset($_SESSION['project-name']))
        $_SESSION['project-name'] = '';
    echo "<form name='f1'>Confermare progetto: " .
    "<input type='text' value='{$_SESSION['project-name']}'/><input type<input type='submit'/></form>";
}
else {
    chdir('../..');
    $_SESSION['project-name'] = $_REQUEST['p'];
    if (!file_exists($_REQUEST['p'])) {
        echo '<p>Ooops, project ' . $_REQUEST['p'] . ' does not exist. <a href="general-index.php">go back</a>';
    } else {
        $_SESSION['project-name'] = $_REQUEST['p'];
        if (!isset($_SESSION['files-to-update']))
            $_SESSION['files-to-update'] = '';
        Build::setup(DirFunctions::realDir(getcwd()));
        GeneralIndex::load();
        //var_dump(GeneralIndeh::$index);
        echo "<li><b>Descriptors:</b><tt>";
        foreach (GeneralIndex::$index->descriptors as $simpleName => $f_list) {
            echo "<p><li style='margin-left:25px;color:#999'>$simpleName:";
            foreach ($f_list as $f => $c) {
                echo "<li style='margin-left:50px'>$f:$simpleName</li>";
            }
            echo "</li></tt>";
        }
        echo "<p><li><b>Macros:</b><tt>";
        foreach (GeneralIndex::$index->macros as $simpleName => $f_list) {
            echo "<p><li style='margin-left:25px;color:#999'>$simpleName:";
            foreach ($f_list as $f => $c) {
                echo "<li style='margin-left:50px'>$f:$simpleName</li>";
            }
            echo "</li></tt>";
        }
        echo "<p><li><b>Parts:</b><tt>";
        foreach (GeneralIndex::$index->parts as $simpleName => $f_list) {
            echo "<p><li style='margin-left:25px;color:#999'>$simpleName";
            foreach ($f_list as $f => $c) {
                echo "<li style='margin-left:50px'>$f:$simpleName</li>";
                foreach ($c->to as $t) {
                    echo "<li style='margin-left:75px; color:#393'>---&gt; ".get_class($t)." {$t->fullName}</li>";
                }
            }
            echo "</li>";
        }
        echo "<p><li><b>(Part) Files:</b><p><tt>";
        foreach (GeneralIndex::$index->files as $name => $f) {
            echo "<li style='margin-left:25px'>$name";
            foreach ($f->to as $t) {
                echo "<li style='margin-left:50px; color:#393'>---&gt; ".get_class($t)." {$t->fileName}</li>";
            }
            echo "</li>";
        }
		Build::end();
    }
}
WebPage::end();
?>
