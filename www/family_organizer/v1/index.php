<?php

require_once '../include/DbHandler.php';
require_once '../include/PassHash.php';
require '../libs/Slim/Slim/Slim.php';

\Slim\Slim::registerAutoloader();

$app = new \Slim\Slim();

// Global Variable user id from db
$user_id = NULL;

/**
* Verify required params
*/
function verifyRequiredParams($required_fields){
	$error = false;
	$error_fields = "";
	$request_params = array();
	$request_params = $_REQUEST;
	// Handling PUT request params
	if($_SERVER['REQUEST_METHOD'] == 'PUT'){
		$app = \SLIM\SLIM::getInstance();
		parse_str($app->request()->getBody(), $request_params);
	}
	foreach ($required_fields as $fields) { 
		if(!isset($request_params[$fields]) || strlen(trim($request_params[$fields])) <= 0){
			$error = true;
			$error_fields .= $fields . ', ';
		}
	}

	if ($error) {
		// Required fields are missing
		// echo error and stop the app
		$response = array();
		$app = \Slim\Slim::getInstance();
		$response["error"] = true;
		$response["message"] = 'Required fields(s) ' . substr($error_fields,0,-2) . ' is missing or empty';
		echoResponse(400,$response);
		$app->stop(); 
	}
}

/**
*Validate email address
*/
function validateEmail($email){
	$app = \Slim\Slim::getInstance();
	if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
		$response["error"] = true;
		$response["message"] = 'Email address is not valid';
		echoResponse(400, $response);
		$app->stop();
	}
}

/**
* Echoing json response to client
* @param String $status_code http response
* @param Int $response json response
*/
function echoResponse($status_code,$response){
	$app = \Slim\Slim::getInstance();
	// http response code
	$app->status($status_code);
	//set response content type
	$app->contentType('application/json');
	echo json_encode($response);
}

/**
* User Registration
* method - POST
*params - name, email, password
*/ 
$app->post('/register', function() use ($app){
// check for required params
	verifyRequiredParams(array('name','email','password'));
	$response = array();
//reading post params
	$name = $app->request->post('name');
	$email = $app->request->post('email');
	$password = $app->request->post('password');

//validating email params
	validateEmail($email);

	$db = new DbHandler();
	$res = $db->createUser($name, $email, $password);

	if ($res == USER_CREATED_SUCCESSFULLY) {
		$response["error"] = false;
		$response["message"] = "You are successfully registered";
		echoResponse(201,$response);
	}elseif ($res == USER_CREATED_FAILED) {
		$response["error"] = true;
		$response["message"] = "An error occurred while registereing";
		echoResponse(200,$response);
	}elseif ($res == USER_ALREADY_EXISTED) {
		$response["error"] = true;
		$response["message"] = "This email is already in use";
		echoResponse(200,$response);
	}
});

/**
* User Login
* method - POST
*params - email, password
*/ 
$app->post('/login', function() use ($app){
	verifyRequiredParams(array('email', 'password'));
	$email = $app->request()->post('email');
	$password = $app->request()->post('password');
	$response = array();

	$db = new DbHandler;
	if ($db->checkLogin($email,$password)) {
		$user = $db->getUserByEmail($email);

		if ($user != NULL) {
			$response["error"] = false;
			$response['name'] = $user['email'];
			$response['apiKey'] = $user['api_key'];
			$response['createdAt'] = $user['created_at'];
		}else{
			$response['error'] = true;
			$response['message'] = "An error has occurred.  Please try again";
		}
	}else{
		$response['error'] = true;
		$response['message'] = "Login failed.  Invalid Login";
	}
	echoResponse(200, $response);
});

/**
* Check for valid API key
*/ 

function authenticate(\Slim\Route $route){
	// Get request headers
	$headers = apache_request_headers();
	$response = array();
	$app = \Slim\Slim::getInstance();

	// Verify Authorization Header
	if(isset($headers['Authorization'])){
		$db = new DbHandler();

		// Get api key
		$api_key = $headers['Authorization'];

		// Validate api key
		if (!$db->isValidApiKey($api_key)) {
			// api key is not present in table
			$response["error"] = true;
			$response["message"] = "Access Denied.  Invalid Api key";
			echoResponse(401,$response);
			$app->stop();
		}else{
				global $user_id;
				// get user id
				$user = $db->getUserID($api_key);
				if ($user != NULL) {
					$user_id = $user["id"];
				}
			}
		}else{
		$response["error"] = true;
		$response["message"] = "Api key is missing";
		echoResponse(400, $response);
		$app->stop();
	}
}

/**
* Create item
* method POST
* params - name
*/

$app->post('/item', 'authenticate', function() use ($app) {
	// check required params
	verifyRequiredParams(array('item'));

	$response = array();
	$item = $app->request->post('item');
	$description = $app->request->post('description');

	global $user_id;
	$db = new DbHandler();	
	
		// Create item
		$item_id = $db->createItem($user_id, $item, $description);

		if ($item_id != NULL) {
			$response["error"] = false;
			$response["message"] = "Item created successfully";
			$response["item_id"] = $item_id;
		}else{
			$response["error"] = true;
			$response["message"] = "Item was not created";
		}
	echoResponse(201, $response);
});

/**
* Get all items for a user
* method Get
*/

$app->get('/items', 'authenticate',function(){
	global $user_id;
	$response = array();
	$db = new DbHandler();

	// getting all items
		$result = $db->getAllUserItems($user_id);

		$response["error"] = false;
		$response["item"] = array();

		// looping through array to prepare array
		while ($item = $result->fetch_assoc()) {
			$temp = array();
			$temp["id"] = $item["id"];
			$temp["item"] = $item["item"];
			$temp["description"] = $item["description"];
			$temp["active"] = $item["active"];
			$temp["createdAt"] = $item["created_at"];
			array_push($response["item"],$temp);
		}
	echoResponse(200,$response);
});

/**
* Get single items for a user
* Method Get
*/

$app->get('/item/:id', 'authenticate', function($item_id){
	global $user_id;
	$response = array();
	$db = new DbHandler;

	//get item
	$result = $db->getItem($item_id, $user_id);

	if ($result != NULL) {
		$response["error"] = false;
		$response["id"] = $result["id"];
		$response["item"] = $result["item"];
		$response["description"] = $result["description"];
		$response["active"] = $result["active"];
		$response["createdAt"] = $result["created_at"];
		echoResponse(200,$response);
	}else{
		$response["error"] = true;
		$response["message"] = "Item doesn't exists";
		echoResponse(404,$response);
	}
});

/**
* Updating existing items
* method Put
* params item, description, status
*/

$app->put('/item/:id', 'authenticate', function($item_id)use($app){
	verifyRequiredParams(array('item','active', 'description'));

	global $user_id;
	$item = $app->request->put('item');
	$item = $app->request->post('item');
	$active = $app->request->put('active');
	$description = $app->request->put('description');

	$db = new DbHandler;
	$response = array();
	$result = $db->updateItem($user_id, $item_id, $item, $description, $active);
	if($result){
		$response["error"] = false;
		$response["message"] ="Item was updated successfully";
	}else{
		$response["error"] = true;
		$response["message"] = "Item was not updated successfully"; 
	}
	echoResponse(200,$response);

});


$app->run();

?>