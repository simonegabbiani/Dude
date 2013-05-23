<?php

/* I APOLOGIZE FOR ITALIAN COMMENTS IN THIS FILE. THEY WILL BE TRANSLATED SOON AS POSSIBLE */

##
## SHMApplicationData.php
##
## Simula l'oggetto ASP Application (nella sola funzionalità di
## salvare e leggere dati condivisi). PHP deve supportare i semafori 
## e la shared memory.
##
## ATTENZIONE: SU WINDOWS QUESTA LIBRERIA NON E' IN GRADO DI USARE 
## I SEMAFORI PER CUI NON C'E' GESTIONE DEGLI ACCESSI CONCORRENTI.
## NON GESTISCE NEPPURE I CONTESTI.
## (il server di produzione deve essere Unix)
##
## Al contrario di IIS o di un servlet container i dati sono tenuti
## in vita anche successivamente a un riavvìo del server Apache.
## Per eseguire un reset occorre chiamare esplicitamente Destroy().
##
## La libreria è fondamentalmente stupida e non salva niente su file,
## è adatta solo a piccoli dati. Per default alloca 64K a contesto.
##
## Occorre tenere presente che la consistenza di ApplicationData
## è tale in una singola macchina. Ad esempio se l'applicazione è 
## tenuta in vita per mezzo di solo un database ma il layer PHP 
## fosse distribuito si più server quest'oggetto non funzionerebbe
## correttamente.
##
## Il lato positivo è che ApplicationData dovrebbe essere sensibilmente
## performante e relativamente leggero e non necessita di DBMS.
##
## Esempi:
##
##		$Application = new ApplicationData( 'MYPETSTORE.PublicSection' );
##
##		$Application->Set( 'foo', 'bar' );
##		echo $Application->Get( 'foo' );
##
##		$Application->AcquireLock();
##		if (($i = $Application->Get('Counter')) != NULL)
##			if ($i < 0) 
##				$i++;
##		$Application->Set( 'Counter', $i );
##		$Application->ReleaseLock();
##
##
## Impostare la variabile globale SAVEAPPCONTEXTS su true
## perché tutti i contesti selezionati vengano via via salvati.
## Così facendo è possibile conoscere tutti i contesti
## aperti per mezzo del metodo getOpenedContexts()
##

require_once "IApplicationData.php";

// This is a very very important number, because it decides the isolation level
// of our applcation. What do we mean with "application"? A single web site?
// a set of services in different virtual directories? Or simply means where 
// SHMApplicationData.php is located? and so on.. Our Application object implements 
// the concept of Context that allow to personalize sub-application instances,
// I've decided to NOT isolate Application ever, so all applications are able to
// share any data. If you want to avoid this, you could use getmyinode().
if (!isset(APPLICATION_SEM_ID))
	define(APPLICATION_SEM_ID, 0xA00); //fixed number for all applications shared | getmyinode()
	
if (!isset(IAD_APPLICATION_SERVER_SHMID))
	define(IAD_APPLICATION_SERVER_SHMID, APPLICATION_SEM_ID);
	
if (!isset(IAD_APPLICATION_SHM_SIZE))
	define(IAD_APPLICATION_SHM_SIZE, 0xFFFF);

	
class SHMUnsupportedException extends Exception {
}

class SHMApplicationData implements IApplicationData
{
	
	/**
	 * Tiene aperto il segmento per l'intera istanza, 
	 * evitando di aprirlo e chiuderlo più volte.
	 *
	 * Tipicamente si istanzia l'oggetto una volta sola
	 * poi la gc fa tutto da se.
	 *
	 */
	private $SHMDataBlockId = 0;
	private $ContextID = IAD_APPLICATION_SERVER_SHMID;
	private $userLockSemId = 0;
	
	function __construct( $Context = IAD_AUTOMATIC ) {
		//echo "ApplicationData($Context)<br>";
		if (!function_exists("shmop_open")) {
			throw new SHMUnsupportedException();
		}
		// seleziona un contesto
		// (ogni contesto punta a un diverso segmento di memoria condivisa)
		if (!IAD_IS_MSWIN) {
			if ($Context == IAD_AUTOMATIC)
				$this->ContextID = ftok( __FILE__, 'a' );
			else if ($Context != IAD_SERVER) // ftok non disponibile su Windows
				if (function_exists( 'ftok' ) && ($this->ContextID = ftok( $Context, 'a' )) == 0) {
					user_error( 'Bad context name (use a real file path)', E_USER_ERROR );
					$this->ContextID = IAD_APPLICATION_SERVER_SHMID;
				}
			//echo "Using context id: {$this->ContextID}<br>";
		}
		else if ($Context !== IAD_SERVER) {
			user_error( "Only IAD_SERVER context supported on Windows platforms, ignoring '$Context'", E_USER_NOTICE );
			$Context = IAD_SERVER;
		}
		// se richiesto registra i contesti diversi da IAD_SERVER
		// (salva anche nel caso IAD_IS_MSWIN sia su true)
		if ($Context != IAD_SERVER && isset($GLOBALS['SAVEAPPCONTEXTS']) && $GLOBALS['SAVEAPPCONTEXTS']) {
			$ServerApplication = new ApplicationData( IAD_SERVER );
			// non usa un hash specifico affinché sia più veloce in scrittura
			// echo "Saving context {$this->ContextID} in context list<br>";
			$ServerApplication->Set( '$$['.$this->ContextID, $Context );
		}
		//echo "__contruct(): ok<br>";
	}
	

	function __destruct() {
		if ($this->SHMDataBlockId != 0)
			shmop_close( $this->SHMDataBlockId );
	}
	

	private function set_shmapp( $buffer ) {
		// echo __METHOD__ . "<br>";
		if ($this->SHMDataBlockId == 0) {
			// apre o crea il blocco di memoria condivisa
			if (($this->SHMDataBlockId = shmop_open( $this->ContextID, 'w', 0, 0 )) == false) {
				$this->SHMDataBlockId = shmop_open( $this->ContextID, 'c', 0664, IAD_APPLICATION_SHM_SIZE );
				// echo "creating SHMDataBlockId {$this->SHMDataBlockId}<br>";
				if (!$this->SHMDataBlockId) {
				   user_error( 'Couldn\'t create shared memory segment', E_USER_ERROR );
				   return NULL;
				}
			}
		}
		// salva la variabile
		$l = strlen((string)( $s = serialize( $buffer ) ));
		if ($l >= IAD_APPLICATION_SHM_SIZE) {
			user_error( 'ApplicationData data out of memory (' . $this->ContextID . ')', E_USER_ERROR );
			return NULL;
		}
		// echo "\$this->SHMDataBlockId = '" . $this->SHMDataBlockId . "'<br>";
		if (shmop_write( $this->SHMDataBlockId, $s, 0 ) != $l)
			user_error( "Could not write data into shared memory segment", E_USER_ERROR );
		return $buffer;
	}
	
	
	private function get_shmapp() {
		//echo __METHOD__ . "<br>";
		if ($this->SHMDataBlockId == 0) { // prima chiamata nell'arco di questo script
			if (($this->SHMDataBlockId = shmop_open( $this->ContextID, 'w', 0, 0 )) == NULL)
				return NULL;
			if ($this->SHMDataBlockId == 0) {
			   user_error( 'Couldn\'t open shared memory segment', E_USER_ERROR );
			   return NULL;
			}
		}
		$buffer = unserialize( shmop_read( $this->SHMDataBlockId, 0, 0xFFFF ) );
		return $buffer;
	}
	
	
	/**
	 * Non usare in combinazione con Set o Get in quanto poco performante.
	 *
	 */
	public function Exists( $name ) {
		// non chiede alcun lock, non avrebbe molto senso ai fini di questa richiesta
		// (avrebbe più senso, eventualmente, lo chiedesse esplicitamente il client 
		// fino al termine delle operazioni che ha requisito l'esito di questa 
		// stessa interrogazione).
		if (!($data = $this->get_shmapp()))
			return false;
		return isset( $data[ $name ] );
	}
	
	
	public function Set( $name, $value ) {
		$SemId = 0;
		if ($this->userLockSemId == 0 && !IAD_IS_MSWIN) {// non modificare
			if (($SemId = sem_get( $this->ContextID, 0664, false )) == false)
				user_error( 'Couldn\'t open semaphore', E_USER_ERROR );
			sem_acquire( $SemId );
		}
		if (!($data = $this->get_shmapp())) {
			//echo "[buffer empty: building new hash]<br>";
			$data = array();
		}
		//echo "[populating hash]<br>";
		$data[ $name ] = $value;
		$this->set_shmapp( $data );
		if ($this->userLockSemId == 0 && !IAD_IS_MSWIN)
			sem_release( $SemId );
	}
	
	
	public function Get( $name ) {
		$SemId = 0;
		if ($this->userLockSemId == 0 && !IAD_IS_MSWIN) {// non modificare
			// semaforo: i dati devono essere consistenti
			if (($SemId = sem_get( APPLICATION_SEM_ID, 0664, false )) == false)
				user_error( 'Couldn\'t open semaphore', E_USER_ERROR );
			sem_acquire( $SemId );
		}
		$data = $this->get_shmapp();
		if ($this->userLockSemId == 0 && !IAD_IS_MSWIN)
			sem_release( $SemId );
		if (isset( $data[ $name ] ))
			return $data[ $name ];
		else
			return NULL;
	}

	// i lock sono sempre specifici di un solo contesto
	public function Lock() {
		if (!IAD_IS_MSWIN) {
			if ($this->userLockSemId != 0)
				user_error( 'Lock already opened', E_USER_ERROR );
			else {
				// semaforo: i dati devono essere consistenti
				if (($this->userLockSemId = sem_get( APPLICATION_SEM_ID, 0664, false )) == false)
					user_error( 'Couldn\'t open semaphore', E_USER_ERROR );
				sem_acquire( $this->userLockSemId );
			}
		}
	}
	
	public function Unlock() {
		if (!IAD_IS_MSWIN) {
			if ($this->userLockSemId == 0)
				user_error( 'Lock does not exist', E_USER_ERROR );
			else 
				sem_release( $this->userLockSemId );
		}
	}
	
	/**
	 * Distrugge il segmento di memoria condivisa e perde tutti i 
	 * dati applicazione.
	 *
	 */
	public function Destroy() {
		if ($this->SHMDataBlockId == 0) // prima chiamata nell'arco di questo script
			if (!($this->SHMDataBlockId = shmop_open( $this->ContextID, 'w', 0, 0 )))
				return;
		shmop_write( $this->SHMDataBlockId, serialize( NULL ), 0 );
		shmop_delete( $this->SHMDataBlockId );
		shmop_close( $this->SHMDataBlockId );
		$this->SHMDataBlockId = 0;
	}

	/**
	 * Non è molto performante.
	 *
	 * Restituisce un array vuoto nel caso l'unico contesto usato sia IAD_SERVER
	 *
	 */
	public function getOpenedContexts() {
		if (!isset($GLOBALS['SAVEAPPCONTEXTS']) || !$GLOBALS['SAVEAPPCONTEXTS']) {
			user_error( "getOpenedContexts(): In this script SAVEAPPCONTEXTS is false or not set", E_USER_NOTICE );
		}
		if ($this->ContextID != IAD_APPLICATION_SERVER_SHMID) {
			$o = new ApplicationData( IAD_SERVER );
			return $o->getOpenedContexts();
		}
		else {
			$output = array();
			$ServerData = $this->get_shmapp();
			if (is_array($ServerData)) {
				foreach (array_keys($ServerData) as $k => $v) {
					if (substr( $k, 0, 3 ) == '$$[')
						$output[$k] = $v;
				}
			}
			return $output;
		}
	}
	
}

?>