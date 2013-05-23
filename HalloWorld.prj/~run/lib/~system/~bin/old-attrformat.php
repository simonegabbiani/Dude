<?php

/* I APOLOGIZE FOR ITALIAN COMMENTS, THEY WILL BE TRANSLATED SOON AS POSSIBLE */

// Ora riconosce soltanto:
// ANY|STRING|NUMBER
//
// Da aggiungere eventualmente VAR-NAME o altri filtri di contesto, tipo chi ha generato la variabile, 
// se la variabile ? stata modificata dopo che ? stata generata la prima volta, se ? dentro un ciclo, ecc.
//
// Nella vers precedente, che era un interprete, riconosceva anche i tipi della variabile (poteva provare
// i cast al volo..) e inoltre check_tag_attr_syntax era prevista dentro le macro, ora invece deve essere
// il compilatore a fare il controllo, prima di risolvere l'utilizzo di una macro
//

class AttrFormatException extends Exception {}
class AttrMissingException extends Exception {}

abstract class AttrCheckable {
 	protected function __format_hasAttr($a) {
		return isset($this->al[$a]);
	}

	//se ? assente restituisce null
	//nel caso vi sia semplicemente una variabile, restituisce 'any'
	//nel caso vi sia del testo (anche puramente numerico) e una variabile, restiuisce string
	//solo nel caso vi sia un valore numerico esplicito restituisce num
	//in tutti gli altri casi restituisce string
	//(potremmo mettere un secondo post-compilazione che vada a leggersi almeno le risorse di testo
	//o costruire dei metodi in automatico, per il test)
	protected function __format_getAttr($a) {
		if (!isset($this->al[$a])) 
			$x = null;
		else {
			$a = $this->al[$a];
			if (strrpos($a, '{%') == 0 && strpos($a, '%}') == strlen($a) - 2) 
				$x = 'any';
			else if (strrpos($a, '{%') > 0 || strpos($a, '%}') < strlen($a) - 2) 
				$x = 'string';
			else if ($a == '') 
				$x = 'string'; //manca gestione 'mandatory' nel format attributi
			else { 
				$v = (float) $a;
				if ($v == $a) 
					$x = 'num';
				else
					$x = 'string';
			}
		}
		////echo "getAttr ($a): $x \n";
		return $x;
	}   
}

class AttrFormat extends AttrCheckable {
    var $al;
    var $exludeAttrList;
    private function _check($formatEvalCode, DOMElement $useNode = null, &$preformattedAttributesList = null) {
//        foreach (array_keys($this->al) as $at)
//            if (array_search($at, $this->excludeAttrList) === false && array_key_exists($at, $list) === false)
//                throw new AttrFormatException("e2");
		if (!$useNode && $preformattedAttributesList)
			$this->al =& $preformattedAttributesList;
		else
	        $this->al = AttrFormat_OLD::namedNodeMapToArray($useNode->attributes);//togliere da qui
        eval("\$result = $formatEvalCode;");
        return $result;
    }
	public static function prepareFormat($format, &$excludeList, DocComponent &$c) {
        $af = new AttrFormat_OLD($format, $excludeList);
        try {
            $cacheEvalCode = $af->check2($af->format, false, AttrFormat_OLD::OP_NO_EVAL);
        } 
        catch (Exception $e) {
			$e = $e->getMessage();
		    //if ($e == "e2")
			//	throw new AttrFormatException("Ci sono attributi in pi?, non previsti. Mi aspettavo solo questi: ".$this->format);
			//else {
            $a = array(	'p1' => 'Manca una parentesi chiusa',
                'p2' => 'C\'? una parentesi chiusa di troppo',
                'f1' => 'Errore nello specificare un argomento facoltativo',
                'a1' => 'Manca il "type", es.: "argument=string" anzichè semplicemente "argument"',
                'a2' => 'Type "%s" non riconoscito o errato',
                'e1' => 'Uno o pi? attributi non hanno il valore corretto oppure sono assenti, il formato previsto ? (1)',
                'e3' => 'Gli argomenti con lo stesso nome devono essere dello stesso tipo [ND: overload part mancante]:',
                'e4' => 'Unknown type in format');
            throw new AttrFormatException(sprintf($a[substr($e,0,2)], substr($e, 2)));
			//}
        }  
        //TODO modificare check2 e afre sia getattributelist e op_no_eval in un unico passaggio
        foreach(array_keys($af->lastList) as $n)
			array_push($c->attributesIndex, $n);

        unset($af);
        return $cacheEvalCode; //, &$index);
    }
    public static function check($formatEvalCode, DOMElement $useNode = null, &$preformattedAttributesList = null) {
        $a = new AttrFormat();
        if (!$a->_check($formatEvalCode, $useNode, $preformattedAttributesList))
			throw new AttrMissingException("Attributi mancanti o di valore non corrispondente");
		unset($a);        
    }
}

class AttrFormat_OLD extends AttrCheckable {

	const OP_CHECK = 0;
	const OP_NAME_LIST = 1;
	const OP_DETAIL_LIST = 2;
    const OP_NO_EVAL = 3;
	
	var $excludeAttrList;

	function __construct($format, &$excludeAttrList = array()) {
		;//echo "---- FORMAT '$format'" . implode(',', $excludeAttrList);
		$this->format = strtolower($format);
		$this->excludeAttrList = $excludeAttrList;
	}
	
	public static function namedNodeMapToArray($map, $visualForm = false) {
		$a = array(); 
		if (!$visualForm)
			foreach ($map as $name => $attr) 
				$a[$name] = $attr->value;
		else
			foreach ($map as $name => $attr) 
				$a[$name] = "$name = '".addcslashes($attr->value, '\'')."'";
		return $a;
	}

	function check($useNode, $exception = false, $strict = false) {
		$this->al = self::namedNodeMapToArray($useNode->attributes);
		try {
			$this->check2($this->format, $strict);
		} catch (Exception $e) {
			$e = $e->getMessage();
			if (!$exception)
				return $e;
			else if ($e == "e1")
				throw new Exception("Attributi mancanti o di valore non corrispondente. Vedi il formato: ".$this->format);
			else if ($e == "e2")
				throw new Exception("Ci sono attributi in pi?, non previsti. Mi aspettavo solo questi: ".$this->format);
			else {
				$a = array(	'p1' => 'Manca una parentesi chiusa',
					'p2' => 'C\'? una parentesi chiusa di troppo',
					'f1' => 'Errore nello specificare un argomento facoltativo',
					'a1' => 'Manca il "type", es.: "argument=string" anzich� semplicemente "argument"',
					'a2' => 'Type "%s" non riconoscito o errato',
					'e1' => 'Uno o pi? attributi non hanno il valore corretto oppure sono assenti, il formato previsto ?: "' . $this->format . '" (1)',
					'e3' => 'Gli argomenti con lo stesso nome devono essere dello stesso tipo [ND: overload part mancante]: '. $this->format,
					'e4' => 'Unknown op_type in format: ' . $this->format);
				throw new Exception(sprintf($a[substr($e,0,2)], substr($e, 2)));
			}
		}
	}
    
	/*
	 * Restituisce un insieme di nomi univoci. La regola ? che se un nome si ripete
	 * deve avere lo stesso tipo.
	 * 
	 */
	public function getAttributeList($with_types = false) {
		try {
			return $this->check2($this->format, false, $with_types ? self::OP_DETAIL_LIST : self::OP_NAME_LIST);
		} catch (Exception $e) {
			;//echo "ECCEZIONE";
			$a = array(	'p1' => 'Manca una parentesi chiusa',
					'p2' => 'C\'? una parentesi chiusa di troppo',
					'f1' => 'Errore nello specificare un argomento facoltativo',
					'a1' => 'Manca il "type", es.: "argument=string" anzich� semplicemente "argument"',
					'a2' => 'Type "%s" non riconoscito o errato',
					'e1' => 'Uno o pi? attributi non hanno il valore corretto oppure sono assenti, il formato previsto ?: "' . $this->format . '" (2)',
					'e3' => 'Gli argomenti con lo stesso nome devono essere dello stesso tipo [ND: overload part mancante]: '. $this->format,
					'e4' => 'Unknown type in format: ' . $this->format);
			throw new Exception(sprintf($a[substr($e->getMessage(),0,2)], substr($e->getMessage(), 2)));
		}
	}

	// [facoltativo]  {attrname && $f0}
	// [{hash-field=STRING|descriptor-item=STRING|sql-record=STRING} as=STRING]
    public function check2($format, $strict = false, $op_type = self::OP_CHECK) {
		//echo "--A--";
		$format = trim(str_replace('  ', ' ', $format));
		//echo "FORMAT <tt>'$format'</tt>";
		if (strlen($format) > 0) {
			if ($format[0] == '{') $format = '('.substr($format, 1);
			if ($format[strlen($format)-1] == '}') $format = substr($format, 0, -1).')';
		}
		//echo "--B--";
		$trans = array(' |' => '|', '| '=> '|', '|'=>'||', '||{'=>'||(', '}||'=>')||', '{'=>'&&(', '}'=>')&&', ' '=>'&&'); //')&&)'=>'))', '(&&('=>'((', 
		$format = str_replace($k = array_keys($trans), array_values($trans), $format);
		//echo "======= format=<tt>$format</tt></br>";
		$format = str_replace(array('(&&', '&&)'), array('(', ')'), $format);
		$ob = substr_count($format, '('); $cb = substr_count($format, ')');
		if ($ob > $cb) throw new Exception("p1");
		if ($ob < $cb) throw new Exception("p2");
		//if ($strict) $al = GET('TAG.ATTR_LIST');
		$list = array();
//		echo "op_type = $op_type";
//		echo "FORMAT = '$format'";
		$usort = preg_split('/&&|\|\||\(|\)/', $format);
		usort($usort, 'AttrFormat_OLD::attr_sort');
		foreach($usort as $a) if ($a != '') {
			list($n, $t) = $this->resolveAttr($a, $n, self::OP_DETAIL_LIST);
			if ($op_type == self::OP_CHECK || $op_type == self::OP_NO_EVAL) {
				$format = str_replace($a, $this->resolveAttr($a, $n), $format);
				//echo "********* n='$n' t='$t' bool=".(int)(isset($list[$n]) && $list[$n] != $t); 
				//var_dump($list);
				if (isset($list[$n]) && $list[$n] != $t) {
					;//echo "===eccezione==";
					throw new Exception('e3');
				}
				$list[$n] = $t; //lista univoca di nomi
			}
			else if ($op_type == self::OP_NAME_LIST)
				array_push($list, $this->resolveAttr($a, $n)); //compatibilit? con codici scritti in precedenza
			else if ($op_type == self::OP_DETAIL_LIST) {
				$list[$n] = $t; //lista univoca di nomi
			}
			else throw new Exception('e4'); //"unknown op_type: $op_type");
		}
		//var_dump($list);
		if ($strict) 
			foreach (array_keys($this->al) as $at) {
//				echo "<br>array_search($at, \$this->excludeAttrList) = " . (int)array_search($at, $this->excludeAttrList);
//				echo "<br>array_search($at, \$list) = " . (int)array_key_exists($at, $list);
				if (array_search($at, $this->excludeAttrList) === false && array_key_exists($at, $list) === false) {
//					echo "NON PREVEDO $at\n";
					throw new Exception("e2");
				}
			}
		//echo "--D--";
		$z = ($op_type == self::OP_CHECK || $op_type = self::OP_NO_EVAL) ? '(check) ' : '(get list) ';
		;//echo "Z=$z";
		if ($op_type != self::OP_CHECK && $op_type != self::OP_NO_EVAL) {
			//echo "RETURN LIST";
			//var_dump($list);
			return $list;
		}
        $this->lastList =& $list;
		//echo "--E--";
		if ($format == '')
			$format = 'true';
		//echo "--- format=<tt>$format</tt><br/>";
        if ($op_type != self::OP_NO_EVAL) {
    		eval("\$result = $format;");
        }
        else {
           return $format;
        }
		////echo "<b>RESULT = '$result'</b>";
		if (!$result) throw new Exception("e1"); //user error, format OK
		//echo "--F--";
		return true;
		
		//$a = ($this->__format_hasAttr('a') && ($this->__format_getAttr('a')))&&($this->__format_hasAttr('b') && ($this->__format_getAttr('b') != null))&&||&&($this->__format_hasAttr('c') && ($this->__format_getAttr('c') != null))&&($this->__format_hasAttr('a') && ($this->__format_getAttr('a')));
	}
	
	public static function attr_sort($a, $b) {
		list($a) = explode('=', $a, 1);
		list($b) = explode('=', $b, 1);
	    return strlen($b) - strlen($a);
	}

	private function resolveAttr($a, &$n, $op_type = self::OP_CHECK) {
		if ($f = ($a[0] == '[')) {
			if (substr($a, -1) != ']') throw new Exception("f1");
			$a = substr($a, 1, -1);
		}
		@list($n, $t) = explode('=', $a); //non modificare pi? $n
		if (!isset($t)) throw new Exception("a1$n"); //manca '=TIPO'
		if ($op_type == self::OP_NAME_LIST) return $n;
		if ($op_type == self::OP_DETAIL_LIST) return array($n, $t);

		$buff = "\$this->__format_getAttr('$n')"; 
		$t = strtolower($t);
		/*
		# QUI ERA 'RUNTIME' (!)
		if ($t == 'any') $buff = "($buff !== false && $buff != null)"; //TODO accordarsi con valore di riorno GET_ATTR quando attr no n? presnete
		else if ($t == 'string') $buff = "(string)$buff != ''";
		else if ($t == 'num' || $t == 'number' || $t == 'int' || $t == 'float') $buff = "is_numeric($buff)";
		else if ($t == 'hash' || $t == 'array') $buff = "is_array($buff)";
		else if (is_numeric($t))
			$buff = "(int)$buff  == ".((int)$t);
		else if	($t[0] == '"' || $t[0] == '\'')
			$buff = "strtolower($buff) == strtolower($t)";
		else throw new Exception("a2$t"); //TIPO non riconosciuto o errato
		*/
		# ORA E' SOLO COMPILE-TIME (non conosce il valore delle variabili, 
		# che in ogni caso NON hanno tipo, per cui si basa solo sui valori 'costanti')
		if ($t == 'num') $buff = "$buff == 'num' || $buff == 'any'";
		else if ($t == 'string') $buff = "$buff != null";
		if ($f) $buff = "(!\$this->__format_hasAttr('$n') || ($buff))";
		else $buff = "(\$this->__format_hasAttr('$n') && ($buff))";
		return $buff;
	}

} //class
?>
