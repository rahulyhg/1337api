<?php

/* ***************************************************************************************************
** INIT **********************************************************************************************
*************************************************************************************************** */ 
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/config.php';
require __DIR__ . '/helpers/shared.php';

// KLEIN ROUTER SETUP
$router = new \Klein\Klein();

// REDBEAN ORM SETUP
R::setup($config['db']['host'], $config['db']['user'], $config['db']['pass']);
R::setAutoResolve( TRUE );
R::freeze( FALSE );

// TEST DB CONNECTION
if(R::testConnection() == FALSE){
	api_error('DB_CONN_FAIL');
	exit();
};

// INSPECT TABLES
$api = array();
$api['edges'] = R::inspect();

// REDBEAN ORM DEBUG MODE ON
if($config['api']['debug']){
	R::debug( TRUE, 1 );
};

/* ***************************************************************************************************
** KLEIN ROUTER - PRIVATE ROUTES *********************************************************************
*************************************************************************************************** */ 

$router->respond(function ($request, $response, $service, $app) use ($router) {

	$service->addValidator('edge', function ($str) {
		global $api;
		return in_array($str, $api['edges']);
	});

    $router->onError(function ($router, $err_msg, $request, $response) {

		$err = array(
			'error' => true, 
			'message' => getMessage($err_msg)
		);
		echo json_encode($err);

    });

    $router->onHttpError(function ($code, $request, $response, $router) {
		header('HTTP/1.0 400 Bad Request');
		$err = array('error' => true, 'message' => getMessage('INVALID_REQUEST'));
		echo json_encode($err);
    });

});

$router->with("/api/public", "controllers/public.php");
$router->with("/api/private", "controllers/private.php");
//$router->with("/api/auth", "controllers/auth.php");

$router->dispatch();

/* ***************************************************************************************************
** API OUTPUT FUNCTIONS ******************************************************************************
*************************************************************************************************** */ 

// FORBIDDEN OUTPUT
function api_forbid(){
	header('HTTP/1.0 400 Bad Request');
	$res = array('error' => true, 'message' => getMessage('INVALID_REQUEST'));
	api_output($res);
};

// ERROR OUTPUT
function api_error($msg, $debug = ''){
	global $config;

	$res = array('error' => true, 'message' => getMessage($msg));
	
	if($config['api']['debug']){
		$res['debug'] = $debug;
	};	

	echo json_encode($res);
};

// API JSON OUTPUT
function api_output($res){
//	echo json_encode($res);
};

?>	