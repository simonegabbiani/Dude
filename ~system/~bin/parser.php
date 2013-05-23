<?php

require_once "functions.php";
require_once "stack.php";
require_once "uncategorized.php";
require_once "parse-functions.php";

class XMLDocumentError extends Exception {}
class UnknownTagException extends Exception {}
class UnresolvedPartAddressException extends Exception {}
class UnrecognizedInstructionSymbol extends Exception {}
class IncorrectEventName extends Exception {}
class MissingPartName extends Exception {}
class DescriptorNotFound extends Exception {}
class IllegalEventOnGlobalFile extends Exception {}
class ComponentsNotAllowedInGlobalFile extends Exception {}
class MissingForHandlingAttribute extends Exception {}
class IllegalEvent extends Exception{}
class CannotSpecifyHandlersInGlobalFile extends Exception {}
class SessionInApplcationException extends Exception {}
class UnknownEventName extends Exception {}
class UnsupportedEvent extends Exception {}
class GlobalEventsCannotHaveName extends Exception {}
class PartNameAlreadyDefined extends Exception {}
class AssetFileNotFound extends Exception {}
class AssetPositionNotSupported extends Exception {}

interface IParser {
	//scremare tutti gli argomenti superflui, ovunque + alleggerire ovunque ove possibile
	public function openTag($index, $buffer, $localName, $nsUri, DOMElement $node, &$info, $coord_n, $coord_s);
	public function closeTag($index, $buffer, $localName, $nsUri, DOMElement $node, &$info);
	public function startSubBlock($index, $buffer, $localName, DOMElement $node, &$info);
	public function endSubBlock($index, $buffer, $localName, DOMElement $node, &$info);
    public function endAllSubBlocks($index, $buffer);
	public function cData($index, $buffer, $data);
}

class MacroBindingAttribute {
	public function MacroBindingAttribute($parser, $value) {
		$this->parser = $parser; 
		$this->value = $value;
	}
	public static function is($domain_result) {
		return (isset($domain_result[0]->d) && $domain_result[0]->d[$domain_result[1]][1] instanceof MacroBindingAttribute);
	}
}

//macros are never cumulable: there is one macro opened at a time! (important!)
class CoreParser {
	const GO_DEEP = 0;
	const IGNORE_CHILDS = 1; //non usata
	const DEFAULT_BLOCK = 2;
	const FETCH_NAMED_BLOCKS = 3;
	const NO_BLOCKS = 4;
	var $client;
	var $node;
	var $curr_coord_n = -1;
	var $curr_coord_s = -1;

	//if we are in a macro, it indicates the line number on which it was when it entered inside of it
	//to find original line number: original_doc_macro_start_line + (currline - macro_start_line)
	var $macro_start_line = 0; 

	public function CoreParser(IParser $client) {$this->client = $client;}

	public function parse(DOMNode $node, $index = 0, $buffer = 0, $fatherStatus = self::GO_DEEP, $fatherInfo = null, $coord_s = 0) {
		$coord_n = 0;
		foreach ($node->childNodes as $n) {
			$this->curr_coord_n = $coord_n;
			$this->curr_coord_s = $coord_s;
			$this->node = $n;
			if ($n->nodeType == XML_ELEMENT_NODE) { //todo: controllo che il namespace prefix sia coerente o assente
				if ($fatherStatus == self::FETCH_NAMED_BLOCKS) {
					$buffer++;
					$this->client->startSubBlock($index, $buffer, $n->localName, $n, $fatherInfo);
					if ($n->childNodes->length) $this->parse($n, $index, $buffer, 0, null, $coord_s + 1);
					$this->client->endSubBlock($index, $buffer, $n->localName, $n, $fatherInfo);
					$buffer--;
				}
				else {
					$uri = $n->lookupNamespaceURI(($n->prefix != '') ? $n->prefix : '');
					$info = new Object();
					$status = $this->client->openTag($index, $buffer, $n->localName, $uri, $n, $info, $coord_n, $coord_s);
                    if (isset($info->newIndex)) $index = $info->newIndex;
					if ($n->childNodes && $status != self::IGNORE_CHILDS) {
						$this->depth++;
						// GO_DEEP it isn't used for sub elements of a part
						// DEFAULT_BLOCK or FETCH_NAMED_BLOCKS indicates that there are not sub elements
						// NO_BLOCKS indicates, yes a part, but also that it has no subelements
						if ($status == self::DEFAULT_BLOCK || $status == self::NO_BLOCKS) {
							$buffer++;
							if ($n->hasChildNodes()) {
								$this->client->startSubBlock($index, $buffer, '', $n, $info);
								$this->parse($n, $index, $buffer, 0, null, $coord_s + 1);
								$this->client->endSubBlock($index, $buffer, '', $n, $info);
							}
							$buffer--;
							//it is called also for part that does not have sub elements
							//useful for export data
							$this->client->endAllSubBlocks($index, $buffer);
						}
						else {
							$this->parse($n, $index, $buffer, $status, $info, $coord_s + 1);
						}
						$this->depth--;
					}
					$this->client->closeTag($index, $buffer, $n->localName, $uri, $n, $info);
				}
				$coord_n++;
			}
			else if ($n->nodeType == XML_CDATA_SECTION_NODE || $n->nodeType == XML_TEXT_NODE) {
				if (trim($n->nodeValue) != '') $this->client->cData($index, $buffer, trim($n->nodeValue), 0, '');
			}
		}
        if ($fatherStatus == self::FETCH_NAMED_BLOCKS)
            $this->client->endAllSubBlocks($index, $buffer);
	}
}

class PartParser implements IParser {
	var $currentIndex;
	var $startNode;
	var $coreParser;
    var $attrFormat = null;
	var $dc; 
    var $cf;
	var $uc = array(); //upm [qua coinvoglia anche r_parts e r_macros]
	var $eb = array();
	var $callbacks = array();
	var $stack;	
    var $stackSource; 
    var $fileSetsSource;
	var $macro; 
    var $upm = array();
	var $searchingForSubelements = array(); //una sola lista alla volta
	var $buffer = array(); 
    var $currentBuffer = 0;
	var $code = 0;
	public function PartParser(&$cf, &$dc, $node, &$fileSetsSource) {
		$this->buffer[0] = '';
		$this->cf =& $cf;
		$this->dc =& $dc;
		$this->stack = new Stack();
		$this->stack->pushSource($this->fileSetsSource = $fileSetsSource, '');
		$this->stackSource = new StackSource($dc, '', false);
		$this->domain = $this->stackSource;
		$this->stack->pushSource($this->stackSource);
		if (isset($this->dc->attributesIndex)) 
            foreach ($this->dc->attributesIndex as $a)
                $this->stackSource->set($a, null, PART_ATTRIBUTES_WRITE_PRIVILEGE);
		$this->startNode = $node;
	}
	/*public function resolveReferences($data) {
		return $data;
	}*/
    //todo: optimize render concat
	public function openTag($index, $buffer, $localName, $nsUri, DOMElement $node, &$info, $coord_n, $coord_s) {
		$status = -1;
        try {
		 $c = null;
         $ns_area = TODO::getNamespaceArea($nsUri, $this->cf->dom->documentElement->namespaceURI);
		 if ($ns_area & TODO::NS_KEYWORD)
         {
		// * sub-element callback
			if ((($localName == 'code' || $localName == 'php') && $node->hasAttribute('sub-elements')) || $localName == 'sub-elements') {
                $this->subElementsFound = true;
				if (!($a = $node->getAttribute('name'))) $a = $node->getAttribute('sub-elements');
				$status = ParseFunctions::subelement_callback(ParseFunctions::SUBELEM_RENDER, $this, $node, $index, $buffer, $a, $info);
			}
        // * apply-part-and-get-contents
            else if (($localName == 'code' || $localName == 'php') && ($c = $node->getAttribute('apply-part-and-get-contents'))) {
                list($nsUri, $localName) = GeneralIndex::resolvePartAddress($node, $c);
                if (!($c = GeneralIndex::$index->nearest(DocComponent::PART, $localName, $nsUri, $this->dc->df->fileName, $this->dc->simpleName)))
					throw new UnresolvedPartAddressException("$nsUri:$localName");
                if ($c->howFetchSubElements() !== false)
                    throw new CannotUseStructuredPartHere("$nsUri:$localName");
                AttrFormat::check($c->attrFormatCache, $node);
                $info->newIndex = $index = count($this->upm);
				$this->upm[] = $this->part = array('coord' => array($coord_n, $coord_s), 'alias' => $this->coreParser->getAttribtue('as'), 'cmp' => $c, 'node' => $this->coreParser->node);
                //terminare dopo i dati: serve il set (makeAssignment)
				ParseFunctions::applyPartAndGetContents($this, $node, $c, $index, $buffer);
            }
		// * non-structural keyword
			else if (array_search($localName, array('set', 'get', 'code', 'php', 'capture', 'export', 'if-subelement-exists')) !== false) {
				$status = ParseFunctions::switch_keyword_tag($this, $node, $index, $buffer, $info);
			}
		// * apply|with
			else if ($localName == 'use' || $localName == 'copy') { // $localName == 'with' || $localName == 'apply') {
				if ($this->macro) 
					throw_xml($node, 'IllegalIstructionException', 'Macros or Descriptors cannot refer directly to Parts, you must use apply-part-and-get-contents (without use of sub-elements)');
				list($nsUri, $localName) = GeneralIndex::resolvePartAddress($node, $node->getAttribute('part'));
				if (!($c = GeneralIndex::$index->nearest(DocComponent::PART, $localName, $nsUri, $this->dc->df->fileName, $this->dc->simpleName)))
					throw new UnresolvedPartAddressException("$nsUri:$localName");				
				AttrFormat::check($c->attrFormatCache, $node);
                $this->dc->to($info->subElementComponent = $c);
                $info->newIndex = $index = count($this->upm);
                $a = $this->coreParser->node->getAttribute('as');
                if ($a == '' && CREATE_DEFAULT_ALIASES) $a = $c->simpleName;
                $this->part = array('coord' => array($coord_n, $coord_s), 'alias' => $a, 'cmp' => $c, 'node' => $this->coreParser->node, 'se-used' => array());
				$this->upm[] = $this->part;//$this->part non può essere usato, si può usare solo upm
				ParseFunctions::apply_part($this, $node, $c, $index, $buffer);
                $this->stack->pushSource($this->upm[count($this->upm)-1]['source'] = TODO::createExportDataSource($c, $a, $this, $index));//default
				$status = $c->howFetchSubElements();
			}
		 }
		 // * macro
		 if ($status == -1) {
            //NS_COMPONENT || NS_KEYWORD
            if ($ns_area ^ TODO::NS_EXTERN && (($c = GeneralIndex::$index->nearest(DocComponent::MACRO, $localName, $nsUri, $this->dc->df->fileName, $this->dc->simpleName))
                                                || (/*isset($this->dc->isUpdater) &&*/ ($c = GeneralIndex::$index->nearest(DocComponent::DESCRIPTOR, $localName, $nsUri, $this->dc->df->fileName, $this->dc->simpleName))))) {
                //if ($this->dc->simpleName == 'ExploreProjects' && $c->simpleName == 'when') {
					//echo 'founddd ';
				//}
                AttrFormat::check($c->attrFormatCache, $node);
                TODO::bindMacroAttributes($this, $c, $node);
                $this->dc->to($info->subElementComponent = $c);
                $info->newIndex = $index = count($this->upm);
                $a = $this->coreParser->node->getAttribute('as');
                if ($a == '' && CREATE_DEFAULT_ALIASES) $a = $c->simpleName;
				if (!($client_domain = $this->domain)) die('client_domain null');
                $this->upm[] = $this->macro = array('coord' => array($coord_n, $coord_s), 'cmp' => $c, 'node' => $this->coreParser->node, 
                                    'source' => $this->domain = new StackSource($c, $a, $index), 'se-used' => array());
				//$this->stack->pushSource($c->df->fileSets);//mancava ancora il codice su CompileFile per montare fileSets su DocMacro (o DocFile) poi ho scartao per non appesantire troppo, quindi no fileSets dentro le macro
                //ParseFunctions::append(ParseFunctions::PHP_CODE, $this, $buffer, "\$this->__m_index = $index;\n");
				$this->stack->pushSource($this->macro['source']);
				foreach ($this->macroAttrBinding as $name => $v) {
					//on attr write: while pre-valorize on DS_M the attribute values on the macro, the read-only have no sense, when/if Ill use the "references" I must use 'true'
					$r = $this->stack->set($this->domain, '', $name, $v /*new MacroBindingAttribute($this, $v)*/, MACRO_ATTRIBUTES_WRITE_PRIVILEGE);
					ParseFunctions::append(ParseFunctions::PHP_CODE, $this, $buffer, Expr::buildVarName_fromSet($r) . ' = ' 
							. ($v === null ? 'null' : Expr::RealRValue('any', $v, false, $this, $client_domain, $node))."; ");
				}				
				if ($c->solid) {
					ParseFunctions::process_tag($ns_area, $this, $node, $localName, $nsUri, $index, $buffer, $info);
					//ParseFunctions::append(ParseFunctions::VALUE_CONTENT, $this, $buffer, '<'.$c->simpleName.'>');//capire attributi+namespace output..(in generale)
				}
                $this->coreParser->parse(Compiler::fetchMacroNode($c), $index, $buffer);
				//if ($c->solid) ParseFunctions::append(ParseFunctions::VALUE_CONTENT, $this, $buffer, '</'.$c->simpleName.'>');//capire attributi+namespace output..(in generale)
                $this->macro = null;//uesto non è ancora vero per i dati: i dati prodotti dalla macro devono vivere nei callbacks
                $this->domain = $this->stackSource;
                $status = $c->howFetchSubElements();
            }
        // * user tag
            else if ($ns_area == TODO::NS_EXTERN || isset(HTML::$tags[$localName])) {
                $status = ParseFunctions::process_tag($ns_area, $this, $node, $localName, $nsUri, $index, $buffer, $info);
            }
			else {
				throw_xml($node, 'UnknownTagException', str_replace('\\','/',substr(realpath($nsUri),strlen($GLOBALS['compiler.rootdir']))).":$localName (Tips: 1. always declare explicit external namespaces (exception only for XHTML tags) 2. maybe the namespace path points to a wrong path)", $this->dc->df->fileName);
            }
		 }
        } catch (AttrMissingException $e) {
			throw_xml($this->coreParser->node, 'AttrMissingException', 'for use of '.$c->fullName, $this);
        } 
		catch (Exception $e) {
			Build::error(1, get_class($e) . ' ' . $e->getMessage(), $this->dc->df->fileName, $this->coreParser->node->getLineNo());
		}
		return $status;
	}
	public function startSubBlock($index, $buffer, $name, DOMElement $node, &$info) {
		if ($name != '' && $node->prefix)
			Log::message("Namespace prefix '{$node->prefix}' ignored in sub element declaration", $this->dc->df->fileName, $node);
		if (!isset($this->upm[$index]['cmp']->subElements[$name == '' ? ' ' : strtolower($name)])) {
			Build::error(1, "Unprovided sub element '$name', expecting: ".implode(', ', array_keys($this->upm[$index]['cmp']->subElements)), $this->dc->df->fileName, $node->getLineNo());
		}
		$this->buffer[$buffer] = '';
		//echo "<li>START SUB BLOCK index=$index '$name' name=".$this->dc->fullName;
        $this->upm[$index]['se-used'][$name == '' ? ' ' : strtolower($name)] = 1;//in convenzione con sub_elem_name in ParseFunctions
        //when we shall have sub-element export="...
        //$this->stack->pushSource(TODO::prepareExportDataSource($upm[$index]['alias'], $p, $name));
	}
	public function endSubBlock($index, $buffer, $name, DOMElement $node, &$info) {
 
        $this->upm[$index]['se-used'][$name == '' ? ' ' : strtolower($name)] += (int) ($this->buffer[$buffer] != '');
        ParseFunctions::append_last();
        $this->callbacks[] = array('name' => $name, 'buffer' => $this->buffer[$buffer], 'index' => $index, 'obj' => $info->subElementComponent);
		unset($this->buffer[$buffer]);
        //$this->stack->removeLastSource();//quando avremo <sub-element export="..
	}
    public function endAllSubBlocks($index, $buffer) {
        $i = count($this->upm) - 1;
        $c = (isset($this->upm[$i]['cmp'])) ? $this->upm[$i]['cmp'] : $this->upm[$i]['cmp'];
		//here there is a predisposition to permit export data also for the macro, but no elsewhere
		//first all allm I want know if export is corretto: it must be include only variables included also in exportData
		if ($c->exportCaller) {
			if ($c->exportDiff != 0) {
				$this->upm[$i]['mp']->popAll();
				foreach ($c->exportData[DEFAULT_NAME] as $n => $d)//per ora $d è sempre '1' poi sarà un array di opzioni
					if (!isset($c->exportCaller[$n]))
						$this->upm[$i]['cmp']->push($n, null, true); //come in createExportDataSource
			}
			$this->stack->priorityDownLastQuitted();
			$this->stack->setLastQuitted($this->upm[$i]['source']);
		}
		else {
			$this->stack->removeLastSource();
			//if ($c instanceof DocMacro)
			//	$this->stack->removeLastSource();//fileSets			
		}
    }
	public function closeTag($index, $buffer, $localName, $nsUri, DOMElement $n, &$info) {
		if (isset($info->writeEndTag)) ParseFunctions::append(ParseFunctions::CODED_EXPR_IN_RENDER, $this, $buffer, '"'.$info->writeEndTag.'"'); //in CoreParser?
		if (isset($info->codeOnEndTag)) ParseFunctions::append(ParseFunctions::PHP_CODE, $this, $buffer, $info->codeOnEndTag); //in CoreParser?
        if (isset($info->wasCode)) $this->code--;
	}
	public function cData($index, $buffer, $data) {
        if ($this->code) //remember: in PHP you can use the 'application' prefix in inline mode only for R-VALUEs. Otherwise use runtime API.
    		ParseFunctions::append(ParseFunctions::PHP_CODE, $this, $buffer, 
					//$in_php_string, PartParser $p, StackSource $domain, DOMNode $n) {
                    Expr::resolveReferences(false, $data, $this, $this->domain, $this->coreParser->node, -1));
        else
			// buffer .= ...
        	ParseFunctions::append(ParseFunctions::CODED_EXPR_IN_RENDER, $this, $buffer, //forOutput false in quanto, in cData, va fatto esplicitamente. solo all'interno degli attributi è implicito in quanto, se attributo, vuol dire che è tag, perciò forOutput (che s'intende xml)
					Expr::RealRValue('string', $data, '', $this, $this->domain, $this->coreParser->node, false, false));
	}
	public function start() {
		$this->coreParser = new CoreParser($this);
		$this->coreParser->parse($this->startNode);
        ParseFunctions::append_last();
	}
}
//serializable
class DocComponent {
	const MACRO = 0;
	const PART =  1;
    const DESCRIPTOR = 2;
	const FILE_SETS = 3;
	var $id;
	var $update = true;
	var $rt_data = null;
	var $type;
	var $simpleName;
	var $fullName;
	var $freeAttr;
    var $attrFormatCache;
    var $attributesIndex = array();
	var $df;
	var $subElements;
    var $exportData;
	var $exportCaller;
	var $exportDiff;
	var $useSession = false; //array(); //use array to see the macro/descriptor that generates the exception SessionInApplcationException <see Expr::resolveSymbol>
    var $to = array(); //[type] => DocComponent..
	 var $to_context = array(); //context name (it is unique, always) [VALORIZED THROUGH GENERALINDEX->NOTIFYCONTEXTURL]
	public function __sleep() {	//$this->update = false; $this->rt_data = null; //superfluo
		return array('type', 'simpleName', 'fullName', 'df', 'subElements', 
			'exportData', 'exportCaller', 'exportDiff', 'useSession', /*'rt_data', */
            'id', /*'update', */ 'to', 'to_context', 'attrFormatCache', 'attributesIndex', 'freeAttr'); }
    public function to(DocComponent $c) {
        $this->to[$c->id/*fullName*/] = $c;
        $this->df->to($c->df);
		GeneralIndex::$index->notifyFrom($this, $c);
    }
	public static function factory($type, DocFile &$df, DOMNode $n) {
        if ($type == self::PART)
            $p = new DocPart();
        else if ($type == self::MACRO)
            $p = new DocMacro();
        else if ($type == self::DESCRIPTOR)
            $p = new DocDescriptor();
		else if ($type == self::FILE_SETS) {
			$p = new DocFileSets();
			$p->df =& $df;
			return $p;
		}
		//$p = ($type == self::PART) ? new DocPart() : ($type == self::DESCRIPTOR) ? new DocDescriptor() : new DocMacro();
		$p->id = GeneralIndex::$index->gidmax++;
		$p->type = $type;
		$p->df =& $df;
		$p->simpleName = $n->getAttribute("name");
		if (strpos($p->simpleName, ':') !== false)
			throw_xml($n, 'IncorrectEventName', 'Cannot use attribute ":"');
		$p->fullName = $df->fileName .':'. $p->simpleName;
		$p->rt_data = array();
        if ($type == self::DESCRIPTOR && $n->hasAttribute('solid') && $n->getAttribute('solid') != 'true')
            throw_xml($n, 'IllegalAttribute', 'solid');
		if ($type == self::MACRO && strtolower($n->getAttribute('solid')) == 'true')
            $p->solid = true;
		return $p;
	}
	public function howFetchSubElements() {
		if (count($this->subElements) == 0) return CoreParser::NO_BLOCKS;
		if (count($this->subElements) == 1 && isset($this->subElements[DEFAULT_NAME])) return CoreParser::DEFAULT_BLOCK;
		return CoreParser::FETCH_NAMED_BLOCKS;
	}
}
class DocPart extends DocComponent {
	var $implements = array();
    var $forHandling;
    var $runOn;
    var $fileIndex = 0;
	 var $isAutoContext = false;
	public function __sleep() {
		$a = parent::__sleep();
		$a[] = 'runOn';
		$a[] = 'forHandling';
		$a[] = 'implements';
		$a[] = 'isAutoContext';
		return $a;
	}
}
class DocMacro extends DocComponent {
	var $solid = false;
	var $hasCode = false;
	var $code = null;
	//var $fileSets = null;
    var $isDescriptor = false;
	public function __sleep() {	if (!$this->hasCode) $this->code = null; $a = parent::__sleep(); /*$a[] = 'fileSets'; */ return $a; }
	//load|save (ricordati n.1 livello in più: <dude><macro>..content..</macro></dude>)
	public function loadCode() {
		$f = $GLOBALS['compiler.rootdir'] . (($this->df->isLib) ? '~system/~/m.'.Utils::encode($this->fullName).'.xml' : $_SESSION['project-name'].'/~/m.'.Utils::encode($this->fullName).'.xml');
        return $this->code = file_get_contents($f);
	}
	public function saveCode() { //pernde this->code solo dopo con il serialize di DocMacro
		$f = $GLOBALS['compiler.rootdir'] . (($this->df->isLib) ? '~system/~/m.'.Utils::encode($this->fullName).'.xml' : $_SESSION['project-name'].'/~/m.'.Utils::encode($this->fullName).'.xml');
        file_put_contents($f, $this->code);
	}
}

class DocDescriptor extends DocMacro {
    var $solid = true;
	//var $rootOnly = false;
    var $isDescriptor = true;
	var $handlers = array();
	function handled(DocPart $p) {
		$this->handlers[] = $p;
	}
	public function __sleep() {
		$a = parent::__sleep();
		$a[] = 'handlers';
		//$a[] = 'rootOnly';
		return $a;
	}
}

class DocFileSets extends DocComponent {
}

class DocFile { //df
	var $isLib;
	var $fileName;
	var $parts = array(); // [nome] => DocComponent
	var $macros = array(); // [nome] => DocComponent
    var $to = array(); //di troppo
	 var $r_creates = array();
    public function to(DocFile $f) {
        $this->to[$f->fileName] = $f;
    }
	 public function __sleep() { 
		return array('isLib', 'fileName', 'parts', 'macros', 'to', 'r_creates'); 
	}
}

class DocFileAssets {
	var $files;
	var $requires;
	var $creates;
	var $requires_extern;
}

// runtime di compilazione (non serializzabile)
class CompileFile { //cf
	var $df;
	var $dom;
	var $isGlobal;
	var $fileSets;
	var $macroContainerNode;
	var $partNodes = array(); //DocComponent, DOMElement, sub-elements
   var $handlers_tmp = array();
	var $declaredNamespaces;
	static $comp_count = 0;
	public function CompileFile($file, $isLibrary = false) {
        $this->isGlobal = $file == '~global.xml';
		try {
			$this->dom = openXMLDoc($GLOBALS['compiler.filebasedir'].$file);
		}
		catch (PHPWarning $e) {
			@list($p, $m) = $a = explode(']:', $e->getMessage(), 2);
			if (!isset($m)) $m = $p;
			Build::error(Build::ERR_FATAL, $m, $file);
		}
		if ($this->dom->documentElement) {
			if ($this->dom->documentElement->tagName != 'dude') 
				return;
            if ((string)$this->dom->documentElement->namespaceURI != '' && strtolower($this->dom->documentElement->namespaceURI) != 'http://local/dude') {
                Log::warning(0, 'Skip source file', 'Skipping document <dude> with unknown namespace: "'.$this->dom->documentElement->namespaceURI.'"', $file, $this->dom->documentElement);
                return;
            }
            /*if ($this->dom->documentElement->namespaceURI && substr($this->dom->documentElement->namespaceURI, 0, 5) != 'http:' 
                    && $this->dom->documentElement->namespaceURI != Compiler::DUDE_NAMESPACE_URI) //UNCOMMENT!!
					throw_xml($this->dom->documentElement, 'UnsupportedNamespaceFeature', 'Default namespace, if specified, must be http://local/Dude or an URI outside Dude applications');*/
			$this->declaredNamespaces = array();
			TODO::getNamespacesForElement($this->dom->documentElement, $this->declaredNamespaces);
			$this->dom->documentElement->appendChild($this->macroContainerNode = $this->dom->createElementNS('http://local/Dude', 'macro-container'));
			$this->df = new DocFile();
			$this->df->isLib = $isLibrary;
			$this->df->fileName = $file;
			$this->as = new DocFileAssets();
			$this->compile(); //unire..
			return;
		}
	}
	
	//concentrati su compile-file
    public function compile() {
		//var_dump($this->df->fileName);
		self::$comp_count++;
        chdir($GLOBALS['compiler.filebasedir'].dirname($this->df->fileName));        
        $cfg = array('d' => Compiler::DUDE_NAMESPACE_URI);
		//assets
		$this->fileSets = new StackSource(new DocComponent(), '', false, false, Utils::encode($this->df->fileName));
		$dc = DocComponent::factory(DocComponent::FILE_SETS, $this->df, $this->dom->documentElement); 
		$tmp_parser_filesets = new PartParser($this, $dc, null, $this->fileSets);
        foreach ($this->dom->documentElement->childNodes as $n) {
         if ($n->nodeType != XML_ELEMENT_NODE) continue;
         $p = null; 
		 //var_dump($n->namespaceURI);
         $ns_area = TODO::getNamespaceArea($n->namespaceURI, $this->dom->documentElement->namespaceURI);
         try {
            $desc = null;
            $yet_not_found = false;
				$name = $n->localName;
				if ($ns_area & TODO::NS_KEYWORD && strtolower($n->localName) == 'set') {
					$name = $n->getAttribute("name");
					if ($this->isGlobal)
						throw new FileSetsNotAllowedInGlobalFile($name);
					if (isset($this->file_sets[$name]))
						throw new DuplicateFileSetException($name, $this);
					$value = Expr::RealRValue('any', $n->getAttribute('value'), null, $tmp_parser_filesets, $this->fileSets, $n, false, true);
					$this->fileSets->set($name, $value, !(strtolower($n->getAttribute('read-only')) != 'true'));
				}
				else if ($ns_area & TODO::NS_KEYWORD && strtolower($n->localName) == 'part') {
					$p = $this->compile_part($n);
				}
				else if ($ns_area & TODO::NS_KEYWORD && (strtolower($n->localName) == 'define-macro' 
						|| $desc = (strtolower($n->localName) == 'define-descriptor'))) {
					$p = $this->compile_macro($n, isset($desc));
				}
				else if ($ns_area & TODO::NS_KEYWORD && $n->localName == 'context') {
					$c = Context::factory($this->df, $n);
					if ($this->isGlobal) //meglio evitare altrimenti vanno tutti lì e con le relazioni..
						throw new ComponentsNotAllowedInGlobalFile($c->name);
					GeneralIndex::$index->notifyContext($c, $n);                    
				}
				else if ($ns_area & TODO::NS_KEYWORD && $n->localName == 'asset') {
					if (($f = $n->getAttribute('file')) || ($f = $n->getAttribute('files'))) {
						$parse = Expr::RealRValue('bool', $n->getAttribute('parse'), false, null, null, null, false, true);
						foreach (explode(',', $f) as $f) {
							//if (COMPILER_CHECKS_ASSETS && ($f == '.' || $f == '..' || strpos($f, '/') !== false))
							if (COMPILER_CHECKS_ASSETS && (substr($f, 0, 3) == '../' || substr(0, 3) == '..\\'))
								throw_xml($n, 'Relative asset paths cannot reference to parent positions (in the project you can use absolute path, instead):', $f, $this->df->fileName);
							if (COMPILER_CHECKS_ASSETS && (substr($f, 0, 2) == './' || substr(0, 2) == '.\\'))
								throw_xml($n, 'Relative asset paths cannot use the \'./\' prefix (simply, remove it):', $f, $this->df->fileName);
							$this->as->files[] = array(trim($f), $parse);
						}
					}
					else if ($f = $n->getAttribute('require')) {
						$parse = Expr::RealRValue('bool', $n->getAttribute('parse'), false, null, null, null, false, true);
						foreach (explode(',', $f) as $f) {
							if (COMPILER_CHECKS_ASSETS && (substr($f, 0, 3) == '../' || substr(0, 3) == '..\\'))
								throw_xml($n, 'Relative asset paths cannot reference to parent positions (in the project you can use absolute path, instead):', $f, $this->df->fileName);
							if (COMPILER_CHECKS_ASSETS && (substr($f, 0, 2) == './' || substr(0, 2) == '.\\'))
								throw_xml($n, 'Relative asset paths cannot use the \'./\' prefix (simply, remove it):', $f, $this->df->fileName);
							$this->as->requires[] = array(trim($f), $parse);
						}
					}
					else if ($f = $n->getAttribute('require-extern')) {
						$parse = Expr::RealRValue('bool', $n->getAttribute('parse'), false, null, null, null, false, true);
						foreach (explode(',', $f) as $f) {
							if (COMPILER_CHECKS_ASSETS && $f[0] != '/' && substr($f, 0, 2) != '~/')
								throw_xml($n, 'Extern assets must be always an absolute path:', $f, $this->df->fileName);
							if (substr($f, 0, 2) == '~/')
								$f = $GLOBALS['compiler.rootdir'] . substr($f, 2);
							$this->as->requires_extern[] = array(trim($f), $parse);
						}
					}
					/*
					else if ($f = $n->getAttribute('create-file')) {
						if ($n->hasAttribute('parse')) throw_xml($n, 'create-file assets do not have the parse attribute', $f, $this->df->fileName);
						foreach (explode(',', $f) as $f) {
							//case insensitive, like MS Windows
							if (@isset($this->as->f_creates[strtolower($f)])) throw_xml($n, 'create-file '.$f.' already specified (be aware of letter cases)', $f, $this->df->fileName);
							$this->as->f_creates[strtolower($f)] = array($f); //ready to future optionals
						}
					}*/
					else if ($f = $n->getAttribute('create-require')) {
						if ($n->hasAttribute('parse')) throw_xml($n, 'create-require assets do not have the parse attribute', $f, $this->df->fileName);
						foreach (explode(',', $f) as $f) {
							//case insensitive, like MS Windows
							if (@isset($this->df->r_creates[strtolower($f)])) throw_xml($n, 'create-require '.$f.' already specified (be aware of letter cases)', $f, $this->df->fileName);
							if (strpbrk($f, '\\/') !== false) throw_xml($n, 'create-require '.$f.': path not allowed', $f, $this->df->fileName);
							$this->df->r_creates[strtolower($f)] = array($f); //ready to future optionals
						}
					}
					else throw_xml($n, 'asset declaration syntax error or unknown attributes: file / require / create-file only', $f, $this->df->fileName);
				}
            else if ($ns_area & TODO::NS_KEYWORD && $n->localName == 'macro-container') {
            }
            else if ($ns_area & TODO::NS_KEYWORD || $ns_area & TODO::NS_COMPONENT) {
                //echo "<li><b>[component] ".$n->tagName."</b></li>";
                $this->probablyDescriptors[] = $n;
            }
            else //if ($ns_area & TODO::NS_EXTERN || $ns_area & TODO::NS_INVALID) 
					//|| STRICT_KEYWORD_NAMESPACES || !isset(Compiler::$Dude_Root_Keywords[$n->localName]))
                throw_xml($n, 'UnrecognizedInstructionSymbol(1)', $n->namespaceURI.':'.$n->localName.' (ns-area: '.$ns_area.')');
         }
         catch (Exception $e) { //AttrFormatException $e) {
			 throw_xml($n, get_class($e), $e->getMessage(), $this->df->fileName);
            //die('Errore formato attributi "'.$n->getAttribute('attr-format').'" @ '.$p->fullName);
         }
        }
		unset($tmp_parser_filesets);
		//solo ora può verificare che gli handlers siano dichiarati corramente
        foreach ($this->handlers_tmp as $p) {
            if (isset($this->df->macros[$p->forHandling])) $m = $this->df->macros[$p->forHandling]; else $m = null;
            if (!$m || !$m->isDescriptor)
                Build::error(Build::ERR_FATAL, 'Descriptor not found', $p->forHandling);
            if (!$p->runOn) $p->runOn = 'context-start';
            $p->forHandling = $m;
			$m->handled($p);
        }
	}

	public function compile_part(DOMElement $n, $isUpdater = false) {
        $p = DocComponent::factory(DocComponent::PART, $this->df, $n);
		$p->runOn = $n->getAttribute('run-on');
        if ($p->forHandling = $n->getAttribute('for-handling-descriptor'))
			$this->handlers_tmp[] =& $p;
		if ($this->isGlobal) {
			if ($p->forHandling) throw_xml($n, 'CannotSpecifyHandlersInGlobalFile', $p->simpleName);
		} else {
			if (!$p->forHandling && $p->runOn) throw_xml($n, 'MissingForHandlingAttribute', $p->fullName);
		}
		if ($p->runOn) {
			$e = Compiler::$Events;
			if (!isset($e[$p->runOn]))
				throw_xml($n, 'UnknownEventName', '"'.$p->runOn.'" @ '.$p->fullName);
			if ($p->runOn == 'session-end') 
				throw_xml($n, 'UnsupportedEvent', $p->df->fileName.':session-end');
			if ($this->isGlobal) {
				if ($p->simpleName)
					throw_xml($n, 'GlobalEventsCannotHaveName', $p->fullName.' ..run-on='.$p->runOn);
				else {
					$p->simpleName = $p->runOn;
					$p->fullName = $p->df->fileName.':'.$p->runOn;
				}
			} else if (!$p->simpleName) {
				$p->simpleName = $p->forHandling.'-'.$p->runOn;
				$p->fullName = $p->df->fileName .':'. $p->simpleName;
			}
		}
		$startPoint = null;
        if (($e = $n->getAttribute('auto-context')) != '') {
			if (!$p->simpleName) {
				$p->simpleName = $e;
				$p->fullName = $p->df->fileName.':'.$e;
			}
			$p->isAutoContext = true;
            $c = Context::factory($this->df, $n, $p);	// <------ set the partStartPoint
            GeneralIndex::$index->notifyContext($c, $n);
			$startPoint = $c->partStartPoint;
        }
		if ($p->simpleName == '')
			throw_xml($n, 'MissingPartName', $this->df->fileName);
        if (isset($this->df->parts[$p->simpleName]))
            throw_xml($n, 'PartNameAlreadyDefined', $p->fullName);
        $p->fileIndex = count($this->df->parts);
        $this->df->parts[$p->simpleName] = $p;
        $s = array(); TODO::readSubElementsAndExportData(DocComponent::PART, $p->simpleName, $this->dom, $n, $s, $startPoint);
        $this->partNodes[] = array($p, $n, $s); //forse serve fullName..
        $p->subElements =& $s['index'];
        $p->exportData =& $s['export-vars'];
		$p->exportCaller =& $s['export-caller'];
		$p->exportDiff = count($p->exportData) - count($p->exportCaller);
		//to externalize in a second moment
		//if ($n->getAttribute('attr-format') == '*')
		//	$p->freeAttr = true;
		//else
	    $p->attrFormatCache = AttrFormat::prepareFormat($n->getAttribute('attr-format'), Compiler::$Reserved_Part_Attributes, $p);
        if ($isUpdater) $p->isUpdater = true;
        GeneralIndex::$index->notifyPart($p);
		return $p;
    }
	 // returns object DOMELement when found otherwise FALSE
	 // TODO: really, it does not check the prefix, thus there is not isolated XML code, 
	 // then we cannot add external -non Dude- tag 
	 private static function compile_macro_findFirstCodeTag($n) {
		foreach ($n->childNodes as $n) {
			if ($n->nodeType != XML_ELEMENT_NODE) continue;
			// TODO: non verifica il prefix, perciò non si potrà usarlo..
			if ($n->tagName == 'code') return $n;
			else throw_xml($n, 'Unknown element');
		}
		return false;
	 }
	 public function compile_macro(DOMElement $n, $isDescriptor) {
        $p = DocComponent::factory($isDescriptor ? DocComponent::DESCRIPTOR : DocComponent::MACRO, $this->df, $n);
        if ($this->isGlobal)
			throw_xml($n, 'ComponentsNotAllowedInGlobalFile', $p->fullName, $this->df->fileName);
        if (isset($this->df->macros[$p->simpleName]))
            Build::error(1, "Macro/Descriptor {$p->fullName} already defined in {$this->df->fileName}", $this->df->fileName, $n->getLineNo());
        $this->df->macros[$p->simpleName] = $p;
        $s = array(); TODO::readSubElementsAndExportData($isDescriptor ? DocComponent::DESCRIPTOR : DocComponent::MACRO, $p->simpleName, $this->dom, $n, $s);
        $this->macroNodes[] = array($p, $n, $s);
        $p->rt_data[RT_NODE_C] = 0; $p->rt_data[RT_NODE] = $n;
		  if (!$nc = self::compile_macro_findFirstCodeTag($n)) {
			$nc = $n->ownerDocument->createElementNS('http://local/Dude', 'code');
			$nc->setAttribute('sub-elements', '');
			$n->appendChild($nc);
		  }
		  $ns_list = array();
		  TODO::getNamespacesForElement($n, $ns_list, false);
		  foreach ($this->declaredNamespaces as $prefix => $url) {
			 if (!isset($ns_list[$prefix])) {
				$n->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:'.$prefix, $url);
			}
		  }
		  $p->code = $this->dom->saveXML($n); //lo perde solo con dopo il serialize + TODO namespace <dude> 
        $p->saveCode();
        $p->hasCode = (strtolower($n->getAttribute('quick-call')) == 'true');
        $p->subElements =& $s['index'];
        $p->exportData =& $s['export-vars']; //superflua
//		if ($n->getAttribute('attr-format') == '*')
//			$p->freeAttr = true;
//		else
	    $p->attrFormatCache = AttrFormat::prepareFormat($n->getAttribute('attr-format'), Compiler::$Reserved_Macro_Attributes, $p);
        if ($p->isDescriptor = $isDescriptor)
            GeneralIndex::$index->notifyDescriptor($p);
        else
            GeneralIndex::$index->notifyMacro($p);     
		return $p;
    }
    public function find_used_descriptors() {
        //if (!isset($this->probablyDescriptors)) return;
        $c = $this->dom->createElementNS('http://local/Dude', 'part');
        $c->setAttribute('name', 'dude-content-updater');
        chdir($GLOBALS['compiler.filebasedir'].dirname($this->df->fileName));
        if (isset($this->probablyDescriptors))
          foreach ($this->probablyDescriptors as $n) {
			$name = $n->localName;
			$ns_area = TODO::getNamespaceArea($n->namespaceURI, $this->dom->documentElement->namespaceURI);
            if ($ns_area & TODO::NS_COMPONENT && $m = GeneralIndex::$index->nearest(DocComponent::DESCRIPTOR, $n->localName, $n->namespaceURI, $this->df->fileName, '')) {
                $c->appendChild($n);
					 //NOTE: there is not yet relation between use of the descriptor in the file and which part of that file use it
					 //in order to establish a relation between them. So we create a generic relation for entire file (and every component)
                $this->df->to($m->df);
				$this->to_desc[] = $m;
            }
			else if ($ns_area ^ TODO::NS_KEYWORD) {
				//var_dump('STRICT_KEYWORD_NAMESPACES='.STRICT_KEYWORD_NAMESPACES.' !ISSET='.(!isset(Compiler::$Dude_Root_Keywords[$n->localName])));
				//if (STRICT_KEYWORD_NAMESPACES || !isset(Compiler::$Dude_Root_Keywords[$n->localName]))
					Build::error(1, 'Unrecognized symbol '.$n->namespaceURI.':'.$n->localName .' or namespace path does not exists ('.$ns_area.')', $this->df->fileName, $n->getLineNo());
            }
          }
        $this->dom->documentElement->appendChild($c);
        $this->compile_part($c, true); //fa il notifyPart()
    }
	static $global_copy_file = array();
	public function generate($isLib = false) 
	{
        $mtime = filemtime($GLOBALS['compiler.filebasedir'].$this->df->fileName);
        $pathPrefix = '';
		$dir = dirname($this->df->fileName);
		if ($dir == '.') $dir = '';
        if ($this->df->isLib) { /* $pathPrefix = 'lib/';*/ $mtime = -$mtime; }
		chdir($GLOBALS['compiler.filebasedir'].$dir);
		$c = "<?php\n# {$this->df->fileName}\n"; //require '~runtime.php';\n";
		$requires_list = '';
		$copy_file = array();
		//$rprefix = DirFunctions::rootPrefix($dir);
		if (isset($this->as->requires))
			foreach ($this->as->requires as $f) {//errori saranno verificati alla creazione del context
				list($f, $parse) = $f;
				$f = $pathPrefix.addcslashes($f, '\'');
				//the require can be absolute or relative but no to parents
				//$req_f = $f[0] == '/' ? $rprefix.substr($f, 1) : $f;
				$req_f = $f[0] == '/' ? 'ROOT_PREFIX.\''.$f.'\'' : "'$f'";
				$c .= "require_once $req_f;\n";
				if ($f[0] != '/') $f = "$dir/$f";
				//if ($f[0] == '/') $f = substr($f, 1);
				if ($f[0] != '/') $f = "/$f";
				if (!isset($copy_file[$f])) {
					//var_dump('copy file: '.$f);
					$requires_list .= ' Piece::$FILES[\''.$f."'] = array(".($this->df->isLib?'true':'false').", $parse);\n";
					$copy_file[$f] = array($this->df->isLib, $parse);// costretto a copiare, una prima volta
				}
			}
			//customized asset requires
			/*
			if (isset($this->df->r_creates))
				foreach ($this->df->r_creates as $f) {//errori saranno verificati alla creazione del context
					list($f) = $f;
					//$f = $pathPrefix . addcslashes($f, '\'');
					//the create-require is only realtive, translated as absolute when compiled/installed
					//[TODO: ASSICURATI CHE create-require NON ABBIA SIMBOLI DI PERCORSO!]
					$req_f = 'ROOT_PREFIX.\'lib/'.Utils::encode($fileWhereUsed).'__'.Utils::encode($df->fileName).'__'.$f.'\'';
					$php .= "include_once $req_f;\n"; //TODO: eventuale errore se non è presente?
				}
			*/
			$c .= "\nclass ".($f = Utils::encode($this->df->fileName))." {\n  static \$DS_F = array("; $cm1 = '';
		foreach ($this->fileSets->d as $i => $d) {
			$c .= "$cm1{$i} => ".$d[1]; $cm1 = ', ';
		}
		$c .= ");\n}\n";
        $m_used = array();//used macro in this file, in general, not from a particular part
        $handlers = array();
		$upm_file = array();
        foreach ($this->partNodes as $np) {
            //$c .= "$f::parts["
            $p = new PartParser($this, $np[0], $np[1], $this->fileSets);
            $p->start();
			$upm_file[$p->dc->simpleName] = $p->upm;
            $c .= "/* <part name='".$p->dc->simpleName."'> */\nclass ".Utils::encode($p->dc->fullName)." extends Piece {\n";
			$c .= '  const PART_NAME = \''.addcslashes($p->dc->fullName, '\'')."';\n";
			//$c .= '  const PART_BUILD_ID = '.$p->dc->id.";\n";
			//now it is a property because in PHP is is ugly access to statics/constants from instances
			$c .= '  var $PART_BUILD_ID = '.$p->dc->id.";\n";
			/*$c .= '  //const ATTR_INDEX = array('; $cm1 = '';
			foreach ($p->dc->attributesIndex as $i => $n) 
				{ $c .= $cm1 . "'$n' => $i"; $cm1 = ', '; }
			$c .= ");\n";*/
            $c .= "\n  var \$USED_SUBELEMENTS = array("; $cm1 = '';
            foreach ($p->upm as $index => $upm) {
                $c .= $cm1."$index => array("; $cm2 = '';
                foreach (array_keys($upm['se-used']) as $bname) {
					$bname = strtolower($bname);
                    $c .= "$cm2'$bname' => ".$upm['se-used'][$bname]; $cm2 = ',';
                }
                $c .= ')'; $cm1 = ',';
            }
			$c .= ");\n\n";
            if ($p->dc->forHandling) {
                $c .= "  //this is a handler\n  const HANDLER_FOR = '{$p->dc->forHandling->fullName}';\n";
                $c .= "  const HANDLER_RUN_ON = '{$p->dc->runOn}';\n\n";
                $handlers[$p->dc->fullName] = array($p->dc->forHandling->simpleName, $p->dc->forHandling->fullName, $p->dc->runOn);
            }
            $c .= "  public function main(\$CONTEXT, &\$DS/*, Piece \$__caller*/) {\n\t\$this->DS =& \$DS;\n"
						;// . "    \$this->__caller_buffer[] = \$this->caller = \$__caller;";

			if (trim($p->buffer[0]) != '') $c .= $p->buffer[0].';';
			$exportBinding = TODO::bindExportData($p, $p->dc->exportCaller);
			$c .= "\n\t\$this->DS_E = $exportBinding; \n  }\n"; //\$this->caller = @array_pop(\$this->__caller_buffer); }\n";
            foreach ($p->callbacks as $cb) {
                $c .= "  public function ".Utils::encode($cb['obj']->fullName)."_do_".Utils::encode($cb['name'])."_{$cb['index']}(\$CONTEXT, &\$DS_E) {\n{$cb['buffer']}\n  }\n";
            }
            $c .= "}\n\n";
        }
        foreach ($handlers as $h_fullName => $buff) //0 => d_simpleName, 1 => d_fullName, 2 => runOn
            $c .= "Piece::\$HANDLERS['{$buff[2]}']['{$buff[1]}'] = '".Utils::encode($h_fullName)."';\n";
		$c .= "if (!defined('AUTO_UPDATE_CACHE')) {\n";
        $c .= ' Piece::$AUTO_UPDATE[\''.$pathPrefix.addcslashes($this->df->fileName, '\'')."'] = $mtime;\n";
		if (isset($this->as->files))
			foreach ($this->as->files as $f) {//errori saranno verificati alla creazione del context
				//var_dump($f);
				list($f, $parse) = $f;
				$f = /*$pathPrefix.*/addcslashes($f, '\'');
				if ($f[0] != '/') $f = "$dir/$f";
				//if ($f[0] == '/') $f = substr($f, 1);
				if ($f[0] != '/') $f = "/$f";
				//var_dump($f);
				$c .= ' Piece::$FILES[\''.$f.'\'] = array('.($this->df->isLib?'true':'false').", $parse);\n";
			}
		if (isset($this->as->creates))
			foreach ($this->as->creates as $f) {//errors will be verified at the context creation time
				list($f /*, $parse*/) = $f;
				$f = /*$pathPrefix.*/addcslashes($f, '\'');
				if ($f[0] != '/') $f = "$dir/$f";
				//if ($f[0] == '/') $f = substr($f, 1);
				if ($f[0] != '/') $f = "/$f";
				//var_dump($f);
				$c .= ' Piece::$C_FILES[\''.$f.'\'] = array('.($this->df->isLib?'true':'false').");\n";
			}
		$c .= $requires_list;
        $c .= "}\n?>";
		//if (!defined('CONTEXT_NAME'))
		//	echo "<pre>".htmlspecialchars($c)."</pre><hr/>";
		//scrittura file
		chdir($GLOBALS['compiler.filebasedir']);
        $f_name = Utils::chext($this->df->fileName, 'xml', 'php');
        $f = array();
        if ($this->df->isLib) {
            $f[0] = '~system/~runtime/';
            $f[1] = $_SESSION['project-name'].'/~run/lib/';
        } else {
            $f[0] = '~run/';
        }
		//var_dump(getcwd());
        foreach ($f as $f) {
            @mkdir(dirname($f . $f_name), 0777, true);
            file_put_contents($f . $f_name, $c);
			file_put_contents($f . Utils::chext($f_name, 'php', 'upm'), serialize($upm_file));
			foreach ($copy_file as $cf => $data) {
			list($isLIb, $parse) = $data;
			if (!$isLib && $cf[0] != '/') die('copy_file: mi aspettavo slash iniziale, oppure mi aspetto sempre un percorso assoluto');
				$cf = substr($cf, 1); //all have now the slash at the beginning
				//TODO: check and generate conflicts with PHP name file and with the source file
				$d = dirname($f.$cf);
				if (@mkdir($d) === false && file_exists($d) && !is_dir($d))
					Build::error(1, 'Cannot create the path '.$d.' due of a file with name \''.basename($d).'\' that exists in destination directory. Try use different name or delete the file', $this->df->fileName);
				//var_dump("copy: $cf to $f{$cf}");
				//if (!isset($global_copy_file[$cf])) {commentato per via che $f è un array
					if (@copy($cf, $f.$cf) === false)
						Build::error(1, 'Asset file not found '.$cf, $this->df->fileName);
				//	else
				//		$global_copy_file[$cf] = true;
				//}
				//var_dump("realpath $cf: ".realpath($cf));			
			}
        }
	}
}

?>