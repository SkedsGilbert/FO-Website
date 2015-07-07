<?php

class DbConnect{
	private $conn;

	function _construct(){

	}

	function connect(){
		inclued_once dirname(_FILE_).'./Config.php';

		//DB connection
		$this->conn = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME);
		//Check for connection
		if (mysql_errno()) {
			echo "Failed to connect to MySQL: ".mysql_error();
		}
		return $this->conn;
	}
}

?>