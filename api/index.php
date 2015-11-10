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

/* CONFIG LOCALE */
use Sinergi\Dictionary\Dictionary;
$locale_dir = __DIR__ . '/locale';
$caption = new Dictionary($config['api']['locale'], $locale_dir );

// TODO: Convert require functions controllers to PSR-4 compliant class SlimBean\\.
require __DIR__ . '/controllers/api.php';
require __DIR__ . '/controllers/auth.php';

// SLIM ROUTER SETUP
$app = new \Slim\App;
require __DIR__ . '/app/dependencies.php';
require __DIR__ . '/app/helpers.php';
require __DIR__ . '/app/middleware.php';

// REDBEAN ORM SETUP
R::setup($config['db']['host'], $config['db']['user'], $config['db']['pass']);
R::setAutoResolve( TRUE );
R::freeze( TRUE );

// TEST DB CONNECTION
if(R::testConnection() == FALSE){
	$response->withJson(getMessage('DB_CONN_FAIL'));
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

	$this->get('/hi', 												'SlimBean\Api:hi');
	$this->get('/edges', 											'SlimBean\Api:edges'); 

	$this->get('/{edge:'.$api['edgesRegex'].'}[/list]', 			'SlimBean\Api:retrieve'); 
	$this->get('/{edge:'.$api['edgesRegex'].'}/count', 				'SlimBean\Api:count'	);
	$this->get('/{edge:'.$api['edgesRegex'].'}/schema', 			'SlimBean\Api:schema');
	$this->get('/{edge:'.$api['edgesRegex'].'}/export', 			'SlimBean\Api:export'); 

	$this->get('/{edge:'.$api['edgesRegex'].'}/{id:[0-9]+}', 		'SlimBean\Api:read'	);
	$this->get('/{edge:'.$api['edgesRegex'].'}/{id:[0-9]+}/exists', 'SlimBean\Api:exists'); 

	$this->post('/{edge:'.$api['edgesRegex'].'}', 					'SlimBean\Api:create');
	$this->post('/{edge:'.$api['edgesRegex'].'}/upload', 			'SlimBean\Api:upload');

	$this->put('/{edge:'.$api['edgesRegex'].'}/{id:[0-9]+}', 		'SlimBean\Api:update');
	$this->patch('/user/{id:[0-9]+}/password', 						'SlimBean\Api:updatePassword'); 

	$this->delete('/{edge:'.$api['edgesRegex'].'}/{id:[0-9]+}', 	'SlimBean\Api:destroy'); 

})->add('auth_check');

// PUBLIC ROUTES
$app->group('/public', function () use ($api){

	$this->get('/', 'SlimBean\Api:soon');
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