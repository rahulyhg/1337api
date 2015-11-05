<?php
use \Firebase\JWT\JWT;
use Goodby\CSV\Export\Standard\Exporter;
use Goodby\CSV\Export\Standard\ExporterConfig;

/* ***************************************************************************************************
** PRIVATE API VALIDATE REQUEST FUNCTIONS ************************************************************
*************************************************************************************************** */ 
/*
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
*/

/* ***************************************************************************************************
** GET FUNCTIONS *************************************************************************************
*************************************************************************************************** */ 

function api_hi ($request, $response, $args) {
	global $caption;

	try {
		if(!empty($caption['messages'])) {

			// build api response payload
			$payload = array(
				'message' => getMessage('HI')
			);
			
			// output response payload
			$response->withJson($payload);

		} 
		else{
			throw new Exception("Arquivo de mensagens não encontrado.", 1);
		}
		
	} catch (Exception $e) {
		throw new Exception("Arquivo de mensagens não encontrado.", 1);
	}
};

function api_edges ($request, $response, $args) {
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

		// build api response payload
		$payload = array(
			'edges' 	=> $edges,
			'actions' 	=> $config['api']['actions'],
		);

		// output response playload
		$response->withJson($payload);

	} 
	catch (Exception $e) {
		api_error($response, 'EDGES_FAIL', $e->getMessage());
	}
};

function api_list ($request, $response, $args) {
	global $config;

	try {

		// check if request is paginated or ALL
		if(!empty($args['page'])){
			// param page exists, let's get this page 
			$page 	= $args['page'];
			$limit 	= $config['api']['params']['pagination'];
			$items 	= R::findAll( $args['edge'], 'ORDER BY id DESC LIMIT '.(($page-1)*$limit).', '.$limit);
		}else{
			// param page doesn't exist, let's get all
			$items = R::findAll( $args['edge'], 'ORDER BY id DESC' );
		}

		// check if list is not empty
		if(!empty($items)){
			// list is not empty, let's foreach and build response array
			foreach ($items as $item => $content) {
				foreach ($content as $field => $value) {
					$payload[$item][$field] = $value;
				};
			};
		}
		else{
			// list is empty, let's return empty array
			$payload = array();
		}

		// output response
		$response->withJson($payload);

	} catch (Exception $e) {
		api_error($response, 'LIST_FAIL', $e->getMessage());
	}
};

function api_count ($request, $response, $args) {
	global $config;

	try {
		// define response vars
		$count = R::count($args['edge']);
		$limit = $config['api']['params']['pagination'];

		// build response payload
		$payload = array(
			'sum' 			=> $count,
			'pages' 		=> ceil($count/$limit),
			'itemsPerPage' 	=> $limit
		);

		// output response payload
		$response->withJson($payload);
		
	} catch (Exception $e) {
		api_error($response, 'COUNT_FAIL', $e->getMessage());
	}
};

function api_export ($request, $response, $args) {

	try {

		// TODO:
		// <b>Strict Standards</b>:  Declaration of Goodby\CSV\Export\Standard\CsvFileObject::fputcsv() should be compatible with SplFileObject::fputcsv($fields, $delimiter = NULL, $enclosure = NULL, $escape = NULL) in <b>/Volumes/DATA/Dev/umstudio/ums_redbean/api/vendor/goodby/csv/src/Goodby/CSV/Export/Standard/CsvFileObject.php</b> on line <b>84</b><br />

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
		$rawData = R::findAll($args['edge']);
		$data = R::exportAll($rawData, FALSE, array('NULL'));

		// inject field keys to data as csv export table heading
		$keys = array_keys($data[0]);
		array_unshift($data, $keys);

		// define outstream
		$dateHash = str_replace(array(':','-',' '), '', R::isoDateTime());
		$filename = 'export-'.$args['edge'].'-'.$dateHash.'.csv';

		//outstream response
		header('Content-Type: text/csv');
		header('Content-Disposition: attachment; filename='. $filename);

		// TODO: how to export big tables? memory runs out.
		$outstream = $exporter->export('php://output', $data);
		
	} catch (Exception $e) {
		api_error($response, 'EXPORT_FAIL', $e->getMessage());
	}
};

function api_schema ($request, $response, $args) {

	global $api;
	global $config;

	try {

		// read database schema
		$schema['raw'] = R::getAssoc('SHOW FULL COLUMNS FROM '.$args['edge']);

		// if raw schema found
		if(!empty($schema['raw'])){

			// define schema response array
			$payload = array(
				'bean' 					=> $args['edge'],
				'title' 				=> getCaption('edges', $args['edge'], $args['edge']),
				'icon' 					=> getCaption('icon', $args['edge'], $args['edge']),
				'type' 					=> 'object',
				'required' 				=> true,
				'additionalProperties' 	=> false,
				'properties' 			=> array(),
			);

			// fill properties node into schema response array
			foreach ($schema['raw'] as $field => $properties) {

				// check if field is not at config blacklist
				if(!in_array($field, $config['schema']['default']['blacklist'])){

					// check if field defines one-to-many relationship
					if(substr($field, -3, 3) == '_id'){
						$parent = substr($field, 0, -3);

						$payload['properties'][$field] = array(
							'type' 				=> 'integer',
							'title' 			=> getCaption('fields', $payload['bean'], $parent),
							'required'	 		=> true,
							'minLength'	 		=> 1,
							'enum' 				=> array(),
							'options' 			=> array(
								'enum_titles' 	=> array(),
							),
						);

						$parentOptions = R::getAssoc( 'SELECT id, name FROM '.$parent );

						foreach ($parentOptions as $key => $value) {
							$payload['properties'][$field]['enum'][] = $key;
							$payload['properties'][$field]['options']['enum_titles'][] = $value;
						};

					}

					// else, field is literal and we can go on
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
						$payload['properties'][$field] = array(
							'type'			=> $type,
							'format' 		=> $format,
							'title' 		=> getCaption('fields', $args['edge'], $field),
							'required'	 	=> true,
							'minLength' 	=> $minLength,
							'maxLength'		=> $maxLength
						);

						// array merge to custom properties defined at config
						if(isset($config['schema']['custom']['fields'][$field])){
							$payload['properties'][$field] = array_merge($payload['properties'][$field], $config['schema']['custom']['fields'][$field]);
						};

						// add '*' to field title if required.
						if($payload['properties'][$field]['minLength'] > 0){
							$payload['properties'][$field]['title'] = $payload['properties'][$field]['title'] . '*';
						}

					};

				};

				// ADD RAW STRUCTURE NODE TO SCHEMA
				// IF FIELD DEFINES ONE-TO-MANY RELATIONSHIP
				if(substr($field, -3, 3) == '_id'){
					$parentBean = substr($field, 0, -3);
					$parent = R::getAssoc('DESCRIBE '. $parentBean);

					foreach ($parent as $field => $properties) {
						$payload['structure'][$parentBean] = array(
							'field' 		=> $field,
							'properties' 	=> $properties,
						);
					}
				}
				// ELSE, FIELD DEFINES
				else{
					$payload['structure'][$field] = array(
						'field' 		=> $field,
						'properties' 	=> $properties,
					);
				};
			};

			// IF _UPLOADS MANY-TO-MANY RELATIONSHIP EXISTS
			if(in_array($args['edge'] .'_uploads', $api['edges'])){
			
				$payload['properties']['uploads_id'] = 
					
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
			$response->withJson($payload);
		}

		else{
			throw new Exception("Error Processing Request (edge raw schema not found)", 1);
		}

	} catch (Exception $e) {
		api_error($response, 'SCHEMA_FAIL', $e->getMessage());
	}
};

function api_read ($request, $response, $args) {
	global $config;
	
	try {
		// load item
		$item = R::load( $args['edge'], $args['id'] );

		// if item retrieved
		if(!empty($item['id'])){

			// foreach $item field, build response payload array
			foreach ($item as $field => $value) {
				if(!in_array($field, $config['schema']['default']['blacklist'])) {

					$payload[$field] = $value;

					// IF field represents one-to-many relationship
					if(substr($field, -3, 3) == '_id'){
						$parentEdge = substr($field, 0, -3);
						$parent = R::load( $parentEdge, $value );

						// IF parent is retrieved
						if(!empty($parent['id'])){
							
							// foreach $parent field, add to response payload array
							foreach ($parent as $parentField => $parentValue) {
								$payload[$parentEdge][$value][$parentField] = $parentValue;
							};
						}
						else{
							throw new Exception('Error Processing Request (ID: '.$value.' FROM TABLE: '.$parentEdge.' NOT FOUND', 1);
						}	
					}
				}
			};

			//output response payload
			$response->withJson($payload);
		}
		else{
			throw new Exception('Error Processing Request (ID: '.$args['id'].' FROM TABLE: '.$args['edge'].' NOT FOUND', 1);
		}
		
	} catch (Exception $e) {
		api_error($response, 'READ_FAIL', $e->getMessage());
	}
};

function api_exists ($request, $response, $args) {

	// check if item is retrieved from database
	$item = R::find( $args['edge'], ' id = '.$args['id'] );
	$exists = !empty($item) ? true : false;

	// build api response payload
	$payload = array('exists' => $exists);
	// output response payload
	$response->withJson($payload);
};

function api_soon ($request, $response, $args) {

	// build api response payload
	$payload = array('message' => getMessage('COMING_SOON'));
	// output response payload
	$response->withJson($payload);
};

/* ***************************************************************************************************
** POST FUNCTIONS ************************************************************************************
*************************************************************************************************** */ 

function api_create ($request, $response, $args) {
	global $api;

	R::begin();
	try{
	
		// dispense 'edge'
		$item = R::dispense( $args['edge'] );
		$formData = $request->getParsedBody();
		$schema['raw'] = R::getAssoc('DESCRIBE ' . $args['edge']);

		// foreach $req content, build array to insert
		foreach ($formData as $field => $v) {

			// IF field defines uploads many-to-many relationship
			if($field == 'uploads_id' && in_array($args['edge'] .'_uploads', $api['edges'])){
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
		$payload = array(
			'id' 		=> $id,
			'message' 	=> getMessage('CREATE_SUCCESS') . ' (id: '.$id.')',
		);
		
		//output response
		$response->withJson($payload);
	}
	catch(Exception $e) {
		R::rollback();
		api_error($response, 'CREATE_FAIL', $e->getMessage());
	}
};

function api_update ($request, $response, $args) {
	global $api;

	R::begin();
	try {
		// dispense 'edge'
		$id = $args['id'];
		$item = R::load( $args['edge'], $id );
		$formData = $request->getParsedBody();
		$schema['raw'] = R::getAssoc('DESCRIBE '.$args['edge']);

		// foreach $req content, build array to update
		foreach ($formData as $field => $v) {
			
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
		$payload = array(
			'id' 		=> $id,
			'message' 	=> getMessage('UPDATE_SUCCESS') . ' (id: '.$id.')',
		);

		//output response
		$response->withJson($payload);
		
	} catch (Exception $e) {
		R::rollback();
		api_error($response, 'UPDATE_FAIL', $e->getMessage());
	}
};

function api_updatePassword ($request, $response, $args) {

	global $config;

	$item = R::load( 'user', $args['id'] );
	$formData = $request->getParsedBody();

	if( $item['password'] == md5($formData['password']) ){
		
		if ($formData['new_password'] == $formData['confirm_new_password']) {

			$item['password'] = md5($formData['new_password']);
			$item['modified'] = R::isoDateTime();
			R::store( $item );
			$payload['message'] = 'Atualizado com Sucesso. (id: '.$args['id'].')';

		} else{
			$payload['message'] = 'deu ruim. confirmação não bate';

		}

	} else{
		$payload['message'] = 'deu ruim. password errado';

	}

	//output response
	$response->withJson($payload);
};

function api_destroy ($request, $response, $args) {

	R::begin();
	try {
		// dispense 'edge'
		$id = $args['id'];
		$item = R::load( $args['edge'], $id );
		$formData = $request->getParsedBody();

		// destroy item, commit if success
	    R::trash($item);
		R::commit();

		// build api response array
		$payload = array(
			'id' 		=> $id,
			'message' 	=> getMessage('DESTROY_SUCCESS') . ' (id: '.$id.')',
		);

		// output response
		$response->withJson($payload);

	} catch (Exception $e) {
		R::rollback();
		api_error($response, 'DESTROY_FAIL', $e->getMessage());		
	}
};

function api_upload ($request, $response, $args) {

	global $config;

	R::begin();
	try{

		// get parsedbody
		$formData = $request->getParsedBody();

		// validate content from $req
			// if blob was sent
			if (!empty($formData['blob'])) {
				$blob = $formData['blob'];
			} 
			else{
				throw new Exception("Error Processing Request (blob not found at request)", 1);
			}

			// if filesize was sent
			if (!empty($formData['filesize'])) {
				$filesize = $formData['filesize'];
			} 
			else{
				throw new Exception("Error Processing Request (filesize not found at request)", 1);
			}

			// if filename was sent
			if (!empty($formData['filename'])) {
				$filename = $formData['filename'];
			} 
			else{
				throw new Exception("Error Processing Request (filename not found at request)", 1);
			}

		// explode $blob
			// TODO: this procedure can be done in one line if I use regex. verify correct expression.
			list($type, $blob) = explode(';', $blob);
			list(,$type) = explode(':', $type);
			list(,$blob) = explode(',', $blob);

			// decode blob data
			$data = base64_decode($blob);

		// define path and new filename
			$basepath 	= $config['api']['uploads']['basepath'];
			$datepath 	= str_replace('-', '/', R::isoDate()) . '/';
			$fullpath 	= $basepath . $datepath;
			$hashname 	= md5($filename.'-'.R::isoDateTime()) . '.' . end(explode('.', $filename));

		// write file
			// TODO: we should validate if the file was actually saved at disk.
			// if folder doesn't exist, mkdir 
			if (!file_exists($fullpath)) {
				mkdir( $fullpath, 0777, true );
			};
			file_put_contents($fullpath . $hashname, $data);

		// insert at database
			
			// build insert upload array
			$upload = array(
				'path' 		=> $datepath . $hashname,
				'filename' 	=> $hashname,
				'type' 		=> $type,
				'size' 		=> $filesize,
				'edge' 		=> $args['edge'],
				'created' 	=> R::isoDateTime(),
				'modified' 	=> R::isoDateTime(),
			);

			// dispense uploads edge
			$file = R::dispense('uploads');

			foreach ($upload as $k => $v) {
				$file[$k] = $v;
			}			

			R::store($file);
			$id = R::getInsertID();
			R::commit();

		// build api response array
			$payload = array(
				'id' 		=> $id,
				'message' 	=> getMessage('UPLOAD_SUCCESS') . ' (id: '.$id.')',
			);

		//output response
			$response->withJson($payload);
	}
	catch(Exception $e) {
		R::rollback();
		api_error($response, 'UPLOAD_FAIL', $e->getMessage());
	}
};


/* ***************************************************************************************************
** ERROR FUNCTIONS ***********************************************************************************
*************************************************************************************************** */ 

// ERROR OUTPUT
function api_error($response, $msg, $debug = ''){
	global $config;

	$err = array(
		'error' => true, 
		'message' => getMessage($msg)
	);
	
	if($config['api']['debug']){
		$err['debug'] = $debug;
	};	

	return $response->withStatus(400)->withJson($err);
};


// FORBIDDEN OUTPUT
function api_forbid(){
	header('HTTP/1.0 400 Bad Request');
	$res = array('error' => true, 'message' => getMessage('INVALID_REQUEST'));
	api_output($res);
};

?>