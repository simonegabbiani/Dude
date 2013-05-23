<?php
# ~system/WebMasterTools/Page-List-Utils.xml

class _A8system_2FWebMasterTools_2FPage_3FList_3FUtils_21xml {
  static $DS_F = array();
}
/* <part name='Page-List'> */
class _A8system_2FWebMasterTools_2FPage_3FList_3FUtils_21xml_3APage_3FList extends Piece {
  const PART_NAME = '~system/WebMasterTools/Page-List-Utils.xml:Page-List';
  var $PART_BUILD_ID = 44;

  var $USED_SUBELEMENTS = array();

  public function main($CONTEXT, &$DS/*, Piece $__caller*/) {
	$this->DS =& $DS;
$this->DS/*size*/[1] = min($this->DS/*size*/[1], 3);
			$first = min($this->DS/*max*/[2], max(0, $this->DS/*current*/[0] - ceil($this->DS/*size*/[1] / 2)));
			for ($i = 0; $i < $this->DS/*size*/[1] && $i < $this->DS/*max*/[2]; $i++) {	 $this->DS/*PageNumber*/[3] = $i + $first;
;if ($this->DS/*PageNumber*/[3] == $this->DS/*current*/[0])
	$this->client->node($CONTEXT, $this->DS_E = array($this->DS/*PageNumber*/[3]), '_A8system_2FWebMasterTools_2FPage_3FList_3FUtils_21xml_3APage_3FList_do_Selected', $this->index);
				else
	$this->client->node($CONTEXT, $this->DS_E = array($this->DS/*PageNumber*/[3]), '_A8system_2FWebMasterTools_2FPage_3FList_3FUtils_21xml_3APage_3FList_do_Unselected', $this->index);
			};
	$this->DS_E = array(); 
  }
}

/* <part name='dude-content-updater'> */
class _A8system_2FWebMasterTools_2FPage_3FList_3FUtils_21xml_3Adude_3Fcontent_3Fupdater extends Piece {
  const PART_NAME = '~system/WebMasterTools/Page-List-Utils.xml:dude-content-updater';
  var $PART_BUILD_ID = 68;

  var $USED_SUBELEMENTS = array();

  public function main($CONTEXT, &$DS/*, Piece $__caller*/) {
	$this->DS =& $DS;

	$this->DS_E = array(); 
  }
}

if (!defined('AUTO_UPDATE_CACHE')) {
 Piece::$AUTO_UPDATE['~system/WebMasterTools/Page-List-Utils.xml'] = -1344236404;
}
?>