<?php
# ~system/Base/DX.xml
require_once 'dataexchange.php';
require_once 'dblibrary.inc.php';

class _A8system_2FBase_2FDX_21xml {
  static $DS_F = array();
}
/* <part name='dx-query'> */
class _A8system_2FBase_2FDX_21xml_3Adx_3Fquery extends Piece {
  const PART_NAME = '~system/Base/DX.xml:dx-query';
  var $PART_BUILD_ID = 21;

  var $USED_SUBELEMENTS = array();

  public function main($CONTEXT, &$DS/*, Piece $__caller*/) {
	$this->DS =& $DS;
$dx = new DBSQLToDataExchange();
			$dx->query($this->DS/*query*/[0], $this->DS/*db-conn*/[1], $this->DS/*assoc*/[2]);	 $this->DS/*dx-result*/[3] = $dx;
;
	$this->DS_E = array(&$this->DS/*dx-result*/[3]); 
  }
}

/* <part name='dude-content-updater'> */
class _A8system_2FBase_2FDX_21xml_3Adude_3Fcontent_3Fupdater extends Piece {
  const PART_NAME = '~system/Base/DX.xml:dude-content-updater';
  var $PART_BUILD_ID = 61;

  var $USED_SUBELEMENTS = array();

  public function main($CONTEXT, &$DS/*, Piece $__caller*/) {
	$this->DS =& $DS;

	$this->DS_E = array(); 
  }
}

if (!defined('AUTO_UPDATE_CACHE')) {
 Piece::$AUTO_UPDATE['~system/Base/DX.xml'] = -1344497428;
 Piece::$FILES['/~system/Base/dataexchange.php'] = array(true, false);
 Piece::$FILES['/~system/Base/dblibrary.inc.php'] = array(true, false);
}
?>