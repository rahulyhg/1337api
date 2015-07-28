<?php

/* ***************************************************************************************************
** INIT **********************************************************************************************
*************************************************************************************************** */ 
require 'rb-p533.php';
require 'config.php';

R::setup($config['db']['host'], $config['db']['user'], $config['db']['pass']);
R::setAutoResolve( TRUE );

if($config['api']['debug']){    
	R::debug( TRUE, 0 );
}

$config['api']['beansList'] = R::inspect();

/* ***************************************************************************************************
** GET ROUTES ****************************************************************************************
*************************************************************************************************** */ 

if( !empty($_GET) && in_array($_GET['action'], $config['api']['get']['whitelist'])){

	$request['action']	 = $_GET['action'];
	$request['edge']	 = $_GET['edge'];
	$request['param']	 = $_GET['param'];

	switch($request['action']) {

		case 'hi':
			$result['message'] = 'Hi, Elijah!';
			output($result);
		break;

		case 'edges':
			edges($config);
		break;

		case 'inspect':
			$result[$request['edge']] = R::inspect($request['edge']);
			output($result);
		break;

		case 'search':
			$result['message'] = 'in development: action "search"';
			output($result);
		break;

		case 'read':
			if (in_array($_GET['edge'], $config['api']['beansList'])){
				read($request);
			}
			else{
				forbidden();
			}

		break;

		default:
			$result['message'] = 'action not supported.';
			output($result);
		break;
	}

} 

else {
	forbidden();
}

/* ***************************************************************************************************
** POST ROUTES ***************************************************************************************
*************************************************************************************************** */ 



/* ***************************************************************************************************
** RETURN FUNCTIONS **********************************************************************************
*************************************************************************************************** */ 

function read($request){

	// READ - list all
	if(empty($request['param'])){
		$items = R::findAll( $request['edge'] );

		foreach ($items as $item => $content) {
			foreach ($content as $k => $v) {
				$result[$item][$k] = $v;
			}
		}
	}

	// READ - count all
	elseif ($request['param'] == 'count') {
		$pagesCount = R::count( $request['edge'] );
		$result[$request['edge']] = $pagesCount;
	}

	// READ - view one
	else{
		$item = R::load( $request['edge'], $request['param'] );
		foreach ($item as $k => $v) {
			$result[$k] = $v;
		}
	}

	// OUTPUT
	output($result);
}

function edges($config){
	$result['get actions'] = $config['api']['get']['whitelist'];
	$result['post actions'] = $config['api']['post']['whitelist'];
	$result['edges'] = $config['api']['beansList'];
	output($result);
}

function output($result){
	echo json_encode($result);
}

function forbidden($result){
	$result['message'] = 'elijah says: NO.';
	echo json_encode($result);
}

?>	