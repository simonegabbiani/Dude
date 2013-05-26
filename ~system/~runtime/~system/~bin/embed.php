<?php
# ~system/~bin/embed.xml
require_once 'build.php';
require_once 'functions.php';
require_once 'stack.php';
require_once 'uncategorized.php';
require_once 'parse-functions.php';
require_once 'parser.php';
require_once 'expr.php';
require_once 'context.php';
require_once 'exception-tags.php';
require_once 'old-attrformat.php';
require_once 'exploder-lib.php';

class _A8system_2F_A8bin_2Fembed_21xml {
  static $DS_F = array();
}
/* <part name='Build-Context-List'> */
class _A8system_2F_A8bin_2Fembed_21xml_3ABuild_3FContext_3FList extends Piece {
  const PART_NAME = '~system/~bin/embed.xml:Build-Context-List';
  var $PART_BUILD_ID = 52;

  var $USED_SUBELEMENTS = array(0 => array());

  public function main($CONTEXT, &$DS/*, Piece $__caller*/) {
	$this->DS =& $DS;
if (!isset($_REQUEST['Dude.Build-Setup']))
				throw new Exception('Dude Embed.xml:Build-Setup missing');$keys = array_keys(GeneralIndex::$index->context);
			sort($keys);	 $this->DS/*List*/[0] = array();
;foreach ($keys as $k)
				array_push($this->DS/*List*/[0], 
					array('name' => $k, 'instance' => GeneralIndex::$index->context[$k],
							'deleted' => GeneralIndex::$index->context[$k]->tmpDeleted,
							'url' => Utils::encode(GeneralIndex::$index->context[$k]->name, true, true).'.php'));;
	$this->DS_E = array(&$this->DS/*List*/[0]); 
  }
}

/* <part name='project-list'> */
class _A8system_2F_A8bin_2Fembed_21xml_3Aproject_3Flist extends Piece {
  const PART_NAME = '~system/~bin/embed.xml:project-list';
  var $PART_BUILD_ID = 54;

  var $USED_SUBELEMENTS = array(0 => array(' ' => 2));

  public function main($CONTEXT, &$DS/*, Piece $__caller*/) {
	$this->DS =& $DS;
	 $this->DS/*value*/[1] = "";
if (!file_exists("C:/xampp/htdocs/xampp/external/0.4.7/"))
			throw new DudeEngineException_ApplicationHasMoved();
		
		$fePN__dir = new DirectoryIterator('C:/xampp/htdocs/xampp/external/0.4.7/');
		foreach ($fePN__dir as $fePN__f) {
			$fePN__n = $fePN__f->getFilename();
			if ($fePN__f->isDir() && $fePN__n != '~system' && $fePN__n != '.' && $fePN__n != '..') {	 $this->DS_M0/*projectName*/[0] = $fePN__f->getFilename();
;
	$this->node($CONTEXT, $this->DS_E = array($this->DS/*value*/[1]), '_A8system_2F_A8bin_2Fembed_21xml_3Aforeach_3FProject_3FName_do_', 0);
			}
		}	 $this->DS/*value*/[1] = (substr  (  $this->DS/*value*/[1]  , strlen($this->DS/*delimiter*/[0]    )   ));

	self::$buffer[$CONTEXT] .= $this->DS/*value*/[1];;
	$this->DS_E = array(&$this->DS/*value*/[1]); 
  }
  public function _A8system_2F_A8bin_2Fembed_21xml_3Aforeach_3FProject_3FName_do__0($CONTEXT, &$DS_E) {
	 $this->DS/*value*/[1] = ($this->DS/*value*/[1] .= $this->DS/*delimiter*/[0].'\''.addcslashes($this->DS_M0/*projectName*/[0], '\'').'\'');

  }
}

/* <part name='explode-context'> */
class _A8system_2F_A8bin_2Fembed_21xml_3Aexplode_3Fcontext extends Piece {
  const PART_NAME = '~system/~bin/embed.xml:explode-context';
  var $PART_BUILD_ID = 55;

  var $USED_SUBELEMENTS = array(0 => array());

  public function main($CONTEXT, &$DS/*, Piece $__caller*/) {
	$this->DS =& $DS;
if (!isset($_REQUEST['Dude.Build-Setup']))
				throw new Exception('Dude Embed.xml:Build-Setup missing');$x = GeneralIndex::$index->context[ $this->DS/*name*/[1] ];
		$info = array(); $text = '';
		Exploder::$keys = array(); //bleah mettere a posto
		Exploder::part('plain', $x->partStartPoint->fullName, $info, $text);	 $this->DS/*Text*/[2] = $text;
;
		foreach ($info as $u) {	 $this->DS/*SimpleName*/[3] = $u['cmp']->simpleName;
;	 $this->DS/*FileName*/[4] = $u['cmp']->df->fileName;
;	 $this->DS/*UPMData*/[5] = &$u;
;
	$this->client->node($CONTEXT, $this->DS_E = array($this->DS/*UPMData*/[5],$this->DS/*Text*/[2],$this->DS/*SimpleName*/[3],$this->DS/*FileName*/[4]), '_A8system_2F_A8bin_2Fembed_21xml_3Aexplode_3Fcontext_do_ForEachComponent', $this->index);
		};
	$this->DS_E = array(); 
  }
}

/* <part name='dude-content-updater'> */
class _A8system_2F_A8bin_2Fembed_21xml_3Adude_3Fcontent_3Fupdater extends Piece {
  const PART_NAME = '~system/~bin/embed.xml:dude-content-updater';
  var $PART_BUILD_ID = 75;

  var $USED_SUBELEMENTS = array();

  public function main($CONTEXT, &$DS/*, Piece $__caller*/) {
	$this->DS =& $DS;

	$this->DS_E = array(); 
  }
}

if (!defined('AUTO_UPDATE_CACHE')) {
 Piece::$AUTO_UPDATE['~system/~bin/embed.xml'] = -1361447180;
 Piece::$FILES['/~system/~bin/build.php'] = array(true, false);
 Piece::$FILES['/~system/~bin/functions.php'] = array(true, false);
 Piece::$FILES['/~system/~bin/stack.php'] = array(true, false);
 Piece::$FILES['/~system/~bin/uncategorized.php'] = array(true, false);
 Piece::$FILES['/~system/~bin/parse-functions.php'] = array(true, false);
 Piece::$FILES['/~system/~bin/parser.php'] = array(true, false);
 Piece::$FILES['/~system/~bin/expr.php'] = array(true, false);
 Piece::$FILES['/~system/~bin/context.php'] = array(true, false);
 Piece::$FILES['/~system/~bin/exception-tags.php'] = array(true, false);
 Piece::$FILES['/~system/~bin/old-attrformat.php'] = array(true, false);
 Piece::$FILES['/~system/~bin/exploder-lib.php'] = array(true, false);
}
?>