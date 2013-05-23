<?php
# ~system/WebMasterTools/Form-Utils.xml
require_once 'WebMasterTools.php';

class _A8system_2FWebMasterTools_2FForm_3FUtils_21xml {
  static $DS_F = array();
}
/* <part name='Include_Javascript'> */
class _A8system_2FWebMasterTools_2FForm_3FUtils_21xml_3AInclude_3DJavascript extends Piece {
  const PART_NAME = '~system/WebMasterTools/Form-Utils.xml:Include_Javascript';
  var $PART_BUILD_ID = 37;

  var $USED_SUBELEMENTS = array(0 => array(' ' => 2));

  public function main($CONTEXT, &$DS/*, Piece $__caller*/) {
	$this->DS =& $DS;
$this->DS_M0/*condition*/[0] = (isset($_REQUEST['WMT_FM.scriptLoaded'])); if (!($this->DS_M0[0])) {
	$this->node($CONTEXT, $this->DS_E = array(), '_A8system_2FBase_2FA_21xml_3Awhen_3Ffalse_do_', 0);
		};
	$this->DS_E = array(); 
  }
  public function _A8system_2FBase_2FA_21xml_3Awhen_3Ffalse_do__0($CONTEXT, &$DS_E) {

	self::$buffer[$CONTEXT] .= "<script language=\"javascript\" src=\"".ROOT_PREFIX."".'/lib/~system/WebMasterTools/'."form-utils.js\"></script>";	 $_REQUEST['WMT_FM.scriptLoaded'] = 1;

  }
}

/* <part name='TimeSelectKit'> */
class _A8system_2FWebMasterTools_2FForm_3FUtils_21xml_3ATimeSelectKit extends Piece {
  const PART_NAME = '~system/WebMasterTools/Form-Utils.xml:TimeSelectKit';
  var $PART_BUILD_ID = 38;

  var $USED_SUBELEMENTS = array(0 => array());

  public function main($CONTEXT, &$DS/*, Piece $__caller*/) {
	$this->DS =& $DS;
$h = $m = 0;
			if ((int)$this->DS/*value*/[0] > 0) {
				list($h, $m) = explode(':', $this->DS/*value*/[0]);
				list($h, $m) = array((int)$h, (int)$m);
			}$CONTEXT++; self::$buffer[$CONTEXT] = '';
	self::$buffer[$CONTEXT] .= "<select id=\"ID_".($this->client->PART_BUILD_ID.'_'.$this->index)."_hh\" name=\"".$this->DS/*name*/[1]."_hh\" onChange=\"WMT_FM_updateTime('ID_".($this->client->PART_BUILD_ID.'_'.$this->index)."')\">";for ($i=0; $i < 24; $i++) { 
						if ($h == $i) {
	self::$buffer[$CONTEXT] .= "<option selected=\"true\">";
						}
						else {
	self::$buffer[$CONTEXT] .= "<option>";
						}
						if ($i < 10) $i = '0' . $i;
	self::$buffer[$CONTEXT] .= "$i";
	self::$buffer[$CONTEXT] .= "</option>";
					}
	self::$buffer[$CONTEXT] .= "</select>";	$this->DS/*hhSelect*/[2] = ''; $this->DS/*hhSelect*/[2].= self::$buffer[$CONTEXT]; $CONTEXT--;
$CONTEXT++; self::$buffer[$CONTEXT] = '';
	self::$buffer[$CONTEXT] .= "<select id=\"ID_".($this->client->PART_BUILD_ID.'_'.$this->index)."_mm\" name=\"".$this->DS/*name*/[1]."_mm\" onChange=\"WMT_FM_updateTime('ID_".($this->client->PART_BUILD_ID.'_'.$this->index)."')\">";for ($i=0; $i < 60; $i++) { 
						if ($m == $i) {
	self::$buffer[$CONTEXT] .= "<option selected=\"true\">";
						}
						else {
	self::$buffer[$CONTEXT] .= "<option>";
						}
						if ($i < 10) $i = '0' . $i;
	self::$buffer[$CONTEXT] .= "$i";
	self::$buffer[$CONTEXT] .= "</option>";
					}
	self::$buffer[$CONTEXT] .= "</select>";	$this->DS/*mmSelect*/[3] = ''; $this->DS/*mmSelect*/[3].= self::$buffer[$CONTEXT]; $CONTEXT--;

	$DS_C = array();
	$this->P[0] = $this->get($this, 0, '_A8system_2FWebMasterTools_2FForm_3FUtils_21xml_3AInclude_3DJavascript'); $this->P[0]->main($CONTEXT, $DS_C, $this);

	self::$buffer[$CONTEXT] .= "<input id=\"ID_".($this->client->PART_BUILD_ID.'_'.$this->index)."\" type=\"hidden\" name=\"".$this->DS/*name*/[1]."\" value=\"".$this->DS/*value*/[0]."\"/>";
	$this->client->node($CONTEXT, $this->DS_E = array($this->DS/*hhSelect*/[2],$this->DS/*mmSelect*/[3]), '_A8system_2FWebMasterTools_2FForm_3FUtils_21xml_3ATimeSelectKit_do_', $this->index);;
	$this->DS_E = array(); 
  }
}

/* <part name='DateSelectKit'> */
class _A8system_2FWebMasterTools_2FForm_3FUtils_21xml_3ADateSelectKit extends Piece {
  const PART_NAME = '~system/WebMasterTools/Form-Utils.xml:DateSelectKit';
  var $PART_BUILD_ID = 40;

  var $USED_SUBELEMENTS = array(0 => array(),1 => array(),2 => array(),3 => array());

  public function main($CONTEXT, &$DS/*, Piece $__caller*/) {
	$this->DS =& $DS;
$d = $m = $y = 0;
			if ((int)$this->DS/*value*/[4] > 0) {
				list($y, $m, $d) = explode('-', $this->DS/*value*/[4]);
				list($y, $m, $d) = array((int)$y, (int)$m, (int)$d);
			}$CONTEXT++; self::$buffer[$CONTEXT] = '';
	self::$buffer[$CONTEXT] .= "<select id=\"ID_".($this->client->PART_BUILD_ID.'_'.$this->index)."_dd\" name=\"".$this->DS/*name*/[5]."_dd\" onChange=\"WMT_FM_updateDate('ID_".($this->client->PART_BUILD_ID.'_'.$this->index)."')\">";$this->DS_M0/*current-value*/[0] = $d; $this->DS_M0/*only-when*/[1] = $this->DS/*use-empty*/[0]; $this->DS_M0/*label*/[2] = "--"; $this->DS_M0/*value*/[3] = ""; if ($this->DS_M0/*current-value*/[0] == $this->DS_M0/*value*/[3])
	self::$buffer[$CONTEXT] .= "<option value=\"".$this->DS_M0/*value*/[3]."\" selected=\"true\">";
			else
	self::$buffer[$CONTEXT] .= "<option value=\"".$this->DS_M0/*value*/[3]."\">";
	self::$buffer[$CONTEXT] .= $this->DS_M0/*label*/[2];
	self::$buffer[$CONTEXT] .= "</option>";for ($i=1; $i < 32; $i++) {
						if ($d == $i) {
	self::$buffer[$CONTEXT] .= "<option selected=\"true\">";
						}
						else {
	self::$buffer[$CONTEXT] .= "<option>";
						}
						if ($i < 10) $i = '0' . $i;
	self::$buffer[$CONTEXT] .= "$i";
	self::$buffer[$CONTEXT] .= "</option>";
					}
	self::$buffer[$CONTEXT] .= "</select>";	$this->DS/*ddSelect*/[6] = ''; $this->DS/*ddSelect*/[6].= self::$buffer[$CONTEXT]; $CONTEXT--;
$CONTEXT++; self::$buffer[$CONTEXT] = '';
	self::$buffer[$CONTEXT] .= "<select id=\"ID_".($this->client->PART_BUILD_ID.'_'.$this->index)."_mm\" name=\"".$this->DS/*name*/[5]."_mm\" onChange=\"WMT_FM_updateDate('ID_".($this->client->PART_BUILD_ID.'_'.$this->index)."')\">";$this->DS_M1/*current-value*/[0] = $m; $this->DS_M1/*only-when*/[1] = $this->DS/*use-empty*/[0]; $this->DS_M1/*label*/[2] = "--"; $this->DS_M1/*value*/[3] = ""; if ($this->DS_M1/*current-value*/[0] == $this->DS_M1/*value*/[3])
	self::$buffer[$CONTEXT] .= "<option value=\"".$this->DS_M1/*value*/[3]."\" selected=\"true\">";
			else
	self::$buffer[$CONTEXT] .= "<option value=\"".$this->DS_M1/*value*/[3]."\">";
	self::$buffer[$CONTEXT] .= $this->DS_M1/*label*/[2];
	self::$buffer[$CONTEXT] .= "</option>";for ($i=1; $i < 13; $i++) { 
						if ($m == $i) {
	self::$buffer[$CONTEXT] .= "<option selected=\"true\">";
						}
						else {
	self::$buffer[$CONTEXT] .= "<option>";
						}
						if ($i < 10) $i = '0' . $i;
	self::$buffer[$CONTEXT] .= "$i";
	self::$buffer[$CONTEXT] .= "</option>";
					}
	self::$buffer[$CONTEXT] .= "</select>";	$this->DS/*mmSelect*/[7] = ''; $this->DS/*mmSelect*/[7].= self::$buffer[$CONTEXT]; $CONTEXT--;
$CONTEXT++; self::$buffer[$CONTEXT] = '';
	self::$buffer[$CONTEXT] .= "<select id=\"ID_".($this->client->PART_BUILD_ID.'_'.$this->index)."_yy\" name=\"".$this->DS/*name*/[5]."_yy\" onChange=\"WMT_FM_updateDate('ID_".($this->client->PART_BUILD_ID.'_'.$this->index)."')\">";$this->DS_M2/*current-value*/[0] = $y; $this->DS_M2/*only-when*/[1] = $this->DS/*use-empty*/[0]; $this->DS_M2/*label*/[2] = "----"; $this->DS_M2/*value*/[3] = ""; if ($this->DS_M2/*current-value*/[0] == $this->DS_M2/*value*/[3])
	self::$buffer[$CONTEXT] .= "<option value=\"".$this->DS_M2/*value*/[3]."\" selected=\"true\">";
			else
	self::$buffer[$CONTEXT] .= "<option value=\"".$this->DS_M2/*value*/[3]."\">";
	self::$buffer[$CONTEXT] .= $this->DS_M2/*label*/[2];
	self::$buffer[$CONTEXT] .= "</option>";$y_min = $this->DS/*year-min*/[2];
					$y_max = $this->DS/*year-max*/[1];
					if (!is_null($this->DS[3]))
						$order = strtolower(trim($this->DS/*order*/[3]));
					if (!isset($order) || ($order != 'asc' && $order != 'desc')) {
						if ($y_min == date('Y'))
							$order = 'asc';
						else 
							$order = 'desc';
					}
					if ($order == 'desc')
					 for ($i= $y_max; $i > $y_min; $i--) { 
						if ($y == $i) {
	self::$buffer[$CONTEXT] .= "<option selected=\"true\">";
						}
						else {
	self::$buffer[$CONTEXT] .= "<option>";
						}
	self::$buffer[$CONTEXT] .= "$i";
	self::$buffer[$CONTEXT] .= "</option>";
					 }
					else 
					 for ($i= $y_min; $i < $y_max + 1; $i++) { 
						if ($y == $i) {
	self::$buffer[$CONTEXT] .= "<option selected=\"true\">";
						}
						else {
	self::$buffer[$CONTEXT] .= "<option>";
						}
	self::$buffer[$CONTEXT] .= "$i";
	self::$buffer[$CONTEXT] .= "</option>";
					 }
	self::$buffer[$CONTEXT] .= "</select>";	$this->DS/*yySelect*/[8] = ''; $this->DS/*yySelect*/[8].= self::$buffer[$CONTEXT]; $CONTEXT--;

	$DS_C = array();
	$this->P[3] = $this->get($this, 3, '_A8system_2FWebMasterTools_2FForm_3FUtils_21xml_3AInclude_3DJavascript'); $this->P[3]->main($CONTEXT, $DS_C, $this);

	self::$buffer[$CONTEXT] .= "<input id=\"ID_".($this->client->PART_BUILD_ID.'_'.$this->index)."\" type=\"hidden\" name=\"".$this->DS/*name*/[5]."\" value=\"".$this->DS/*value*/[4]."\"/>";
	$this->client->node($CONTEXT, $this->DS_E = array($this->DS/*ddSelect*/[6],$this->DS/*mmSelect*/[7],$this->DS/*yySelect*/[8]), '_A8system_2FWebMasterTools_2FForm_3FUtils_21xml_3ADateSelectKit_do_', $this->index);;
	$this->DS_E = array(); 
  }
}

/* <part name='SelectNum'> */
class _A8system_2FWebMasterTools_2FForm_3FUtils_21xml_3ASelectNum extends Piece {
  const PART_NAME = '~system/WebMasterTools/Form-Utils.xml:SelectNum';
  var $PART_BUILD_ID = 41;

  var $USED_SUBELEMENTS = array();

  public function main($CONTEXT, &$DS/*, Piece $__caller*/) {
	$this->DS =& $DS;

	self::$buffer[$CONTEXT] .= "<select name=\"".$this->DS/*name*/[4]."\">";$hide_numbers = explode(',', $this->DS/*hide-numbers*/[1]);
				foreach ($hide_numbers as $h => $n) $hide_numbers[$h] = (int)$n;
				
				$labels = array();
				if ($this->DS/*custom-labels*/[0] != '') {
					$buff = explode(',', $this->DS/*custom-labels*/[0]);
					foreach ($buff as $L) {
						list($v, $l) = explode(':', $L);
						if (!isset($l) || !is_numeric($v)) throw new Exception("SelectNum(".$this->DS/*name*/[4].": custom-labels attribute must be in this format: 'num:label, num:label, ..'. you given: '" . $this->DS/*custom-labels*/[0] . "'"); 
						$labels[(int)$v] = $l;
					}
				}
				if ($this->DS/*from*/[3] > $this->DS/*to*/[5]) {
					for ($i=$this->DS/*from*/[3]; $i > $this->DS/*to*/[5] - 1; $i--) {
						if (array_search($i, $hide_numbers) !== false) continue;
						if ((int)$this->DS/*value*/[2] == $i) {
	self::$buffer[$CONTEXT] .= "<option value=\"$i\" selected=\"true\">";
						} else {
	self::$buffer[$CONTEXT] .= "<option value=\"$i\">";
						}
						$v = (isset($labels[$i])) ? $labels[$i] : $i;
	self::$buffer[$CONTEXT] .= "$v";
	self::$buffer[$CONTEXT] .= "</option>";
					}
				}
				else {
					for ($i=$this->DS/*from*/[3]; $i < $this->DS/*to*/[5] + 1; $i++) { 
						if (array_search($i, $hide_numbers) !== false) continue;
						if ((int)$this->DS/*value*/[2] == $i) {
	self::$buffer[$CONTEXT] .= "<option value=\"$i\" selected=\"true\">";
						} else {
	self::$buffer[$CONTEXT] .= "<option value=\"$i\">";
						} 
						$v = (isset($labels[$i])) ? $labels[$i] : $i;
	self::$buffer[$CONTEXT] .= "$v";
	self::$buffer[$CONTEXT] .= "</option>";
					}
				}
	self::$buffer[$CONTEXT] .= "</select>";;
	$this->DS_E = array(); 
  }
}

/* <part name='SelectList'> */
class _A8system_2FWebMasterTools_2FForm_3FUtils_21xml_3ASelectList extends Piece {
  const PART_NAME = '~system/WebMasterTools/Form-Utils.xml:SelectList';
  var $PART_BUILD_ID = 43;

  var $USED_SUBELEMENTS = array();

  public function main($CONTEXT, &$DS/*, Piece $__caller*/) {
	$this->DS =& $DS;

	self::$buffer[$CONTEXT] .= "<select name=\"".$this->DS/*name*/[4]."\">";if (!$this->DS/*list-separator*/[0])	 $this->DS/*list-separator*/[0] = ",";
			$index_by = null;
			if (!is_null($this->DS[1])) $index_by = trim(strtolower($this->DS/*index-by*/[1]));
			if (!is_null($this->DS[0])) $sep = $this->DS/*list-separator*/[0];
			if (!$sep) $sep = ',';
			$values = explode($sep, $this->DS/*list-values*/[2]);
			$i = 0;
			foreach ($values as $v) {
				$selected = '';
				if ($index_by == 'number') {
					if ($i == (int)$this->DS/*value*/[3])
						$selected = 'selected="true"';
	self::$buffer[$CONTEXT] .= "<option value='$i' $selected>";
	self::$buffer[$CONTEXT] .= htmlentities($v);
	self::$buffer[$CONTEXT] .= "</option>";
				}
				else if ($index_by == 'colon') {
					@list($value, $label) = explode(':', $v);
					$value = trim($value);
					if ($value == $this->DS/*value*/[3])
						$selected = 'selected="true"';
					if (!isset($label)) { $label = $value; $value = ''; } else { $value = 'value="'.htmlentities($value).'"'; }
	self::$buffer[$CONTEXT] .= "<option $value $selected>";
	self::$buffer[$CONTEXT] .= htmlentities($label);
	self::$buffer[$CONTEXT] .= "</option>";
				}
				else {
					if ($this->DS/*value*/[3] == trim($v)) 
						$selected = 'selected="true"';
	self::$buffer[$CONTEXT] .= "<option $selected>";
	self::$buffer[$CONTEXT] .= htmlentities($v);
	self::$buffer[$CONTEXT] .= "</option>";
				}
			}
	self::$buffer[$CONTEXT] .= "</select>";;
	$this->DS_E = array(); 
  }
}

/* <part name='dude-content-updater'> */
class _A8system_2FWebMasterTools_2FForm_3FUtils_21xml_3Adude_3Fcontent_3Fupdater extends Piece {
  const PART_NAME = '~system/WebMasterTools/Form-Utils.xml:dude-content-updater';
  var $PART_BUILD_ID = 67;

  var $USED_SUBELEMENTS = array();

  public function main($CONTEXT, &$DS/*, Piece $__caller*/) {
	$this->DS =& $DS;

	$this->DS_E = array(); 
  }
}

if (!defined('AUTO_UPDATE_CACHE')) {
 Piece::$AUTO_UPDATE['~system/WebMasterTools/Form-Utils.xml'] = -1346228256;
 Piece::$FILES['/~system/WebMasterTools/form-utils.js'] = array(true, false);
 Piece::$FILES['/~system/WebMasterTools/WebMasterTools.php'] = array(true, false);
}
?>