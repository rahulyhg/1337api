<?php
use \Firebase\JWT\JWT;
use Goodby\CSV\Export\Standard\Exporter;
use Goodby\CSV\Export\Standard\ExporterConfig;

/* ***************************************************************************************************
** PRIVATE API VALIDATE REQUEST FUNCTIONS ************************************************************
*************************************************************************************************** */ 

// CHECK AUTHORIZATION HEADER
if(function_exists('apache_request_headers')){
	$headers = apache_request_headers();
	if(array_key_exists('Authorization', $headers)){
		$authHeader = $headers['Authorization'];
	}
} 
else {
	$headers = $_SERVER;
	if(array_key_exists('HTTP_AUTHORIZATION', $headers)){
		$authHeader = $headers['HTTP_AUTHORIZATION'];
	}
}
// VALIDATE AUTHORIZATION HEADER
$auth = false;
try {
	if(!empty($authHeader)){
		// Extract the jwt from the Bearer
		list($jwt) = sscanf( $authHeader, 'Bearer %s');

		if($jwt) {
			// decode the jwt using the key from config
			$secretKey 	= base64_decode($config['auth']['jwtKey']);
			$token 		= JWT::decode($jwt, $secretKey, array('HS512'));

			if($token){
				$auth = true;
			}

			else {
				throw new Exception('Invalid token found at Authorization Header.', 1);
			}

		} 
		else {
			throw new Exception('Token not found at Authorization Header.', 1);
		}
	} 
	else{
		throw new Exception('Authorization Header not found.', 1);
	}
	
} catch (Exception $e) {
	header('HTTP/1.0 401 Unauthorized');
	echo $e->getMessage();
	die();
};

// CHECK REQUEST_METHOD HEADER
if ( $auth == false || empty($req) || !in_array($_SERVER['REQUEST_METHOD'], ['GET','POST']) ) {
	header('HTTP/1.0 405 Method Not Allowed');
	$res = array('error' => true, 'message' => 'HTTP/1.0 405 Method Not Allowed');
	echo json_encode($res);
	die();
}

/* ***************************************************************************************************
** PRIVATE GET ROUTES ********************************************************************************
*************************************************************************************************** */ 
if ($_SERVER['REQUEST_METHOD'] == 'GET') {

	switch($req['action']) {

		case 'hi':
			api_hi();
		break;

		case 'edges':
			if (empty($req['edge'])){
				api_edges();
			} 
			else {
				api_forbid();
			}
		break;

		case 'search':
			if (in_array($req['edge'], $config['api']['beans'])){
				api_search($req);
			}
			else{
				api_forbid();
			}
		break;

		case 'read':
			if (in_array($req['edge'], $config['api']['beans']) && !empty($req['param'])){
				api_read($req);
			}
			else{
				api_forbid();
			}
		break;

		case 'exists':
			if (in_array($req['edge'], $config['api']['beans']) && !empty($req['param'])){
				api_exists($req);
			}
			else{
				api_forbid();
			}
		break;

		case 'list':
			if (in_array($req['edge'], $config['api']['beans'])){
				api_list($req);
			}
			else{
				api_forbid();
			}
		break;

		case 'count':
			if (in_array($req['edge'], $config['api']['beans']) && empty($req['param'])){
				api_count($req);
			}
			else{
				api_forbid();
			}
		break;

		case 'schema':
			if (in_array($req['edge'], $config['api']['beans'])){
				api_schema($req);
			}
			else{
				api_forbid();
			}
		break;		

		case 'export':
			if (in_array($req['edge'], $config['api']['beans'])){
				api_export($req);
			}
			else{
				api_forbid();
			}
		break;		

		default:
			api_forbid();
		break;
	};

};

/* ***************************************************************************************************
** PRIVATE POST ROUTES *******************************************************************************
*************************************************************************************************** */ 

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

	$req['content'] = json_decode(file_get_contents("php://input"),true);

	switch($req['action']) {

		case 'create':
			if (in_array($req['edge'], $config['api']['beans'])){
				api_create($req);
			}
			else{
				api_forbid();
			}
		break;

		case 'updatePassword':
			if ($req['edge'] == 'user' && !empty($req['param'])){
				api_updatePassword($req);
			}
			else{
				api_forbid();
			}
		break;

		case 'update':
			if (in_array($req['edge'], $config['api']['beans'])){
				api_update($req);
			}
			else{
				api_forbid();
			}
		break;

		case 'upload':
			if (in_array($req['edge'], $config['api']['beans'])){
				api_upload($req);
			}
			else{
				api_forbid();
			}
		break;

		case 'destroy':
			if (in_array($req['edge'], $config['api']['beans'])){
				api_destroy($req);
			}
			else{
				api_forbid();
			}
		break;

		default:
				api_forbid();
		break;
	};

};

/* ***************************************************************************************************
** PRIVATE RETURN FUNCTIONS **************************************************************************
*************************************************************************************************** */ 

function api_hi(){
	global $caption;

	if(!empty($caption['messages'])) {
		$res = array('message' => getMessage('HI'));
	} 
	else{
		$res = array('error' => true, 'message' => 'Arquivo de mensagens não encontrado.');
	}

	//output response
	api_output($res);

};

function api_create($req){
	global $config;

	R::begin();
	try{
	
		// dispense 'edge'
		$item = R::dispense( $req['edge'] );
		$schema['raw'] = R::getAssoc('DESCRIBE ' . $req['edge']);

		// foreach $req content, build array to insert
		foreach ($req['content'] as $field => $v) {

			// IF field defines uploads many-to-many relationship
			if($field == 'uploads_id' && in_array($req['edge'] .'_uploads', $config['api']['beans'])){
				$upload = R::dispense( 'uploads' );
				$upload->id = $v;
				$item->sharedUploadList[] = $upload;
			}

			// IF field is a password, hash it up
			else if ($field == 'password'){
				$item[$field] = md5($v);
			}

			else{
				$item[$field] = $v;			
			};
		
		};

		// inject created and modified current time to array to insert
		$item['created'] 	= R::isoDateTime();
		$item['modified'] 	= R::isoDateTime();

		// insert item, returns id if success
		R::store($item);
		$id = R::getInsertID();
		R::commit();

		// build api response array
		$res = array(
			'id' 		=> $id,
			'message' 	=> getMessage('CREATE_SUCCESS') . ' (id: '.$id.')',
		);
		
		//output response
		api_output($res);
	}
	catch(Exception $e) {
		R::rollback();
		api_error('CREATE_FAIL', $e->getMessage());
	}

};

function api_read($req){
	global $config;

	try {

		// select item
		$item = R::load( $req['edge'], $req['param'] );

		// IF item retrieved
		if(!empty($item['id'])){

			// foreach $item field, build array to response 
			foreach ($item as $field => $v) {
				if(!in_array($k, $config['schema']['default']['blacklist'])) {

					$res[$field] = $v;

					// IF field represents one-to-many relationship
					if(substr($field, -3, 3) == '_id'){
						$parentEdge = substr($field, 0, -3);
						$parent = R::load( $parentEdge , $v );

						// IF parent is retrieved
						if(!empty($parent['id'])){
							
							// foreach $parent field, build array to response 
							foreach ($parent as $parentField => $parentValue) {
								$res[$parentEdge][$v][$parentField] = $parentValue;
							};
						}
						else{
							throw new Exception('Error Processing Request (ID: '.$v.' FROM TABLE: '.$parentEdge.' NOT FOUND', 1);
						}	
					}
				};
			};

			//output response
			api_output($res);
		}
		else{
			throw new Exception('Error Processing Request (ID: '.$req['param'].' FROM TABLE: '.$req['edge'].' NOT FOUND', 1);
		}
		
	} catch (Exception $e) {
		api_error('READ_FAIL', $e->getMessage());
	}

};

function api_exists($req){
	// check if item is retrieved from database
	$exists = R::find($req['edge'],' id = '.$req['param'].' ' );
	$res['exists'] = !empty($exists) ? true : false;

	//output response
	api_output($res);
};

function api_export($req){

	try {

		// init Goodby\CSV\Export\ 
		if (class_exists('Goodby\CSV\Export\Standard\ExporterConfig')) {
			$exportConfig = new ExporterConfig();
			if (class_exists('Goodby\CSV\Export\Standard\Exporter')) {
				$exporter = new Exporter($exportConfig);
			}
			else{
				throw new Exception("CLASS NOT FOUND (Goodby\CSV\Export\Standard\Exporter)", 1);
			}
		}
		else{
			throw new Exception("CLASS NOT FOUND (Goodby\CSV\Export\Standard\ExporterConfig)", 1);
		}

		// collect data
		$rawData = R::findAll($req['edge']);
		$data = R::exportAll($rawData, FALSE, array('NULL'));

		// inject field keys to data as csv export table heading
		$keys = array_keys($data[0]);
		array_unshift($data, $keys);

		// define outstream
		$dateHash = str_replace(array(':','-',' '), '', R::isoDateTime());
		$filename = 'export-'.$req['edge'].'-'.$dateHash.'.csv';

		//outstream response
		header('Content-Type: text/csv');
		header('Content-Disposition: attachment; filename='. $filename);

		// TODO: how to export big tables? memory runs out.
		$outstream = $exporter->export('php://output', $data);
		
	} catch (Exception $e) {
		api_error('EXPORT_FAIL', $e->getMessage());
	}

};

function api_update($req){
   global $config;

	$item = R::load( $req['edge'], $req['param'] );
	$schema['raw'] = R::getAssoc('DESCRIBE '.$req['edge']);

	foreach ($req['content'] as $k => $v) {
		// IF rel uploads many-to-many relationship
		if($k == 'uploads_id' && in_array($req['edge'] .'_uploads', $config['api']['beans'])){
			
			$upload = R::dispense( 'uploads' );
			$upload->id = $v;
			$item->sharedUploadList[] = $upload;
		}
		// IF field is a password, hash it up
		else if ($k == 'password'){
			$item[$k] = md5($v);
		}
		else{
			$item[$k] = $v;			
		};
		
	};
		$item['modified'] = R::isoDateTime();

	R::store( $item );
	$res['message'] = 'Atualizado com Sucesso. (id: '.$req['param'].')';

	//output response
	api_output($res);
};

function api_updatePassword($req){
   global $config;

	$item = R::load( $req['edge'], $req['param'] );

	if( $item['password'] == md5($req['content']['password']) ){
		
		if ($req['content']['new_password'] == $req['content']['confirm_new_password']) {

			$item['password'] = md5($req['content']['new_password']);
			$item['modified'] = R::isoDateTime();
			R::store( $item );
			$res['message'] = 'Atualizado com Sucesso. (id: '.$req['param'].')';

		} else{
			$res['message'] = 'deu ruim. confirmação não bate';

		}

	} else{
		$res['message'] = 'deu ruim. password errado';

	}

	//output response
	api_output($res);
};

function api_destroy($req){

	$item = R::load( $req['edge'], $req['param'] );
    R::trash( $item );

	$res['message'] = 'Excluído com Sucesso. (id: '.$req['param'].')';

	//output response
	api_output($res);
};

function api_list($req){
   global $config;

	// LIST - list all
	if(empty($req['param'])){
		$items = R::findAll( $req['edge'] );

		if(!empty($items)){
			foreach ($items as $item => $content) {
				foreach ($content as $k => $v) {
					$res[$item][$k] = $v;
				};
			};
		} else{
			$res = array();
		}

	}

	// LIST - paginated
	else{
		
		$page 	= $req['param'];
		$limit 	= $config['api']['params']['pagination'];
		$items 	= R::findAll( $req['edge'], 'ORDER BY id DESC LIMIT '.(($page-1)*$limit).', '.$limit);

		if(!empty($items)){
			foreach ($items as $item => $content) {
				foreach ($content as $k => $v) {
					$res[$item][$k] = $v;
				};
			};			
		} else{
			$res = array();
		}

	};

	//output response
	api_output($res);
};

function api_search($req){
	$res['message'] = 'in development: action "search"';

	//output response
	api_output($res);
};

function api_count($req){
	global $config;

	// COUNT - count all
	$count = R::count( $req['edge'] );
	$limit = $config['api']['params']['pagination'];

	$res['sum'] 			= $count;
	$res['pages'] 			= round($count/$limit);
	$res['itemsPerPage'] 	= $limit;
	
	//output response
	api_output($res);
};

function api_schema($req){
	global $config;
	global $caption;

	$schema['raw'] = R::getAssoc('SHOW FULL COLUMNS FROM '.$req['edge']);

	if($schema['raw']){

		// DEFINE SCHEMA 
		$res = array(
			'bean' 					=> $req['edge'],
			'title' 				=> getCaption('edges', $req['edge'], $req['edge']),
			'icon' 					=> getCaption('icon', $req['edge'], $req['edge']),
			'type' 					=> 'object',
			'required' 				=> true,
			'additionalProperties' 	=> false,
		);

		// ADD PROPERTIES NODE TO SCHEMA
		foreach ($schema['raw'] as $field => $properties) {

			// CHECK IF FIELD IS NOT AT CONFIG BLACKLIST
			if(!in_array($field, $config['schema']['default']['blacklist'])){

				// IF FIELD DEFINES ONE-TO-MANY RELATIONSHIP
				if(substr($field, -3, 3) == '_id'){
					$parentBean = substr($field, 0, -3);
					$parent = R::getAssoc('DESCRIBE '. $parentBean);

					$res['properties'][$field] = array(
						'type' 				=> 'integer',
						'title' 			=> getCaption('fields', $req['edge'], substr($field, 0, -3)),
						'required'	 		=> true,
						'minLength'	 		=> 1,
						'enum' 				=> array(),
						'options' 			=> array(
							'enum_titles' 	=> array(),
						),
					);

					$parentOptions = R::getAssoc( 'SELECT id, name FROM '.$parentBean );

					foreach ($parentOptions as $key => $value) {
						$res['properties'][$field]['enum'][] = $key;
						$res['properties'][$field]['options']['enum_titles'][] = $value;					
					};

				}

				// ELSE, FIELD DEFINES
				else{

					// prepare data
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
					$res['properties'][$field] = array(
						'type'			=> $type,
						'format' 		=> $format,
						'title' 		=> getCaption('fields', $req['edge'], $field),
						'required'	 	=> true,
						'minLength' 	=> $minLength,
						'maxLength'		=> $maxLength
					);

					// array merge to custom properties defined at config
					if(isset($config['schema']['custom']['fields'][$field])){
						$res['properties'][$field] = array_merge($res['properties'][$field], $config['schema']['custom']['fields'][$field]);
					};

					// add '*' to field title if required.
					if($res['properties'][$field]['minLength'] > 0){
						$res['properties'][$field]['title'] = $res['properties'][$field]['title'] . '*';
					}

				};

			};

			// ADD RAW STRUCTURE NODE TO SCHEMA
			// IF FIELD DEFINES ONE-TO-MANY RELATIONSHIP
			if(substr($field, -3, 3) == '_id'){
				$parentBean = substr($field, 0, -3);
				$parent = R::getAssoc('DESCRIBE '. $parentBean);

				foreach ($parent as $field => $properties) {
					$res['structure'][$parentBean] = array(
						'field' 		=> $field,
						'properties' 	=> $properties,
					);
				}
			}
			// ELSE, FIELD DEFINES
			else{
				$res['structure'][$field] = array(
					'field' 		=> $field,
					'properties' 	=> $properties,
				);
			};
		};

		// IF _UPLOADS MANY-TO-MANY RELATIONSHIP EXISTS
		if(in_array($req['edge'] .'_uploads', $config['api']['beans'])){
		
			$res['properties']['uploads_id'] = 
				
				array(
					'title' 	=> 'Imagem',
					'type'		=> 'string',
					'format' 	=> 'url',
					'required'	=> true,
					'minLength' => 0,
					'maxLength' => 128,
	  				'options'	=> array(
	  					'upload' 	=> true,
	  				),
					'links' 	=> array(
						array(
				            'rel' 	=> '',
							'href' 	=> '{{self}}',
						),
					),
				);
		}

		//output response
		api_output($res);
	}

	else{
		api_error('INVALID_SCHEMA');
	}

}

function api_edges(){
   global $config;
   global $caption;

	// BUILD HIERARCHY, IF EXISTS
	$hierarchy = array();
	$hierarchyArr = R::getAll('
		SELECT TABLE_NAME as child, REFERENCED_TABLE_NAME as parent
		FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
		WHERE REFERENCED_TABLE_NAME IS NOT NULL
	');

	foreach ($hierarchyArr as $key => $value) {
		if( empty($hierarchy[$value['child']]) ){
			$hierarchy[$value['child']] = array();
		} 
		array_push($hierarchy[$value['child']], $value['parent']);
	}

	// BUILD BEANS LIST
	foreach ($config['api']['beans'] as $k => $v) {

		if( !in_array($v, $config['api']['edges']['blacklist']) ) {

			$beans[$v] = array(
				'name' 			=> $v,
				'title' 		=> getCaption('edges', $v, $v),
				'count' 		=> R::count($v),
				'icon' 			=> getCaption('icon', $v, $v),
				'has_parent' 	=> false,
				'has_child' 	=> false,
			);

		};

	};

	// BUILD HIERARCHY LIST - DEPTH 1
	foreach ($beans as $bean => $obj) {
		if( array_key_exists($bean, $hierarchy) ){

			$beans[$bean]['has_parent'] = true;

			foreach ($hierarchy[$bean] as $y => $z) {
				$beans[$z]['has_child'] = true;
				$beans[$bean]['parent'][$z] = $beans[$z];
			}

		};
	};
	// BUILD HIERARCHY LIST - DEPTH 2
	foreach ($beans as $bean => $obj) {
		if($beans[$bean]['has_parent']){

			foreach ($beans[$bean]['parent'] as $parentBean => $parentObj) {

				if( array_key_exists($parentBean, $hierarchy) ){

					$beans[$bean]['parent'][$parentBean]['has_parent'] = true;

					foreach ($hierarchy[$parentBean] as $y => $z) {
						$beans[$bean]['parent'][$parentBean]['parent'][$z] = $beans[$z];
					}

				}
			}

		};

	};

	$res['beans'] 		= $beans;
	$res['actions'] 	= $config['api']['actions'];

	api_output($res);
};

function api_upload($req){

	// var definition
	$data = $req['content']['blob'];

	list($type, $data) 	= explode(';', $data);
	list(, $data)      	= explode(',', $data);
	$data = base64_decode($data);
	$type = explode(':', $type);
	$type = $type[1];

	$basePath 		= '../uploads/';
	$chronoPath 	= str_replace('-', '/', R::isoDate()) . '/';
	$fullPath 		= $basePath . $chronoPath;
	$filename 			= $req['content']['filename'];
	$md5Filename 		= md5($filename) . '.' . end(explode('.', $filename));

	// build insert array
	$file = array(
		'path' 		=> $chronoPath . $md5Filename,
		'filename' 	=> $md5Filename,
		'type' 		=> $type,
		'size' 		=> $req['content']['filesize'],
		'edge' 		=> $req['edge'],
		'created' 	=> R::isoDateTime(),
		'modified' 	=> R::isoDateTime(),
	);

	// write file
	if (!file_exists($fullPath)) {
		mkdir( $fullPath, 0777, true );
	};

	file_put_contents($fullPath . $filename, $data);

	// insert at database
	$upload = R::dispense('uploads');
	
	foreach ($file as $k => $v) {
		$upload[$k] = $v;
	};

	$id = R::store($upload);
	$res['id'] = $id;
	$res['message'] = 'Criado com Sucesso. (id: '.$id.')';

	//output response
	api_output($res);

};

?>