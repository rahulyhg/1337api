<?php
error_reporting(-1);
ini_set('display_errors', 'On');
/* ***************************************************************************************************
** INIT **********************************************************************************************
*************************************************************************************************** */ 
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/config.php';
require __DIR__ . '/controllers/api.php';
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

// if angular style, like this. If jquery style TODO formdata
// 	print_r($request->paramsPost());

	$request->formData = array();
	$decoded = json_decode($request->body(), true);
	
	if(!empty($decoded)){
		$request->formData = $decoded;
	}

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

$router->with('/api/private', function () use ($router) {

	// VALIDATE AUTH
	$router->respond(function ($request, $response, $service) { 
		$service->validate('teste', 'teste')->isLen(4,16); 
	});

	// ROUTES
	$router->respond('GET', '/hi', 'api_hi');
	$router->respond('GET', '/edges', 'api_edges'); 

	$router->respond('GET', '/list/[a:edge]/[i:page]?', 'api_list'); 
	$router->respond('GET', '/count/[a:edge]', 'api_count');
	$router->respond('GET', '/export/[a:edge]', 'api_export'); 
	$router->respond('GET', '/schema/[a:edge]', 'api_schema');

	$router->respond('GET', '/read/[a:edge]/[i:id]', 'api_read');
	$router->respond('GET', '/exists/[a:edge]/[i:id]', 'api_exists'); 

	$router->respond('POST', '/create/[a:edge]', 'api_create');
	$router->respond('POST', '/update/[a:edge]/[i:id]', 'api_update');
	$router->respond('POST', '/updatePassword/user/[i:id]', 'api_updatePassword'); 
	$router->respond('POST', '/destroy/[a:edge]/[i:id]', 'api_destroy'); 
	$router->respond('POST', '/upload/[a:edge]', 'api_upload');

	// $klein->respond('POST', '/posts', $callback);
	// $klein->respond('PUT', '/posts/[i:id]', $callback);
	// $klein->respond('DELETE', '/posts/[i:id]', $callback);
	// $klein->respond('OPTIONS', null, $callback);

});

$router->with('/api/public', function () use ($router) {
	$router->respond('GET', '/', 'api_soon');
});

$router->with("/api/auth", "controllers/auth.php");

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