<?php

//require_once "parse-functions-data.php"; //catch_reference|set|get|capture
class UnresolvedDataException extends Exception {
/*
	public function __construct(PartParser $p, $symbol) {
		parent::__construct(Utils::errorMsg($p->coreParser->node, "\"$symbol\" @ " . ($p->macro ? $p->macro['macro']->fullName : $p->dc->fullName)
						. ($p->macro ? ' (Tip: inside a Macro the only way to references to an attribute is using &lt;code get-attr=../&gt;)' : '')));
	}
*/
}

class MissingArgument extends Exception {}
class UnknownAttribute extends Exception {}
class UnknownCodeInstruction extends Exception {}

abstract class ParseFunctions {

	public static function apply_part(PartParser $p, DOMElement $e, DocComponent $c, $index, $buffer) {
		$partUseName = Utils::encode($c->fullName);
		//if (!$p->freeAttr)
		self::append(self::PHP_CODE, $p, $buffer, "\n\t\$DS_C = " . TODO::bindPartAttributes($p, $c, $e) . ";\n");
		//else
		//self::append(self::PHP_CODE, $p, $buffer, "\n\t\$DS_C = " . TODO::bindAnyAttribute($p, $e) . ";\n");
		self::append(self::PHP_CODE, $p, $buffer, "\t\$this->P[$index] = \$this->get(\$this, $index, '$partUseName', '".addcslashes($p->part['alias'], '\'')."'); \$this->P[$index]->main(\$CONTEXT, \$DS_C, \$this);\n"); //fare tutto in apply_piece, dove vanno passati gli attributi
	}

	const SUBELEM_RENDER = 0;
	const SUBELEM_GET_CONTENTS = 1;
	
	public static function subelement_callback($mode, PartParser $p, DOMElement $n, $index, $buffer, $name) {
		$p->subElementsFound = true;
		$ref = $p->macro ? '$this' : '$this->client';
		$obj = $p->macro ? $p->macro['cmp'] : $p->dc;
		$index = $p->macro ? /* IN 4.6 è $p->currentIndex */ $index : '$this->index'; //istanza
		$sc = $p->code == 0 ? ';' : '';
		$exportBinding = isset($p->dc->exportData[DEFAULT_NAME]) ? TODO::bindExportData($p, $p->dc->exportData[DEFAULT_NAME]) : 'array()';
		if ($mode == self::SUBELEM_GET_CONTENTS)	
			self::append(self::PHP_CODE, $p, $buffer, "self::get_subelements_content(\$CONTEXT, $ref, \$this->DS_E = $exportBinding, '" . Utils::encode($obj->fullName) . "_do_" . Utils::encode($name) . "', $index)$sc");
		else
			self::append(self::PHP_CODE, $p, $buffer, "\n\t{$ref}->node(\$CONTEXT, \$this->DS_E = $exportBinding, '" . Utils::encode($obj->fullName) . "_do_" . Utils::encode($name) . "', $index)$sc");
	}

	public static function switch_keyword_tag(PartParser $p, DOMElement $n, $index, $buffer, &$closeInfo) {
		// * export
		if ($n->localName == 'export') {
			if (isset($p->subElementsFound))
				Build::error(1, "Cannot use export within sub-elements ({$p->dc->fullName})", $p->dc->df->fileName, $n->getLineNo());
		}
		// * set
		else if ($n->localName == 'set' || (($n->localName == 'code' || $n->localName == 'php') && $v = $n->getAttribute('set'))) {
			if (!isset($v))
				$v = $n->getAttribute('name'); //qui puoi fare isset($v) ma più in giù non più!!
			$v = Expr::resolveSymbol(false, $p, null, $v, Expr::FOR_LEFT, true);
			//$value = Expr::resolveAttrRValueAndReferences($n->getAttribute('value'), $p, $p->domain);
			$value = Expr::RealRValue('any', $n->getAttribute('value'), false, $p, $p->domain, $n);
			if (is_array($v) && $v[0] == 'application') {
				$expr = "ApplicationData::Set('{$v[1]}', $value)";
			} else {
				if (is_array($v)) {
					try {
						$r = $p->stack->set($dom = $p->macro ? $p->macro['source'] : $p->stackSource, $v[0], $v[1], $n->getAttribute('value'));
					} catch (Exception $e) {
						throw_xml($n, get_class($e), $e->getMessage());
					}
					$var_name = Expr::buildVarName_fromSet($r);
				} else
					$var_name = $v;
				$expr = "$var_name = $value";
			}
			self::append(self::PHP_CODE, $p, $buffer, "\t $expr" . ((($n->localName == 'code' || $n->localName == 'php') && $p->code > 0) ? '' : ";\n"));
		}
		// * capture
		else if ($n->localName == 'capture') {
			if (!($v = $n->getAttribute('into')))
				throw_xml($n, '', "Missing argument 'into'", $p->dc->df->fileName);
			list($alias, $v) = Expr::splitVarAlias($v);
			$append = ''; $init = false;
			if (Expr::RealRValue('bool', $n->getAttribute('append'), false, $p, $p->domain, $n)) {
				$append = '.';
				list($gs, $i) = $p->stack->get($dom = $p->macro ? $p->macro['source'] : $p->stackSource, $alias, $v, true);
				$init = ($gs == null);
			}
			self::append(self::PHP_CODE, $p, $buffer, '$CONTEXT++; self::$buffer[$CONTEXT] = \'\';');
			//$this->parse_set($dataName, '@self::$buffer[$CONTEXT]', '', '');
			$r = $p->stack->set($dom = $p->macro ? $p->macro['source'] : $p->stackSource, $alias, $v, $n->getAttribute('value'));
			$r[1] = $r[1][0]; //scarto 'old' e rendo compatibile con r restituito da get che accetta buildVarName
			$var_name = Expr::buildVarName($r);
			if ($init) $init = $var_name . ' = \'\'; ';
			$closeInfo->codeOnEndTag = "\t" . $init . $var_name . $append . '= self::$buffer[$CONTEXT]; $CONTEXT--' . (($n->localName == 'code' && $p->code > 0) ? '' : ";\n");
		}
		// * if-subelement-exists name=STRING [and-empty|and-not-empty =BOOL|
		else if ($n->localName == 'if-subelement-exists' || (($n->localName == 'code' || $n->localName == 'php') && (($sub_elem_name = $n->getAttribute('subelement-exists')) || $n->hasAttribute('subelement-exists')))) {
			$code = isset($sub_elem_name);
			if (!$code)
				$sub_elem_name = $n->getAttribute('name');
			if ($sub_elem_name == '')
				$sub_elem_name = ' ';
			$sub_elem_name = strtolower($sub_elem_name);
			//self::append(self::PHP_CODE, $p, $buffer, 'echo "<li>this->index: ".$this->index." (index='.$index.')"; var_dump($this->USED_SUBELEMENTS); if (is_object($this->client)) { echo "<li>this->client->index=".$this->client->index; var_dump($this->client->USED_SUBELEMENTS); }');
			$ref_obj = $p->macro ? '$this' : '$this->client';
			$ref_index = $p->macro ? $index : '$this->index';
			$and_empty = strtolower($n->getAttribute('and-not-empty')) == 'true' ? " && {$ref_obj}->USED_SUBELEMENTS[$ref_index]['$sub_elem_name'] > 1" :
					strtolower($n->getAttribute('and-empty')) == 'true' ? " && {$ref_obj}->USED_SUBELEMENTS[$ref_index]['$sub_elem_name'] == 1" : '';
			$condition = "(isset({$ref_obj}->USED_SUBELEMENTS[$ref_index]['$sub_elem_name'])$and_empty)";
			self::append(self::PHP_CODE, $p, $buffer, $code ? $condition : "\n\tif $condition {\n");
			if (!$code)
				$closeInfo->codeOnEndTag = "\n\t}";
		}
		else if ($n->localName == 'code' || $n->localName == 'php') {
			$p->code++;
			$closeInfo->wasCode = true;
			// * tag-start|tag-end
			$vc = '';
			if (($v = $n->getAttribute('tag-start')) || ($v = $n->getAttribute('render-tag-start')) || ($vc = $n->getAttribute('tag')) || ($vc = $n->getAttribute('render-tag'))) {
				if ($vc != '') {
					//I COULD get xml content of the node and process with resolveSymbols BUT the users could think
					//that all macros and parts inside of that content would be normally accepted/processed but they are not!
					//So explicitly deny any content.
					if ($n->childNodes->length > 0)
						throw_xml($n, 'UnsupportedFeatureException', 'Please use start-tag and end-tag for this kind of content. tag or render-tag is intended only for a single "closed" tag, e.g. <input />');
					$v = $vc; 
					$vc = '/';
				}
				$c = '';
				try {
					for ($i = 0; $i < $n->attributes->length; $i++) {
						$item = $n->attributes->item($i);
						if ($item->name != 'tag-start' && $item->name != 'tag-end' && $item->name != 'render-tag-start' && $item->name != 'render-tag-end')
							$c .= ' ' . $item->name . '=\"".'.Expr::RealRValue('string', $item->value, '', $p, $p->domain, $n, true).'."\"';
					}
				} catch (SyntaxError $x) {
					throw_xml($e, get_class($x), $x->getMessage(), $p);
				}
				self::append(self::CODED_EXPR_IN_RENDER, $p, $buffer, '"<'.$v.$c.$vc.'>"');
				if ($p->code == 1)
					self::append(self::PHP_CODE, $p, $buffer, ";\n");				
			}
			else if (($v = $n->getAttribute('tag-end')) || ($v = $n->getAttribute('render-tag-end'))) {
				self::append(self::CODED_EXPR_IN_RENDER, $p, $buffer, '"</'.$v.'>"');
				if ($p->code == 1)
					self::append(self::PHP_CODE, $p, $buffer, ";\n");			
			}
			// * render
			else if ($v = $n->getAttribute('render')) {
				///* lower */ $type, $something, $in_php_string, PartParser $p, StackSource $domain, DOMNode $n, $acceptReferences = true) {
				self::append(self::CODED_EXPR_IN_RENDER, $p, $buffer, 
						Expr::RealRValue('string', $v, false, $p, $p->domain, $n, false, false, false, true));
				if ($p->code == 1)
					self::append(self::PHP_CODE, $p, $buffer, ";\n");
			}
			// * render-data
			else if ($v = $n->getAttribute('render-data')) {
				$vi = array();
				//V-INDEX
				list($alias, $v) = Expr::splitVarAlias($v); //codice ridondante anche in resolveContextURL
				$r = $p->stack->get($p->domain, $alias, $v);
				if ($r[1] === false)
					throw_xml($n, 'Unresolved data exception', $v,  $p->dc->df->fileName);
				self::append(self::CODED_EXPR_IN_RENDER, $p, $buffer, Expr::buildVarName($r, $vi)); //perché self::append_last() aggiunge sempre e cmq le virgolette..
				if ($p->code == 1)
					self::append(self::PHP_CODE, $p, $buffer, ";\n");
			}
			// * render-attr
			else if ($v = $n->getAttribute('render-attr')) {
				self::append(self::CODED_EXPR_IN_RENDER, $p, $buffer, Expr::buildAttrVarName($p->macro ? $p->macro['cmp'] : $p->dc, $v, ($p->macro ? $p->macro['source']->macroIndex : false)) . '}');
				if ($p->code == 1)
					self::append(self::PHP_CODE, $p, $buffer, ";\n");
			}
			// * get-attr
			else if ($v = $n->getAttribute('get-attr')) {
				self::append(self::PHP_CODE, $p, $buffer, Expr::buildAttrVarName($p->macro ? $p->macro['cmp'] : $p->dc, $v, ($p->macro ? $p->macro['source']->macroIndex : false)));
				if ($p->code == 1)
					self::append(self::PHP_CODE, $p, $buffer, ";\n");
			}
			// * get-any-attr
			else if ($v = $n->getAttribute('get-attr')) {
			}
			// * has-attr
			else if ($v = $n->getAttribute('has-attr')) {
				if ($p->macro) {
					//$v = ($p->macroAttrBinding[$v] === null) = 'false' : 'true';
					$v = (string) ($p->macroAttrBinding[$v] !== null);
					///* lower */ $type, $something, $in_php_string, PartParser $p, StackSource $domain, DOMNode $n, $forOutput = false, $acceptConstOnly = false
					self::append(self::PHP_CODE, $p, $buffer, 
							Expr::RealRValue('bool', $v, false, $p, $p->domain, $n, false, true)); 
							//Expr::buildRValue($p->stackSource, $p->macroAttrBinding[$v]));
				}
				else
					self::append(self::PHP_CODE, $p, $buffer, '!is_null(' . Expr::buildAttrVarName($p->macro ? $p->macro['cmp'] : $p->dc, $v, false) . ')');
			}
			// * exists-data
			else if ($v = $n->getAttribute('exists')) {
				if (strpos($v, '{%') !== false || strpos($v, '%}') !== false)
					throw_xml($n, 'Unresolved data exception', $v,  $p->dc->df->fileName);
				self::append(self::PHP_CODE, $p, $buffer, Expr::resolveSymbol(false, $p, $p->domain, $v, Expr::FOR_EXISTS));
				if ($p->code == 1)
					self::append(self::PHP_CODE, $p, $buffer, ";\n");
			}
			// * get-descriptors-content (debug)
			else if ($v = $n->hasAttribute('get-descriptors-content')) {
				self::append(self::PHP_CODE, $p, $buffer, 'self::create_user_content($CONTEXT, \'' . Utils::encode($p->dc->df->fileName) . '\')');
				if ($p->code == 1)
					self::append(self::PHP_CODE, $p, $buffer, ";\n");
			}
			else if ($n->hasAttribute('get-subelement-content')) { //ma non fa un render, così !???
				self::subelement_callback(self::SUBELEM_GET_CONTENTS, $p, $n, $index, $buffer, $n->getAttribute('get-subelement-content'));
				if ($p->code == 1)
					self::append(self::PHP_CODE, $p, $buffer, ";\n");
			}
			else if ($n->attributes->length > 0) {
				throw_xml($n, 'Unknown code (php) instruction', $v,  $p->dc->df->fileName);
				//throw new UnknownCodeInstruction('code ' . Utils::representXMLAttributes($n));
			}
		}
	}
	
	# e sono rimasto anche qui
	public static function process_tag($ns_area, PartParser $p, DOMElement $e, $localName, $nsUri, $index, $buffer, &$info) {
		try {
			//var_dump($e->localName. " ns area: $ns_area");
			if ($ns_area & TODO::NS_KEYWORD || $ns_area & TODO::NS_COMPONENT 
					|| $e->namespaceURI == 'http://www.w3.org/html' || $e->namespaceURI == 'http://www.w3.org/1999/xhtml')
				$rewrite_abs_links = true;
			$c = "\"<{$e->localName}"; //tagName per conservare il prefix originale
			if ($e->localName == 'br') {
				echo '';
			}
			if ($e->hasAttributes()) {
				for ($i = 0; $i < $e->attributes->length; $i++) {
					$value = $e->attributes->item($i)->value;
					$name = $e->attributes->item($i)->name;
//					if ($name == 'xmlns-ns') $xmlns_ns = $value; da mettere per ora solo in tag-start
//					if ($name == 'xmlns-uri') $xmlns_uri = $value;
					$rewrite = false;
					if ($name == 'src' || $name == 'href') {
						$value = trim($value);
						if ($value && $value[0] == '/' && isset($rewrite_abs_links))
							$rewrite = true;
						else { if ($value && substr($value, 0, 2) != '{%' && $value[0] != '$' && $value[0] != '@' && $value[0] != '#' && substr($value, 0, 7) != 'http://' && substr($value, 0, 6) != 'ftp://' && substr($value, 0, 11) != 'javascript:')
							Log::message('Relative HTML link: '.$value, $p->dc->df->fileName, $e); }
					}
					if ($rewrite) { $value = substr($value, 1); }
					//if (strpos($value, 'icon')) { $_REQUEST['debugx1'] = true;
					//	echo "<li> (process_tag) ".htmlspecialchars($value); }
					//	else $_REQUEST['debugx1'] = false;
					$value = Expr::RealRValue('string', $value, '', $p, $p->domain, $e, true);
					if ($rewrite) $value = 'ROOT_PREFIX.'.$value;
					$c .= ' ' . $name . '=\"".'.$value.'."\"';
				}
			}
		} catch (SyntaxError $x) {
			throw_xml($e, get_class($x), $x->getMessage(), $p);
		}
		
		if ($e->childNodes->length || ($rewrite_abs_links && 
				/* TODO this could be configurable by the client for certain namespaces */
				array_search($e->localName, HTML::$tagsCanbeClosedInline) === false)) {
			$c .= ">\""; $info->writeEndTag = "</{$e->localName}>";
		} else
			$c .= "/>\"";
		self::append(self::CODED_EXPR_IN_RENDER, $p, $buffer, $c);
	}

	static $last_parser; //nota: esiste una sola istanza di PartParser alla volta
	static $last_buffer;
	static $last_p_code;
	static $last_rvalue = '';

	const PHP_CODE = 0;	//similar to APPEND_CODED_CONTENT but it will not put it as arument in self::render()
	const CODED_EXPR_IN_RENDER = 1;  //the expression must is like $this->DS[123]."<table>". It can (or it cannot) starts with quotes, however it is **PHP code** ready to be putted as RValue
	const VALUE_CONTENT = 2;  //create a string with that value, or it creates the PHP expression able to represent the specified value, so it is not PHP code but it is a content codified in a PHP string. It does not call the output filter. It can be used rarely because in most cases we will use RealRValue. Not using resolveReferences, not resolve symbols

	//automatically append the semicolon only at self::render (!)

	public static function append($type, PartParser $p, $buffer, $code_or_rvalue) {
		if ($type > 0) {
			if (self::$last_rvalue != '' && (self::$last_p_code != $p->code || self::$last_buffer != $buffer))
				self::append_last();

			if ($type == self::VALUE_CONTENT)
				$code_or_rvalue = '\''.addcslashes($code_or_rvalue, '\'').'\'';
			
		if (strpos(self::$last_rvalue, '<br>') !== false)
				throw new Exception('<br>');

			self:: $last_buffer = $buffer;
			if (self:: $last_rvalue != '' && $code_or_rvalue != '')
				self:: $last_rvalue .= '.';
			
			/*if (substr(self::$last_rvalue, -2) == '".' && $code_or_rvalue[0] == '"')
				self:: $last_rvalue = substr(self::$last_rvalue, 0, -2) . substr($code_or_rvalue, 1);
			else*/
				self:: $last_rvalue .= $code_or_rvalue;

			self:: $last_p_code = $p->code;
			self:: $last_parser = $p;
		}
		else {
			if (self::$last_rvalue != '')
				self::append_last();
			$p->buffer[$buffer] .= $code_or_rvalue;
		}
	}

	public static function append_last() {
		if (self::$last_rvalue == '')
			return;
		self::$last_rvalue = str_replace('"."', '', self::$last_rvalue);
		if (USE_SELF_RENDER)
			self::$last_parser->buffer[self::$last_buffer] .=
					//be sure to have processed with addcslashes
					"\n\tself::render(\$CONTEXT, " . self::$last_rvalue . ')' . (self::$last_p_code > 0 ? '' : ';');
		else
			self::$last_parser->buffer[self::$last_buffer] .=
					//be sure to have processed with addcslashes
					"\n\tself::\$buffer[\$CONTEXT] .= " . self::$last_rvalue . (self::$last_p_code > 0 ? '' : ';');
		self::$last_rvalue = ''; //in convention with first line of append()
	}

}

?>
