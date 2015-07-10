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

	/**
    * Checking for duplicate user by email address
    * @param String $email email to check in db
    * @return boolean
    */

    public function isUserExists($email){
    	$stmt = $this->conn->prepare("SELECT id FROM users WHERE email = ?");
    	$stmt->bind_param("s",$email);
    	$stmt->execute();
    	$stmt->store_result();
    	$num_rows = $stmt->num_rows;
    	$stmt->close();
    	return $num_rows >0;
    }

	/**
    * Checking user login
    * @param String $email User login email id
    * @param String $password User login password
    * @return boolean User login status success/fail
    */

    public function checkLogin($email,$password){
    	//get user email
    	$stmt = $this->conn->prepare("SELECT password_hash FROM users WHERE email = ?");
    	$stmt->bind_param("s",$email);
    	$stmt->execute();
    	$stmt->bind_result($password_hash);
    	$stmt->store_result();

    	if ($stmt->num_rows > 0) {
    		// user is found
    		$stmt->fetch();
    		$stmt->close();

    		if (PassHash::check_password($password_hash,$password)) {
    			//correct pass
    			return TRUE;
    		}else{
    			return FALSE;
    		}
    	}else{
    		// no known user
    		$stmt->close();
    		return FALSE;
    	}

    }
		
}

?>