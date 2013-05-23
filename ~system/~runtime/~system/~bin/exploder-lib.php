<?php



class Exploder {
	static $OpenedFiles = array(); 
	static $OpenedFiles_ContentCache = array(); 
	static $keys = array();
	//it want GeneralIndex caricato
	public static function getOpenedFileContent($fileName) {
		return self::$OpenedFiles_ContentCache[ $fileName ];
	}
	public static function get_info($fullName) {
		$a = explode(':', $fullName);
		if (count($a) != 2)
			die('no part given or resource name error: ' . $fullName);
	}
	private static function open($fullName, $isMacro = false) {
		@list($f, $p) = explode(':', $fullName);
		if (!isset($p))
			die("no part given");
		if (!isset(self::$OpenedFiles[ $f ])) {
			if ($isMacro) $where =& GeneralIndex::$index->macros; else $where =& GeneralIndex::$index->parts;
			$cmp = $where[$p][$f];
			if ($cmp == null) die('Component '.$fullName.' not found or wrong part/macro specification (1) f='.$f);
			if ($cmp->df->isLib) {
				$upm_file = $GLOBALS['compiler.projectdir'].'~run/lib/'.Utils::chext($f, 'xml', 'upm');
				$xml_file = $GLOBALS['compiler.rootdir'].$f;
			}
			else { 
				$upm_file = $GLOBALS['compiler.projectdir'].'~run/'.Utils::chext($f, 'xml', 'upm');
				$xml_file = $GLOBALS['compiler.projectdir'].'/'.$f;
			}
			//var_dump("file: $xml_file");
			self::$OpenedFiles_ContentCache[ $f ] = '';
			self::$OpenedFiles[ $f ] = array(unserialize(file_get_contents($upm_file)), 
														openXMLDoc($xml_file, self::$OpenedFiles_ContentCache[ $f ]), $cmp);
		}
		return array($f, $p, self::$OpenedFiles[ $f ][0], self::$OpenedFiles[ $f ][1]);
	}
	static function macro($mode, $fullName, &$forInfo = null, &$text = '', $nest = 0) {
		return self::start(true, $mode, $fullName, $forInfo, $text, $nest);
	}
	static function part($mode, $fullName, &$forInfo = null, &$text = '', $nest = 0) {
		return self::start(false, $mode, $fullName, $forInfo, $text, $nest);
	}
	private static function start($isMacro, $mode, $fullName, &$forInfo = null, &$text = '', $nest = 0) {
		list($f, $p, $upm, $dom) = self::open($fullName, $isMacro);
		//Trova il sorgente del parto e lancia l'"expoder"
		foreach ($dom->documentElement->childNodes as $n) {
			if ($n->nodeType != XML_ELEMENT_NODE) continue;
			if (TODO::NS_KEYWORD & TODO::getNamespaceArea($n->namespaceURI, null, true) && ($n->getAttribute('name') == $p || $n->getAttribute('auto-context') == $p)) {
				if ($mode == 'plain' && !$isMacro)
					self::parse_plain($n, $upm[$p], $forInfo, $text, $nest);
				else if ($mode == 'find')
					return array($f, $p, $n, $dom);
				//	self::parse_full($n, $upm[$p], $forInfo, $text, $nest);
			}
		}
	}
	//coords sono da deprecare completamente, è un abbaglio demente.
	static function find($node, $coord_n, $coord_s, $current_coord_s = 0) {
		$current_coord_n = 0;
		foreach ($node->childNodes as $n) {
			if ($n->nodeType != XML_ELEMENT_NODE) continue;
			$n = self::node($n, $coord_n, $coord_s, $current_coord_s + 1);
			if ($f) return $n;
			if ($coord_n == $current_coord_n && $coord_s == $current_coord_s)
				return $node;
			$current_coord_n++;
		}
	}
	static function parse_plain(DOMNode $node, $upm, &$forInfo, &$text, $nest, $index = 0, $cs = 0) {
		$cn = 0;
		foreach ($upm as $u) {
			// il filtro viene scelto dopo, intanto metto tutto
			if ($u['cmp'] instanceof DocPart && !isset(self::$keys[$u['cmp']->fullName])) {
				if (is_array($forInfo))
					$forInfo[] = $u;
				else
					echo " &nbsp; " . $u['cmp']->simpleName . " (".$u['coord'][0].', '.$u['coord'][1].")";
				$text .= " &nbsp; " . $u['cmp']->simpleName . " (".$u['coord'][0].', '.$u['coord'][1].")";
				self::$keys[$u['cmp']->fullName] = true;
				self::part('plain', $u['cmp']->fullName, $forInfo, $text, $nest++);
			}
		}
	}
}

class ExploderUPM {
	static $OpenedFiles = array();
	//it want GeneralIndex caricato
	static $recursive = true;
	static $unique = false;
	static $ordered = false;
	static $uniqueIndex = array();
	static function macro($fullName, &$forInfo = null, &$text = '', $nest = 0) {
		return self::start(true, $fullName, $forInfo, $text, $nest); 
	}
	static function part($fullName, &$forInfo = null, &$text = '', $nest = 0) {
		return self::start(false, $fullName, $forInfo, $text, $nest); 
	}
	private static function start($isMacro, $fullName, &$forInfo = null, &$text = '', $nest = 0) {
		@list($f, $p) = explode(':', $fullName);
		if (!isset($p))
			die("no part given");
		if (self::$ordered && !self::$unique)
			die('ordered ..only with unique, sorry');
		if (!isset(self::$OpenedFiles[ $f ])) {
			if ($isMacro) $where =& GeneralIndex::$index->macros; else $where =& GeneralIndex::$index->parts;
			$cmp = $where[$p][$f];
			if ($cmp == null) die('Component '.$fullName.' not found or wrong part/macro specification (2)');
			if ($cmp->df->isLib) {
				$upm_file = $GLOBALS['compiler.projectdir'].'~run/lib/'.Utils::chext($f, 'xml', 'upm');
				$xml_file = $GLOBALS['compiler.rootdir'].$f;
			}
			else { 
				$upm_file = $GLOBALS['compiler.projectdir'].'~run/'.Utils::chext($f, 'xml', 'upm');
				$xml_file = $GLOBALS['compiler.projectdir'].'/'.$f;
			}
			//var_dump("file: $xml_file");
			self::$OpenedFiles[ $f ] = array(unserialize(file_get_contents($upm_file)), null, $cmp);
		}
		$upm = self::$OpenedFiles[ $f ][0];
		//Trova il sorgente del parto e lancia l'"expoder"
		if (!$isMacro)
			self::parse_upm($upm[$p], $forInfo, $text, $nest++);
		if (self::$ordered) {
			ksort(self::$uniqueIndex);
			$tmp = null; $i = 0;
			foreach (self::uniqueIndex as $u_name => $f_index) {
				$tmp = $forInfo[$i];
				$forInfo[$i] = $forInfo[$f_index];
				$forInfo[$f_index] = $tmp;
				$i++;
			}
		}
		return array($f, $p, $cmp);
	}
	static function parse_upm($upm, &$forInfo, &$text, $nest) {
		foreach ($upm as $u) {
			$add = true;
			if (self::$unique) {
				$n = get_class($u['cmp']).$u['cmp']->fullName;
				if (!isset(self::$uniqueIndex[$n]))
					self::$uniqueIndex[$n] = count($forInfo);
				else
					$add = false;
			}
			if ($add) {
				if (is_array($forInfo)) 
					$forInfo[] = $u;
				else {
					$tab = str_repeat('&nbsp; ', $nest);
					echo " <br/>$tab&nbsp; " . $u['cmp']->simpleName . " (".$u['coord'][0].', '.$u['coord'][1].")";
				}
			}
			if ($u['cmp'] instanceof DocPart) {
				if (self::$recursive) self::part($u['cmp']->fullName, $forInfo, $text, $nest++);
			} else if (!$forInfo && $add) {
				echo " (Macro)";
			}
		}
	}
}


?>
