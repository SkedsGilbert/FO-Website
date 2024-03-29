<?php

/**
* This class will handle the CRUD methods for the DB
*	@author Gilbert Rodriguez
*/
class DbHandler
{
	private $conn;

	function __construct(){
		require_once dirname(__FILE__).'./DbConnect.php';
		//open DB
		$db = new DbConnect();
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
			$stmt = $this->conn->prepare("INSERT INTO users(name, email, password_hash, api_key, active) values(?, ?, ?, ?, 1)");
			$stmt->bind_param("ssss", $name, $email, $password_hash,$api_key);
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
	* Generate Api key
	*/
	public function generateApiKey(){
		return md5(uniqid(rand(),true));
	}


	/**
    * Checking for duplicate user by email address
    * @param String $email email to check in db
    * @return boolean
    */

    private function isUserExists($email){
    	$stmt = $this->conn->prepare("SELECT id FROM users WHERE email = ?");
    	$stmt->bind_param("s",$email);
    	$stmt->execute();
    	$stmt->store_result();
    	$num_rows = $stmt->num_rows;
    	$stmt->close();
    	return $num_rows > 0;
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

    /**
    * Get user by email
    * @param String $email
    */
    public function getUserByEmail($email){
    	$stmt = $this->conn->prepare("SELECT id,name, email, api_key, active, created_at FROM users WHERE email = ?");
    	$stmt->bind_param("s",$email);
    	if ($stmt->execute()) {
    		$user = $stmt->get_result()->fetch_assoc();
    		$stmt->close();
    		return $user;
    	}else{
    		return NULL;
    	}
    }

    /**
    * Get user id by API key
    * @param String $api_key user api key
    */
    public function getUserID($api_key){
    	$stmt = $this->conn->prepare("SELECT id FROM users WHERE api_key = ?");
    	$stmt->bind_param("s",$api_key);
    	if ($stmt->execute()) {
    		$user_id = $stmt->get_result()->fetch_assoc();
    		$stmt->close();
    		return $user_id;
    	}else{
    		return NULL;
    	}
    }

    /**
    * Get User Groups
    * @param Int $user_id from user_groups table
    */


    /**
    * Get API key by user id
    * @param String $user_id from user table
    */
	public function getApiKeyByID($user_id){
		$stmt = $this->conn->prepare("SELECT api_key FROM users WHERE id = ?");
		$stmt->bind_param("i", $user_id);
		if ($stmt->excute()) {
			$api_key = $stmt->get_result()->fetch_assoc();
			$stmt->close();
			return $api_key;
		}else{
			return NULL;
		}
	}	

	/**
	* Checking if API key exist
	* @param String $api_key users api key
	* @return boolean
	*/
	public function isValidApiKey($api_key){
		$stmt = $this->conn->prepare("SELECT id FROM users WHERE api_key = ?");
		$stmt->bind_param("s",$api_key);
		$stmt->execute();
		$stmt->store_result();
		$num_rows = $stmt->num_rows;
		$stmt->close();
		return $num_rows > 0;
	}

    /*----------- items Table Mehods-----------------------------*/

    /**
    * Creating new items
    * @param String $user_id id for the user of the item
    * @param String $items items varchar
    */

    public function createItem($user_id,$item,$description = NULL){
        //create task row
        $stmt = $this->conn->prepare("INSERT INTO items(item,description) VALUES(?,?)");
        $stmt->bind_param("ss",$item,$description);
        $result = $stmt->execute();
        $stmt->close();

        if($result){
            //assign task to user
            $new_item_id = $this->conn->insert_id;
            $res = $this->createUserItem($user_id,$new_item_id);
            if ($res) {
                //item created successfully
                return $new_item_id;
            }else{
                return NULL;
            }
        }else{
            return NULL;
        }
    }

    /**
    * Get an item
    * @param String $item_id for the item
    */
    public function getItem($item_id,$user_id){
        $stmt = $this->conn->prepare("SELECT i.id, i.item, i.description, i.active, i.created_at FROM items i, user_items ui WHERE i.id = ? AND ui.item_id = i.id AND ui.user_id = ?" );
        $stmt->bind_param("ii", $item_id,$user_id);
        if ($stmt->execute()) {
            $item = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            return $item;
        }else{
            return var_dump($stmt);
        }        
    }

    /**
    * Get all items for a user
    * @param String $user_id id of the user
    */
    public function getAllUserItems($user_id){
        $stmt = $this->conn->prepare("SELECT i.* FROM items i, user_items ui WHERE i.id = ui.item_id AND ui.user_id = ?");
        $stmt->bind_param("i",$user_id);
        $stmt->execute();
        $items = $stmt->get_result();
        $stmt->close();
        return $items;
    }

    /**
    * Get all items for a group
    * @param String $group_id of the group
    */
    public function getAllGroupItems($group_id){
        $stmt = $this->conn->prepare("SELECT i.item, i.description, ui.user_id FROM items i JOIN user_items ui ON ui.item_id = i.id JOIN user_groups ug ON ug.user_id = ui.user_id WHERE i.active = 1 AND ug.group_id = ?");
        $stmt->bind_param("i",$group_id);
        $stmt->execute();
        $items = $stmt->get_result();
        $stmt->close();
        return $items;
    }

    /**
     * Updating items
     * @param String $task_id id of the items
     * @param String $items items text
     * @param String $status items status
     */

    public function updateItem($user_id, $item_id, $item, $description, $active){
        $stmt = $this->conn->prepare("UPDATE items i, user_items ui SET i.item = ?, i.description = ?, i.active = ?  WHERE i.id = ? AND i.id = ui.item_id AND ui.user_id =?");
        $stmt->bind_param("ssiii", $item, $description, $active, $item_id, $user_id);
        $stmt->execute();
        $num_affected_rows = $stmt->affected_rows;
        $stmt->close();
        return $num_affected_rows > 0;
    }

    /*----------- user_items Table Mehods-----------------------------*/

    /**
    * Function to assign an item to a user
    * @param String $user_id id of the user
    * @param String $item_id id of the item
    */

    public function createUserItem($user_id, $item_id){
        $stmt = $this->conn->prepare("INSERT INTO user_items(user_id,item_id) VALUES(?,?)");
        $stmt->bind_param("ii",$user_id,$item_id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

     /*----------- groups Table Mehods-----------------------------*/

    /**
    * Function to assign an item to a user
    * @param String $user_id id of the user
    * @param String $item_id id of the item
    */
    public function createGroup($user_id,$hoh_email){
        //create task row
        $stmt = $this->conn->prepare("INSERT INTO groups(hoh_email) VALUES(?)");
        $stmt->bind_param("s",$hoh_email);
        $result = $stmt->execute();
        $stmt->close();

        if($result){
            //assign task to user
            $new_group_id = $this->conn->insert_id;
            $res = $this->createUserGroup($user_id,$new_group_id);
            if ($res) {
                //item created successfully
                return $new_group_id;
            }else{
                return NULL;
            }
        }else{
            return NULL;
        }
    }
/*----------- user_groups Table Mehods-----------------------------*/
    public function createUserGroup($user_id, $group_id){
        $stmt = $this->conn->prepare("INSERT INTO user_groups(user_id,group_id) VALUES(?,?)");
        $stmt->bind_param("ii",$user_id,$group_id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    public function getUserGroupIds($user_id){
        $stmt = $this->conn->prepare("SELECT * FROM user_groups WHERE user_id = ?  AND active = 1 ORDER BY group_id");
        $stmt->bind_param("i",$user_id);
        $stmt->execute();
        $group = $stmt->get_result();
        $stmt->close();
        return $group;
    }


}

?>