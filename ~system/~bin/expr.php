<?php
   
class SyntaxError extends Exception {}
class CannotUseContextURLInLValue extends Exception {}
class CannotUseReferencesHere extends Exception {}

class Expr {
    static $extern_prefixes = array('session', 'application', 'context', 'get', 'post', 'cookie');
    
    /*public static function buildRValue(StackSource $domain, $rawValue, $type) {//per ora restituisce semplici stringhe
        return '"'.addcslashes($rawValue, '"').'"';
    }*/
	public static function buildVarName_fromSet(/*StackSetREsult*/ $r) {
		$r[1] = $r[1][0];
		return self::buildVarName($r);
	}
   public static function buildVarName(/*StackGetResult*/ $domain_result, $vectorIndexes = array()) {
        if ($domain_result[0]->macro)
            $n = '$this->DS_M'.$domain_result[0]->macroIndex;
        else if ($domain_result[0]->export)
            $n = '$this->P['.$domain_result[0]->exportIndex.']->DS_E';
			//$n = '$this->DS_E['.$domain_result[0]->exportIndex.']';
        else if ($domain_result[0]->file_set)
			$n = $domain_result[0]->file_set.'::$DS_F';
		else
            $n = '$this->DS';
		$n .= '/*'.$domain_result[0]->d[$domain_result[1]][0].'*/';
		$n .= '['.$domain_result[1].']';
		foreach($vectorIndexes as $v)
			$n .= "[$v]";
		return $n;
    }
    public static function buildAttrVarName(DocComponent $c, $attrName, $indexIfMacro) {
		$mi = $indexIfMacro !== false ? '_M'.$indexIfMacro : '';
	    return '$this->DS'.$mi.'['.array_search($attrName, $c->attributesIndex).']';
    }
    public static function splitVarAlias($varName) {
        $e = explode('.', $varName, 2);
        if (!isset($e[1])) array_unshift($e, '');
        return $e;
    }
	
	// ------------------------------------------------------------------
	// n.3 functions resolves all:
	// - RealRValue
	// - resolveReferences
	// - resolveSymbol
    const FOR_RIGHT = 0;
    const FOR_LEFT = 1;
	const FOR_EXISTS = 2;
	public static function resolveSymbol($in_php_string, PartParser $p, $domain, $symbol, $for = self::FOR_RIGHT, $ignoreAppLValueException = false) {
		$isset = false;
		$string_mode = 1; //1.brackets 0.concat -1.plain text
		if (strtolower(substr($symbol, 0, 12)) == 'context-url:') {
			if ($for == self::FOR_LEFT) 
				throw new CannotUseContextURLInLValue($symbol);
			if ($for == self::FOR_EXISTS)
				return (string)(int)isset(GeneralIndex::$index->context[substr($symbol, 12)]);
			list($result, $x_name, $x_params) = Expr::buildContextURL($p, $p->domain, substr($symbol, 12));
			if ($x_name) //It notifies only relation to other not to itself
				GeneralIndex::$index->notifyContextURL($p->dc, $x_name);
			$string_mode = 0; // string concatenation
		}
		else if (strtolower(substr($symbol, 0, 12)) == 'require-url:') {
			if ($for == self::FOR_LEFT) 
				throw new CannotUseAssetReferencesInLValue($symbol);
			$file = substr($symbol, 12);
			if (!$p->dc->forHandling || !isset($p->dc->df->r_creates[strtolower($file)])) {
				throw new Exception('request-url: the file '.$file.' does not exist in the source file of the handler'); //TODO terminare, più prolisso
			}
			$lib = $p->dc->df->isLib ? 'lib_' : '';
			$result = "ROOT_PREFIX.'lib/".$lib.Utils::encode($p->dc->df->fileName)."__'.\$this->DS[0].'__custom-code.php'";
			if ($for == self::FOR_EXISTS)
				return (string)(int)file_exists($file);
			$string_mode = 0; // string concatenation
		}
		else {
			list($a, $v) = self::splitVarAlias($symbol);
			if (strlen($a) > 2) $_a = strtolower($a); else $_a =& $a;
			if ($a == '~') {
				$_v = strtolower($v);
				if ($_v == 'partname') {
					if ($for == self::FOR_EXISTS) return '1';
					$result = 'self::PART_NAME'; //brackets ok
				}
				if ($_v == 'alias') {
					if ($for == self::FOR_EXISTS) return '1';
					$result = '$this->alias'; //brackets ok
				}
				else if ($_v == 'coordinates') {
					if ($for == self::FOR_EXISTS) return '1';
					$result = '' . $p->coreParser->curr_coord_n . ',' . $p->coreParser->curr_coord_s . ''; 
					$string_mode = -1;
				}
				// LastUPM data was implemented to allow the "edit-spots"
				else if ($_v == 'lastupm_coordinates') {
					if ($for == self::FOR_EXISTS) return '1';
					$result = "array(".$p->upm[count($p->upm)-1]['coord'][0].",".$p->upm[count($p->upm)-1]['coord'][1].")";
					$string_mode = 0; //variable object, cannot use brackets or plain string
				}
				// also this one
				else if ($_v == 'parentnodepath') {
					if ($for == self::FOR_EXISTS) return '1';
					$result = $p->coreParser->node->parentNode->getNodePath();
					$string_mode = -1; //plain text
				}
				else if ($_v == 'lastupm_name') {
					if ($for == self::FOR_EXISTS) return '1';
					$result = $p->upm[count($p->upm)-1]['cmp']->fullName;
					$string_mode = -1;
				}
				else if ($_v == 'lastupm_type') {
					if ($for == self::FOR_EXISTS) return '1';
					$result = get_class($p->upm[count($p->upm)-1]['cmp']);
					$string_mode = -1;
				}
				//End of LastUPM data
				else if ($_v == 'filepath') {
					if ($for == self::FOR_EXISTS) return '1';
					$result = dirname($p->dc->df->fileName).'/';
					if ($p->dc->df->isLib) $result = '/lib/'.$result;
					$string_mode = -1;
				}
				else if ($_v == 'partbuildid') {
					if ($for == self::FOR_EXISTS) return '1';
					//(commentato perché più veloce) $result = 'self::PART_BUILD_ID'; //brackets ok
					$result = '$this->PART_BUILD_ID'; //brackets ok
				}
				else if ($_v == 'uniqueid') {
					if ($for == self::FOR_EXISTS) return '1';
					//(deprecato come costante perché più veloce) $result = '(self::PART_BUILD_ID.\'_\'.$this->index)'; $string_mode = 0; //no brackets 
					$result = '($this->client->PART_BUILD_ID.\'_\'.$this->index)'; $string_mode = 0; //no brackets 
				}
				else if ($_v == 'caller') {
					if ($for == self::FOR_EXISTS) return '1';
					$result = '$this->client::PART_NAME'; //brackets ok
				}
				else if ($_v == 'filename') {
					if ($for == self::FOR_EXISTS) return '1';
					$result = $p->dc->df->fileName; $string_mode = -1; //plain string, no variable or symbol
				}
				else if ($_v == 'encodedfilename') {
					if ($for == self::FOR_EXISTS) return '1';
					$result = Utils::encode($p->dc->df->fileName); $string_mode = -1; //plain string, no variable or symbol
				}
				else if ($_v == 'contextname') {
					if ($for == self::FOR_EXISTS) return '1';
					$result = 'CONTEXT_NAME'; $string_mode = 0; //defined, cannot use brackets
				}
				else if ($_v == 'projectname') {
					if ($for == self::FOR_EXISTS) return '1';
					$result = 'PROJECT_NAME'; $string_mode = 0; //defined, cannot use brackets
				}
				else if ($_v == 'rootprefix') {
					if ($for == self::FOR_EXISTS) return '1';
					$result = 'ROOT_PREFIX'; $string_mode = 0; //defined, cannot use brackets
				}
				else if ($_v == 'dudepath') { //will not be available after export (that will remain, but will not be valid)
					if ($for == self::FOR_EXISTS) return 'file_exists("'.addcslashes($GLOBALS['compiler.rootdir'], '"').'")';
					$result = $GLOBALS['compiler.rootdir']; $string_mode = -1; //plain text
				}
				else throw_xml($p->coreParser->node, 'UnresolvedDataException', $symbol, $p->dc->df->fileName);
			}
			else if ($_a == 'application') {
				if ($for == self::FOR_RIGHT) 
					$result = "ApplicationData::Get('$v')";
				else if ($for == self::FOR_EXISTS)
					$result = "ApplicationData::Exists('$v')";
				else if (!$ignoreAppLValueException)
					throw_xml($p->coreParser->node, 'CannotUseApplicationInLValue', $symbol, $p->dc->df->fileName);
			}
			else if ($_a == 'session') {
				GeneralIndex::$index->notifySessionVar($v);
				$p->dc->useSession = true; //[$p->macro ? $p->macro['macro']->fullName : $p->fullName] = $symbol;
				$isset = true;
				$result = "\$_SESSION['$v']";
			}
			else if ($_a == 'context') {
				$isset = true;
				$result = "\$_REQUEST['$v']";
			}
			else if ($_a == 'cookie') {
				$isset = true;
				$result = "\$_COOKIE['$v']";
			}
			else if ($_a == 'get') {
				$isset = true;
				$result = "\$_GET['$v']";
			}
			else if ($_a == 'post') {
				$isset = true;
				$result = "\$_POST['$v']";
			}
			else if ($_a == 'system') {
				$isset = true;
				$result = "\$_SERVER['".strtoupper($v)."']";
			}
			else if ($for == self::FOR_RIGHT) {
//				if ($v == 'field1Caption') {
//					echo 1;
//				}
				$r = $p->stack->get($domain, $a, $v);
				if ($for == self::FOR_EXISTS)
					return (string)(int)($r[1] === false);
				if ($r[1] === false){
					echo '';
					$s = $p->stack;
					throw_xml($p->coreParser->node, 'UnresolvedDataException', $symbol, $p->dc->df->fileName);
				}
				$result = self::buildVarName($r);
			}
			if ($for == self::FOR_EXISTS && $isset) {
				$result = "isset($result)";
				$string_mode = 0;
			}
			if (!isset($result))
				return array($_a, $v);
		}
		if ($in_php_string) { //ma addcslaches!?
			if ($string_mode == -1)
				$result = addcslashes($result, $in_php_string);
			else
				$result = $string_mode == 1 ? '{'.$result.'}' : ($in_php_string.'.'.$result.'.'.$in_php_string);
		} else if ($string_mode == -1)
			$result = '\''.addcslashes($result,'\'').'\'';
		return $result;
    }
    public static function resolveReferences($in_php_string, $v, PartParser $p, StackSource $domain, DOMNode $n, 
			$skipExpressionChar = 0, &$symbol_found = 0, $forOutput = false, $phpVarsInString = false, $noEscape = false) {
		if (strpos($v, '{%') === false) {
			if ($skipExpressionChar == 0 && !$noEscape) return addcslashes($v, "\\\r\n\t"); 
			if ($skipExpressionChar == 1) return substr($v, 1);
			return $v;
		}
		$buff = ''; 
		$escaped = false; $prev_c = '';
		$in_symbol = false; $in_string = '';
		$text_found = 0; $start = $end = (int)($skipExpressionChar == 1); $strlen = strlen($v);
		for ($i=$start; $i<$strlen; $i++) {
			//TODO ADD SUPPORT FOR PHP COMMENTS
			$c = $v[$i];
			if (!$in_php_string && !$in_symbol && ($c == '"' || $c == '\'') && (!$in_string || $c == $in_string)) { // $in_php_string le stringhe
				$in_string = ($in_string && !$escaped) ? false : $c;
				$escaped = false;
			}
			else if ($in_string && $c == '\\') {
				$escaped = true;
			} 
			else $escaped = false;
			if ($c == '}' && $prev_c == '%') {
				if (!$in_symbol) throw_xml($n, 'SyntaxError (1)', $v);
				if ($in_php_string && $buff) $buff .= $in_php_string.'.'; //chiude stringa 
				$buff .= self::resolveSymbol(false, $p, $domain, substr($v, $start + 2, $i - $start - 3));
				$end = $i + 1;
				$in_symbol = false;
				$symbol_found++;
			}
			else if ($c == '{' && $i < ($strlen-1) && $v[$i+1] == '%') {
				if ($in_symbol) throw_xml($n, 'SyntaxError (2)', $v);
				if ($in_php_string && $buff) $buff .= '.'.$in_php_string; //apre stringa
				else if ($end == 0 && $i > 0) $buff .= $in_php_string;
				$token = substr($v, $end, $i - $end);
				//$token = addcslashes($token, '\\');
				//if ($in_php_string) $token = addcslashes($token, "\\");
				if (/*$in_php_string && */$skipExpressionChar == 0 && !$noEscape) $token = addcslashes($token, "\\\t\n\r");
				if ($phpVarsInString) $token = addcslashes($token, '$');
				if ($forOutput)
					$buff .= addcslashes(Compiler::outputEntitiesFilter($token), $in_php_string);
				else
					$buff .= addcslashes($token, $in_php_string);
				$start = $i;
				$in_symbol = true;
				$text_found++;
			}
			else if ($i == $strlen - 1) {
				if ($in_symbol || $in_string) throw_xml($n, 'SyntaxError (3)', $v);
				if ($in_php_string) $buff .= ($buff ? '.' : '').$in_php_string; //apre stringa
				$token = substr($v, $end, $i - $end + 1);
				//if ($in_php_string) $token = addcslashes($token, "\\");
				if (/*$in_php_string && */$skipExpressionChar == 0 && !$noEscape) $token = addcslashes($token, "\\\t\n\r");
				if ($phpVarsInString) $token = addcslashes($token, '$');
				if ($forOutput)
					$buff .= addcslashes(Compiler::outputEntitiesFilter($token), $in_php_string);
				else
					$buff .= addcslashes($token, $in_php_string);
				if ($buff && $in_php_string) $buff .= $in_php_string; //chiude stringa
				$text_found++;
			}
			$prev_c = $c;
		}
		return $buff;
	}
	//returns a code that can be used as r-value in php
	const RV_EXPR = 1;
	const RV_CONST = 2;
	public static function RealRValue(/* lower */ $type, $something, $in_php_string, PartParser $p = null, 
			StackSource $domain = null, DOMNode $n = null, $forOutput = false, $acceptConstOnly = false, 
			$phpVarsInString = false, $noEscape = false) {
			//'string', $value, '', $p, $p->domain, $e, true);
		$result_info = self::RV_CONST;
		if ($something === null) return 'null';
		if (gettype($something) != 'string') die('RealRValue: not string');
		if (strpos($something, '{%') === false) { //non devo usare resolveReferences
			//if ($in_php_string) $something = addcslashes($something, "\\");
			if (/*$in_php_string && */!$noEscape && $something != '' && $something[0] != '@') 
				$something = addcslashes($something, "\\\t\n\r");
			if ($something != '' && $something[0] == '$' && $type != 'string') {
				 if ($acceptConstOnly)
					throw_xml($n, 'CannotUseReferencesHere 1', $something, $p->macro ? $p->macro['cmp']->df->fileName : $p->dc->df->fileName);
				return $something;//todo: solo se è davvero una semplice e sola variabile php
			}
			if ($something != '' && $something[0] == '@') {
				if ($acceptConstOnly && (strpos($something, '{%') !== false || strpos($something, '$') !== false))
					throw_xml($n, 'CannotUseReferencesHere 2', $something, $p->macro ? $p->macro['cmp']->df->fileName : $p->dc->df->fileName);
				return substr($something, 1);
			}
			$_something  = strtolower($something);
			if ($type == 'string') goto string_const;
			if ($type == 'bool' || $type == 'boolean') goto boolean_const;
			if ($type == 'number' || $type == 'int' || $type == 'num') goto number_const;
			if ($type == 'var') goto var_const;
			goto any_const;
		}
		else if ($acceptConstOnly)
			throw_xml($n, 'CannotUseReferencesHere 3', $something, $p->macro ? $p->macro['cmp']->df->fileName : $p->dc->df->fileName);
		
		$symbols_found = 0;
		$in_php_string = $something[0] != '@' ? '"' : '';
		//if (isset($_REQUEST['debugx1']) && $_REQUEST['debugx1']) {
		//	echo "<li>in_php_string: '$in_php_string' v='$phpVarsInString' s='$symbols_found' o='$forOutput'";
		//	echo "skip expr. char = '".(int)(!$in_php_string)."' ";
		//}
		if ((int)(!$in_php_string)) { $forOutput = false; } //echo " forOutput='$forOutput'"; }
		$something = self::resolveReferences($in_php_string, $something, $p, $domain, $n, (int)(!$in_php_string), $symbols_found, $forOutput, $phpVarsInString);
		if ((int)(!$in_php_string)) $something = "($something)";
		if ($symbols_found > 0) $result_info = self::RV_EXPR;
		//if ((int)(!$in_php_string))
		//	echo "<li> (realrvalue) ".htmlspecialchars($something);
		return $something;
		
	var_const:
		if ($something == '' || $_something == 'null' || $_something == 'false' || $_something == 'no')
			return 'null';
		//goto any_const;
		
	any_const:
		if ($_something == 'true' || $_something == 'yes' || $_something == 'no' || $_something == 'false')
			goto boolean_const;
		if (is_numeric($something))
			goto number_const;
		//goto string_const;

	string_const:
		$something = addcslashes($something, $in_php_string);
		if ($in_php_string) return $something;
		if ($forOutput) 
			return '"'.addcslashes(Compiler::outputEntitiesFilter($something), '"').'"';
		return '"'.addcslashes($something, '"').'"';
		
	boolean_const:
		if ($_something == 'true' || $_something == 'yes'
				|| (is_numeric($something) && (double)$something != 0)) 
			return 'true';
		return 'false';
		
	number_const:
		if ($_something == 'true' || $_something == 'yes') return '1';
		if ($something == '') return '0';
		return (string)(double)$something;
	}

	const START_STRING		= 1;
	const END_STRING		= 2;
	const START_PARENTHESIS = 3;
	const END_PARENTHESIS	= 4;
	const START_WORD		= 5;
	const END_WORD			= 6;
	const ASSIGNMENT		= 7;
	const COMMA				= 8;
	const START_V_INDEX		= 9;
	const END_V_INDEX		= 10;
	
	private static function tokenize($b, &$map, $last_pos, &$p_count, $options = TOKENIZE_CONTEXT_URL) {//restituisce lunghezza blocco trovato
		if ($b == '') throw new SyntaxError($last_pos);
		$i = -1;
		$in_string = -1;
		$in_word = false;
		$len = strlen($b);
		$next_escape = -1;
		$last_start_word = 0;
		$status = 0;
		for ($i=0; $i<$len; $i++) {
			$c = strtolower($b[$i]);
			$c = ($c >= 'a' && $c <= 'z') || ($c >= '0' && $c <= '9') || $c == '$' || ($c == '.' ) || $c == '_' || $c == '-';
			if ($c && !$in_word && $options & TOKENIZE_CONTEXT_URL && $p_count > 0 && $in_string == -1) {  
				$in_word = $c; $map[] = array(Expr::START_WORD, $last_start_word = $last_pos + $i );
				continue; }
			if (!$c && $in_word) { $map[] = array(Expr::END_WORD, $last_pos + $i ); $in_word = false;	 }
			if ($in_string == -1 && $b[$i] == '(') { $p_count++; $map[] = array(Expr::START_PARENTHESIS, $last_pos + $i);
					$i += self::tokenize(substr($b, $i + 1), $map, $last_pos + $i + 1, $p_count, $options); continue; }
			if ($in_string == -1 && $b[$i] == ')') { $p_count--; $map[] = array(Expr::END_PARENTHESIS, $last_pos + $i); break; }
			if ($in_string == -1 && $b[$i] == '"') { $in_string = $i; $q = '"'; $map[] = array(Expr::START_STRING, $last_pos + $i ); continue; }
			if ($in_string == -1 && $b[$i] == '\'') { $in_string = $i; $q = '\''; $map[] = array(Expr::START_STRING, $last_pos + $i ); continue; }
			if ($in_string == -1 && $b[$i] == ',') { $map[] = array(Expr::COMMA, $last_pos + $i ); continue; }
			if ($in_string == -1 && $b[$i] == '=') { $map[] = array(Expr::ASSIGNMENT, $last_pos + $i ); continue; }
			if ($in_string > -1 && $b[$i] == '\\') { $next_escape = $i + 1; continue; }
			if ($in_string > -1 && $next_escape != $i && $b[$i] == $q) { $in_string = -1; $map[] = array(Expr::END_STRING, $last_pos + $i ); continue; }
			if ($in_string == -1 && $b[$i] == '[') { $map[] = array(Expr::START_V_INDEX, $last_pos + $i ); continue; }
			if ($in_string == -1 && $b[$i] == ']') { $map[] = array(Expr::END_V_INDEX, $last_pos + $i ); continue; }
		}
		if ($in_string != -1)
			throw new SyntaxError('missing closing quote');
		return $i + 1;
	}
	
	public static function buildContextURL(PartParser $parser, StackSource $domain, $url, $p_count = 0) {
		$map = array(); 
		$p_count = 0; $x = null;
		if ($url != '') {
			if (strpos($url, '(') === false) $url .= '()';
			self::tokenize($url, $map, 0, $p_count);
		}
		if (count($map) > 0) $name = substr($url, 0, $map[0][1]); else $name = $url;
		if (!$name) {
			if (!isset(GeneralIndex::$index->start_points[$parser->dc->id])) {
				var_dump($parser->dc->fullName . "id = ". $parser->dc->id);
				var_dump($s = GeneralIndex::$index->parts);
				throw new SyntaxError("anonymous context-url can be used only in part \"start points\""); 
			}
			$x = GeneralIndex::$index->start_points[$parser->dc->id];
		} else {
			if (!isset(GeneralIndex::$index->context[$name])) {
				if (DENY_URL_TO_UNRESOLVED_CONTEXT) throw new UnresolvedContextName($name);
				Log::warning(Log::UNRESOLVED_CONTEXT_NAME, $name, 
						'Unresolved context name: "'.$name.'"', $parser->cf->df->fileName, $parser->coreParser->node);
				//$xxx = GeneralIndex::$index->contexts;
				$p_name = $name;
				if (isset(GeneralIndex::$index->context['404']))
					$x = GeneralIndex::$index->context[$_404 = '404'];
				else
					$name = "(ROOT_PREFIX.'~unready.php?name=".addcslashes($name, '\'')."')";
			} else {
				$x = GeneralIndex::$index->context[$name];
			}
		}
		if ($p_count < 0) throw new SyntaxError("too many closed parenthesis or missing open parenthesis or string quote error");
		else if ($p_count > 0) throw new SyntaxError("missing closed parenthesis or string quote error");
		$t_pos = 0;
		$p_count = 0;
		$n_assegnazioni_argomento[0] = 0;
		$prec_START_arg[0] = 0;
		$last_start_word = -1;
		$arguments = array();
		$arguments['*'] = '';
		for ($a='*',$i=0; $i<count($map); $i++) {  //inizio parentesi con p_count > 1 significa che sono in un'espressione, dovrei fregarmene se è i nome di una funzione, però dovrei anceh capire il nome di una variabile dude!
			$t = $map[$i];
			if ($t[0] == Expr::END_WORD || $t[0] == Expr::START_WORD) continue;
			$token = trim(substr($url, $t_pos, $t[1] - $t_pos));
			if ($t[0] == Expr::START_PARENTHESIS) { 
				$p_count++; $n_assegnazioni_argomento[$p_count] = 0; $prec_START_arg[$p_count] = $i; }//lo sovrascrive Expr::ASSIGNMENT se ce n'è bisogno
			if ($t[0] == Expr::END_PARENTHESIS) $p_count--; 
			if ($t[0] == Expr::ASSIGNMENT) { $n_assegnazioni_argomento[$p_count]++; 
				if ($p_count == 1 && $n_assegnazioni_argomento[$p_count] == 1) { $a = null; $prec_START_arg[$p_count] = $i; } }
			if ($t[0] == Expr::ASSIGNMENT && $n_assegnazioni_argomento[$p_count] == 1 && $p_count == 1)
				{ $a = $token; $arguments[$token] = ''; }
			if (($t[0] == Expr::COMMA && $p_count == 1) || ($t[0] == Expr::END_PARENTHESIS && $p_count == 0)) {
				//if (!isset($x)) break;
				$n_assegnazioni_argomento[1] = 0;
				for ($p = $prec_START_arg[1]; $p <= $i; $p++) {
					$xpos = $map[$p][1];
					$what = $map[$p][0];
					if ($what != Expr::COMMA && $p < count($map) - 1) {
						$subtok = trim(substr($url, $xpos, $map[$p + 1][1] - $xpos));
						if ($what == Expr::START_WORD && $subtok[0] != '$' && ((string)((float)$subtok)) != $subtok
								//salta i nomi di funzione:
								&& $i > 1 && $map[$i-2][0] != self::START_PARENTHESIS) {
							list($alias, $subtok) = Expr::splitVarAlias($subtok);
							$r = $parser->stack->get($domain, $alias, $subtok);
							if ($r[1] === false)
								throw_xml($parser->coreParser->node, 'UnresolvedDataException', $subtok, $parser->dc->df->fileName);
							//todo add support to V-INDEX: I must cycle wating and of v-index the pass them as array
							//or externalize from buildVarName
							$arguments[$a] .= Expr::buildVarName($r, array());
						}
						else 
							$arguments[$a] .= $subtok;
					}
				}
			}
			$t_pos = $t[1] + (int)($t[0] != Expr::START_WORD);
		}
		$c = ''; $cm = '';
		if ($name == '/Projects/Explorer') {
			echo 'found';
		}
		if (!$x) return array($name, $p_name, '');
		if (!isset($_404) && !ENABLE_SMART_ARGUMENTS_ON_CONTEXT_URL)
			AttrFormat::check($x->partStartPoint->attrFormatCache, null, $arguments);
		//GeneralIndex::$index->notifyAnonymousContext($this->dc, $arguments);
		if (!isset($_404))
		 foreach($arguments as $n => $a) {
			if ($n == '*' || $n == '') continue;
			if (isset($x) && array_search($n, $x->partStartPoint->attributesIndex) === false)
				throw_xml($parser->coreParser->node, 'UnknownAttribute', "'$n' for ".$x->partStartPoint->fullName, 
						$parser->dc->df->fileName);
			if ($a[0] == '=') $a[0] = ' ';
			if ($name)
				$c .= '.\''.urlencode($n).'=\'.urlencode('.$a.').\'&\'';
			else
				$c .= "$cm'$n' => $a";
			$cm = ',';
		 }
		else { //404 è sempre URL
		 $c = '.\'context-name='.urlencode($name).'\'';
		 $name = '404';
		}
		if ($name) {
			$enc_name = Utils::encode($name, true).'.php';
			if ($enc_name[0] == '/') $enc_name = substr($enc_name, 1);
			if ($c != '') { $enc_name .= '?'; }
			$c = "ROOT_PREFIX.'$enc_name'$c";
		} else {
			$c = "self::reprocess_url(array($c))";
		}
		return array("($c)", $name, $c);
	}
	
}

define('TOKENIZE_CONTEXT_URL', 1);

?>
