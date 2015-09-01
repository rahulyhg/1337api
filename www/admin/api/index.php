<?php

/* ***************************************************************************************************
** INIT **********************************************************************************************
*************************************************************************************************** */ 
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/config.php';

R::setup($config['db']['host'], $config['db']['user'], $config['db']['pass']);
R::setAutoResolve( TRUE );

$config['api']['beans'] = R::inspect();

if($config['api']['debug']){
	R::debug( TRUE, 0 );
}

/* ***************************************************************************************************
** API REQUEST ***************************************************************************************
*************************************************************************************************** */ 

$request = array(
	'mode' 		=> $_REQUEST['mode'],
	'action' 	=> $_REQUEST['action'],
	'edge' 		=> $_REQUEST['edge'],
	'param'	 	=> $_REQUEST['param']
);

/* ***************************************************************************************************
** API REQUEST MODE **********************************************************************************
*************************************************************************************************** */ 

switch ($request['mode']) {
	case 'signin':
		require 'auth.php';
		break;
	case 'private':
		require 'private.php';
		break;
	case 'public':
		require 'public.php';
		break;
	default:
		api_forbidden($config);
		break;
}

/* ***************************************************************************************************
** API OUTPUT FUNCTIONS ******************************************************************************
*************************************************************************************************** */ 

function api_output($result){
	echo json_encode($result);
};

function api_forbidden($config){

	$result = array(
		'result' 	=> 1,
		'error' 	=> true,
		'success' 	=> false,
		'message' 	=> $config['api']['messages']['forbidden'],
		'data' 		=> array()
	);

	echo json_encode($result);
};

?>