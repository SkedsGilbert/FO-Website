<?php

/**
* This class will handle the CRUD methods for the DB
*	@author Gilbert Rodriguez
*/
class DbHandler
{
	private $conn;

	function _costruct(){
		require_once dirname(_FILE_).'./DbConnect.php';
		//open DB
		$db = new DbConnect()
		$this->conn = $db->connect();
	}

/*----------------------------User Table Methods-----------------------------------------*/
	
	/**
	*	Creating new user
	*	@param String $name User full name
	*	@param String $email User login email id
	*	@param String $password User login password
	*/

	public function createUser($name, $email, $password) {
		require_once 'PassHash.php';
		$response = array();

		//First check if user already existed in db
		if(!$this->isUserExists($email)){
			//Create hash
			$password_hash = PassHash::hash($password);
			//Create API key
			$api_key = $this->generateApiKey();
			//insert query
			$stmt = $this->conn->prepare("INSERT INTO users(name,email,password_hash,api_key,active) values(?,?,?,?,?,1");
				$stmt->bind_param("ssss",$name, $email, $password,$api_key);

			$result = $stmt->execute();
			$stmt->close();

			//Check insert was successful
			if ($result) {
				return USER_CREATED_SUCCESSFULLY;
			}else{
				return USER_CREATE_FAILED;
			}		
		}else {
			return USER_ALREADY_EXISTED;
		}
		return $response;
	}
		
}

?>