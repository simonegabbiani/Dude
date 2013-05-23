<?php
# ~system/Base/D.xml

class _A8system_2FBase_2FD_21xml {
  static $DS_F = array();
}
/* <part name='dbconn-profile-application-start'> */
class _A8system_2FBase_2FD_21xml_3Adbconn_3Fprofile_3Fapplication_3Fstart extends Piece {
  const PART_NAME = '~system/Base/D.xml:dbconn-profile-application-start';
  var $PART_BUILD_ID = 14;

  var $USED_SUBELEMENTS = array();

  //this is a handler
  const HANDLER_FOR = '~system/Base/D.xml:dbconn-profile';
  const HANDLER_RUN_ON = 'application-start';

  public function main($CONTEXT, &$DS/*, Piece $__caller*/) {
	$this->DS =& $DS;
echo "dbconnprofile:handler started on-application-start";
			var_dump($this->DS);
			if (!ApplicationData::Exists('DBConn.profiles'))
				$a = array();
			else
				$a = ApplicationData::Get('DBConn.profiles');
			if (($id = $this->DS/*x*/[3]->getAttribute('id')) == null) 
				$id = 'default';
			if (isset($a[ $id ]) && $this->DS/*c*/[1] > 1)
				throw new UserConfigurationError('DBConn: profile '.$id.' already defined. Check usages for descriptor <dbconn-profile>');
			$a[$id] = array('username' => $this->DS/*x*/[3]->getAttribute('username'), 
						'password' => $this->DS/*x*/[3]->hasAttribute('enc-password') ? base64_decode($this->DS/*x*/[3]->getAttribute('enc-password')) : $this->DS/*x*/[3]->getAttribute('password'),
						'host' => $this->DS/*x*/[3]->getAttribute('host'), 'db' => $this->DS/*x*/[3]->getAttribute('db'), 'vendor' => $this->DS/*x*/[3]->getAttribute('vendor') );	 ApplicationData::Set('DBConn.profiles', $a);;
	$this->DS_E = array(); 
  }
}

/* <part name='dbconn-profile-context-end'> */
class _A8system_2FBase_2FD_21xml_3Adbconn_3Fprofile_3Fcontext_3Fend extends Piece {
  const PART_NAME = '~system/Base/D.xml:dbconn-profile-context-end';
  var $PART_BUILD_ID = 15;

  var $USED_SUBELEMENTS = array();

  //this is a handler
  const HANDLER_FOR = '~system/Base/D.xml:dbconn-profile';
  const HANDLER_RUN_ON = 'context-end';

  public function main($CONTEXT, &$DS/*, Piece $__caller*/) {
	$this->DS =& $DS;
foreach ($GLOBALS['DBConn.pdo'] as $c)
				DB_MYSQL::close($c);;
	$this->DS_E = array(); 
  }
}

/* <part name='dbconn-profile-session-start'> */
class _A8system_2FBase_2FD_21xml_3Adbconn_3Fprofile_3Fsession_3Fstart extends Piece {
  const PART_NAME = '~system/Base/D.xml:dbconn-profile-session-start';
  var $PART_BUILD_ID = 16;

  var $USED_SUBELEMENTS = array();

  //this is a handler
  const HANDLER_FOR = '~system/Base/D.xml:dbconn-profile';
  const HANDLER_RUN_ON = 'session-start';

  public function main($CONTEXT, &$DS/*, Piece $__caller*/) {
	$this->DS =& $DS;
	 $_SESSION['DBConn.profiles'] = ApplicationData::Get('DBConn.profiles');
;
	$this->DS_E = array(); 
  }
}

/* <part name='dude-content-updater'> */
class _A8system_2FBase_2FD_21xml_3Adude_3Fcontent_3Fupdater extends Piece {
  const PART_NAME = '~system/Base/D.xml:dude-content-updater';
  var $PART_BUILD_ID = 61;

  var $USED_SUBELEMENTS = array();

  public function main($CONTEXT, &$DS/*, Piece $__caller*/) {
	$this->DS =& $DS;

	$this->DS_E = array(); 
  }
}

Piece::$HANDLERS['application-start']['~system/Base/D.xml:dbconn-profile'] = '_A8system_2FBase_2FD_21xml_3Adbconn_3Fprofile_3Fapplication_3Fstart';
Piece::$HANDLERS['context-end']['~system/Base/D.xml:dbconn-profile'] = '_A8system_2FBase_2FD_21xml_3Adbconn_3Fprofile_3Fcontext_3Fend';
Piece::$HANDLERS['session-start']['~system/Base/D.xml:dbconn-profile'] = '_A8system_2FBase_2FD_21xml_3Adbconn_3Fprofile_3Fsession_3Fstart';
if (!defined('AUTO_UPDATE_CACHE')) {
 Piece::$AUTO_UPDATE['~system/Base/D.xml'] = -1369305116;
}
?>