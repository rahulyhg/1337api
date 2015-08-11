<?php

/* ***************************************************************************************************
** INIT **********************************************************************************************
*************************************************************************************************** */ 
require 'rb-p533.php';
require 'config.php';

R::setup($config['db']['host'], $config['db']['user'], $config['db']['pass']);
R::setAutoResolve( TRUE );

$config['api']['beans'] = R::inspect();

if($config['api']['debug']){
	R::debug( TRUE, 0 );
}

/* ***************************************************************************************************
** GET ROUTES ****************************************************************************************
*************************************************************************************************** */ 

if($_SERVER['REQUEST_METHOD'] == 'GET') {

	if( !empty($_GET) && in_array($_GET['action'], $config['api']['actions']['get'])){

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
				if (in_array($request['edge'], $config['api']['beans'])){
					api_search($request);
				}
				else{
					api_forbidden($config);
				}
			break;

			case 'read':
				if (in_array($request['edge'], $config['api']['beans']) && !empty($request['param'])){
					api_read($request, $config);
				}
				else{
					api_forbidden($config);
				}
			break;

			case 'exists':
				if (in_array($request['edge'], $config['api']['beans']) && !empty($request['param'])){
					api_exists($request, $config);
				}
				else{
					api_forbidden($config);
				}
			break;

			case 'list':
				if (in_array($request['edge'], $config['api']['beans'])){
					api_list($request, $config);
				}
				else{
					api_forbidden($config);
				}
			break;

			case 'count':
				if (in_array($request['edge'], $config['api']['beans']) && empty($request['param'])){
					api_count($request, $config);
				}
				else{
					api_forbidden($config);
				}
			break;

			case 'schema':
				if (in_array($request['edge'], $config['api']['beans'])){
					api_schema($request, $config);
				}
				else{
					api_forbidden($config);
				}
			break;		

			default:
				api_forbidden($config);
			break;
		};

	} 
	else {
		api_forbidden($config);
	};

};

/* ***************************************************************************************************
** POST ROUTES ***************************************************************************************
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
			if (in_array($request['edge'], $config['api']['beans'])){
				api_create($request);
			}
			else{
				api_forbidden($config);
			}
		break;

		default:
				api_forbidden($config);
		break;
	};

};

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
			if (in_array($request['edge'], $config['api']['beans'])){
				api_update($request);
			}
			else{
				api_forbidden($config);
			}
		break;

		default:
				api_forbidden($config);
		break;
	};

};

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
			if (in_array($request['edge'], $config['api']['beans'])){
				api_destroy($request);
			}
			else{
				api_forbidden($config);
			}
		break;

		default:
				api_forbidden($config);
		break;
	};

};

/* ***************************************************************************************************
** RETURN FUNCTIONS **********************************************************************************
*************************************************************************************************** */ 

function api_create($request){
	$item = R::dispense( $request['edge'] );

	foreach ($request['content'] as $k => $v) {
		$item[$k] = $v;
	};
		$item['created'] = R::isoDateTime();
		$item['modified'] = R::isoDateTime();

	$id = R::store($item);
	$result['message'] = 'Criado com Sucesso. (id: '.$id.')';

	// OUTPUT
	api_output($result);

};

function api_read($request, $config){

	// READ - view one
	$item = R::load( $request['edge'], $request['param'] );

	foreach ($item as $k => $v) {
		if(!in_array($k, $config['schema']['default']['blacklist'])) {

			if(substr($k, -3, 3) == '_id'){
				$parentBean = substr($k, 0, -3);
				$parent = R::load( $parentBean , $v );
				
				foreach ($parent as $key => $value) {
					$result[$parentBean][$v][$key] = $value;
				};
			}
			else{
				$result[$k] = $v;
			};
		};
	};

	// OUTPUT
	api_output($result);
};

function api_exists($request, $config){

	// EXISTS?
	$exists = R::find($request['edge'],' id = '.$request['param'].' ' );

	if( empty( $exists ) )
	{
		$result['exists'] = false;
	}
	else{
		$result['exists'] = true;
	}

	// OUTPUT
	api_output($result);
};

function api_update($request){

	$item = R::load( $request['edge'], $request['param'] );

	foreach ($request['content'] as $k => $v) {
		$item[$k] = $v;
	};
		$item['modified'] = R::isoDateTime();

	R::store( $item );
	$result['message'] = 'Atualizado com Sucesso. (id: '.$request['param'].')';

	// OUTPUT
	api_output($result);
};

function api_destroy($request){

	$item = R::load( $request['edge'], $request['param'] );
    R::trash( $item );

	$result['message'] = 'Excluído com Sucesso. (id: '.$request['param'].')';

	// OUTPUT
	api_output($result);
};

function api_list($request, $config){

	// LIST - list all
	if(empty($request['param'])){
		$items = R::findAll( $request['edge'] );

		foreach ($items as $item => $content) {
			foreach ($content as $k => $v) {
				$result[$item][$k] = $v;
			};
		};
	}

	// LIST - paginated
	else{
		
		$page 	= $request['param'];
		$limit 	= $config['api']['params']['pagination'];
		$items 	= R::findAll( $request['edge'], 'ORDER BY id LIMIT '.(($page-1)*$limit).', '.$limit);

		foreach ($items as $item => $content) {
			foreach ($content as $k => $v) {
				$result[$item][$k] = $v;
			};
		};
	};

	// OUTPUT
	api_output($result);
};

function api_search($request){
	$result['message'] = 'in development: action "search"';

	// OUTPUT
	api_output($result);
};

function api_count($request, $config){
	
	// COUNT - count all
	$count = R::count( $request['edge'] );
	$limit = $config['api']['params']['pagination'];

	$result['sum'] 		= $count;
	$result['pages'] 	= round($count/$limit);
	
	// OUTPUT
	api_output($result);
};

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

	foreach ($schema['raw'] as $field => $properties) {

		if(!in_array($field, $config['schema']['default']['blacklist'])){

			if(substr($field, -3, 3) == '_id'){
				$parentBean = substr($field, 0, -3);
				$parent = R::getAssoc('DESCRIBE '. $parentBean);

				$result['properties'][$parentBean] = array(
					'type' 				=> 'string',
					'title' 			=> ucfirst($parentBean),
					'required'	 		=> true,
					'minLength'	 		=> 1,
					'enum' 				=> array(),
					'options' 			=> array(
						'enum_titles' 	=> array(),
					),
				);

				$parentOptions = R::getAssoc( 'SELECT id, name FROM '.$parentBean );

				foreach ($parentOptions as $key => $value) {
					$result['properties'][$parentBean]['enum'][] = $key;
					$result['properties'][$parentBean]['options']['enum_titles'][] = $value;					
				};

			}
			else{

				// PREPARE DATA;
				$dbType 	= preg_split("/[()]+/", $schema['raw'][$field]['Type']);
				$type 		= $dbType[0];
				$format 	= $dbType[0];
				$maxLength 	= (!empty($dbType[1]) ? (int)$dbType[1] : '');
				$minLength 	= ($schema['raw'][$field]['Null'] == 'YES' ? 0 : 1);

				// converts db type to json-editor expected type
				if(array_key_exists($type, $config['schema']['default']['type'])){
					$type = $config['schema']['default']['type'][$type];
				};

				// converts db type to json-editor expected format
				if(array_key_exists($format, $config['schema']['default']['format'])){

					if($format == 'varchar' && $maxLength > 256){
						$format = 'textarea';
					}
					else{
						$format = $config['schema']['default']['format'][$format];
					};
				};

				// builds default properties array to json-editor
				$result['properties'][$field] = array(
					'type'			=> $type,
					'format' 		=> $format,
					'title' 		=> ucfirst($field),
					'required'	 	=> true,
					'minLength' 	=> $minLength,
					'maxLength'		=> $maxLength
				);

				if(isset($config['schema']['custom']['fields'][$field])){
					$result['properties'][$field] = array_merge($result['properties'][$field], $config['schema']['custom']['fields'][$field]);
				};

				// add '*' to field title if required.
				if($result['properties'][$field]['minLength'] > 0){
					$result['properties'][$field]['title'] = $result['properties'][$field]['title'] . '*';
				}

			};

		};

		// RAW STRUCTURE

		if(substr($field, -3, 3) == '_id'){
			$parentBean = substr($field, 0, -3);
			$parent = R::getAssoc('DESCRIBE '. $parentBean);

			foreach ($parent as $key => $value) {

				$result['structure'][$parentBean] = array(
					'field' 		=> $key,
					'properties' 	=> $value,
				);
			
			}

		}
		else{
			$result['structure'][$field] = array(
				'field' 		=> $field,
				'properties' 	=> $properties,
			);
		};
	};

	// OUTPUT
	api_output($result);
}

function api_edges($config){

	$hierarchy = R::getAssoc('
		SELECT TABLE_NAME, REFERENCED_TABLE_NAME
		FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
		WHERE REFERENCED_TABLE_NAME IS NOT NULL
	');
	
	foreach ($config['api']['beans'] as $k => $v) {

		$beans[$v] = array(
			'name' 	=> $v,
			'title' => ucfirst($v),
			'count' => R::count($v),
			'icon' 	=> 'th-list',
		);

		if(array_key_exists($v, $hierarchy)){

			$beans[$v]['parent'] = array(
				'name' 	=> $hierarchy[$v],
				'title' => ucfirst($hierarchy[$v]),
				'count' => R::count($hierarchy[$v]),
				'icon' 	=> 'th-list',
			);

			$beans[$hierarchy[$v]]['child'] = array(
				'name' 	=> $v,
				'title' => ucfirst($v),
				'count' => R::count($v),
				'icon' 	=> 'th-list',
			);

		}

	};

	$result['beans'] 	= $beans;
	$result['actions'] 	= $config['api']['actions'];

	api_output($result);
};

function api_output($result){
	echo json_encode($result);
};

function api_forbidden($config){
	$result['message'] = $config['api']['messages']['forbidden'];
	echo json_encode($result);
};

?>