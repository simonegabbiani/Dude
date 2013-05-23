<?php

class AutoUpdateDoneButNotRedirected extends Exception {}

if (AUTO_UPDATE && !Piece::getExecutionStatus() && $_SERVER['REQUEST_METHOD'] == 'GET' 
			&& (!function_exists('session_status') || session_status() != PHP_SESSION_DISABLED)) {
	if (!session_id())
		session_start();
	if (!isset($_SESSION['auto-update-count-check']) || strpos($_SERVER['QUERY_STRING'], '--reset-auto-update-check--') !== false) {
		$_SESSION['auto-update-count-check'] = 1;
	}
	else if (++$_SESSION['auto-update-count-check'] > 2) {
		die("<html><body>Sometime this message could appear: the system want to be sure that the auto-update process does not generated a loop. <a href='{$_SERVER['REQUEST_URI']}?--reset-auto-update-check--'>Click here to continue</a>. If this message persists there is an error in your source file of some kind that the compiler does not recognize.. please check in the development environment</html></body>");
	}
	$FILES_UPDATE_LIB = 
		$FILES_UPDATE_PRJ = array();
	if (file_exists('../'.ROOT_PREFIX.'~global.xml') 
			&& @filemtime(ROOT_PREFIX.'~global.php') < @filemtime('../'.ROOT_PREFIX.'~global.xml'))
		$FILES_UPDATE_PRJ[] = '~global.xml';
	if (Piece::$AUTO_UPDATE)
		foreach (Piece::$AUTO_UPDATE as $f => $mtime)
		{
			if ($mtime < 0) {
				if (- $mtime < filemtime('../../'.ROOT_PREFIX.$f))
					$FILES_UPDATE_LIB[] = $f;
			}
			else {
				if ($mtime < filemtime('../'.ROOT_PREFIX.$f))
					$FILES_UPDATE_PRJ[] = $f;
			}
		}
	 if ($FILES_UPDATE_LIB || $FILES_UPDATE_PRJ) {
		$dir = getcwd();
		chdir(ROOT_PREFIX.'../..');
        require_once '~system/~bin/build.php';
        $_SESSION['project-name'] = PROJECT_NAME;
        Build::setup(DirFunctions::realDir(getcwd()));
		try {
            if (!Compiler::build($FILES_UPDATE_PRJ, $FILES_UPDATE_LIB, false)) {
                die("<html><body>The project need a complete rebuild, cannot continue with the auto-update process (index file not found).</html></body>");
			}
            if (!isset(GeneralIndex::$index->context[CONTEXT_NAME])) {
                die("<html><body>Due of changes in the source files, this context does not exist anymore. Please check the <a href='DudeEnvironment/~run/ContextList.php'>context-list</a></html></body>");
            }
            else {
                ContextFactory::createRunFile(GeneralIndex::$index->context[CONTEXT_NAME]);
				if (isset($FILES_UPDATE_PRJ) && array_search('~global.xml', $FILES_UPDATE_PRJ) !== false)
	               ContextFactory::createEventFiles();
                //ob_clean();
                //$link = Utils::encode(CONTEXT_NAME, true).'.php';
                $link = $_SERVER['REQUEST_URI'];
				$link = str_replace('?--reset-auto-update-check--', '', $link);
                //throw new AutoUpdateDoneButNotRedirected('Please <a href="'.$link.'">click here</a> to continue');
                //header('Location:'.$link);//contatore per cicli
				die("<html><body>The file has been updated, please wait or <a href='$link'>click here</a></html></body>");
            }
        } catch (Exception $e) {
            Piece::throwLoadError('build', get_class($e).': '.$e->getMessage(), $e->getTraceAsString());
			chdir($dir);
        }
		Build::end();
    }
	//if there is an auto-update of the sourcefiles, it cannot know if really the assets are yet missing
	else if (Piece::$FILES && (AUTO_UPDATE_REQUIRES || AUTO_UPDATE_FILES)) {
		//var_dump("CWD: ".getcwd() . " ROOT_PREFIX: " . ROOT_PREFIX);
		$COPY_LOG = array();
		foreach (Piece::$FILES as $f => $lib) {
			list($lib, $parse) = $lib;
			if ($lib) $lib = '../';
			$f = substr($f, 1);
			if (($mtime = filemtime($lib.'../'.ROOT_PREFIX.$f)) === false) {
				Piece::throwLoadError('conf', 'asset-file-not-found', $f);
				break;
			}
			$d = $lib ? 'lib/' : '';
			$src = $lib.'../'.ROOT_PREFIX.$f;
			$dest = ROOT_PREFIX.$d.$f;
			$COPY_LOG[] = "Checking $src\n";
			if (@filemtime($dest) < $mtime) {
				@mkdir(dirname($dest), 0777, true);
				$ddd = getcwd();
				if ($parse) {//todo: errors, execution_status, ecc.
					$content = file_get_contents($src);
					file_put_contents($dest, str_ireplace('{%~.rootprefix%}', ROOT_PREFIX, $content));
				} else {
					$COPY_LOG[] = "\t..copied to $dest\n";
					if (copy($src, $dest) === false) {
						var_dump(realpath($src));
						var_dump(realpath($dest));
					}
					if (substr($dest, -4) == '.zip') {
						$zip = new ZipArchive;
						if ($zip->open($dest) === TRUE) {
							$zip->extractTo(dirname($dest));
							$zip->close();
						} else {
							var_dump('FAILED EXTRACT ZIP FILE: '.$dest);
						}
					}
				}
			} else
				$COPY_LOG[] = "\t ..not copied\n";
		}
		$FILES = array('~application-manage', '~unready', '~auto_update', '~error', '~runtime');
		foreach ($FILES as $f) {
			//var_dump(realpath(ROOT_PREFIX.'../../'));
			if (filemtime($src = ROOT_PREFIX.'../../~system/~runtime/'.$f.'-ORIGINAL.php') > filemtime($dest = ROOT_PREFIX.$f.'.php'))
			 { copy($src, $dest); $COPY_LOG[] = "Copied also $src\n"; }
		}
	}
}

if (isset($_SESSION))
	unset($_SESSION['auto-update-count-check']);

?>