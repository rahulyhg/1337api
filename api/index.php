<?php
error_reporting(-1);
ini_set('display_errors', 'On');

/* CONFIG PHP - GLOBALS */
date_default_timezone_set('America/Sao_Paulo');

/* ***************************************************************************************************
** INIT **********************************************************************************************
*************************************************************************************************** */ 
require __DIR__ . '/vendor/autoload.php';

$config = require __DIR__ . '/app/config.php';

// TODO: Convert require functions controllers to PSR-4 compliant class SlimBean\\.
require __DIR__ . '/controllers/api.php';
require __DIR__ . '/controllers/auth.php';

// SLIM ROUTER SETUP
$app = new \Slim\App;
require __DIR__ . '/app/helpers.php';
require __DIR__ . '/dependencies.php';

// REDBEAN ORM SETUP
R::setup($config['db']['host'], $config['db']['user'], $config['db']['pass']);
R::setAutoResolve( TRUE );
R::freeze( TRUE );

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

// PRIVATE ROUTES - REQUIRE AUTH
$app->group('/private', function () use ($api){

	$this->get('/hi', 												'api_hi'	);
	$this->get('/edges', 											'api_edges'	); 

	$this->get('/{edge:'.$api['edgesRegex'].'}[/list]', 			'api_list'	); 
	$this->get('/{edge:'.$api['edgesRegex'].'}/count', 				'api_count'	);
	$this->get('/{edge:'.$api['edgesRegex'].'}/schema', 			'api_schema');
	$this->get('/{edge:'.$api['edgesRegex'].'}/export', 			'api_export'); 

	$this->get('/{edge:'.$api['edgesRegex'].'}/{id:[0-9]+}', 		'api_read'	);
	$this->get('/{edge:'.$api['edgesRegex'].'}/{id:[0-9]+}/exists', 'api_exists'); 

	$this->post('/{edge:'.$api['edgesRegex'].'}', 					'api_create');
	$this->post('/{edge:'.$api['edgesRegex'].'}/upload', 			'api_upload');

	$this->put('/{edge:'.$api['edgesRegex'].'}/{id:[0-9]+}', 		'api_update');
	$this->patch('/user/{id:[0-9]+}/password', 						'api_updatePassword'); 

	$this->delete('/{edge:'.$api['edgesRegex'].'}/{id:[0-9]+}', 	'api_destroy'); 

})->add('auth_check');

// PUBLIC ROUTES
$app->group('/public', function () use ($api){

	$this->get('/', 'api_soon');
	$this->get('/test', 'SlimBean\Api:test');


});

// AUTH ROUTES
$app->group('/auth', function () use ($api){

	$this->post('', 'auth_signin');

});

/* ***************************************************************************************************
** SLIM RUN! *****************************************************************************************
*************************************************************************************************** */ 
$app->run();

?>	