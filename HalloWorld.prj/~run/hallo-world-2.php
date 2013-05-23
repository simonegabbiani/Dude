<?php
# hallo-world-2.xml

class hallo_3Fworld_3F2_21xml {
  static $DS_F = array();
}
/* <part name='My-HTML-Document'> */
class hallo_3Fworld_3F2_21xml_3AMy_3FHTML_3FDocument extends Piece {
  const PART_NAME = 'hallo-world-2.xml:My-HTML-Document';
  var $PART_BUILD_ID = 57;

  var $USED_SUBELEMENTS = array(0 => array());

  public function main($CONTEXT, &$DS/*, Piece $__caller*/) {
	$this->DS =& $DS;

	self::$buffer[$CONTEXT] .= "<html lang=\"it\"><head><title>".$this->DS/*title*/[0]."</title><script language=\"javascript\" src=\"".ROOT_PREFIX."JS/jquery.min.js\"></script><style type=\"text/css\">h1, p {\n\t\t\t\t\t\tpadding:10px;";$this->DS_M0/*weight*/[0] = null; $this->DS_M0/*size*/[1] = null; $this->DS_M0/*y*/[2] = null; $this->DS_M0/*x*/[3] = null; if ($this->DS_M0/*x*/[3] == null)	 $this->DS_M0/*x*/[3] = "5px";
		if ($this->DS_M0/*y*/[2] == null)	 $this->DS_M0/*y*/[2] = "5px";
		if ($this->DS_M0/*size*/[1] == null)	 $this->DS_M0/*size*/[1] = "10px";
		if ($this->DS_M0/*weight*/[0] == null)	 $this->DS_M0/*weight*/[0] = 75;
		$w = (int)(255 * (1 - $this->DS_M0/*weight*/[0] / 100)); 
		$buffer = 
			"box-shadow:".$this->DS_M0/*x*/[3]." ".$this->DS_M0/*y*/[2]." ".$this->DS_M0/*size*/[1]." rgb($w, $w, $w);"
			. "-moz-box-shadow: ".$this->DS_M0/*x*/[3]." ".$this->DS_M0/*y*/[2]." ".$this->DS_M0/*size*/[1]." rgb($w, $w, $w);"
			. "-webkit-box-shadow: ".$this->DS_M0/*x*/[3]." ".$this->DS_M0/*y*/[2]." ".$this->DS_M0/*size*/[1]." rgb($w, $w, $w);"
			. "-o-box-shadow: ".$this->DS_M0/*x*/[3]." ".$this->DS_M0/*y*/[2]." ".$this->DS_M0/*size*/[1]." rgb($w, $w, $w);";
		$w = dechex((int)($w / 2));
		$buffer .=
			"filter:progid:DXImageTransform.Microsoft.dropshadow(OffX=".$this->DS_M0/*x*/[3].", OffY=".$this->DS_M0/*y*/[2].", Color='#{$w}000000');";
	self::$buffer[$CONTEXT] .= "$buffer";
	self::$buffer[$CONTEXT] .= "}</style></head><body><h1>".$this->DS/*title*/[0]."</h1>";
	$this->client->node($CONTEXT, $this->DS_E = array(), 'hallo_3Fworld_3F2_21xml_3AMy_3FHTML_3FDocument_do_PageContent', $this->index);
	self::$buffer[$CONTEXT] .= "</body></html>";;
	$this->DS_E = array(); 
  }
}

/* <part name='/index-2'> */
class hallo_3Fworld_3F2_21xml_3A_2Findex_3F2 extends Piece {
  const PART_NAME = 'hallo-world-2.xml:/index-2';
  var $PART_BUILD_ID = 58;

  var $USED_SUBELEMENTS = array(0 => array('pagecontent' => 1));

  public function main($CONTEXT, &$DS/*, Piece $__caller*/) {
	$this->DS =& $DS;
	 $this->DS/*my-title*/[0] = "Hallo World";

	$DS_C = array($this->DS/*my-title*/[0]);
	$this->P[0] = $this->get($this, 0, 'hallo_3Fworld_3F2_21xml_3AMy_3FHTML_3FDocument'); $this->P[0]->main($CONTEXT, $DS_C, $this);
;
	$this->DS_E = array(); 
  }
  public function hallo_3Fworld_3F2_21xml_3AMy_3FHTML_3FDocument_do_PageContent_0($CONTEXT, &$DS_E) {

	self::$buffer[$CONTEXT] .= "<p>This is the content of the page</p>";
  }
}

/* <part name='dude-content-updater'> */
class hallo_3Fworld_3F2_21xml_3Adude_3Fcontent_3Fupdater extends Piece {
  const PART_NAME = 'hallo-world-2.xml:dude-content-updater';
  var $PART_BUILD_ID = 73;

  var $USED_SUBELEMENTS = array();

  public function main($CONTEXT, &$DS/*, Piece $__caller*/) {
	$this->DS =& $DS;

	$this->DS_E = array(); 
  }
}

if (!defined('AUTO_UPDATE_CACHE')) {
 Piece::$AUTO_UPDATE['hallo-world-2.xml'] = 1369306240;
 Piece::$FILES['/JS/jquery.min.js'] = array(false, false);
}
?>