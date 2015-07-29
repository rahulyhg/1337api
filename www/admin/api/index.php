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

if($_SERVER['REQUEST_METHOD'] == 'GET') {

	if( !empty($_GET) && in_array($_GET['action'], $config['api']['get']['whitelist'])){

		$request['action']	 = $_GET['action'];
		$request['edge']	 = $_GET['edge'];
		$request['param']	 = $_GET['param'];

		switch($request['action']) {

			case 'hi':
				$result['message'] = 'Hi, Elijah!';
				api_output($result);
			break;

			case 'edges':
				api_edges($config);
			break;

			case 'search':
				if (in_array($_GET['edge'], $config['api']['beansList'])){
					api_search($request);
				}
				else{
					api_forbidden();
				}
			break;

			case 'read':
				if (in_array($_GET['edge'], $config['api']['beansList'])){
					api_read($request);
				}
				else{
					api_forbidden();
				}
			break;

			case 'count':
				if (in_array($_GET['edge'], $config['api']['beansList'])){
					api_count($request);
				}
				else{
					api_forbidden();
				}
			break;		

			case 'schema':
				if (in_array($_GET['edge'], $config['api']['beansList'])){
					api_schema($request);
				}
				else{
					api_forbidden();
				}
			break;		

			default:
				api_forbidden();
			break;
		}

	} 

	else {
		api_forbidden();
	}

}

/* ***************************************************************************************************
** PUT ROUTES ****************************************************************************************
*************************************************************************************************** */ 

if ($_SERVER['REQUEST_METHOD'] == 'PUT') {

	$request_array = explode('/', $_SERVER['REQUEST_URI']);

	$request['action']	 = $request_array[sizeof($request_array) - 3];
	$request['edge']	 = $request_array[sizeof($request_array) - 2];
	$request['param']	 = $request_array[sizeof($request_array) - 1];
	$request['content']	 = json_decode(file_get_contents("php://input"),true);

	switch($request['action']) {

		case 'update':
			if (in_array($request['edge'], $config['api']['beansList'])){
				api_update($request);
			}
			else{
				api_forbidden();
			}
		break;

		default:
			$result['message'] = 'action not supported.';
			api_output($result);
		break;

	}

}

/* ***************************************************************************************************
** DELETE ROUTES ****************************************************************************************
*************************************************************************************************** */ 

if ($_SERVER['REQUEST_METHOD'] == 'DELETE') {

	$request_array = explode('/', $_SERVER['REQUEST_URI']);

	$request['action']	 = $request_array[sizeof($request_array) - 3];
	$request['edge']	 = $request_array[sizeof($request_array) - 2];
	$request['param']	 = $request_array[sizeof($request_array) - 1];

	switch($request['action']) {

		case 'destroy':
			if (in_array($request['edge'], $config['api']['beansList'])){
				api_destroy($request);
			}
			else{
				api_forbidden();
			}
		break;

		default:
			$result['message'] = 'action not supported.';
			api_output($result);
		break;

	}

}

/* ***************************************************************************************************
** RETURN FUNCTIONS **********************************************************************************
*************************************************************************************************** */ 

function api_read($request){

	// READ - list all
	if(empty($request['param'])){
		$items = R::findAll( $request['edge'] );

		foreach ($items as $item => $content) {
			foreach ($content as $k => $v) {
				$result[$item][$k] = $v;
			}
		}
	}

	// READ - view one
	else{
		$item = R::load( $request['edge'], $request['param'] );
		foreach ($item as $k => $v) {
			$result[$k] = $v;
		}
	}

	// OUTPUT
	api_output($result);
}


function api_update($request){

	$item = R::load( $request['edge'], $request['param'] );

	foreach ($request['content'] as $k => $v) {
		$item[$k] = $v;
	}
		$item['modified'] = R::isoDateTime();

	R::store( $item );
	$result = 'Atualizado com Sucesso. (id: '.$request['param'].')';

	// OUTPUT
	api_output($result);
}

function api_destroy($request){

    $item = R::load( $request['edge'], $request['param'] );

    R::trash( $item );
	$result = 'Excluído com Sucesso. (id: '.$request['param'].')';

	// OUTPUT
	api_output($result);
}

function api_search($request){
	$result['message'] = 'in development: action "search"';

	// OUTPUT
	api_output($result);
}

function api_count($request){
	
	// COUNT - count all
	$count = R::count( $request['edge'] );
	$result['sum'] = $count;
	
	// OUTPUT
	api_output($result);
}

function api_schema($request){

	$schema['raw'] =  R::inspect($request['edge']);
	
	// SCHEMA - inspect all
	$result['bean'] = $request['edge'];
	$result['title'] = ucfirst($request['edge']);
	foreach ($schema['raw'] as $key => $value) {
		$result['structure'][$key]['field'] = $key;
		$result['structure'][$key]['name'] = ucfirst($key);
		$result['structure'][$key]['type'] = $value;
	}
	
	// OUTPUT
	api_output($result);
}

function api_edges($config){
	
	foreach ($config['api']['beansList'] as $k => $v) {
		$beans[$k]['name'] = $v;
		$beans[$k]['title'] = ucfirst($v);
	}

	$result['beans']			 = $beans;
	$result['actions']['get']	 = $config['api']['get']['whitelist'];
	$result['actions']['put']	 = $config['api']['put']['whitelist'];
	$result['actions']['del']	 = $config['api']['delete']['whitelist'];

	api_output($result);
}

function api_output($result){
	echo json_encode($result);
}

function api_forbidden($result){
	$result['message'] = 'elijah says: NO.';
	echo json_encode($result);
}

?>