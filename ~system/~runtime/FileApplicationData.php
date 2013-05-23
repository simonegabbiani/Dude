<?php

require_once "IApplicationData.php";

class FileApplicationData implements IApplicationData
{
	private $path;
	public function __construct($path) {
		if (!file_exists($this->path = $path))
			die("FileApplicationData: il path '$path' non esiste [parametro obbligatorio]");
	}
	public function Exists( $name ) {
		return file_exists($this->path."/".urlencode($name));
	}
	public function Set( $name, $value ) {
		file_put_contents($this->path."/".urlencode($name), serialize($value));
	}
	public function Get( $name ) {
		return @unserialize(@file_get_contents($this->path."/".urlencode($name)));
	}
	public function Lock() {
		//usare flock()
	}
	public function Unlock() {
	}
	public function Destroy() {
	}
	public function getOpenedContexts() {
	}
}




?>