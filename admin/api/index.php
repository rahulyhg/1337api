<?php

/* ***************************************************************************************************
** INIT **********************************************************************************************
*************************************************************************************************** */ 
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/config.php';
require __DIR__ . '/helpers.php';

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
** API REQUEST ***************************************************************************************
*************************************************************************************************** */ 

// BUILD $REQ OBJ FROM SERVER $_REQUEST
foreach ($_REQUEST as $k => $v) {
	$req[$k] = $v;
};

/* ***************************************************************************************************
** API REQUEST MODE **********************************************************************************
*************************************************************************************************** */ 

// SWITCH ROUTER FOR REQUEST MODE
switch ($req['mode']) {
	case 'auth':
		require 'auth.php';
		break;
	case 'private':
		require 'private.php';
		break;
	case 'public':
		require 'public.php';
		break;
	default:
		api_forbid();
		break;
};

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

	api_output($res);
};

// API JSON OUTPUT
function api_output($res){
	echo json_encode($res);
};

?>