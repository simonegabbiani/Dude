<?php
# hallo-world-1.xml

class hallo_3Fworld_3F1_21xml {
  static $DS_F = array();
}
/* <part name='My-HTML-Document'> */
class hallo_3Fworld_3F1_21xml_3AMy_3FHTML_3FDocument extends Piece {
  const PART_NAME = 'hallo-world-1.xml:My-HTML-Document';
  var $PART_BUILD_ID = 56;

  var $USED_SUBELEMENTS = array();

  public function main($CONTEXT, &$DS/*, Piece $__caller*/) {
	$this->DS =& $DS;

	self::$buffer[$CONTEXT] .= "<html lang=\"it\"><head><title>".$this->DS/*title*/[0]."</title><script language=\"javascript\" src=\"".ROOT_PREFIX."JS/jquery.min.js\"></script></head><body><h1>".$this->DS/*title*/[0]."</h1><hr></hr>";
	$this->client->node($CONTEXT, $this->DS_E = array(), 'hallo_3Fworld_3F1_21xml_3AMy_3FHTML_3FDocument_do_PageContent', $this->index);
	self::$buffer[$CONTEXT] .= "</body></html>";;
	$this->DS_E = array(); 
  }
}

/* <part name='/index'> */
class hallo_3Fworld_3F1_21xml_3A_2Findex extends Piece {
  const PART_NAME = 'hallo-world-1.xml:/index';
  var $PART_BUILD_ID = 57;

  var $USED_SUBELEMENTS = array(0 => array('pagecontent' => 1));

  public function main($CONTEXT, &$DS/*, Piece $__caller*/) {
	$this->DS =& $DS;
	 $this->DS/*my-title*/[0] = "Hallo World";

	$DS_C = array($this->DS/*my-title*/[0]);
	$this->P[0] = $this->get($this, 0, 'hallo_3Fworld_3F1_21xml_3AMy_3FHTML_3FDocument', 'My-HTML-Document'); $this->P[0]->main($CONTEXT, $DS_C, $this);
;
	$this->DS_E = array(); 
  }
  public function hallo_3Fworld_3F1_21xml_3AMy_3FHTML_3FDocument_do_PageContent_0($CONTEXT, &$DS_E) {

	self::$buffer[$CONTEXT] .= "<p>This is the content of the page</p>";
  }
}

/* <part name='dude-content-updater'> */
class hallo_3Fworld_3F1_21xml_3Adude_3Fcontent_3Fupdater extends Piece {
  const PART_NAME = 'hallo-world-1.xml:dude-content-updater';
  var $PART_BUILD_ID = 76;

  var $USED_SUBELEMENTS = array();

  public function main($CONTEXT, &$DS/*, Piece $__caller*/) {
	$this->DS =& $DS;

	$this->DS_E = array(); 
  }
}

if (!defined('AUTO_UPDATE_CACHE')) {
 Piece::$AUTO_UPDATE['hallo-world-1.xml'] = 1369306042;
 Piece::$FILES['/JS/jquery.min.js'] = array(false, false);
}
?>