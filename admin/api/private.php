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
			if (in_array($req['edge'], $api['edges'])){
				api_search($req);
			}
			else{
				api_forbid();
			}
		break;

		case 'read':
			if (in_array($req['edge'], $api['edges']) && !empty($req['param'])){
				api_read($req);
			}
			else{
				api_forbid();
			}
		break;

		case 'exists':
			if (in_array($req['edge'], $api['edges']) && !empty($req['param'])){
				api_exists($req);
			}
			else{
				api_forbid();
			}
		break;

		case 'list':
			if (in_array($req['edge'], $api['edges'])){
				api_list($req);
			}
			else{
				api_forbid();
			}
		break;

		case 'count':
			if (in_array($req['edge'], $api['edges']) && empty($req['param'])){
				api_count($req);
			}
			else{
				api_forbid();
			}
		break;

		case 'schema':
			if (in_array($req['edge'], $api['edges'])){
				api_schema($req);
			}
			else{
				api_forbid();
			}
		break;		

		case 'export':
			if (in_array($req['edge'], $api['edges'])){
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
			if (in_array($req['edge'], $api['edges'])){
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
			if (in_array($req['edge'], $api['edges'])){
				api_update($req);
			}
			else{
				api_forbid();
			}
		break;

		case 'upload':
			if (in_array($req['edge'], $api['edges'])){
				api_upload($req);
			}
			else{
				api_forbid();
			}
		break;

		case 'destroy':
			if (in_array($req['edge'], $api['edges'])){
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
	global $api;

	R::begin();
	try{
	
		// dispense 'edge'
		$item = R::dispense( $req['edge'] );
		$schema['raw'] = R::getAssoc('DESCRIBE ' . $req['edge']);

		// foreach $req content, build array to insert
		foreach ($req['content'] as $field => $v) {

			// IF field defines uploads many-to-many relationship
			if($field == 'uploads_id' && in_array($req['edge'] .'_uploads', $api['edges'])){
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
	global $api;

	R::begin();
	try {
		// dispense 'edge'
		$id = $req['param'];
		$item = R::load( $req['edge'], $id );
		$schema['raw'] = R::getAssoc('DESCRIBE '.$req['edge']);

		// foreach $req content, build array to update
		foreach ($req['content'] as $field => $v) {
			
			// IF field defines uploads many-to-many relationship
			if($field == 'uploads_id' && in_array($req['edge'] .'_uploads', $api['edges'])){
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

		// inject modified current time to array to update
		$item['modified'] = R::isoDateTime();

		// update item, commit if success
		R::store( $item );
		R::commit();

		// build api response array
		$res = array(
			'id' 		=> $id,
			'message' 	=> getMessage('UPDATE_SUCCESS') . ' (id: '.$id.')',
		);

		//output response
		api_output($res);
		
	} catch (Exception $e) {
		R::rollback();
		api_error('UPDATE_FAIL', $e->getMessage());
	}

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

	R::begin();
	try {
		// dispense 'edge'
		$id = $req['param'];
		$item = R::load( $req['edge'], $id );

		// destroy item, commit if success
	    R::trash($item);
		R::commit();

		// build api response array
		$res = array(
			'id' 		=> $id,
			'message' 	=> getMessage('DESTROY_SUCCESS') . ' (id: '.$id.')',
		);

		// output response
		api_output($res);

	} catch (Exception $e) {
		R::rollback();
		api_error('DESTROY_FAIL', $e->getMessage());		
	}

};

function api_list($req){
	global $config;

	try {

		// check if request is paginated or ALL
		if(!empty($req['param'])){
			// param page exists, let's get this page 
			$page 	= $req['param'];
			$limit 	= $config['api']['params']['pagination'];
			$items 	= R::findAll( $req['edge'], 'ORDER BY id DESC LIMIT '.(($page-1)*$limit).', '.$limit);
		}else{
			// param page doesn't exist, let's get all
			$items = R::findAll( $req['edge'], 'ORDER BY id DESC' );
		}

		// check if list is not empty
		if(!empty($items)){
			// list is not empty, let's foreach and build response array
			foreach ($items as $item => $content) {
				foreach ($content as $field => $value) {
					$res[$item][$field] = $value;
				};
			};
		}
		else{
			// list is empty, let's return empty array
			$res = array();
		}

		// output response
		api_output($res);

	} catch (Exception $e) {
		api_error('LIST_FAIL', $e->getMessage());
	}

};

function api_count($req){
	global $config;

	try {
		// define response vars
		$count = R::count($req['edge']);
		$limit = $config['api']['params']['pagination'];

		// build response array
		$res = array(
			'sum' 			=> $count,
			'pages' 		=> round($count/$limit),
			'itemsPerPage' 	=> $limit
		);

		// output response
		api_output($res);
		
	} catch (Exception $e) {
		api_error('COUNT_FAIL', $e->getMessage());
	}

};

function api_schema($req){
	global $config;
	global $caption;
	global $api;

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
		if(in_array($req['edge'] .'_uploads', $api['edges'])){
		
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
	global $api;
	global $config;

	try {

		// build edges list
		if (!empty($api['edges'])) {
			$edges = array();
			foreach ($api['edges'] as $k => $edge) {
				if( !in_array($edge, $config['api']['edges']['blacklist']) ) {

					$edges[$edge] = array(
						'name' 			=> $edge,
						'title' 		=> getCaption('edges', $edge, $edge),
						'count' 		=> R::count($edge),
						'icon' 			=> getCaption('icon', $edge, $edge),
						'has_parent' 	=> false,
						'has_child' 	=> false,
					);

				};
			};
		}
		else {
			throw new Exception('Error Processing Request', 1);
		}

		// build hierarchy array, if exists		
		$hierarchyArr = R::getAll('
			SELECT TABLE_NAME as child, REFERENCED_TABLE_NAME as parent
			FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
			WHERE REFERENCED_TABLE_NAME IS NOT NULL
		');

		if(!empty($hierarchyArr)){
			foreach ($hierarchyArr as $k => $v) {
				if(empty($hierarchy[$v['child']])){
					$hierarchy[$v['child']] = array();
				} 
				array_push($hierarchy[$v['child']], $v['parent']);
			};
		}
		else{
			$hierarchy = array();
		}

		// if not empty hierarchy, build depth
		if(!empty($hierarchy)){

			// build hierarchy list - depth 1
			foreach ($edges as $edge => $obj) {
				if( array_key_exists($edge, $hierarchy) ){
					$edges[$edge]['has_parent'] = true;

					foreach ($hierarchy[$edge] as $y => $z) {
						$edges[$z]['has_child'] = true;
						$edges[$edge]['parent'][$z] = $edges[$z];
					}

					// build hierarchy list - depth 2
					if($edges[$edge]['has_parent']){
						foreach ($edges[$edge]['parent'] as $parentBean => $parentObj) {
							if(array_key_exists($parentBean, $hierarchy)){
								$edges[$edge]['parent'][$parentBean]['has_parent'] = true;
								foreach ($hierarchy[$parentBean] as $y => $z) {
									$edges[$edge]['parent'][$parentBean]['parent'][$z] = $edges[$z];
								}
							}
						}
					};

				};
			};

		}

		// build api response array
		$res = array(
			'edges' 	=> $edges,
			'actions' 	=> $config['api']['actions'],
		);

		// output response
		api_output($res);

	} 
	catch (Exception $e) {
		api_error('EDGES_FAIL', $e->getMessage());
	}

};

function api_upload($req){
	global $config;

	R::begin();
	try{

		// if blob exists at req, define vars
		if (!empty($req['content']['blob'])) {
			$blob = $req['content']['blob'];

			list($type, $blob) 	= explode(';', $blob);
			list(, $blob)      	= explode(',', $blob);

//			$data = base64_decode(preg_replace('data:[^;]+;base64,', '', $data));

//"data:image/gif;base64,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
			$blob = base64_decode($blob);
			$type = explode(':', $type);
			$type = $type[1];

			$basePath 		= '../uploads/';
			$chronoPath 	= str_replace('-', '/', R::isoDate()) . '/';
			$fullPath 		= $basePath . $chronoPath;
			$filename 		= $req['content']['filename'];
			$md5Filename 	= md5($filename) . '.' . end(explode('.', $filename));

		}
		else{
			throw new Exception("Error Processing Request (blob not found at request)", 1);
		}


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

		file_put_contents($fullPath . $filename, $blob);

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
		api_error('UPLOAD_FAIL', $e->getMessage());
	}

};


function api_search($req){
	api_error('SEARCH_SOON');
};

?>