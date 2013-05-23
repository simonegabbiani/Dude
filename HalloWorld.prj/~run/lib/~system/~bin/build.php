<?php

if (!defined('BUILD_PHP')) {
	define('BUILD_PHP', 1);

//internal configuration, leave as is
define('ALLOW_CHAINED_HANLDERS', true); //cannot disable, for now
define('CREATE_DEFAULT_ALIASES', true); //recommended
define('USE_SELF_RENDER', false); //recommended
define('EXCLUDE_EVENT_LOG_context-start', 0);
define('EXCLUDE_EVENT_LOG_context-end', 0);
define('ENABLE_SMART_ARGUMENTS_ON_CONTEXT_URL', true);
define('PART_ATTRIBUTES_WRITE_PRIVILEGE', false); //runtime support to default values for optional parameters
define('MACRO_ATTRIBUTES_WRITE_PRIVILEGE', false); //see notes on parser.php openTag..macro
define('COMPILER_CHECKS_ASSETS', true); //not used
define('ENABLE_AUTO_UPDATE', true); //auto-update funcionality on source files
define('ENABLE_AUTO_UPDATE_REQUIRES', true); //expand the auto-update at "asset require"
define('ENABLE_AUTO_UPDATE_FILES', true); //expand auto-update to "asset file"
define('ACCEPT_YES_NO_AS_BOOLEAN_VALUES', true); //cannot disable, for now, see RealRValue()
define('KEEP_EXTERNAL_NAMESPACES_IN_OUTPUT', false); //not yet ready because it misses the name prefixes in output ... (see process_tag())
define('DEBUG__RESTART_APPLICATION_EVERY_UPDATE', false); //I have to resolve: there is a "conflict" between the build e the application, when I do a "clean", who does shut down the application? in typical conditions we should automatize: rebuild->pplication-end->install->application-start, with options/warning when the update upgrades also application things...
define('DENY_URL_TO_UNRESOLVED_CONTEXT', false);
define('STRICT_KEYWORD_NAMESPACES', false); //if true, when it await a name of a component name -via namespace- but it does not exist and is a name of an instruction, it consider this last
define('ENABLE_HTML_URL_REWRITE', true);
define('REQUIRE_URL_REQUIRES_APPLICATION', false); //require-url also fot context and session

require_once "functions.php";
require_once "parser.php";
require_once "expr.php";
require_once "context.php";
require_once "exception-tags.php";

class PHPWarning extends Exception {}
class UnresolvedNamespace_PathDoesNotExists extends Exception {}
class UnsupportedNamespaceFeature extends Exception {}
class IllegalCharacterInFileOrComponentName extends Exception {}
class NameConflictException extends Exception {}
class UnsupportedNamespaceFeatureException extends Exception {}

class Build {
	const ERR_FATAL = 1;
	const ERR_WARNING = 2;
	const ERR_MUST_REBUILD = 4;
    public static function setup($rootDir, $dontManageErrors = false) {
			//var_dump(substr($rootDir, -1));
		  if (substr($rootDir, -1) != '/') $rootDir .= '/';
		  //var_dump("rootDir = $rootDir");
        if (!isset($_SESSION['project-name']) || !$_SESSION['project-name']) die("Build::setup(): session 'project-name' not set");
		  //var_dump('SESSION project-name: '.$_SESSION['project-name']);
        $GLOBALS['compiler.rootdir'] = str_replace('\\', '/', $rootDir);
		  //var_dump("compiler.rootdir = ".$GLOBALS['compiler.rootdir']);
        $GLOBALS['compiler.libdir'] = $rootDir . '~system/'; //può darsi che sia sconveniente.. usare rootdir
		  //var_dump("compiler.libdir = ".$GLOBALS['compiler.libdir']);
        $GLOBALS['compiler.projectdir_'] = $rootDir . $_SESSION['project-name'];
        //var_dump("compiler.projectdir_ = ".$GLOBALS['compiler.projectdir_']);
		  $GLOBALS['compiler.projectdir'] = $GLOBALS['compiler.projectdir_'] . '/';
		  //var_dump('build.php: '.$GLOBALS['compiler.projectdir']);
		//DISABLED DUE OF A BUG (crash)
		if (!$dontManageErrors) 
			set_error_handler("Build::phpErrorHandler", E_NOTICE + E_WARNING);
		Compiler::$Dude_Root_Keywords = array_flip(Compiler::$Dude_Root_Keywords);
		Compiler::$Dude_Code_Keywords = array_flip(Compiler::$Dude_Code_Keywords);
	}
	public static function end() {
		restore_error_handler();
	}
	public static function phpErrorHandler($errno, $errstr, $errfile, $errline) {
		//var_dump($errno, $errstr, $errfile, $errline);
		if (error_reporting() === 0) return;
		@list($tok1, $tok2) = explode('()', $errstr, 2);
		if ($tok1 == 'DOMDocument::loadXML' && strpos($tok2, 'xmlns:') !== false && strpos($tok2, 'is not absolute in Entity') !== false)
			return;
		else if (defined('IGNORE_DOM_PREFIX_WARNINGS') && $tok1 == 'DOMDocument::loadXML' && strpos($tok2, ': Namespace prefix') !== false && strpos($tok2, 'is not defined in Entity') !== false)
			return;
		echo "<li style='color:#999'>$errno - $errstr - $errfile - $errline";
		if ($errno == 2)
			throw new PHPWarning($errstr + "\n" + $errfile);
		self::error(1, $errstr, $errfile, $errline);
	}
	public static function error($type, $message, $file, $line = null) {
		if ($type & self::ERR_MUST_REBUILD)
			unlink($GLOBALS['compiler.projectdir_'].'~/general.index');
		if ($line) $line = ": $line";
		echo "<div style='padding:20px; background-color:red; font-size:12pt;"
				."font-weight:bold; border:10px solid orange; color:white;'>"
				."<tt><span style='color:yellow'>ERROR: $message</span></tt>"
				."<br/>$file <span style='color:yellow'>$line</span>";
		if ($type & self::ERR_MUST_REBUILD)
			"The project will need a complete rebuild.";
		echo "</div>";
		if ($type != self::ERR_WARNING)
			die();
	}
}

class GeneralIndex {

    static $index;
	var $gidmax = 0;
    var $parts = array();
    var $macros = array();
    var $descriptors = array();
    var $files = array();
	var $files_from = array(); // 'filename' => array(id, id, id)
    var $context = array();
	var $start_points = array();
	var $session_var = array();
	var $anonymous_url = array();
    var $rt_data = array();
	var $contexts_ref = array();
	
	public function expand($f, &$list_ff, &$list_ll, &$keys_cache) {
		foreach($list_ll as $f)
			if (!isset($keys_cache[$f])) $keys_cache[$f] = 1;
		foreach($list_ff as $f)
			if (!isset($keys_cache[$f])) $keys_cache[$f] = 1;
		if (isset($this->files_from[$f]))
		  foreach ($this->files_from[$f] as $df)
			if (!isset($keys_cache[$df->fileName])) {
				if (substr($df->fileName, 0, 8) == '~system/')
					$list_ll[] = $df->fileName; 
				else
					$list_ff[] = $df->fileName;
				$keys_cache[$df->fileName] = 1;
			}
	}	
	
	public function notifyFilesToUpdate($files) {//I can use: all
        foreach ($this->parts as $p => $f_list)
            foreach ($files as $f)
                if (isset($f_list[$f]))
                    unset($this->parts[$p][$f]);
        foreach ($this->macros as $p => $f_list)
            foreach ($files as $f)
                if (isset($f_list[$f]))
                    unset($this->macros[$p][$f]);
        foreach ($this->descriptors as $p => $f_list)
            foreach ($files as $f)
                if (isset($f_list[$f]))
                    unset($this->descriptors[$p][$f]);
        foreach ($this->context as $c)
            foreach ($files as $f)
                if ($c->partStartPoint->df->fileName == $f) {
					$c->tmpDeleted = true; 
					unset($this->start_points[$c->partStartPoint->id]);
				}
        foreach ($files as $f) {
            if (isset($this->files[$f]))
                unset($this->files[$f]);
			if (isset($file->files_form[$f]))
				unset($this->files_from[$f]);
			foreach ($this->files_from as $k => $df_list)
				if (isset($df_list[$f]))
					unset($this->files_from[$k][$f]);
		}
    }
	
	//public function notifyFile(DocFile

    //all on ->parts|macro also for update, but they have ->update true
    public function notifyPart(DocComponent &$p) {
        if (preg_match('/[\!\?\='.chr(168).']/', $p->fullName))
            throw new IllegalCharacterInFileOrComponentName($p->fullName);
        if (!isset($this->parts[$p->simpleName]))
            $this->parts[$p->simpleName] = array();
//		$xxx =& $this->parts;
//		$ccc =& $this->context;
        if (!isset($this->parts[$p->simpleName][$p->df->fileName])) {
            $this->parts[$p->simpleName][$p->df->fileName] =& $p;
			$this->files[$p->df->fileName] =& $p->df; //[$p->id] =& $p;
		}
    }

    public function notifyMacro(DocComponent &$p) {
        if (preg_match('/[\!\?\='.chr(168).']/', $p->fullName))
            throw new IllegalCharacterInFileOrComponentName($p->fullName);
        if (!isset($this->macros[$p->simpleName]))
            $this->macros[$p->simpleName] = array();
        if (!isset($this->macros[$p->simpleName][$p->df->fileName])) {
            $this->macros[$p->simpleName][$p->df->fileName] =& $p;
			$this->files[$p->df->fileName] =& $p->df; //[$p->id] =& $p;
		}
    }

    public function notifyDescriptor(DocComponent &$p) {
        if (preg_match('/[\!\?\='.chr(168).']/', $p->fullName))
            throw new IllegalCharacterInFileOrComponentName($p->fullName);
        if (!isset($this->descriptors[$p->simpleName]))
            $this->descriptors[$p->simpleName] = array();
        if (!isset($this->descriptors[$p->simpleName][$p->df->fileName])) {
            $this->descriptors[$p->simpleName][$p->df->fileName] =& $p;
			$this->files[$p->df->fileName] =& $p->df; //[$p->id]=& $p;
		}
    }
	
	public function notifyFrom(DocComponent $source, DocComponent $dest) {
		$this->files_from[$dest->df->fileName][ $source->df->fileName ] =& $source->df;//usato da expand
	}
	
    public function notifyContext(Context &$c, DOMNode $errNode) { //notificato da CompileFile e auto-context
        if (preg_match('/[\!\?\='.chr(168).']/', $c->name))
            throw new IllegalCharacterInFileOrComponentName($p->fullName);
        if (!isset($this->context[$c->name]) || $this->context[$c->name]->tmpDeleted) {
            $this->context[$c->name] = $c;
			$this->start_points[$c->partStartPoint->id] = $c;
		}
        else if ($this->context[$c->name]->df->fileName != $c->df->fileName)
           Build::error(1, "Context {$c->name} already defined in other file ".$c->df->fileName, $this->context[$c->name]->df->fileName, $errNode->getLineNo());
        $this->context[$c->name]->tmpDeleted = false;
    }
	 
	public function notifyContextURL(DocComponent $from, $toContextName) {
		if (!isset($this->contexts_ref[$toContextName]))
			$this->contexts_ref[$toContextName] = array();
		$this->contexts_ref[$toContextName][ $from->fullName ] = 1;
		$from->to_context[ $toContextName ] = 1;
	}
	
	public function notifySessionVar($var) {
		$this->session_var[$var] = true;
	}
	
	public function notifyAnonymousContext(DocComponent $caller, &$arguments) {
		$this->anonymous_url[$caller->id] =& $arguments;
	}

    public static function load($clean = false) {
	   $f = $GLOBALS['compiler.projectdir'] . '~/general.index';
		//var_dump("index file: $f");
		if (!isset($GLOBALS['compiler.rootdir']))
			throw new Exception('missing Build::setup()');
        if (!$clean && file_exists($f)) {
            GeneralIndex::$index = unserialize(file_get_contents($f));
			return true;
		}
        GeneralIndex::$index = new GeneralIndex();
    }

    public function __sleep() {
        $this->rt_data = array();
        return array('parts', 'macros', 'descriptors', 'files', 'rt_data', 'files_from', 'contexts_ref', 
			'context', 'anonymous_url', 'start_points', 'gidmax', 'session_var'); //, 'all_components');
    }

    public static function save() {
        @mkdir($f = $_SESSION['project-name'] . '/~/', 0777, true);
        file_put_contents("$f/general.index", @serialize(GeneralIndex::$index));
    }

    //chdir on the file it is compiling
    static $uri_cache = array();

    //non passare a nearest() un nsUri "NS_EXTERN" altrimetni genera errore, verificare prima.
    public function nearest($type, $simpleName, $nsUri, $fromWhereCmpFile, $fromWhereCmpName, $quickStop = false) {
        if ($type == DocComponent::MACRO)
            $where =& $this->macros; 
        else if ($type == DocComponent::DESCRIPTOR)
            $where =& $this->descriptors;
        else
            $where =& $this->parts;
        if (!isset($where[$simpleName]))
            return false;
		
		//if ($simpleName == 'Context-Info') {
		//	echo 'Context-Info';
		//}
			//echo "<li>type=$type, simplename=$simpleName, nsuri=$nsUri, fromcmpfile=$fromWhereCmpFile, fromcmpname=$fromWhereCmpName";
		  if ($nsUri == 'http://local/Dude') $nsUri = '';
        if (!$nsUri && isset($where[$simpleName][$fromWhereCmpFile]))
            return $where[$simpleName][$fromWhereCmpFile];
        if (substr($nsUri, 0, 5) == 'http:') throw new UnsupportedNamespaceFeatureException($nsUri);
        //var_dump(self::$uri_cache);
        $_nsUri = (!$nsUri ? DEFAULT_NAME : $nsUri);//altrimenti restituisce 'illegal offset type
        if (isset(self::$uri_cache[$fromWhereCmpFile][$fromWhereCmpName][$_nsUri][$simpleName])) {
			return self::$uri_cache[$fromWhereCmpFile][$fromWhereCmpName][$_nsUri][$simpleName];
		}
        $ns = $nsUri;
        $finded = false;
        $i = -1;
        if ($ns != '' && $ns[0] == '/') $ns = $GLOBALS['compiler.projectdir_'] . $ns;
        if ($ns != '' && substr($ns, 0, 2) == '~/') {$lib = true; $ns = $GLOBALS['compiler.libdir'] . substr($ns, 2); }
        if (($ns = realpath($ns)) === false)
            throw new UnresolvedNamespace_PathDoesNotExists($nsUri);
        $ns = str_replace('\\', '/', $ns);
        if (!is_dir($ns)) {
			if (isset($lib))
				$p = substr($ns, strlen($GLOBALS['compiler.rootdir']));
			else
	            $p = substr($ns, strlen($GLOBALS['compiler.projectdir']));
            if (isset($where[$simpleName][$p])) return $where[$simpleName][$p];
            return false;
        }
        foreach ($where[$simpleName] as $c) {
            //TODO: supporto libreria!
            if ($c->df->isLib) $file = $GLOBALS['compiler.rootdir'].$c->df->fileName;
            else $file = $GLOBALS['compiler.projectdir'].$c->df->fileName;
            //TODO: togliere da qui str_replace e usare ovunque lo slash del filesystem host e non sempre '/'
            $mi = DirFunctions::in_dir(/*requested ns:*/ $ns, /*comparison component:*/ str_replace('\\','/',dirname($file))); //DirFunctions::realDir(dirname($c->df->fileName)));
            if ($mi === false)
                continue;
            if ($mi < $i || $i == -1) {
                $i = $mi;
                $finded = $c;
            } else if ($mi == $i)
                throw new NameConflictException($c->fullName);
        }
        return self::$uri_cache[$fromWhereCmpFile][$fromWhereCmpName][$nsUri][$simpleName] = $finded;
    }

    public static function resolvePartAddress($node, $name) {
        $e = explode(':', $name);
        if (!isset($e[1]) || !$e[0])
            $e = array(0 => null, 1 => array_pop($e));
        $e[0] = $node->lookupNamespaceURI($e[0]);
        return $e;
    }
}

class Log {
	const UNRESOLVED_CONTEXT_NAME = 1;
	static $func = '';
	static $log = array();
	public static function setLogHost($func) { self::$func = $func; }
	public static function warning($type, $subject, $message, $file, DOMNode $node) {
		self::$log[$type] = array($subject, $message, $file, $node);
		//func...
		self::message($message, $file, $node);
	}
	public static function message($message, $file, DOMNode $node = null) {
		$n = $node ? ' : '.$node->getLineNo() : '';
		if (!self::$func) echo "<div><tt>Warning: ".htmlspecialchars($message)." $file $n</tt></div>";
		else die('LogHost: function call todo');
	}
	public static function save($fileName = 'log.info') {
		file_put_contents($GLOBALS['compiler.projectdir_']."~/$fileName", serialize($log));
	}	
}


define('DEFAULT_NAME', ' ');
define('RT_NODE', 0);
define('RT_NODE_C', 1);

class Compiler {
	
	static $Events = array(
		'application-start' => 0, 'application-end' => 1, 'session-start' => 2, 
		'session-end' => 3, 'context-start' => 4, 'context-end' => 5, 
		'configuration' => 6
		);
	
	static $Reserved_Part_Attributes = array( 'as', 'name' );
	static $Reserved_Macro_Attributes = array( 'as', 'name' );
	
	static $Dude_Root_Keywords = array( 'set', 'part', 'define-macro', 
		'define-descriptor', 'asset', 'macro-container' );
	
	static $Dude_Code_Keywords = array( 'code', 'php', 'set', 'capture', 
		'sub-elements', 'if-subelement-exists', 'use', 'export' );
	
    static $dom; //cache
	
	static $fullBuild = false;

    const DUDE_NAMESPACE_URI = 'http://local/Dude';
	
	//move in PartParser. it could be set on source files 
	//at the mooment it is for XML/HTML only, and not for cData only for attribute values
	public static function outputEntitiesFilter($s) { return htmlspecialchars($s); } //only HTML
	
    public static function fetchMacroNode(DocMacro $m) { //preload
        // if already done (through CompileFile) return RT_NODE
        if (isset($m->rt_data[RT_NODE])) {
            $m->rt_data[RT_NODE_C]++;
            return $m->rt_data[RT_NODE];
        }
        if ($m->code != null)
            $c = $m->code; 
        else
            $c = $m->loadCode();
        $dom = new DOMDocument();
        $dom->loadXML($c);
        $m->rt_data[RT_NODE_C] = 0;
        $m->rt_data[RT_NODE] = $dom->documentElement;
        return $m->rt_data[RT_NODE];
    }

    public static function readProjectSourceFileList(&$f, &$l, $doLib = false) {
        $d = array();
        DirFunctions::getAllSourceDirectories_Project($_SESSION['project-name'], $d);
        foreach ($d as $dir)
            DirFunctions::getSourceFileList($_SESSION['project-name'], $dir, $f);
        if (!$doLib)
            return;
        $d = array();
        DirFunctions::getAllSourceDirectories_Library($d);
        foreach ($d as $dir)
            DirFunctions::getSourceFileList(null, $dir, $l);
    }

    public static function build($ff, $ll, $rebuildLibraries = false) {
		if (!isset($GLOBALS['compiler.rootdir'])) die('non è stato chiamato Build::setup()');
		if (defined('FORCE_REBUILD_LIB') && FORCE_REBUILD_LIB) $rebuildLibraries = true;
        if (!is_array($ff)) $ff = array();
        if (!is_array($ll)) $ll = array();
        if (!file_exists('~system/~bin'))
            throw new Exception('Errore directory, non mi trovo sulla root Applicazione (2)');
		@mkdir('~system/~/');
      @mkdir($_SESSION['project-name'] . '/~');
		if (!GeneralIndex::load(!$ff && !$ll && $rebuildLibraries) && defined('CONTEXT_NAME'))
			return false;
		if (!$ff && !$ll) {
			self::$fullBuild = true;
            self::readProjectSourceFileList($ff, $ll, $rebuildLibraries);
        } else {
            $keys_cache = array(); //array_fill_keys($filesToUpdate, 1); di modo che expand adesso faccia diff. tra lib e progetto
			$i = 0; while ($i < count($ll)) {
				GeneralIndex::$index->expand($ll[$i++], $ff, $ll, $keys_cache);
			}
            $i = 0; while ($i < count($ff))
                GeneralIndex::$index->expand($ff[$i++], $ff, $ll, $keys_cache);
        }
        HTML::$tags = @array_fill_keys(HTML::$tags, 1);
        GeneralIndex::$index->notifyFilesToUpdate($ff);
        GeneralIndex::$index->notifyFilesToUpdate($ll);
        
        $pp = array(); $lp = array();
		
		$GLOBALS['compiler.filebasedir'] = $GLOBALS['compiler.rootdir'];
		foreach ($ll as $f) {
			$file = pathinfo($f);
			$e = file_exists($_SESSION['project-name'] . '/~run/' . $file['dirname']);
			$f = new CompileFile($f, true);
			if ($f->df) $lp[] = $f;
		}

		$GLOBALS['compiler.filebasedir'] = $GLOBALS['compiler.projectdir'];
		foreach ($ff as $f) {
			$file = pathinfo($f);
			$e = file_exists($_SESSION['project-name'] . '/~run/' . $file['dirname']);
			$f = new CompileFile($f, false);
			if ($f->df) $pp[] =  $f;
		}

        $GLOBALS['compiler.filebasedir'] = $GLOBALS['compiler.rootdir'];
        foreach ($lp as $p)
            $p->find_used_descriptors ();
        
        $GLOBALS['compiler.filebasedir'] = $GLOBALS['compiler.projectdir'];
        foreach ($pp as $p)
            $p->find_used_descriptors ();
		
		//trattare attributi e implementazioni
        
        static::$dom = new DOMDocument();
        static::$dom->loadXML('<dude/>');
        
        $GLOBALS['compiler.filebasedir'] = $GLOBALS['compiler.rootdir'];
        foreach ($lp as $p)
            $p->generate();
        
        $GLOBALS['compiler.filebasedir'] = $GLOBALS['compiler.projectdir'];
        $hasGlobal = false;
		foreach ($pp as $p) {
			$p->generate();
			if ($p->isGlobal) $hasGlobal = true;
		}
		
		//ContextFactory::executeBuildEvent($ll, $ff);
        
		chdir($GLOBALS['compiler.rootdir']);
      copy('~system/~runtime/~runtime-ORIGINAL.php', $_SESSION['project-name'] . '/~run/~runtime.php');
      copy('~system/~runtime/~unready-ORIGINAL.php', $_SESSION['project-name'] . '/~run/~unready.php');
      copy('~system/~runtime/~application-manage-ORIGINAL.php', $_SESSION['project-name'] . '/~run/~application-manage.php');
      copy('~system/~runtime/~error-ORIGINAL.php', $_SESSION['project-name'] . '/~run/~error.php');
      copy('~system/~runtime/~auto_update-ORIGINAL.php', $_SESSION['project-name'] . '/~run/~auto_update.php');
		@mkdir($_SESSION['project-name'] . '/~run/lib/app-data');
		file_put_contents($_SESSION['project-name'] . '/~run/project.name', $_SESSION['project-name']);
		touch($_SESSION['project-name'] . '/~run/compiler.flag');
		
		if (self::$fullBuild || $hasGlobal || DEBUG__RESTART_APPLICATION_EVERY_UPDATE) {
			DirFunctions::clean_dir($_SESSION['project-name'] . '/~run/app-data');
			if (@unlink($_SESSION['project-name'] . '/~run/application.pid') !== false)
				Log::message('Shut-down application', '', null); //non è ancora del tutto vero.. non esegue ancora on-application-end
		}
		
		// ContextFactory::createEventFiles();
        GeneralIndex::save();
        return true;
		
    }

}

}

?>
