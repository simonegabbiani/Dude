<?php
# ~system/Base/CRUD.xml

class _A8system_2FBase_2FCRUD_21xml {
  static $DS_F = array();
}
/* <part name='crudConfigHandler'> */
class _A8system_2FBase_2FCRUD_21xml_3AcrudConfigHandler extends Piece {
  const PART_NAME = '~system/Base/CRUD.xml:crudConfigHandler';
  var $PART_BUILD_ID = 10;

  var $USED_SUBELEMENTS = array();

  //this is a handler
  const HANDLER_FOR = '~system/Base/CRUD.xml:crudConfig';
  const HANDLER_RUN_ON = 'application-start';

  public function main($CONTEXT, &$DS/*, Piece $__caller*/) {
	$this->DS =& $DS;
echo "crudConfiguration:handler started on-application-start";
			if (!ApplicationData::Exists('CRUD.tables'))
				$tables = array();
			else
				$tables = ApplicationData::Get('CRUD.tables');
			if (($db = $this->DS/*x*/[3]->getAttribute('dbconn-profile')) == null) 
				$db = 'default';
			//tables
			$mysql = '';
			foreach ($this->DS/*x*/[3]->childNodes as $xc) {
				if (strtolower($xc->tagName) != 'table') continue;
				$t = $xc->getAttribute('name');
				if (isset($tables[ $t ]) && $this->DS/*c*/[0] > 1)
					throw new UserConfigurationError('CRUD: table '.$t.' already defined. Check usages for descriptor <crudConfig>');
				$tables[ $t ] = array();
				//columns
				foreach ($xc->childNodes as $xcc) {
					if (strtolower($xcc->tagName) != 'col') continue;
					$col = $xcc->getAttribute('name');
					$tables[ $t ][ $col ] = @array('type' => $xcc->getAttribute('type'), 'len' => $xcc->getAttribute('len'));
				}
			}	 ApplicationData::Set('CRUD.tables', $tables);	 ApplicationData::Set('CRUD.dbconnProfile', $db);
			$app = serialize(array('tables'=>$tables, 'dbconn'=>$db)); 
			$out_file = ROOT_PREFIX.'lib/'.$this->DS/*f*/[1].'__'.'_A8system_2FBase_2FCRUD_21xml'.'__custom-code.php';;
	$this->DS_E = array(); 
  }
}

/* <part name='crudShow'> */
class _A8system_2FBase_2FCRUD_21xml_3AcrudShow extends Piece {
  const PART_NAME = '~system/Base/CRUD.xml:crudShow';
  var $PART_BUILD_ID = 11;

  var $USED_SUBELEMENTS = array(0 => array('onrecordfound' => 2,'onempty' => 1,'onerror' => 2),1 => array());

  public function main($CONTEXT, &$DS/*, Piece $__caller*/) {
	$this->DS =& $DS;
$a = ApplicationData::Get('CRUD.tables');
			if (!isset($a[$this->DS/*table*/[0]]))
				throw new ConfigurationErrorException('CRUD: table '.$this->DS/*table*/[0].' not configured');$this->DS_M0/*no-results*/[0] = null; $this->DS_M0/*dbconn*/[1] = null; $this->DS_M0/*query*/[2] = "select * from ".$this->DS/*table*/[0]; $this->DS_M0/*assoc*/[3] = null; $this->DS_M0/*debug*/[4] = null; 	 $this->DS_M0/*Row*/[5] = "null";
;	 $this->DS_M0/*Count*/[6] = 0;
;
		if (($id =$this->DS_M0[1]) == null)
			$id = 'default';$this->DS_M1/*id*/[0] = $id; if (!$GLOBALS['DBConn.pdo'][$id]) {
			if (($id =$this->DS_M1[0]) == null)
				$id = 'default';
			$c = ApplicationData::Get('DBConn.profiles');
			if (!isset($c[$id]))
				throw new Exception('D.xml:dbconn-open: unknown dbconn '.$id);
			$c = $c[$id];
			if ($c['vendor'] == 'mysql')
				$GLOBALS['DBConn.pdo_'.$id] 
					= new PDO("mysql:dbname={$c['db']}; host={$c['host']}", $c['username'], $c['password']);
			else if ($c['vendor'] == 'pgsql')
				$GLOBALS['DBConn.pdo_'.$id] 
					= new PDO("pgsql:dbname={$c['db']}; host={$c['host']}; user={$c['username']}; password={$c['password']}");
			else {
				throw new Exception('D.xml:dbconn-open: don\'t know how create "'.$c['vendor'].'" connection');
			}
			$GLOBALS['DBConn.vendor_'.$id] = $c['vendor']; //simply for cache
		}/* "officially" illegal */
		$query = '';
		if (!is_null($this->DS[]))
			$query =$this->DS[];
		if (!$query &&(isset($this->client->USED_SUBELEMENTS[$this->index]['query'])))
			$query =self::get_subelements_content($CONTEXT, $this->client, $this->DS_E = array(), '_A8system_2FBase_2FCRUD_21xml_3AcrudShow_do_query', $this->index);
		if (!is_null($this->DS[]))
	self::$buffer[$CONTEXT] .= '<tt>QUERY: '.htmlspecialchars($query).'<tt>';
		try {
			$pdo = $GLOBALS['DBConn.pdo_'.$id];
			$stmt = $pdo->query($query, DB_MYSQL::RETNORMAL);
			if ($GLOBALS['DBConn.vendor_'.$id] == 'mysql') {
				$count = $pdo->query('SELECT FOUND_ROWS()')->fetch(PDO::FETCTH_NUM);	 $this->DS/*Affected_Rows*/[1] = (int)$count[0];
;
			}
			else {	 $this->DS/*Affected_Rows*/[1] = $stmt->fetchRow();
;
			}
	$this->client->node($CONTEXT, $this->DS_E = array(), '_A8system_2FBase_2FCRUD_21xml_3AcrudShow_do_OnQuery', $this->index);
			if ($this->DS/*Affected_Rows*/[1]) {
				if (!$this->DS[]) {
					$i = 0;
					while ($row = $stmt->fetch()) {
						$__foreachRecordsetData =& $row;	 $this->DS/*Count*/[2] = $i;	 $this->DS/*Row*/[3] = $row;
	$this->client->node($CONTEXT, $this->DS_E = array(), '_A8system_2FBase_2FCRUD_21xml_3AcrudShow_do_OnRecordFound', $this->index);
						$i++;
					}
				}
			}
			else {
	$this->client->node($CONTEXT, $this->DS_E = array(), '_A8system_2FBase_2FCRUD_21xml_3AcrudShow_do_OnEmpty', $this->index);
			}
		}
		catch (Exception $e) {	 $this->DS/*Exception*/[4] = $e;
	$this->client->node($CONTEXT, $this->DS_E = array(), '_A8system_2FBase_2FCRUD_21xml_3AcrudShow_do_OnError', $this->index);
		};
	$this->DS_E = array(); 
  }
  public function _A8system_2FBase_2FD_21xml_3Aforeach_3Frecordset_do_OnRecordFound_0($CONTEXT, &$DS_E) {

	self::$buffer[$CONTEXT] .= "<li>";
	self::$buffer[$CONTEXT] .= ($this->DS_M0/*Row*/[5]['name']);

	self::$buffer[$CONTEXT] .= "</li>";
  }
  public function _A8system_2FBase_2FD_21xml_3Aforeach_3Frecordset_do_OnEmpty_0($CONTEXT, &$DS_E) {

	self::$buffer[$CONTEXT] .= "vuoto";
  }
  public function _A8system_2FBase_2FD_21xml_3Aforeach_3Frecordset_do_OnError_0($CONTEXT, &$DS_E) {

	self::$buffer[$CONTEXT] .= "mysql error:";
	self::$buffer[$CONTEXT] .= mysql_error();

  }
}

/* <part name='dude-content-updater'> */
class _A8system_2FBase_2FCRUD_21xml_3Adude_3Fcontent_3Fupdater extends Piece {
  const PART_NAME = '~system/Base/CRUD.xml:dude-content-updater';
  var $PART_BUILD_ID = 62;

  var $USED_SUBELEMENTS = array();

  public function main($CONTEXT, &$DS/*, Piece $__caller*/) {
	$this->DS =& $DS;

	$this->DS_E = array(); 
  }
}

Piece::$HANDLERS['application-start']['~system/Base/CRUD.xml:crudConfig'] = '_A8system_2FBase_2FCRUD_21xml_3AcrudConfigHandler';
if (!defined('AUTO_UPDATE_CACHE')) {
 Piece::$AUTO_UPDATE['~system/Base/CRUD.xml'] = -1369305174;
}
?>