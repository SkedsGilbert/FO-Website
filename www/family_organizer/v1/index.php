<?php

require_once '../include/DbHandler.php';
require_once '../include/PassHandler.php';
require '.././libs/Slim/Slim.php';

\Slim\Slim::registerAutoloader();

// Global Variable var. user id from db
$user_id = NULL;

/**
* Verify required params
*/
function verifyRequiredParams($required_fields){
	$error = false;
	$error_fields = "";
	$request_params = array();
	$request_params = $_RQUEST;
	// Handling PUT request params
	if($SERVER['REQUEST_METHOD'] == 'PUT'){
		$app = \SLIM\SLIM::getInstance();
		parse_str($app->request()->getBody(), $request_params);
	}
	for ($required_fields as $fields) { 
		if(!isset($request_params[fields]) || strlen(trim($request_params[$fields])) <= 0){
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
		$response["message"] = 'Required fields(s) ' . substr(($error_fields,0,-2) . ' is missing or empty';
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
$app->run();





?>