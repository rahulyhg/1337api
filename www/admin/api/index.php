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

		$request = array(
			'action' 	=> $_GET['action'],
			'edge' 		=> $_GET['edge'],
			'param'	 	=> $_GET['param']
		);

		switch($request['action']) {

			case 'hi':
				$result['message'] = $config['api']['messages']['hi'];
				api_output($result);
			break;

			case 'edges':
				if (empty($request['edge'])){
					api_edges($config);
				} else {
					api_forbidden($config);
				}
			break;

			case 'search':
				if (in_array($_GET['edge'], $config['api']['beansList'])){
					api_search($request);
				}
				else{
					api_forbidden($config);
				}
			break;

			case 'read':
				if (in_array($_GET['edge'], $config['api']['beansList'])){
					api_read($request, $config);
				}
				else{
					api_forbidden($config);
				}
			break;

			case 'count':
				if (in_array($_GET['edge'], $config['api']['beansList']) && empty($request['param'])){
					api_count($request);
				}
				else{
					api_forbidden($config);
				}
			break;		

			case 'schema':
				if (in_array($_GET['edge'], $config['api']['beansList'])){
					api_schema($request, $config);
				}
				else{
					api_forbidden($config);
				}
			break;		

			default:
				api_forbidden($config);
			break;
		}

	} 

	else {
		api_forbidden($config);
	}

}

/* ***************************************************************************************************
** POST ROUTES ****************************************************************************************
*************************************************************************************************** */ 

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

	$arrayReqUri = explode('/', $_SERVER['REQUEST_URI']);

	$request = array(
		'action'	 => $arrayReqUri[sizeof($arrayReqUri) - 2],
		'edge'		 => $arrayReqUri[sizeof($arrayReqUri) - 1],
		'content'	 => json_decode(file_get_contents("php://input"),true)
	);

	switch($request['action']) {

		case 'create':
			if (in_array($request['edge'], $config['api']['beansList'])){
				api_create($request);
			}
			else{
				api_forbidden($config);
			}
		break;

		default:
				api_forbidden($config);
		break;
	}

}

/* ***************************************************************************************************
** PUT ROUTES ****************************************************************************************
*************************************************************************************************** */ 

if ($_SERVER['REQUEST_METHOD'] == 'PUT') {

	$arrayReqUri = explode('/', $_SERVER['REQUEST_URI']);

	$request = array(
		'action'	 => $arrayReqUri[sizeof($arrayReqUri) - 3],
		'edge'		 => $arrayReqUri[sizeof($arrayReqUri) - 2],
		'param'		 => $arrayReqUri[sizeof($arrayReqUri) - 1],
		'content'	 => json_decode(file_get_contents("php://input"),true)
	);

	switch($request['action']) {

		case 'update':
			if (in_array($request['edge'], $config['api']['beansList'])){
				api_update($request);
			}
			else{
				api_forbidden($config);
			}
		break;

		default:
				api_forbidden($config);
		break;
	}

}

/* ***************************************************************************************************
** DELETE ROUTES ****************************************************************************************
*************************************************************************************************** */ 

if ($_SERVER['REQUEST_METHOD'] == 'DELETE') {

	$arrayReqUri = explode('/', $_SERVER['REQUEST_URI']);

	$request = array(
		'action'	 => $arrayReqUri[sizeof($arrayReqUri) - 3],
		'edge'		 => $arrayReqUri[sizeof($arrayReqUri) - 2],
		'param'		 => $arrayReqUri[sizeof($arrayReqUri) - 1],
	);

	switch($request['action']) {

		case 'destroy':
			if (in_array($request['edge'], $config['api']['beansList'])){
				api_destroy($request);
			}
			else{
				api_forbidden($config);
			}
		break;

		default:
				api_forbidden($config);
		break;
	}

}

/* ***************************************************************************************************
** RETURN FUNCTIONS **********************************************************************************
*************************************************************************************************** */ 

function api_read($request, $config){

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
			if(!in_array($k, $config['api']['form']['fields']['blacklist'])) {
				$result[$k] = $v;
			}
		}
	}

	// OUTPUT
	api_output($result);
}


function api_create($request){

	$item = R::dispense( $request['edge'] );

	foreach ($request['content'] as $k => $v) {
		$item[$k] = $v;
	}
		$item['created'] = R::isoDateTime();
		$item['modified'] = R::isoDateTime();

	$id = R::store($item);
	$result = 'Criado com Sucesso. (id: '.$id.')';

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

function api_schema($request, $config){

	$schema['raw'] = R::getAssoc('DESCRIBE '.$request['edge']);

	// SCHEMA - inspect all
	$result = array(
		'bean' 					=> $request['edge'],
		'title' 				=> ucfirst($request['edge']),
		'type' 					=> 'object',
		'required' 				=> true,
		'additionalProperties' 	=> false,
	);

	foreach ($schema['raw'] as $key => $value) {

		if(!in_array($key, $config['api']['form']['fields']['blacklist'])){

			// PREPARE DATA;
			$schemaType = preg_split("/[()]+/", $schema['raw'][$key]['Type']);
			$type = $schemaType[0];
			$maxLength = (int)$schemaType[1];

			$minLength = ($schema['raw'][$key]['Null'] == 'YES' ? 0 : 1);

			// TYPE AND FORMAT
			switch ($type) {
				case 'date':
					$type = 'string';
					$format = 'date';
					break;
				case 'text':
					$type = 'string';
					$format = 'textarea';
					break;
				case 'tinyint':
					$type = 'boolean';
					$format = 'checkbox';
					break;
				default:
					$type = 'string';
					$format = 'string';
					break;
			};

			
			$result['properties'][$key] = array(
				'type'			=> $type,
				'format' 		=> $format,
				'title' 		=> ucfirst($key),
				'required'	 	=> true,
				'minLength' 	=> $minLength,
				'maxLength'		=> $maxLength
			);

			if(isset($config['schema']['custom'][$key])){
				$result['properties'][$key] = array_merge($result['properties'][$key], $config['schema']['custom'][$key]);
			};

		};

		// RAW STRUCTURE
		$result['structure'][$key] = array(
			'field' 		=> $key,
			'properties' 	=> $value,
		);

	};

	// OUTPUT
	api_output($result);
}

function api_edges($config){
	
	foreach ($config['api']['beansList'] as $k => $v) {
		$beans[$k]['name'] 	= $v;
		$beans[$k]['title'] = ucfirst($v);
		$beans[$k]['count'] = R::count($v);
		$beans[$k]['icon'] 	= 'th-list'; 
	}

	$result['beans']			 = $beans;
	$result['actions']			 = array(
		'get'	 => $config['api']['get']['whitelist'],
		'post'	 => $config['api']['post']['whitelist'],
		'put'	 => $config['api']['put']['whitelist'],
		'del'	 => $config['api']['delete']['whitelist']
	);

	api_output($result);
}

function api_output($result){
	echo json_encode($result);
}

function api_forbidden($config){
	$result['message'] = $config['api']['messages']['forbidden'];
	echo json_encode($result);
}

?>