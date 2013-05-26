<?php
# prova-upload.xml

class prova_3Fupload_21xml {
  static $DS_F = array();
}
/* <part name='HTML-Document'> */
class prova_3Fupload_21xml_3AHTML_3FDocument extends Piece {
  const PART_NAME = 'prova-upload.xml:HTML-Document';
  var $PART_BUILD_ID = 164;

  var $USED_SUBELEMENTS = array(0 => array());

  public function main($CONTEXT, &$DS/*, Piece $__caller*/) {
	$this->DS =& $DS;

	self::$buffer[$CONTEXT] .= "<html lang=\"it\"><head><title>".$this->DS/*title*/[0]."</title><script language=\"javascript\" src=\"".ROOT_PREFIX."JS/jquery.min.js\"></script>";
	$DS_C = array();
	$this->P[0] = $this->get($this, 0, '_A8system_2FWebMasterTools_2Fajax_3Ffile_3Fupload_21xml_3Aajax_3Fupload_3Fhead', 'ajax-upload-head'); $this->P[0]->main($CONTEXT, $DS_C, $this);

	self::$buffer[$CONTEXT] .= "<style type=\"text/css\">h1, p {\n\t\t\t\t\t\tpadding:10px;\n\t\t\t\t\t}</style></head><body><h1>".$this->DS/*title*/[0]."</h1>";
	$this->client->node($CONTEXT, $this->DS_E = array(), 'prova_3Fupload_21xml_3AHTML_3FDocument_do_PageContent', $this->index);
	self::$buffer[$CONTEXT] .= "</body></html>";;
	$this->DS_E = array(); 
  }
}

/* <part name='/test-upload'> */
class prova_3Fupload_21xml_3A_2Ftest_3Fupload extends Piece {
  const PART_NAME = 'prova-upload.xml:/test-upload';
  var $PART_BUILD_ID = 165;

  var $USED_SUBELEMENTS = array(0 => array('pagecontent' => 2),1 => array(),2 => array());

  public function main($CONTEXT, &$DS/*, Piece $__caller*/) {
	$this->DS =& $DS;

	$DS_C = array("File upload test");
	$this->P[0] = $this->get($this, 0, 'prova_3Fupload_21xml_3AHTML_3FDocument', 'HTML-Document'); $this->P[0]->main($CONTEXT, $DS_C, $this);
;
	$this->DS_E = array(); 
  }
  public function prova_3Fupload_21xml_3AHTML_3FDocument_do_PageContent_0($CONTEXT, &$DS_E) {
$this->DS_M1/*progressbar*/[0] = "myProgressBar"; 
	self::$buffer[$CONTEXT] .= "<span class=\"dude_WMT_ajaxFileUpload_Button\">";
	self::$buffer[$CONTEXT] .= "<input tag=\"input\" data-progressbar-id=\"".$this->DS_M1/*progressbar*/[0]."\" type=\"file\" name=\"file\" accept=\"image/jpg, image/jpeg, image/gif, image/png\"/>";
	self::$buffer[$CONTEXT] .= "<input tag=\"input\" type=\"button\" value=\"Confirm\"/>";
	self::$buffer[$CONTEXT] .= "</span>";$this->DS_M2/*id*/[0] = "myProgressBar"; 
	self::$buffer[$CONTEXT] .= "<div id=\"dude_WMT_ajaxFileUpload_ProgressBar_".$this->DS_M2/*id*/[0]."\" style=\"background-color:yellow; border:1px solid orange; width:0px; height:10px\">";
	self::$buffer[$CONTEXT] .= "</div>";
  }
}

/* <part name='dude-content-updater'> */
class prova_3Fupload_21xml_3Adude_3Fcontent_3Fupdater extends Piece {
  const PART_NAME = 'prova-upload.xml:dude-content-updater';
  var $PART_BUILD_ID = 167;

  var $USED_SUBELEMENTS = array();

  public function main($CONTEXT, &$DS/*, Piece $__caller*/) {
	$this->DS =& $DS;

	$this->DS_E = array(); 
  }
}

if (!defined('AUTO_UPDATE_CACHE')) {
 Piece::$AUTO_UPDATE['prova-upload.xml'] = 1369495808;
 Piece::$FILES['/JS/jquery.min.js'] = array(false, false);
}
?>