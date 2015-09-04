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
};

/* ***************************************************************************************************
** API REQUEST ***************************************************************************************
*************************************************************************************************** */ 

foreach ($_REQUEST as $k => $v) {
	$request[$k] = $v;
};

/* ***************************************************************************************************
** API REQUEST MODE **********************************************************************************
*************************************************************************************************** */ 

switch ($request['mode']) {
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
		api_forbidden();
		break;
};

/* ***************************************************************************************************
** API OUTPUT FUNCTIONS ******************************************************************************
*************************************************************************************************** */ 

function api_output($result){

/* TODO: idea for default response
	$output = array(
		'res' 		=> 1,										// response flag
		'error' 	=> false,									// error boolean
		'success' 	=> true,									// success boolean
		'msg' 		=> '',										// msg string
		'data' 		=> $result									// data returned
	);
*/

	echo json_encode($result);

};

function api_forbidden(){
   global $config;

	$output = array(
		'res' 		=> 1,										// response flag
		'error' 	=> true,									// error boolean
		'success' 	=> false,									// success boolean
		'msg' 		=> $config['api']['messages']['forbidden'],	// msg string
		'data' 		=> array()									// data returned
	);

	echo json_encode($output);
};

?>