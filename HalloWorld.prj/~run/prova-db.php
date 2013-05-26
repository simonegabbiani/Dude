<?php
# prova-db.xml

class prova_3Fdb_21xml {
  static $DS_F = array();
}
/* <part name='/prova-db'> */
class prova_3Fdb_21xml_3A_2Fprova_3Fdb extends Piece {
  const PART_NAME = 'prova-db.xml:/prova-db';
  var $PART_BUILD_ID = 60;

  var $USED_SUBELEMENTS = array(0 => array('onrecordfound' => 2,'onerror' => 1,'onempty' => 1));

  public function main($CONTEXT, &$DS/*, Piece $__caller*/) {
	$this->DS =& $DS;
$this->DS_M0/*no-results*/[0] = null; $this->DS_M0/*dbconn*/[1] = null; $this->DS_M0/*query*/[2] = "select * from user"; $this->DS_M0/*assoc*/[3] = null; $this->DS_M0/*debug*/[4] = null; 	 $this->DS_M0/*Row*/[5] = "null";
;	 $this->DS_M0/*Count*/[6] = 0;
;
		if (($id =$this->DS_M0[1]) == null)
			$id = 'default';
		dudeBaseDxml_dbconnOpen( $id, ApplicationData::Get('DBConn.profiles') );
		$query = '';
		if (!is_null($this->DS_M0/*query*/[2]))
			$query =$this->DS_M0[2];
		if (!$query &&(isset($this->USED_SUBELEMENTS[0]['query'])))
			$query =self::get_subelements_content($CONTEXT, $this, $this->DS_E = array(), '_A8system_2FBase_2FD_21xml_3Aforeach_3Frecordset_do_query', 0);
		if (false)
	self::$buffer[$CONTEXT] .= '<tt>QUERY: '.htmlspecialchars($query).'<tt>';
		try {
			$pdo = $GLOBALS['DBConn.pdo_'.$id];
			$stmt = $pdo->query($query);
			if ($GLOBALS['DBConn.vendor_'.$id] == 'mysql') {
				$count = $pdo->query('SELECT FOUND_ROWS()')->fetch(PDO::FETCH_NUM);	 $this->DS_M0/*Affected_Rows*/[7] = (int)$count[0];
;
			}
			else {	 $this->DS_M0/*Affected_Rows*/[7] = $stmt->fetchRow();
;
			}
	$this->node($CONTEXT, $this->DS_E = array(), '_A8system_2FBase_2FD_21xml_3Aforeach_3Frecordset_do_OnQuery', 0);
			if ($this->DS_M0/*Affected_Rows*/[7]) {
				if (!$this->DS_M0[0]) {
					$i = 0;
					while ($row = $stmt->fetch()) {	 $this->DS_M0/*Count*/[6] = $i;	 $this->DS_M0/*Row*/[5] = $row;
	$this->node($CONTEXT, $this->DS_E = array(), '_A8system_2FBase_2FD_21xml_3Aforeach_3Frecordset_do_OnRecordFound', 0);
						$i++;
					}
				}
			}
			else {
	$this->node($CONTEXT, $this->DS_E = array(), '_A8system_2FBase_2FD_21xml_3Aforeach_3Frecordset_do_OnEmpty', 0);
			}
		}
		catch (Exception $e) {	 $this->DS_M0/*Exception*/[8] = $e;
	$this->node($CONTEXT, $this->DS_E = array(), '_A8system_2FBase_2FD_21xml_3Aforeach_3Frecordset_do_OnError', 0);
		};
	$this->DS_E = array(); 
  }
  public function _A8system_2FBase_2FD_21xml_3Aforeach_3Frecordset_do_OnRecordFound_0($CONTEXT, &$DS_E) {

	self::$buffer[$CONTEXT] .= "<li>";
	self::$buffer[$CONTEXT] .= ($this->DS_M0/*Row*/[5]['User']);

	self::$buffer[$CONTEXT] .= "</li>";
  }
  public function _A8system_2FBase_2FD_21xml_3Aforeach_3Frecordset_do_OnError_0($CONTEXT, &$DS_E) {

	self::$buffer[$CONTEXT] .= "errore";
  }
  public function _A8system_2FBase_2FD_21xml_3Aforeach_3Frecordset_do_OnEmpty_0($CONTEXT, &$DS_E) {

	self::$buffer[$CONTEXT] .= "empty";
  }
}

/* <part name='dude-content-updater'> */
class prova_3Fdb_21xml_3Adude_3Fcontent_3Fupdater extends Piece {
  const PART_NAME = 'prova-db.xml:dude-content-updater';
  var $PART_BUILD_ID = 78;

  var $USED_SUBELEMENTS = array(0 => array());

  public function main($CONTEXT, &$DS/*, Piece $__caller*/) {
	$this->DS =& $DS;
$this->DS_M0/*enc-password*/[0] = null; $this->DS_M0/*username*/[1] = "root"; $this->DS_M0/*password*/[2] = ""; $this->DS_M0/*vendor*/[3] = "mysql"; $this->DS_M0/*host*/[4] = "127.0.0.1"; $this->DS_M0/*id*/[5] = null; $this->DS_M0/*db*/[6] = "mysql"; 
	self::$buffer[$CONTEXT] .= "<dbconn-profile vendor=\"mysql\" host=\"127.0.0.1\" db=\"mysql\" password=\"\" username=\"root\">";
	$this->node($CONTEXT, $this->DS_E = array(), '_A8system_2FBase_2FD_21xml_3Adbconn_3Fprofile_do_', 0);
	self::$buffer[$CONTEXT] .= "</dbconn-profile>";;
	$this->DS_E = array(); 
  }
}

if (!defined('AUTO_UPDATE_CACHE')) {
 Piece::$AUTO_UPDATE['prova-db.xml'] = 1369478425;
}
?>