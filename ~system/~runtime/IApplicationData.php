<?php


// ID
define( 'IAD_APPLICATION_SERVER_SHMID', 0xA00 );

// Context
define( 'IAD_SERVER', 0 );		// più performante
define( 'IAD_AUTOMATIC', -1 );	// più ergonomico

// Dimensione dati
define( 'IAD_APPLICATION_SHM_SIZE', 0xFFFF );	// 64K

// Se IAD_IS_MSWIN è true ignora i semafori
define( 'IAD_IS_MSWIN', true );

/**
 * 	IApplicationData
 *
 */
interface IApplicationData {
	public function Exists( $name );
	public function Set( $name, $value );
	public function Get( $name );
	public function Lock();	
	public function Unlock();
	public function Destroy();
	public function getOpenedContexts();
}


?>