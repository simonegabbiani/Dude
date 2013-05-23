<?php

# _base_functions.php

class Object {}

class IllegalCharacterAssignmentException extends Exception {}

class Utils {
	public static function xmlAttributes($node) {
		$a = array();
		if (!$node->hasAttributes()) return $a;
		for($i=0; $i<$node->attributes->length; $i++)
			$a[$node->attributes->item($i)->name] = $node->attributes->item($i)->value;
		return $a;
	}
	public static function representXMLAttributes(DOMNode $node, $excludeAttrList = array()) { //prefix, namespaces
		if (!$node->hasAttributes()) return '';
        $a = ''; $k = array_flip($excludeAttrList);
		for($i=0; $i<$node->attributes->length; $i++)
            if (!isset( $k[ $node->attributes->item($i)->name ] ))
    			$a .= ' '.$node->attributes->item($i)->name .'="'. htmlentities( $node->attributes->item($i)->value ) . '"';
		return $a;
	}
	//creates a version of the name portable on PHP and all filesystems
   //but also recodable to the original
	public static function encode($name, $keepSlash = false, $stripFirstSlash = false) {
		if (strpos($name, '/HomePage_SignUp') !== false) { 
			echo ''; 
		}
		$name = str_replace(array('.', '-', '_', '~'), array('!', '?', '=', chr(168)), $name);
		$x = str_replace('%', '_', rawurlencode($name));
		if (!$keepSlash && $stripFirstSlash) die('Utils::encode() (1)');
		if (!$keepSlash) return $x;
        $x = str_replace('_2F', '/', $x);
		if ($stripFirstSlash && $x[0] == '/') $x = substr($x, 1);
		return $x;
	}
	public static function decode($name) {
		if (strpos($name, '_') !== false) user_error('Utils::decode() used instead of encode?', E_USER_ERROR);
		$name = rawurldecode(str_replace('_', '%', $name));
		return str_replace(array('!', '?', '=', chr(168)), array('.', '-', '_', '~'), $name);
	}
	public static function chext($name, $oldext, $newext) {
		$n = substr($name, 0, -strlen($oldext));
		return $n . $newext;
	}
    public static function errorMsg(DOMNode $node) {
        $a = func_get_args();
        array_shift($a);
        $message = implode(' ', $a);
        return $message.' '.$node->ownerDocument->documentURI.' line: '.$node->getLineNo();
    }
}

class DirFunctions {
	//rewrite: differentiate between library and project: getAllSourceDirectories_Project e getAllSourceDirectories_Library
	public static function getAllSourceDirectories_Project($projectName, &$array_dest, $doProjectsList = false) {
		$prevdir = getcwd();
		chdir($projectName);
		$array_dest[] = '.';
		$i = new RecursiveIteratorIterator(new RecursiveDirectoryIterator('.', RecursiveDirectoryIterator::KEY_AS_PATHNAME), RecursiveIteratorIterator::CHILD_FIRST);
		foreach ($i as $file => $f) 
		{
			if ($f->isDir()) {
				//if ($f->isDot()) continue;
				if (substr($f->getFilename(), 0, 1) == '#') continue;
				$f = str_replace('\\', '/', (string)$f);
				if (strpos($f, '/#') !== false) continue;
				if (strpos($f, './') === 0) $f = substr($f, 2);
				if ($f == "~run") continue;
				if ($f == "~") continue;
				array_push($array_dest, (string)$f);
			}
		}
		chdir($prevdir);
	}
	
	public static function getAllSourceDirectories_Library(&$array_dest) {
		$i = new RecursiveIteratorIterator(new RecursiveDirectoryIterator('~system', RecursiveDirectoryIterator::KEY_AS_PATHNAME), RecursiveIteratorIterator::CHILD_FIRST);
		foreach ($i as $file => $f) 
		{
			if ($f->isDir()) {
				//if ($f->isDot()) continue;
				if (substr($f->getFilename(), 0, 1) == '#') continue;
				$f = str_replace('\\', '/', (string)$f);
				if (strpos($f, '/#') !== false) continue;
				if (strpos($f, './') === 0) $f = substr($f, 2);
				if ($f == '~system') continue;
                if (strpos($f, '~system/~') === 0 && strpos($f, '~system/~bin') !== 0) continue;
				//if (strpos($f, '~system/~bin') === 0) continue;
				if (strpos($f, '~system/~runtime') === 0) continue;
				array_push($array_dest, (string)$f);
			}
		}
	}	
	
	//only for library files, DO NOT specify a project
	public static function getSourceFileList($project, $directory, &$array_dest) {
		$c = 0;
		$prevdir = getcwd();
		if ($project) chdir($project);
		$dir = new DirectoryIterator($directory);
		//echo "<b>$directory</b><br/>";
		foreach ($dir as $f) {
			if ($f->isFile()) {
				$n = $f->getFilename();
				if (substr($n, -4) == '.xml' && $n[0] != '#') {
					$array_dest[] = ($directory != '.') ? "$directory/$n" : $n;
					$c++;
				}
			}
		}
		if ($project) chdir($prevdir);
		return $c;
	}
	
	public static function clean_dir($dir, $DeleteMe = false, $deleteContextFiles = false)
	{
		if ( ! $dh = @opendir ( $dir ) ) return;
		while ( false !== ( $obj = readdir ( $dh ) ) )
		{
			if ( $obj == '.' || $obj == '..') continue;
			//Errore (tryBuildContext li ricostruisce sempre)
			//if (!$deleteContextFiles && substr($obj, 0, 3) == 'do_') continue;
			if ( ! @unlink ( $dir . '/' . $obj ) ) self::clean_dir ( $dir . '/' . $obj, true );
		}
		
		closedir ( $dh );
		if ( $DeleteMe )
		{
			@rmdir ( $dir );
		}
	}
	
	public static function realBaseInfo($dir) {
		if (substr($dir, 0, 2) == '~/') $dir = '../~system/' . substr($dir, 1);
		$dir = str_replace('\\', '/', realpath($dir));
		$base = basename($dir);
		$pdir = pathinfo($dir); 
		if (is_dir($dir) && strripos($pdir['dirname'], "/$base") !== (strlen($pdir['dirname']) - (strlen($base)+1))) {
		   $pdir['dirname'] .= "/$base";
		   $pdir['basename'] = '';
		   $pdir['filename'] = '';
		   $pdir['extension'] = '';
		}
		return $pdir;
	}
	
	public static function realDir($dir) {
        if ($dir && !file_exists($dir)) return false;
		if (!is_dir($dir)) $dir = dirname($dir);
		return realpath($dir);
	}
	
	public static function in_dir_old($dir, $pos, $count = 0) {
		if (stripos($pos, $dir) !== 0) {
            if ($count > 1) {
                echo null;
                return -$count;
            }
            else {
                echo null;
                return self::in_dir($dir, $pos, $count + 1);
            }
			//return $count > 1 ? false : /* negative*/ - self::in_dir($dir, $pos, $count + 1);
        }
		$b = substr($pos, strlen($dir));
		$x = count(explode('/', $b)) - 1;
        return $x;
	}

	public static function in_dir($dir, $pos, $count = 0) {
		if (stripos(/*where*/ $pos,  /*what:*/ $dir) !== 0) {
            return false; //negative values is deprecated due it not allow consistence..
			//return $count > 1 ? false : /* negativo*/ - self::in_dir($dir, $pos, $count + 1);
        }
		$b = substr($pos, strlen($dir));
		$x = count(explode('/', $b)) - 1; //TODO: cache on result of explode (?)
        return $x;
	}
	
	public static function stripDoubleSlashes($d) {
		while (($ed = str_replace('//', '/', $d)) != $d) $d = $ed;
		return $d;
	}
	
	public static function rootPrefix($dir) {
		if (!$dir || $dir == '/') return './';
		$dir = self::stripDoubleSlashes($dir);
		return ($dir != './') ? str_repeat('../', substr_count($dir, '/') - (int)($dir[0] == '/')) : './';
	}
}


function openXMLDoc($f, &$file_content = null) {
	//echo "f=".realpath($f);
	$d = new DOMDocument();
	if (($rf = realpath($f)) === false)
		throw new SourceFileNotFound("non trovo il file '$f' (cwd=".getcwd().")");
	$file_content = file_get_contents(str_replace('\\', '/', $rf));
	$d->loadXML(/*removeDudeComments(*/$file_content/*)*/);
	return $d;
}

function saveXML($node) {
	$s = simplexml_import_dom($node); return $s->asXML();
}

function loadXML($text) {
	$d = new DOMDocument();
	$d->loadXML($text);
	return $d;
}

function throw_xml(DOMNode $n, $excname, $excstr, $src = null) {
	//var_dump($src->macro);
	var_dump($src);
	//debug_print_backtrace();
	if ($src instanceof PartParser) $src = $src->macro ? $src->macro['cmp']->df->fileName : $src->dc->df->fileName;
	if (!$src) $src = $n->ownerDocument->documentURI;
	//throw new $excname($excstr . ' - Line: '.$n->getLineNo().' @ '.$src);
	Build::error(1, "$excname $excstr", $src, $n->getLineNo());
}

/** @deprecated: replace with *instructions*, es.: <comment>..</comment> */
function removeDudeComments($really_do, $src) {
	if (!$really_do) return $src;
	return preg_replace_callback('#/\*(.*?)\*/#s', create_function('$m', 'return str_repeat(" ", strlen($m[1]));'), $src);
}

// due of path 'relativePath', relative to position of partPosition, 
// that is relative to root of the project, it returns an absolute path.
// For example if clientPartPosition is /~bin/Somehting/file.xml and relativePath is './subDir/other.xml'
// it returns (...)/~bin/Something/subDir/altro.xml
// Applies also at Context names.
// We must stay on the root path of Dude installation
function resolveRelativePartPathInProject($clientPartPosition, $relativeLink, $cutFilesystemPath) {
	$c = getcwd();
	if (!file_exists($c.'/'.$_SESSION['project-name']) || !file_exists('~system'))
		throw new Exception('bad cwd: ' . $c);
	if (substr($clientPartPosition, 0, 8) != '~system/')
		$clientPartPosition = $_SESSION['project-name'].'/'.$clientPartPosition;
	$r =  realpath($clientPartPosition);
	$isdir = is_dir($r);
	if ($isdir && substr($r, -1) != '/') $r .= '/';
	$_d = $isdir ? $r : dirname($r);
	chdir($_d);
	$i = pathinfo($relativeLink);
	//echo "da ".getcwd()." mi sposto su ".$i['dirname'];
	chdir($i['dirname']);
	$d = getcwd();
	chdir($c);
	$result = str_replace('\\', '/', $d .'/'. $i['basename']);
	if (!$cutFilesystemPath) 
		return $result;
	$fs_path = str_replace('\\', '/', realpath($c)) . '/';
	if (substr($result, 0, strlen($fs_path)) != $fs_path)
		die('die: check better here..........they must be equal');
	return substr($result, strlen($fs_path));
}

/*class XPQueryConfig {
	var $catchWithoutNs;
	var $nsMap;
	public function XPQueryConfig($catchWithoutNs = true, $nsMap = null) {
		$this->catchWithoutNs = $catchWithoutNs;
		$this->nsMap = $nsMap;
	}
}*/

/**
 * xpQuery
 *
 * It does not concern XQuery but simply it is a useful function for DOMXPath.
 *
 * It considers the instruction '[prefix:]' as "illegal" for XPath but it accepts
 * however in order to find "optional" prefix, or tag without namespace to consider
 * "system tags". If the query includes a prefix in the synopsis [ns:] it executes
 * it TWO times and cumulate the results, first time with the namespace 'local/Dude', 
 * second time without. 
 *
 * It returns an array, NOT a NodeList.
 *
 * foreach(xpQuery('/dude/[d:]part', array('d' => 'local/Dude')) as $n)
 *  ...
 *
 * If you specify a namespace different from 'local/Dude' all tags without namespace
 * prefix go on the result too, so you have to check them one on one.
 * 
 * If you want that xpQuery runs as XPath->query, simply avoid to specify 
 * the synopsis [ns:]
 * 
 */
function xpQuery($dom, $query1, $nsMap=null /* XPQueryConfig $config = null*/, $relNode = null) {
	$d = array();
	$xp = new DOMXPath($dom);
	$query2 = $query1;
	if (is_array($nsMap)) { //if ($config) {
		foreach ($nsMap as $p => $u) {
			$xp->registerNamespace($p, $u);
			$query2 = str_replace("[$p:]", '', $query2);
			$query1 = str_replace("[$p:]", "$p:", $query1);
		}
	}
    if (!$relNode) $relNode = $dom->documentElement;
	$nl = $xp->query($query1, $relNode);
	foreach($nl as $n) array_push($d, $n);
	if ($nl === false) 
		throw new Exception('errore xpath: ' . $query1);
	if ($query1 != $query2) {
		$nl = $xp->query($query2, $relNode);
		foreach($nl as $n) array_push($d, $n);
	}
	return $d;
}

function cleanDir($dir, $DeleteMe = false)
{
	if ( ! $dh = @opendir ( $dir ) ) return;
	while ( false !== ( $obj = readdir ( $dh ) ) )
	{
		if ( $obj == '.' || $obj == '..') continue;
		if ( ! @unlink ( $dir . '/' . $obj ) ) clean_dir ( $dir . '/' . $obj, true );
	}
	
	closedir ( $dh );
	if ( $DeleteMe )
	{
		@rmdir ( $dir );
	}
}

//like array_merge on arrays with string keys, but it obtain same result on arrays with numeric keys 
function array_merge_ukey(&$a1, &$a2) {
	$d = $a1;
	foreach($a2 as $k => $v)
		if (!isset($a1[$k]))
			$d[$k] = $a2[$k];
	return $d;
}


//ref_source is a reference/pointer for performance reasons
//instead, ref_dest is modified
function array_merge_by_reference(&$ref_dest, &$ref_source) {
	foreach ($ref_source as $k => $v)
		$ref_dest[$k] = $v;
}

class SourceFileNotFound extends Exception {}
?>