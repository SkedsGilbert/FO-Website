<?php

require_once '../include/DbHandler.php';
require_once '../include/PassHash.php';
require '../libs/Slim/Slim/Slim.php';

\Slim\Slim::registerAutoloader();

$app = new \Slim\Slim();

// Global Variable var. user id from db
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
		$response["message"] = "You are sucessfully registered";
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

$app->run();

?>