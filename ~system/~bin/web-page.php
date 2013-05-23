<?php
define('START_TIME', WebPage::time());

class WebPage 
{

//------------------------------------------------

    public static function start($title = 'DUDE') {
		if (isset($_REQUEST['p'])) $q = '?p='.$_REQUEST['p']; else $q = '';
    ?>

<html>
	<head>
		<title><?=$title?></title>
		<style>
			.WarningMessage {display:inline; background-color:#ffc; padding:5px;}
		</style>
	</head>
    <body style="margin:0px">
    <div style='background:black; padding:4px; font-family:verdana; font-size:9pt; width:100%; font-weight:normal'>
        &nbsp; <a style='color:white' href='build-index.php<?=$q?>'>build</a>
        &nbsp; <a style='color:white' href='context-index.php<?=$q?>'>context</a>
        &nbsp; <a style='color:white' href='general-index.php<?=$q?>'>index</a>
		<? if ($q) { ?> &nbsp; <a style='color:#999' href='context-index.php'>change..</a> <? } ?>
        <!-- &nbsp; <a style='color:white' href='test-index.php'>test</a>-->
    </div>
    <div style='margin:20px'>

    <?php
    }
    
//------------------------------------------------
    
    public static function end() {
    ?>
	<p><small style='color:#999; font-family:arial,helveticam,serif; font-size:8pt;'>
		<? if (isset(CompileFile::$comp_count)) { ?> <?=CompileFile::$comp_count?> FILES COMPILED IN <?}?>
		TIME: <?=number_format(self::time() - START_TIME, 2)?> SECONDS
	</small></p>
    </div>
    </body>
</html>

    <?php
    }
	
	public static function time() {
		list($usec, $sec) = explode(" ", microtime());
		return ((float)$usec + (float)$sec);
	}
}
?>
