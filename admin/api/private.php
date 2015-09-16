<?php
use \Firebase\JWT\JWT;
use Goodby\CSV\Export\Standard\Exporter;
use Goodby\CSV\Export\Standard\ExporterConfig;

/* ***************************************************************************************************
** API AUTH FUNCTIONS ********************************************************************************
*************************************************************************************************** */ 

function api_validateToken($authHeader){
   global $config;

	// Look for the 'authorization' header
	if($authHeader){

		// Extract the jwt from the Bearer
		list($jwt) = sscanf( $authHeader, 'Bearer %s');

		if ($jwt) {

			try {	
				// decode the jwt using the key from config
				$secretKey 	= base64_decode($config['auth']['jwtKey']);
				$token 		= JWT::decode($jwt, $secretKey, array('HS512'));
				return true;
			} 
			catch (Exception $e) {
				// the token was not able to be decoded.
				// this is likely because the signature was not able to be verified (tampered token)
				header('HTTP/1.0 401 Unauthorized');
				return false;
			}
		} 
		else {
			// No token was able to be extracted from the authorization header
			header('HTTP/1.0 401 Unauthorized');
			return false;
		}

	} 
	else {
		// The request lacks the authorization token
		header('HTTP/1.0 401 Unauthorized');
		echo 'Token not found in request';
		return false;
	}

}

// check authorization headers
$headers = apache_request_headers();
	
if(array_key_exists('Authorization', $headers) && api_validateToken($headers['Authorization']) ){
	$auth = true;
}
else{
	$auth = false;
	header('HTTP/1.0 401 Unauthorized');
	die();
};

// check method headers
if ( empty($req) || !in_array($_SERVER['REQUEST_METHOD'], ['GET','POST']) ) {
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
			} else {
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

		case 'update':
			if (in_array($req['edge'], $config['api']['beans'])){
				api_update($req);
			}
			else{
				api_forbid();
			}
		break;

		case 'updatePassword':
			if ($req['edge'] == "user" && !empty($req['param'])){
				api_updatePassword($req);
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
		$res = array(
			'message' => getMessage('HI'),
		);
	} 
	else{
		$res = array(
			'error' => true,
			'message' => 'Arquivo de mensagens não encontrado.'
		);
	}

	// OUTPUT
	api_output($res);

};

function api_create($req){
	global $config;

	$item = R::dispense( $req['edge'] );
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

	$item['created'] 	= R::isoDateTime();
	$item['modified'] 	= R::isoDateTime();

	$id = R::store($item);

	if($id){
		$res['message'] = 'Criado com Sucesso. (id: '.$id.')';

		// OUTPUT
		api_output($res);
	}
	else{
		api_error('CREATE_FAIL');
	}

};

function api_read($req){
   global $config;

	// READ - view one
	$item = R::load( $req['edge'], $req['param'] );

	foreach ($item as $k => $v) {
		if(!in_array($k, $config['schema']['default']['blacklist'])) {

			$res[$k] = $v;

			// IF ONE-TO-MANY RELATIONSHIP
			if(substr($k, -3, 3) == '_id'){
				$parentBean = substr($k, 0, -3);
				$parent = R::load( $parentBean , $v );
				
				foreach ($parent as $key => $value) {
					$res[$parentBean][$v][$key] = $value;
				};
			}
		
		};
	};

	// OUTPUT
	api_output($res);
};

function api_exists($req){
   global $config;

	// EXISTS?
	$exists = R::find($req['edge'],' id = '.$req['param'].' ' );

	if( empty( $exists ) )
	{
		$res['exists'] = false;
	}
	else{
		$res['exists'] = true;
	}

	// OUTPUT
	api_output($res);
};

function api_export($req){
   global $config;

	$config = new ExporterConfig();
	$exporter = new Exporter($config);

    $bean = R::findAll( $req['edge'] );
    $rawData = R::exportAll($bean, false, array('part'));

	// OUTPUT
	$dateHash = str_replace(array(':','-',' '), '', R::isoDateTime());
	$name = 'export-'.$req['edge'].'-'.$dateHash.'.csv';
    
	header('Cache-Control: max-age=60, must-revalidate');
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename='. $name);
    header('Pragma: no-cache');
    header("Expires: 0");
    $outstream = $exporter->export('php://output', $rawData);
    fclose($outstream);

    exit();
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

	// OUTPUT
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

	// OUTPUT
	api_output($res);
};

function api_destroy($req){

	$item = R::load( $req['edge'], $req['param'] );
    R::trash( $item );

	$res['message'] = 'Excluído com Sucesso. (id: '.$req['param'].')';

	// OUTPUT
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
		$items 	= R::findAll( $req['edge'], 'ORDER BY id LIMIT '.(($page-1)*$limit).', '.$limit);

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

	// OUTPUT
	api_output($res);
};

function api_search($req){
	$res['message'] = 'in development: action "search"';

	// OUTPUT
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
	
	// OUTPUT
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

		// OUTPUT
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

	// OUTPUT
	api_output($res);

};

?>