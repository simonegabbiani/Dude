<?php

require_once "parser.php"; //DocComponent::PART|MACRO

class IncorrectContextName extends Exception {}
class UnresolvedContextName extends Exception {}

//serialize
class Context {
    var $name; //url: ~run/{$name}.php OPPURE ~run/Path/Path/Path.php (quando $name includa un path)
    var $partStartPoint;
    var $defaultAttributes = array(); // 'name' => 'value'
    var $df;
    var $tmpDeleted = false;
    public function __sleep() {
        return array('name', 'partStartPoint', 'defaultAttributes', 'tmpDeleted', 'df');
    }
    public static function factory(DocFile $df, DOMElement $e, DocPart &$autoContext = null) {
        $name = $e->getAttribute($autoContext ? 'auto-context' : 'name');
		if (strpos($name, '/..') !== false || $name[0] == '.')
				throw new IncorrectContextName($name);
        $c = new Context();
        $c->df =& $df;
        if (!($part =& $autoContext)) {
            list($nsUri, $simpleName) = GeneralIndex::resolvePartAddress($e, $e->getAttribute('start-point'));
            $part =& GeneralIndex::$index->nearest(DocComponent::PART, $simpleName, $nsUri, $df->fileName, '');
            if (!$part)
                throw new UnresolvedPartAddressException("$nsUri:$simpleName");
        }
        else {
            $c->df =& $part->df;
        }
        $c->name = $name;
        $c->partStartPoint =& $part;
		//cannot check now ifthe attributes are correct, but now saves the values of which it find
		for($i=0; $i<$e->attributes->length; $i++)
			if (substr($e->attributes->item($i)->name, 0, 5) == 'attr-')
				$c->defaultAttributes[ substr($e->attributes->item($i)->name, 5) ] = $e->attributes->item($i)->value;
        return $c;
    }

}

class ContextFactory {
	
	//!?
	public static function buildURL(PartParser $p, StackSource $domain, $value) {
		list($name, $value) = explode($value, '(');
		$block[$i = 0] = strtok($string, ',');
		do {
			$a1 = strpos($block[$i], '\'');
			$v1 = strpos($block[$i], '"');
			$block[++$i] = strtok(',');
		} while ($tok !== false);
	}
    
    public static function buildPartDependencies(DocPart $startPoint, &$dc_dest, &$requiresMap, $applicationContext = false) {
		if ($applicationContext && $startPoint->useSession)
			throw new SessionInApplcationException($startPoint->fullName);		
		self::_findCmpRecursive($startPoint, $dc_dest, $requiresMap);
        $dc_dest[DocComponent::PART][$startPoint->id/*fullName*/] = $startPoint;
    }
    
	//it do recursion only for the parts 'dude-content-updater'
	//obtain a first list of file, components and a map of descriptors for the DocFile 
	public static function _findDscForFile(DocFile $f, &$f_buffer, &$df_dest, &$dc_dest, &$map, &$requiresMap, $applicationContext = false) {
		//f_buffer is useful to check cycles (loops?)
		if (!isset($f_buffer[$f->fileName]))
			$f_buffer[$f->fileName] = 0;
		else if ($f_buffer[$f->fileName] > 1)
			return;
		$f_buffer[$f->fileName]++;
		if ($applicationContext && GeneralIndex::$index->parts['dude-content-updater'][$f->fileName]->useSession)
			throw new SessionInApplicationException("See descriptors used in ".$f->fileName);
		$pp = GeneralIndex::$index->parts;
		foreach (GeneralIndex::$index->parts['dude-content-updater'][$f->fileName]->to as $d) {
			if (!isset($df_dest[$d->df->fileName])) {
				self::_findDscForFile($d->df, $f_buffer, $df_dest, $dc_dest, $map, $requiresMap);
				$df_dest[$d->df->fileName] = $d->df;
				if (count($d->df->r_creates)) {
					$requiresMap[] = array('server' => $d->df, 'host' => $f);
				}
			}
			if (!isset($dc_dest[$d->type][$d->id/*fullName*/])) {
				//if (!$useNumericKeysForParts)
					$dc_dest[$d->type][$d->id/*fullName*/] = $d;
				//else
				//	$dc_dest[] = $d;
			}
			if ($d instanceof DocDescriptor && !isset($map[$f->fileName][$d->fullName]))
				$map[$f->fileName][$d->df->fileName] = $d->simpleName;
		}
	}
	//recurions for every part referred to the specified component
    private static function _findCmpRecursive(DocComponent $parent, &$c_dest, &$requiresMap, $applicationContext = false) {
 		  foreach ($parent->to as $c) { //<cioè:> array_keys($parent->to[DocComponent::PART])
			if (!isset($f_dest[$c->df->fileName])) {
				if (count($c->df->r_creates) && $c->simpleName == 'dude-content-updater') {
					$requiresMap[] = array('server' => $c->df, 'host' => $parent->df);
				}
			}
         if (!isset($c_dest[$c->type][$c->id])) {
				if ($applicationContext = false && $c->useSession)
					throw new SessionInApplcationException($d->fullName);
				$c_dest[$c->type][$c->id/*fullName*/] = $c;
				self::_findCmpRecursive($c, $c_dest, $requiresMap);
         }
		}
    }
	//missing error for cross dependency between descriptors
	private static function buildFileDependencies(DocFile $f, DocComponent $startPart, &$df_dest, &$map, &$requiresMap, $applicationContext = false) {
		//note: the dude-content-updater is considered but never inserted in the lists
		$c_dest = array();
		$f_buffer = array();
		$df_dest = array($f->fileName => $f);
		self::_findCmpRecursive($startPart, $c_dest, $requiresMap, $applicationContext);
		foreach ($c_dest as $type => $cc_dest)
			foreach ($cc_dest as $c) {
				if (!isset($df_dest[$c->df->fileName])) {
					$df_dest[$c->df->fileName] = $c->df;
					//initialize the requiresMap partially here because findDscForFile does not include it for every df_dest inserted before
					foreach (GeneralIndex::$index->parts['dude-content-updater'][$c->df->fileName]->to as $t)
						if ($t->df->fileName == $c->df->fileName && count($t->df->r_creates) > 0) {
							$requiresMap[] = array('server' => $t->df, 'host' => $t->df);
						}
				}
			}
		foreach ($df_dest as $f) {
			self::_findDscForFile($f, $f_buffer, $df_dest, $c_dest, $map, $requiresMap, $applicationContext);
		}
	}
	
    public static function createRunFile(Context $x) {//usare fwrite
		$part = $x->partStartPoint;
		$outfile = Utils::encode($x->name, true).'.php';
		$cdir = addcslashes(str_replace('\\', '/', dirname(Utils::encode($x->name, true) . '.php')), '\'').'/';
		if ($cdir[0] == '/') $cdir = substr($cdir, 1); //else if ($cdir == './') $cdir = '';
		$rprefix = DirFunctions::rootPrefix($cdir);
		if ($rprefix == '') die('ROot prefix \'\'');
		$php = "<?php\n# $outfile\n";
		$php .= "define('PROJECT_NAME', '".addcslashes($_SESSION['project-name'], '\'')."');\n";
		$php .= "define('CONTEXT_NAME', '".addcslashes($x->name, '\'')."');\n";
		$php .= "define('CONTEXT_DIR', '$cdir');\n";
		$php .= "define('ROOT_PREFIX', '$rprefix');\n"; //minimo, deve essere './'
		$php .= "define('AUTO_UPDATE', ".ENABLE_AUTO_UPDATE.");\n";
		$php .= "define('AUTO_UPDATE_REQUIRES', ".ENABLE_AUTO_UPDATE_REQUIRES.");\n";
		$php .= "define('AUTO_UPDATE_FILES', ".ENABLE_AUTO_UPDATE_FILES.");\n"; 
		$php .= "//---------------------------------------------\n";
		//$php .= "ob_start();\n";
		$php .= "require_once '{$rprefix}~runtime.php';\n";
		$f_list = array();
		$map = array();
		$requiresMap = array();
		self::buildFileDependencies($part->df, $part, $f_list, $map, $requiresMap, false);
		$f_list[$part->df->fileName] = $part->df;
		if (count($map))
			foreach ($map as $fileWhereUsed => $c_list) {
				$php .= "\$X_TOUCH['".Utils::encode($fileWhereUsed)."'] = " . (int)filectime($GLOBALS['compiler.projectdir'] . $fileWhereUsed) . ";\n";
				foreach ($c_list as $dFileName => $dSimpleName)
					$php .= "\$X_MAP['".Utils::encode($fileWhereUsed)."']['$dFileName:$dSimpleName'] = '$dSimpleName';\n";
			}
		else
			$php .= "\$X_MAP = array();\n";
		foreach ($f_list as $df) {
			$php .= "require_once '" . $rprefix . Utils::chext(($df->isLib ? 'lib/' : '') . $df->fileName, 'xml', 'php') . "';\n";
		}
		//customized asset requires
		foreach ($requiresMap as $r) {
			if (isset($r['server']->r_creates))
				foreach ($r['server']->r_creates as $f) {//errori saranno verificati alla creazione del context
					list($f) = $f;
					//the create-require is only realtive, translated as absolute when compiled/installed
					$req_f = 'ROOT_PREFIX.\'lib/'.($r['server']->isLib ? 'lib_' : '').Utils::encode($r['host']->fileName).'__'.Utils::encode($r['server']->fileName).'__'.$f.'\'';
					$php .= "@include_once $req_f;\n"; //TODO: eventuale errore se non è presente?
				}
		}
		//$php .= "ob_end_clean();\n";
		$php .= "require '{$rprefix}~auto_update.php';\n";
		$php .= "//---------------------------------------------\n";
		$php .= "Piece::checkConfigurationReady();\n";
		$php .= "\$started = Piece::checkApplicationReady();\n";
		$php .= "Piece::checkSessionReady(\$started);\n";
		//$php .= "var_dump(\$_SESSION['DBConn.profiles']);\n";
		$php .= "include ROOT_PREFIX.'on-context-start.php';\n";
		$php .= "Piece::executeHandlers(\$X_MAP, Piece::\$HANDLERS, 'context-start');\n";
		$php .= "\$DEF_ARGS = array("; $cm = ''; //defaultArguments in realtà non ripristinato (superfluo)
		foreach ($x->partStartPoint->attributesIndex as $n) {
			if (isset($x->defaultAttributes[$n]))
				$php .= $cm . "'$n' => '".addcslashes($x->defaultAttributes[$n], '\'')."'"; 
			else
				$php .= $cm . "'$n' => null"; 
			$cm = ', ';
		}
		$php .= ");\n";
        $php .= "\$p = Piece::executeStartPoint('" . Utils::encode($x->partStartPoint->fullName) . "');\n";
		$php .= "if (!Piece::getExecutionStatus()) echo Piece::getBuffer(0);\nPiece::resetBuffer();\n";
		$php .= "Piece::executeHandlers(\$X_MAP, Piece::\$HANDLERS, 'context-end');\n";
		$php .= "include ROOT_PREFIX.'on-context-end.php';\n";
		$php .= "Piece::onTerminate();\n";
		$php = "\n\n$php\n?>";
        $outfile = $GLOBALS['compiler.projectdir'] . "~run/$outfile";
        $outdir = dirname($outfile); 
		$cwd = getcwd();
        @mkdir($outdir, 0777, true);
        file_put_contents($outfile, $php);
    }
	
	public static function createEventFiles() {
		$e = array_flip(Compiler::$Events);
		$general_c_list = array();
        $general_f_list = array();
		$map = array();
		$requiresMap = array();
		$php = array_fill(0, 7, '');//escludo build
		$f_buffer = array();
		foreach (GeneralIndex::$index->parts['dude-content-updater'] as $p)
			self::_findDscForFile($p->df, $f_buffer, $general_f_list, $general_c_list, $map, $requiresMap, true);//poiché non è ancora possibile relazionare i descrittori in modo diverso (a meno che non metto "using")
		
		foreach ($map as $fileWhereUsed => $d_list) {
			if (!isset($f_list[$fileWhereUsed])) {
				foreach (array_keys($php) as $i)
					$f_list[$i][$fileWhereUsed] = GeneralIndex::$index->files[$fileWhereUsed];
			}
			foreach ($d_list as $dFileName => $dSimpleName) {
			//TODO: qui manca EX_TOUCH
				foreach (GeneralIndex::$index->descriptors[$dSimpleName][$dFileName]->handlers as $p) {
					if ($p->runOn == 'configuration') {
						if (!isset($f_list[$p->df->fileName])) $f_list[6][$p->df->fileName] = $p->df;
						$php[6] .= "\$EX_MAP['".Utils::encode($fileWhereUsed)."']['$dFileName:$dSimpleName'] = '$dSimpleName';\n";
					}
					else if ($p->runOn == 'application-start') {
						if (!isset($f_list[$p->df->fileName])) $f_list[0][$p->df->fileName] = $p->df;
						$php[0] .= "\$EX_MAP['".Utils::encode($fileWhereUsed)."']['$dFileName:$dSimpleName'] = '$dSimpleName';\n";
					}
					else if ($p->runOn == 'application-end') {
						if (!isset($f_list[$p->df->fileName])) $f_list[1][$p->df->fileName] = $p->df;
						$php[1] .= "\$EX_MAP['".Utils::encode($fileWhereUsed)."']['$dFileName:$dSimpleName'] = '$dSimpleName';\n";
					}
					else if ($p->runOn == 'session-start') {
						if (!isset($f_list[$p->df->fileName])) $f_list[2][$p->df->fileName] = $p->df;
						$php[2] .= "\$EX_MAP['".Utils::encode($fileWhereUsed)."']['$dFileName:$dSimpleName'] = '$dSimpleName';\n";
					}
					//not supported
					else if ($p->runOn == 'session-end') {
						if (!isset($f_list[$p->df->fileName])) $f_list[3][$p->df->fileName] = $p->df;
						$php[3] .= "\$EX_MAP['".Utils::encode($fileWhereUsed)."']['$dFileName:$dSimpleName'] = '$dSimpleName';\n";
					}
				}
			}
		}
		
		$php[0] .= "if (!session_id()) session_start();\n";
		foreach (GeneralIndex::$index->session_var as $n => $v)
			$php[0] .= "unset(\$_SESSION['".addcslashes($n, '\'')."']);\n";
		
		for ($i=0; $i<count($php); $i++) {
			$php[$i] = "\$EX_MAP = array();\n".$php[$i];
			if (isset(GeneralIndex::$index->files['~global.xml'])) {
				foreach (GeneralIndex::$index->files['~global.xml']->parts as $p) {
					if ($p->forHandling || !$p->runOn) if (isset($p->isUpdater) && !$p->isUpdater) die(var_dump($p).'errore interno global');
					if ($p->runOn == $e[$i]) {
						$ev_part[$i] = $p;
						$c_list = array();
						$requiresMap = array();
						self::buildPartDependencies($p, $c_list, $requiresMap, $i < 2);
						foreach ($c_list as $type => $c_list)
							foreach ($c_list as $c);
								if (!isset($f_list[$i][$c->df->fileName]))
									$f_list[$i][$c->df->fileName] = $c->df;
					}
				}
			}
			if (isset($f_list[$i])) {
				foreach ($f_list[$i] as $f)
					$php[$i] .= "require_once '".Utils::chext(($f->isLib ? 'lib/' : '') . $f->fileName, 'xml', 'php') . "';\n";
			}

			//customized asset requires
			foreach ($requiresMap as $r) {
				if (isset($r['server']->r_creates))
					foreach ($r['server']->r_creates as $f) {//errori saranno verificati alla creazione del context
						list($f) = $f;
						//the create-require is only realtive, translated as absolute when compiled/installed
						$req_f = 'ROOT_PREFIX.\'lib/'.($r['server']->isLib ? 'lib_' : '').Utils::encode($r['host']->fileName).'__'.Utils::encode($r['server']->fileName).'__'.$f.'\'';
						$php[$i] .= "@include_once $req_f;\n"; //TODO: eventuale errore se non è presente?
					}
			}
			
			$php[$i] = "<?php\n# on-{$e[$i]}\n" . $php[$i];
			$php[$i] .= "//---------------------------------------------\n";
			//$php[$i] .= "ob_start();\n";
			if ($i < 4 || $i == 6)
				$php[$i] .= "Piece::executeHandlers(\$EX_MAP, Piece::\$HANDLERS, '{$e[$i]}');\n";
			if (isset($ev_part[$i]))
				$php[$i] .= "Piece::executeStartPoint('".Utils::encode($ev_part[$i]->fullName)."');\n";
			//$php[$i] .= "echo Piece::getBuffer(0);\n";
			//if (!defined('EXCLUDE_EVENT_LOG_'.$e[$i]))
			//	$php[$i] .= "file_put_contents('{$e[$i]}.log', ob_get_clean());\n";
			//$php[$i] .= "ob_end();\n";
			$php[$i] .= "?>";
			//$dir = ($i != 6) ? '~run' : '~';
			file_put_contents($GLOBALS['compiler.projectdir'] . "~run/on-$e[$i].php", $php[$i]);
		}	
	}
	
	//public static function executeBuildEvent($ll, $ff) {

}

?>
