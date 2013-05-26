<?php

require_once "old-attrformat.php";

class ExportVarNotFoundInScope extends Exception {}

Class TODO {
    const NS_EXTERN = 0;
    const NS_KEYWORD = 1;
    const NS_COMPONENT = 2;
    const NS_INVALID = 4;
	public static function getNamespaceArea($nsUri, $defaultDocumentNamespace, $findingKwywordsOnly = false) {
		if (!$nsUri || $nsUri == 'http://local/Dude')
			return self::NS_KEYWORD + self::NS_COMPONENT;
		if (substr($nsUri, 0, 5) == 'http:')
            return self::NS_EXTERN;
        if (($nsUri[0] == '/' && file_exists($GLOBALS['compiler.projectdir_'].$nsUri)) 
					|| (substr($nsUri, 0, 2) == '~/' && file_exists($GLOBALS['compiler.rootdir'].'~system'.substr($nsUri, 1))) 
							|| file_exists($nsUri)) {
			if (!STRICT_KEYWORD_NAMESPACES)
				return self::NS_COMPONENT + self::NS_KEYWORD;
			else if (!$findingKwywordsOnly) 
				return self::NS_COMPONENT;
		}
        return self::NS_INVALID;
    }
	public static function getNamespacesForElement($xmlnode, &$ns, $parents = false) {
		$node = $xmlnode->cloneNode(false);
		$dumpDoc = new SimpleXMLElement($node->ownerDocument->saveXML($node));
		foreach ($dumpDoc->getDocNamespaces() as $prefix => $url) {
			if (!isset($ns[$prefix])) $ns[$prefix] = $url;
		}
		if ($parents && $xmlnode->parentNode->nodeType != XML_DOCUMENT_NODE) {
			self::getNamespacesForElement($xmlnode->parentNode, $ns, true); 
		}
	}	
	public static function readSubElementsAndExportData($type, $name, DOMDocument $dom, DOMElement $node, &$array_dest, DocPart $startPointCheck = null) {
		$elem = ($type == DocComponent::MACRO) ? 'define-macro' : ($type == DocComponent::DESCRIPTOR) ? 'define-descriptor' : 'part';
		$subElements = array();
        $cfg = array('d' => Compiler::DUDE_NAMESPACE_URI);
		$subElementsIndex = array();
        $exportBlocks = array();//['blk-name'] => [var, var, var] ---> PS: at the moment only the block ''
		$exportCaller = array();
		$nl = $node->getElementsByTagName('export');
		foreach ($nl as $n) {
			$ns_area = TODO::getNamespaceArea($n->namespaceURI, $dom->documentElement->namespaceURI, true);
			if ($ns_area & TODO::NS_KEYWORD) {
				$vars = $n->getAttribute('var').','.$n->getAttribute('vars');
				foreach(explode(',',$vars) as $v) {
					if ($write = ($v && $v[0] == '*'))
						$v = substr($v, 1);
					if (isset($exportBlocks[DEFAULT_NAME][ trim($v) ])) throw new NameAlreadyExported($v);
					if (strpos($v, '.') !== false) 
						throw new CannotExportAliasedDataOnlyOwn($v);
					else if (trim($v) != '') 
						$exportBlocks[DEFAULT_NAME][ trim($v) ] = (int) $write;
				}
			}
		}
		$nl = $node->getElementsByTagName('sub-elements');
		foreach ($nl as $n) {
			$ns_area = TODO::getNamespaceArea($n->namespaceURI, $dom->documentElement->namespaceURI, true);
			if ($ns_area & TODO::NS_KEYWORD) {
				if ($startPointCheck) Build::error(1, 'Context start points cannot declare sub elements', $startPointCheck->df->fileName, $n->getLineNo());
				$a = strtolower($n->getAttribute('name')); if ($a == '') $a = DEFAULT_NAME;
				$subElementsIndex[$a] = count($subElements);
				$subElements[$a] = $n;
				if ($n->hasAttribute('export')) die('sub-elements export not yet supported in this version');
			}
		}
		foreach(array('php', 'code') as $codeTagName) {
			$nl = $node->getElementsByTagName($codeTagName);
			foreach ($nl as $n) {
				$ns_area = TODO::getNamespaceArea($n->namespaceURI, $dom->documentElement->namespaceURI, true);
				if ($ns_area & TODO::NS_KEYWORD) {
					if (!$n->hasAttribute('sub-elements') && !$n->hasAttribute('get-subelement-content'))
						continue;
					if ($startPointCheck) Build::error(1, 'Context start points cannot declare sub elements', $startPointCheck->df->fileName, $n->getLineNo());
					if (!($a = strtolower($n->getAttribute('sub-elements'))) && !($a = strtolower($n->getAttribute('get-subelement-content'))))
						$a = DEFAULT_NAME;
					$subElementsIndex[$a] = count($subElements);
					$subElements[$a] = $n;
					if ($n->hasAttribute('export')) die('sub-elements export not yet supported in this version');
				}
			}
		}
		if ($e = $node->getAttribute('export')) {
			if ($e == '*') 
				$exportCaller =& $exportData;
			else {
				$e = explode(',', $e);
				foreach ($e as $e) {
					if (!isset($exportBlocks[DEFAULT_NAME][$e]))
						throw_xml($node, 'CallerExportNotPartOfExportBlock', $e);
					$exportCaller[ trim($e) ] = 1;
				}
			}
		}
		$array_dest['sub-elements'] =& $subElements;
		$array_dest['index'] =& $subElementsIndex;
        $array_dest['export-vars'] =& $exportBlocks;
		$array_dest['export-caller'] =& $exportCaller;
		//var_dump($exportBlocks);
	}
    public static function bindPartAttributes(PartParser $p, DocPart $dest, DOMElement $useNode) {
        ////parallelamente, innesto dati nello stack nel costruttore PartParser()
		$c = 'array(';
        foreach ($dest->attributesIndex as $i => $n) {
            if ($useNode->hasAttribute($n))
                $c .= Expr::RealRValue('any', $useNode->getAttribute($n), false, $p, $p->domain, $useNode, false).',';//, $part->attributesList[$n]
            else
                $c .= Expr::RealRValue('any', null, false, $p, $p->domain, $useNode, false).','; //, $part->attributeList[$n], "use/copy part '{$part->name}' binding *unused* attribute '{$n}'") . ',';
        }
        if (substr($c, -1) == ',')
            $c = substr($c, 0, -1);
		return $c . ')';
	}
	// when entering in a macro I map "attribute name" with "attribute value". when that attribute is requested
	// I call Expr::buildRValue() using the domain immediately previous the entering time in the macro 
    public static function bindMacroAttributes(PartParser $p, DocMacro $macro, DOMElement $useNode) {
        $p->macroAttrBinding = array();
        if (isset($macro->attributesIndex)) 
            foreach ($macro->attributesIndex as $n) {
                if ($useNode->hasAttribute($n))
                    $p->macroAttrBinding[$n] = $useNode->getAttribute($n);
                else
                    $p->macroAttrBinding[$n] = null;
            }      
    }
    public static function bindExportData(PartParser $p, $varList) {//ParseFunctions::subelement_callback()
		$c = 'array(';
        foreach ($varList as $n => $write) {
            $r = $p->stack->get($p->stackSource, '', $n);//so che $n non ha alias, vedi PartParser
            if (!$r[0]) throw new ExportVarNotFoundInScope("\"$n\" @ ".$p->dc->fullName);
            $c .= ($write ? '&' : '') . Expr::buildVarName($r) . ',';
        }
        if (substr($c, -1) == ',')
            $c = substr($c, 0, -1);
		return $c . ')';
	}    
    public static function createExportDataSource(DocPart $c, $alias, PartParser $p, $index) {//blockName deprecato
        $s = new StackSource($c, $alias, false, $index);
        if (!isset($c->exportData[DEFAULT_NAME])) return $s; //$blockName '' unique name supported at the moment, see TODO::readSubElementsAndExportData
        foreach (array_keys($c->exportData[DEFAULT_NAME]) as $varName)
            $s->push($varName, null, !$c->exportData[DEFAULT_NAME][$varName]); //valorization of DS_E.. pure "simybolic"
        return $s;
    }
	public static function buildUserTag(DOMElement $e) {
		//prefix, namespaces
		if ($e->childNodes->length)
			return array("<$e->tagName".Utils::representXMLAttributes($e).'>', "</$e->tagName>");
		else
			return array("<$e->tagName".Utils::representXMLAttributes($e).'/>', null);
	}
}

?>
