<?php
error_reporting(-1);
ini_set('error_reporting', E_ALL);
error_reporting(E_ALL & ~E_NOTICE);

/* ***************************************************************************************************
** INIT **********************************************************************************************
*************************************************************************************************** */ 
require __DIR__ . '/vendor/autoload.php';
use \Firebase\JWT\JWT;
use Goodby\CSV\Export\Standard\Exporter;
use Goodby\CSV\Export\Standard\ExporterConfig;

// docs: http://www.sitepoint.com/php-authorization-jwt-json-web-tokens/
// 		 http://www.toptal.com/web/cookie-free-authentication-with-json-web-tokens-an-example-in-laravel-and-angularjs
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

			case 'export':
				if (in_array($request['edge'], $config['api']['beans'])){
					api_export($request, $config);
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

	if($_SERVER['QUERY_STRING'] == 'action=signin'){
		$request['action'] = 'signin';
		$request['content']	 = json_decode(file_get_contents("php://input"),true);

	}
	else{
		$arrayReqUri = explode('/', $_SERVER['REQUEST_URI']);

		$request = array(
			'action'	 => $arrayReqUri[sizeof($arrayReqUri) - 2],
			'edge'		 => $arrayReqUri[sizeof($arrayReqUri) - 1],
			'content'	 => json_decode(file_get_contents("php://input"),true)
		);
	}

	switch($request['action']) {

		case 'signin':
				api_signin($request, $config);
		break;

		case 'create':
			if (in_array($request['edge'], $config['api']['beans'])){
				api_create($request, $config);
			}
			else{
				api_forbidden($config);
			}
		break;

		case 'upload':
			if (in_array($request['edge'], $config['api']['beans'])){
				api_upload($request);
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
				api_update($request, $config);
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

function api_signin($request, $config){

	// validate credentials
	$userCredentials = array(
		'email' => $request['content']['email'],
		'password' => md5($request['content']['password']),
	);

	$user = R::findOne('user', 'email = :email and password = :password and active = true', $userCredentials );

	if(!empty($user)){

		$tokenId    = base64_encode(mcrypt_create_iv(32));
		$issuedAt   = R::isoDateTime();
		$notBefore  = $issuedAt + 10;             //Adding 10 seconds
		$expire     = $notBefore + 60;            // Adding 60 seconds
		$serverName = 'serverName'; // Retrieve the server name from config file
    
    /*
     * Create the token as an array
     */
    $data = [
        'iat'  => $issuedAt,         // Issued at: time when the token was generated
        'jti'  => $tokenId,          // Json Token Id: an unique identifier for the token
        'iss'  => $serverName,       // Issuer
        'nbf'  => $notBefore,        // Not before
        'exp'  => $expire,           // Expire
        'data' => [                  // Data related to the signer user
            'userId'   => $user['id'], // userid from the users table
            'userName' => $userCredentials['email'], // User name
        ]
    ];

/*
     * Extract the key, which is coming from the config file. 
     * 
     * Best suggestion is the key to be a binary string and 
     * store it in encoded in a config file. 
     *
     * Can be generated with base64_encode(openssl_random_pseudo_bytes(64));
     *
     * keep it secure! You'll need the exact key to verify the 
     * token later.
     */
    $secretKey = base64_decode($config['auth']['jwtKey']);
    
    /*
     * Encode the array to a JWT string.
     * Second parameter is the key to encode the token.
     * 
     * The output string can be validated at http://jwt.io/
     */
    $jwt = JWT::encode(
        $data,      //Data to be encoded in the JWT
        $secretKey, // The signing key
        'HS512'     // Algorithm used to sign the token, see https://tools.ietf.org/html/draft-ietf-jose-json-web-algorithms-40#section-3
        );
        
    $unencodedArray = ['jwt' => $jwt];
 
    $result['token'] = $jwt;


	}
	else{
		$result['msg'] = 'Usuário inválido!';
		$result['HttpResponse'] = 'HTTP_UNAUTHORIZED';

	}	





	
//	$userExists = R::find( 'user', $userCredentials);
//	$result['userExists'] = $userExists;

//	if(!empty($userExists){
//		$result['msg'] = 'Usuário válido!';
//	}
//	else{
//		$result['msg'] = 'Usuário inválido!';
//	}	


//	$result['user'] = $user;


//	$result['userExists'] = R::find('user',' email = '.$request['content']['email']);



//	if ( ! $token = JWTAuth::attempt($credentials)) {
//		return Response::json(false, HttpResponse::HTTP_UNAUTHORIZED);
//	}
//
//	return Response::json(compact('token'));


	// OUTPUT
	api_output($result);

};

function api_create($request, $config){
	$item = R::dispense( $request['edge'] );
	$schema['raw'] = R::getAssoc('DESCRIBE '.$request['edge']);

	foreach ($request['content'] as $k => $v) {

		// IF rel uploads many-to-many relationship
		if($k == 'uploads_id' && in_array($request['edge'] .'_uploads', $config['api']['beans'])){
			
			$upload = R::dispense( 'uploads' );
			$upload->id = $v;
			$item->sharedUploadList[] = $upload;
		}
		else{
			$item[$k] = $v;			
		}		
	};

	$item['created'] 	= R::isoDateTime();
	$item['modified'] 	= R::isoDateTime();

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

			$result[$k] = $v;

			// IF ONE-TO-MANY RELATIONSHIP
			if(substr($k, -3, 3) == '_id'){
				$parentBean = substr($k, 0, -3);
				$parent = R::load( $parentBean , $v );
				
				foreach ($parent as $key => $value) {
					$result[$parentBean][$v][$key] = $value;
				};
			}
		
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

function api_export($request, $config){

	$config = new ExporterConfig();
	$exporter = new Exporter($config);

    $bean = R::findAll( $request['edge'] );
    $rawData = R::exportAll($bean, false, array('part'));

	// OUTPUT
	$dateHash = str_replace(array(':','-',' '), '', R::isoDateTime());
	$name = $dateHash.'-export-'.$request['edge'].'.csv';
    
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename='. $name);
    header('Pragma: no-cache');
    header("Expires: 0");
    $outstream = $exporter->export('php://output', $rawData);
    fclose($outstream);

    exit();
};

function api_update($request, $config){

	$item = R::load( $request['edge'], $request['param'] );
	$schema['raw'] = R::getAssoc('DESCRIBE '.$request['edge']);

	foreach ($request['content'] as $k => $v) {
		// IF rel uploads many-to-many relationship
		if($k == 'uploads_id' && in_array($request['edge'] .'_uploads', $config['api']['beans'])){
			
			$upload = R::dispense( 'uploads' );
			$upload->id = $v;
			$item->sharedUploadList[] = $upload;
		}
		else{
			$item[$k] = $v;			
		}		
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

				$result['properties'][$field] = array(
					'type' 				=> 'integer',
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
					$result['properties'][$field]['enum'][] = $key;
					$result['properties'][$field]['options']['enum_titles'][] = $value;					
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

	// MANY-TO-MANY UPLOAD FIELD
	if(in_array($request['edge'] .'_uploads', $config['api']['beans'])){
	
		$result['properties']['uploads_id'] = 
			
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
	api_output($result);
}

function api_edges($config){

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
				'title' 		=> ucfirst($v),
				'count' 		=> R::count($v),
				'icon' 			=> 'th-list',
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

	$result['beans'] 	= $beans;
	$result['actions'] 	= $config['api']['actions'];

	api_output($result);
};

function api_upload($request){

	// var definition
	$data = $request['content']['blob'];

	list($type, $data) 	= explode(';', $data);
	list(, $data)      	= explode(',', $data);
	$data = base64_decode($data);
	$type = explode(':', $type);
	$type = $type[1];

	$basePath 		= '../uploads/';
	$chronoPath 	= str_replace('-', '/', R::isoDate()) . '/';
	$fullPath 		= $basePath . $chronoPath;
	$filename 			= $request['content']['filename'];
	$md5Filename 		= md5($filename) . '.' . end(explode('.', $filename));

	// build insert array
	$file = array(
		'path' 		=> $chronoPath . $md5Filename,
		'filename' 	=> $md5Filename,
		'type' 		=> $type,
		'size' 		=> $request['content']['filesize'],
		'edge' 		=> $request['edge'],
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
	$result['id'] = $id;
	$result['message'] = 'Criado com Sucesso. (id: '.$id.')';

	// OUTPUT
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