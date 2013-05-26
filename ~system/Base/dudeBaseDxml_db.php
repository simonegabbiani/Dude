<?php

	function dudeBaseDxml_dbconnOpen($id, $c) {
		if (!isset($GLOBALS['DBConn.pdo']))	
			$GLOBALS['DBConn.pdo'] = array();
		var_dump($id);
		if (!isset($GLOBALS['DBConn.pdo'][$id])) {
			if (!isset($c[$id]))
				throw new Exception('D.xml:dbconn-open: unknown dbconn '.$id);
			$c = $c[$id];
			if ($c['vendor'] == 'mysql')
				$GLOBALS['DBConn.pdo_'.$id] 
					= new PDO("mysql:dbname={$c['db']}; host={$c['host']}", $c['username'], $c['password']);
			else if ($c['vendor'] == 'pgsql')
				$GLOBALS['DBConn.pdo_'.$id] 
					= new PDO("pgsql:dbname={$c['db']}; host={$c['host']}; user={$c['username']}; password={$c['password']}");
			else {
				throw new Exception('D.xml:dbconn-open: don\'t know how create "'.$c['vendor'].'" connection');
			}
			$GLOBALS['DBConn.vendor_'.$id] = $c['vendor']; //simply for cache
		}
	}

?>