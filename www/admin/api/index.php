<?php

/* ***************************************************************************************************
** INIT **********************************************************************************************
*************************************************************************************************** */ 
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/config.php';
require __DIR__ . '/helpers.php';

R::setup($config['db']['host'], $config['db']['user'], $config['db']['pass']);
R::setAutoResolve( TRUE );

// TEST DB CONNECTION
$conn = R::testConnection();

if(!$conn){
	api_error('DB_CONN_FAIL');
}

$config['api']['beans'] = R::inspect();

if($config['api']['debug']){
	R::debug( TRUE, 0 );
};

/* ***************************************************************************************************
** API REQUEST ***************************************************************************************
*************************************************************************************************** */ 

foreach ($_REQUEST as $k => $v) {
	$req[$k] = $v;
};

/* ***************************************************************************************************
** API REQUEST MODE **********************************************************************************
*************************************************************************************************** */ 

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

function api_forbid(){
	header('HTTP/1.0 400 Bad Request');
	
	$res = array(
		'error' => true, 
		'message' => getMessage('INVALID_REQUEST')
	);
	api_output($res);
};

function api_error($msg){

	$res = array(
		'error' 	=> true,
		'message' 	=> getMessage($msg)
	);

	api_output($res);
};

// OUTPUT
function api_output($res){
	echo json_encode($res);
};

?>