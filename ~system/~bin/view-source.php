<?php
	$from = '';
	if (isset($_REQUEST['from']))
		@chdir(dirname($from = $_REQUEST['from']));
?>
<html>
	<body style='margin:0px;'>
	<?php if ($from != '') { ?>
		<h4 style='margin-bottom:0px; color:red; width:100%; background-color:#fcc; font-family:monospace'><a href='javascript:history.back()' style='color:red'><?=$from?></a></h4>
		<h4 style='margin-top:0px; color:blue; width:100%; background-color:#ccc; font-family:monospace'>&nbsp; -&gt; <?=$_REQUEST['file']?></h4>
	<?php } else { ?>
		<h4 style='color:red; width:100%; background-color:#fcc; font-family:monospace'><?=$_REQUEST['file']?></h4>
	<?php } ?>
		<tt style='display:block; padding:10px'>
<?php
	if (!file_exists($_REQUEST['file']))
		echo "File not found";
	else {
		$l = 1;
		foreach(@file($_REQUEST['file']) as $r) {
			if (strpos(trim($r), 'require_once') === 0)
				echo "$l: require_once '<a href='view-source.php?file=".substr(trim($r), 14, -2)."&from=".urlencode($_REQUEST['file'])."'>".substr(trim($r), 14, -2)."</a>';<br/>";
			else {
				echo "<pre style='margin:0px;'>$l: ".htmlspecialchars($r)."</pre>";
			}
			$l++;
		}
	}
?>
		</tt>
	</body>
</html>