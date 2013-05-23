<?php

	# Runtime codes for implementation of Dude system

	# TODO: remove the EVAL codes
	

	$GLOBALS['AUTO_UPDATE'] = array();

    $__CWD = dirname(__FILE__);
	
	function WARNING($msg) { echo $_SERVER['SCRIPT_NAME'] . ": $msg\n"; }
	
	class DUDE_SHUT_DOWN_CONTEXT extends Exception {}
    
	abstract class Piece {
		
		static private $execution_status = 0;
		static private $execution_on_terminate;

		static protected $buffer = array('');
		
		static public $HANDLERS = array();
		static public $AUTO_UPDATE = array();
		static public $FILES = array();
		static public $C_FILES = array();

      protected $parts;
		protected $index;
		protected $client;
		protected $DS;
		protected $no_bufferize = false;
		//protected $__caller_buffer = array();
		//protected $caller = null;
		
		static protected function encode($s) {
			return htmlentities($s);
		}

		static protected function decode($s) {
			return html_entity_decode($s);
		}
		
		/* Temporarily suppressed because I need subframe environment page
		public static function phpErrorHandler($errno, $errstr, $errfile, $errline) {
			if (error_reporting() === 0) return;
			@list($tok1, $tok2) = explode('()', $errstr, 2);
			echo "<li style='color:#999'>$errno - $errstr - $errfile - $errline";
		}*/

		static protected function reprocess_url($newparams) {
			global $DEF_ARGS;
			$a = ''; $e = '';
			foreach ($DEF_ARGS as $k => $v) {
				if (isset($newparams[$k])) $v = $newparams[$k]; 
				else if (isset($_REQUEST[$k])) $v = $_REQUEST[$k];
				$a .= $e.urlencode($k).'='.urlencode($v); $e='&';
			}
			return $_SERVER['SCRIPT_NAME'].'?'.$a;
		}
		
        public function get($client, $index, $partName) {
            if (isset($this->parts[$index]))
                return $this->parts[$index];
            return $this->parts[$index] = new $partName($client, $index);
        }
		
		public static function getExecutionStatus() {
			return self::$execution_status;
		}
		
		public static function throwLoadError($err_class, $err_code, $subject) {
			self::$execution_status = -1; //interrompere tutto
			self::$execution_on_terminate = array('err' => $err_class, $err_code, $subject);
		}
		
		public static function executeHandlers(&$map, &$HANDLERS, $requestedRunOn) {
			global $X_TOUCH; 
			if (self::$execution_status) return;
			$hhh = self::$HANDLERS;
			$instances = array();
			foreach ($map as $encodedFileName => $data)
				foreach ($data as $dFullName => $dSimpleName) {
					if (self::$execution_status) break;
					//list($decodedFileName, $dSimpleName) = $data;
					if (isset($HANDLERS[$requestedRunOn][$dFullName])) {
						if (!isset($dom[$encodedFileName])) {
							$dom[$encodedFileName] = new DOMDocument();
							$xml = self::create_user_content(0, $encodedFileName);
							$dom[$encodedFileName]->loadXML($xml);
						}
						$nl = $dom[$encodedFileName]->getElementsByTagName($dSimpleName);
						if ($nl->length > 0) {
							if ($requestedRunOn == 'session-start' && (!session_id() || function_exists('session_status') && session_status() == PHP_SESSION_DISABLED)) {
								self::throwLoadError('requirements', 'session-disabled', 'The application requires PHP session but it is disabled or not started');
								break;
							}				
							$hEncodedClassName = $HANDLERS[$requestedRunOn][$dFullName];
							if (!isset($instances[$hEncodedClassName]))
								$instances[$hEncodedClassName] = array(0, new $hEncodedClassName(0, 0));
							$DS = array(null, $encodedFileName, $X_TOUCH[$encodedFileName]);//todo: potrei mettere il DS dentro l'array instances
							foreach ($nl as $n) {
								if (self::$execution_status) break;
								$DS[3] =& $n;
								$DS[0] = $instances[$hEncodedClassName][0]++;
								$instances[$hEncodedClassName][1]->main(0, $DS, null);
							}
						}
					}
				}
			foreach ($instances as $i)
				unset($i[1]); //qui eventuale trigger 'after-event-name'
		}
		
		public static function checkConfigurationReady() {
			if (self::$execution_status) return;
			// is the application configured?
			// ... [if not: if POST throws error otherwise redirect to the configuration tasks, passing the requested context]
		}
		
		public static function checkApplicationReady() {
			if (self::$execution_status) return;
			//chdir(ROOT_PREFIX);
			// inclusion of application management utility
			include '~application-manage.php';
			return defined('APPLICATION_STARTED');
			// if here, application was running or has started automatically without errors
			// application-manage could change execution_status to force operational redirects
			// echo "cwd=".getcwd()." c-dir=".CONTEXT_DIR."<br/>";
			//chdir(CONTEXT_DIR);
		}
		
		public static function checkSessionReady($force = false) {
			if (self::$execution_status) return;
			// is the session started?
			if (!session_id()) @session_start();
			if (!isset($_SESSION['session_tstamp']) || $force) {
				include ROOT_PREFIX.'on-session-start.php';
				$_SESSION['session_tstamp'] = time();
			}
		}
		
      public static function executeStartPoint($partName, $forRunOn = 'context') {
			if (self::$execution_status) return;
            global $DEF_ARGS;
			$p = new $partName(0, 0);
			$DS = array();
			$i = 0; 
			if (is_array($DEF_ARGS))
			  foreach ($DEF_ARGS as $n => $v)
				if (isset($_REQUEST[$n])) $DS[$i++] = $_REQUEST[$n]; else $DS[$i++] = $v;
			try {
            $p->main(0, $DS, null);
			}
			catch (Dude_SHUT_DOWN_CONTEXT $e) {
				var_dump($e->getMessage());
				self::$execution_status = 1; //interrotto da user
			}
			//unset($_SESSION['auto-update-calls']);
         return $p;
        }
		
		public static function onTerminate() {
			global $COPY_LOG;
			if (isset($COPY_LOG) && (!isset($GLOBALS['hide-copy-log']) || !$GLOBALS['hide-copy-log']))
				echo ''; //"<pre>".implode('', $COPY_LOG)."</pre>";
			if (isset(self::$execution_on_terminate['err'])) {
				define('ERROR_CLASS', self::$execution_on_terminate['err']);
				define('ERROR_CODE', self::$execution_on_terminate[0]);
				define('ERROR_MESSAGE', self::$execution_on_terminate[1]);
				include '~error.php';
				die();
			}
		}

		abstract protected function main($CONTEXT, &$DS /*, Piece $caller*/);
		
		public function __construct($client, $index) { //&$se, &$attributes /* if NULL mandatory setted with ->initArguments() */, $index = 1) {
			$this->index = $index;
			$this->client = $client;
		}
		
		public function node($CONTEXT, $DS_E, $callbackMethodName, $clientIndex) {
			if (method_exists($this, "{$callbackMethodName}_{$clientIndex}"))
				eval("\$this->{$callbackMethodName}_{$clientIndex}(\$CONTEXT, \$DS_E);");
		}
		
		public static function get_subelements_content($CONTEXT, $part, $DS_E, $callbackMethodName, $clientIndex) {
			$CONTEXT++; 
			self::$buffer[$CONTEXT] = '';
			$part->node($CONTEXT, $DS_E, $callbackMethodName, $clientIndex);
			return self::$buffer[$CONTEXT];
		}
        
		public static function apply_piece_and_get_contents($CONTEXT, Piece $part, &$DS) {
			$CONTEXT++;
			self::$buffer[$CONTEXT] = '';
			$part->main($CONTEXT, $DS, $this);
			return self::$buffer[$CONTEXT];
		}
		
		public function get_subelement_attribute($name) {
		}
		
		public static function getBuffer($context = 0) {
			return self::$buffer[$context];
		}
		
		public static function resetBuffer() {
			self::$buffer = array();
			self::$buffer[0] = '';
		}

		protected static function render($CONTEXT, $something) {
			self::$buffer[$CONTEXT] .= $something;
		}
		
		protected static function create_user_content($CONTEXT, $encodedFileName) {
//			$f = $encodedFileName.'.content.xml';
//			if ($cache == 'application' && file_exists($f))
//				return file_get_contents($f);
//			if ($cache == 'session' && isset($_SESSION[$f]))
//				return $_SESSION[$f];
//			if ($cache == 'context' && isset($_REQUEST[$f]))
//				return $_REQUEST[$f];
			$c = new ReflectionClass($encodedFileName . '_3Adude_3Fcontent_3Fupdater');
			$d = $c->newInstance(null, null, null); //$d = new UpdateUserContent(null); //il client ? ininfluente poich� non ci sono callbacks
			$DS = array();
			$content = self::apply_piece_and_get_contents($CONTEXT, $d, $DS);
//			if ($cache == 'application') file_put_contents($f, $content);
//			if ($cache == 'session') $_SESSION[$f] = $content;
//			if ($cache == 'context') $_REQUEST[$f] = $content;
			return "<root>$content</root>";
		}

		
		protected function application_set($name, &$value) {
			global $_application;
			return $_application->set($name, $value);
		}
		
		protected function application_get($name) {
			global $_application;
			return  $_application->get($name);
		}
	}
	
	
	// ------------------------------------------------------------------------
	// Application Data support
	
	// ID
	define( 'IAD_APPLICATION_SERVER_SHMID', 0xA00 );

	// Context
	define( 'IAD_SERVER', 0 );		// pi� performante
	define( 'IAD_AUTOMATIC', -1 );	// pi� ergonomico

	// Dimensione dati
	define( 'IAD_APPLICATION_SHM_SIZE', 0xFFFF );	// 64K

	// Se IAD_IS_MSWIN � true ignora i semafori
	define( 'IAD_IS_MSWIN', true );

	/**
	* 	IApplicationData
	*
	*/
	interface IApplicationData {
		public static function Exists( $name );
		public static function Set( $name, $value );
		public static function Get( $name );
		public static function Lock();	
		public static function Unlock();
		public static function Destroy();
		public static function getOpenedContexts();
	}

	class FileApplicationData implements IApplicationData
	{
		public static function Exists( $name ) {
			return file_exists(ROOT_PREFIX.'lib/app-data/'.urlencode($name));
		}
		public static function Set( $name, $value ) {
			file_put_contents(ROOT_PREFIX.'lib/app-data/'.urlencode($name), serialize($value));
		}
		public static function Get( $name ) {
			return @unserialize(@file_get_contents(ROOT_PREFIX.'/lib/app-data/'.urlencode($name)));
		}
		public static function Lock() {
			//usare flock()
		}
		public static function Unlock() {
		}
		public static function Destroy() {
		}
		public static function getOpenedContexts() {
		}
	}	
	

	final class ApplicationData 
		extends FileApplicationData {
		
		}
	
	
	
	


	
?>