<?php
//error_reporting(-1);
//ini_set('display_errors', 'On');

/* ***************************************************************************************************
** INIT **********************************************************************************************
*************************************************************************************************** */ 
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/config.php';
require __DIR__ . '/controllers/api.php';
require __DIR__ . '/controllers/auth.php';
require __DIR__ . '/helpers/shared.php';

// SLIM ROUTER SETUP
$app = new \Slim\App;

// REDBEAN ORM SETUP
R::setup($config['db']['host'], $config['db']['user'], $config['db']['pass']);
R::setAutoResolve( TRUE );
R::freeze( FALSE );

// TEST DB CONNECTION
if(R::testConnection() == FALSE){
	api_error('DB_CONN_FAIL');
	exit();
};

// INSPECT TABLES
$api = array();
$api['edges'] = R::inspect();
$api['edgesRegex'] = implode('|', $api['edges']);

// REDBEAN ORM DEBUG MODE ON
if($config['api']['debug']){
	R::debug( TRUE, 1 );
};

/* ***************************************************************************************************
** SLIM ROUTER - REST ROUTES DEFINITION **************************************************************
*************************************************************************************************** */ 

function isAuth ($request, $response, $next) {
	$auth = true;

	if($auth){
		$response = $next($request, $response);
	}
	else{
		$response->withJson('not authenticated');
	}
    return $response;
}

$app->group('/private', function () use ($api){

	// PRIVATE ROUTES
	$this->get('/hi', 													'api_hi'	);
	$this->get('/edges', 												'api_edges'	); 

	$this->get('/{edge:'.$api['edgesRegex'].'}', 						'api_list'	); 
	$this->get('/{edge:'.$api['edgesRegex'].'}/count', 					'api_count'	);
	$this->get('/{edge:'.$api['edgesRegex'].'}/schema', 				'api_schema');

	$this->get('/export/{edge:'.$api['edgesRegex'].'}', 				'api_export'); 

	$this->get('/read/{edge:'.$api['edgesRegex'].'}/{id:[0-9]+}', 		'api_read'	);
	$this->get('/exists/{edge:'.$api['edgesRegex'].'}/{id:[0-9]+}', 	'api_exists'); 

	$this->post('/create/{edge:'.$api['edgesRegex'].'}', 				'api_create');
	$this->post('/update/{edge:'.$api['edgesRegex'].'}/{id:[0-9]+}', 	'api_update');
	$this->post('/updatePassword/user/{id:[0-9]+}', 					'api_updatePassword'); 
	$this->post('/destroy/{edge:'.$api['edgesRegex'].'}/{id:[0-9]+}', 	'api_destroy'); 
	$this->post('/upload/{edge:'.$api['edgesRegex'].'}', 				'api_upload');

})->add('isAuth');

$app->group('/public', function () use ($api){

	$this->get('/', 'api_soon');

});

$app->group('/auth', function () use ($api){

	$this->post('', 'auth_signin');

});

/* ***************************************************************************************************
** SLIM RUN! *****************************************************************************************
*************************************************************************************************** */ 
$app->run();

?>	