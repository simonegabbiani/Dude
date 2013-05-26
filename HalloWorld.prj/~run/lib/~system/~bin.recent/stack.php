<?php
/*
class StackGetResult {
    public function StackGetResult(StackSource $source, $ds_index) {
        $this->source = $source;
        $thi->$ds_index = $ds_index;
    }
}
*/

class IllegalCharacterInVarName extends Exception {}
class AliasSourceDataNotFound extends Exception {}
class ReadonlyDataException extends Exception {}

class StackSource {
   static $id_count = 1;
   var $dc;
   var $id;
   var $macro;
	var $macroIndex;
   var $export;//exportIndex
	var $exportIndex;
	var $file_set;
	var $d = array();	//i => [simpleName, unprocessedValue, readOnly]
	var $alias = '';
    public function StackSource(DocComponent $dc, $alias, $macro_index = null, $export_index = null, $file_set_encoded_class_name = false) {//TODO: $type=MACRO|EXPORT_DATA 
        $this->id = self::$id_count++;
		$this->dc = $dc; $this->alias = $alias; 
        if (array_search(strtolower($alias), Expr::$extern_prefixes) !== false)
            throw new IllegalAlias($alias);
		//$this->export = $export;
		$this->exportIndex = $export_index;
		$this->export = (!$export_index && $export_index !== 0) ? null : true;
		$this->macroIndex = $macro_index;
		$this->macro = (!$macro_index && $macro_index !== 0) ? null : true;
		$this->file_set = $file_set_encoded_class_name;
    }
	public function push($simpleName, $unprocessedValue, $readOnly = false) {//scope/readonly/ecc.: todo
		$this->d[] = array($simpleName, $unprocessedValue, $readOnly);
		return count($this->d) - 1;
	}
	public function get($simpleName) { //returns i
		for ($i=count($this->d)-1; $i>=0; $i--)
			if ($this->d[$i][0] == $simpleName) return $i;
		return false;
	}
	public function set($simpleName_or_Index, $unprocessedValue, $readOnly = false) { //restituisce array(ds, oldUnprocessedValue)
		if (is_int($simpleName_or_Index)) {
			$i = $simpleName_or_Index;
			$simpleName_or_Index = $this->d[$i][0];
		}
		else if (($i = $this->get($simpleName_or_Index)) === false) 
			return array($i = $this->push($simpleName_or_Index, $unprocessedValue, $readOnly), null);
		if ($this->d[$i][2]) { throw new ReadonlyDataException($simpleName_or_Index . " (stack source: {$this->dc->fullName} .export={$this->exportIndex} .macro={$this->macroIndex} )"); }
		$old = $this->d[$i][1];
		$this->d[$i][1] = $unprocessedValue;
		return array($i, $old);
	}
    public function pop($i, $nameToCompare) {
        $d = array_pop($this->d);
        if ($d[0] != $nameToCompare) die('Stack: nameToCompare failed');
        if (count($this->d) != $i) die('Stack: must use pop in the reverse order');
    }
	public function popAll() {
		$this->d = array();
	}
}
class Stack {
    var $domain = 0; 
    var $groups = array();	//num -> StackSource
	var $aliases = array(); //cache
	//lastQuitted  is a ploy becaouse the scope of alias overloading, by nome, 
	//runs as we expect in a XML/HTML docment
	var $lastQuitted = null;
	public function priorityDownLastQuitted() {
		if ($this->lastQuitted && count($this->aliases[$this->lastQuitted->alias]) > 1) {
			//if there is an alias equal to lastQuitted, destroy lastQuitted
			//it is always the upper alias
			$i = array_pop($this->aliases[$this->lastQuitted->alias]);
			unset($this->groups[$i]);
		}
	}
	public function setLastQuitted(StackSource $s) { 
		$this->lastQuitted = $s; 
	}
	public function pushSource(StackSource $s) {
		$this->aliases[$s->alias][] = count($this->groups);
		$this->groups[] = $s;
        if ($s->macro) $this->macro = $s->macro;
	}
	public function removeLastSource(DocComponent $check = null, $check_alias = null) {
		$g = array_pop($this->groups);
		if (@array_pop($this->aliases[$g->alias]) === false)
			unset($this->aliases[$g->alias]);
		if ($check && $g->dc->fullName != $check->fullName)
			throw new IllegalStateException("DocComponent: $check");
		if ($check_alias && $g->alias != $check_alias)
			throw new IllegalStateException("Alias: $check_alias");
        $this->macro = null;
	}
	public function whoIs($alias, $returnIndex) { //returns GroupStack
		for ($i=count($this->groups)-1; $i>=0; $i--)
			if ($this->groups[$i]->alias == $alias) return $returnIndex ? $i : $this->groups[$i];
		throw new UnknownAliasException($alias);
	}
    public function get(StackSource $domain, $alias, $simpleName, $forSet = false) { //returns array(GroupStack, i)
//      if ($alias == 'U' && $simpleName == 'Row') {
//			echo 'found';
//		}
		for ($i=count($this->groups)-1; $i>=0; $i--) {
			//if ($this->groups[$i]->alias == $alias && (!$forSet || $this->groups[$i]->macro))
            if ($forSet && $domain->macro) {
                //this is a assign request inside a macro, so it isolate for the set anything domain that is not is the macro itself
                if ($this->groups[$i]->id != $domain->id) continue;
            }
            if ((!$alias && $this->groups[$i]->id == $domain->id) || $this->groups[$i]->alias == $alias)
                if (($x = $this->groups[$i]->get($simpleName)) !== false) 
                    return array /*new StackGetResult*/($this->groups[$i], $x);
        }
		return array/*new StackGetResult*/(null, false); //error
	}
	public function set(StackSource $domain, $alias, $simpleName, $value, $readOnly = false) {//readOnly is only for variables on initialization time
		//if (preg_match('/[0..9a..zA..Z\._]/', $simpleName))//otherwise the parser does not work
		//	throw new IllegalCharacterInVarName($simpleName);
		//With the support to "multidot", something could change here, because the first token could not be always an alias 
		if (strpos($simpleName, '.') !== false)
			throw new IllegalCharacterInVarName($simpleName);
		list($g, $i) = $this->get($domain, $alias, $simpleName, true);
        if ($g == null) {
            if ($alias) throw new AliasSourceDataNotFound('cannot use alias when creating new variables: "'.$alias.'.'.$simpleName.'"');
			$g = $domain;
            $r = $g->set($simpleName, $value, $readOnly);
        }
		else if ($i !== false)
            $r = $g->set($i, $value, $readOnly);
		else
			$r = $g->set($simpleName, $value, $readOnly);
        return array($g, $r);
    }
}

?>