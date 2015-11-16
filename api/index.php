<?php
error_reporting(-1);
ini_set('display_errors', 'On');

/* ***************************************************************************************************
** INIT **********************************************************************************************
*************************************************************************************************** */ 

// COMPOSER VENDOR AUTOLOAD
require __DIR__ . '/vendor/autoload.php';

// CONFIG SETTINGS
$config = require __DIR__ . '/app/config.php';

// REDBEAN ORM SETUP
R::setup($config['db']['host'], $config['db']['user'], $config['db']['pass']);

// SLIM ROUTER SETUP
$app = new \Slim\App($config['slim']);

// SLIMBEAN APP SETUP
require __DIR__ . '/app/helpers.php';
require __DIR__ . '/app/dependencies.php';
require __DIR__ . '/app/middleware.php';

// SLIM ROUTER VALIDATE REGEX ARRAY
$validate = array(
	'edges' => implode('|', $config['edges']['list'])
);

/* ***************************************************************************************************
** SLIM ROUTER - REST ROUTES DEFINITION **************************************************************
*************************************************************************************************** */ 

// PRIVATE ROUTES - REQUIRE AUTH
$app->group('/private', function () use ($validate){

	$this->get('/hi', 												'SlimBean\Api:hi');
	$this->get('/edges', 											'SlimBean\Api:edges'); 

	$this->get('/{edge:'.$validate['edges'].'}[/list]', 			'SlimBean\Api:retrieve'); 
	$this->get('/{edge:'.$validate['edges'].'}/count', 				'SlimBean\Api:count'	);
	$this->get('/{edge:'.$validate['edges'].'}/schema', 			'SlimBean\Api:schema');
	$this->get('/{edge:'.$validate['edges'].'}/export', 			'SlimBean\Api:export'); 

	$this->get('/{edge:'.$validate['edges'].'}/{id:[0-9]+}', 		'SlimBean\Api:read'	);
	$this->get('/{edge:'.$validate['edges'].'}/{id:[0-9]+}/exists', 'SlimBean\Api:exists'); 

	$this->post('/{edge:'.$validate['edges'].'}', 					'SlimBean\Api:create');
	$this->post('/{edge:'.$validate['edges'].'}/upload', 			'SlimBean\Api:upload');

	$this->put('/{edge:'.$validate['edges'].'}/{id:[0-9]+}', 		'SlimBean\Api:update');
	$this->put('/users/{id:[0-9]+}/password', 						'SlimBean\Api:updatePassword'); 
	$this->patch('/users/{id:[0-9]+}/password', 					'SlimBean\Api:updatePassword'); 

	$this->delete('/{edge:'.$validate['edges'].'}/{id:[0-9]+}', 	'SlimBean\Api:destroy'); 

})->add('SlimBean\Auth:isAuth');

// PUBLIC ROUTES
$app->group('/public', function () use ($validate){
	$this->get('/', 												'SlimBean\Api:soon');
	$this->get('/hi', 												'SlimBean\Api:hi');
	$this->get('/test', 											'SlimBean\Api:test');
});

// AUTH ROUTES
$app->group('/auth', function () use ($validate){
	$this->post('/signin', 'SlimBean\Auth:signin');
});

/* ***************************************************************************************************
** SLIM RUN! *****************************************************************************************
*************************************************************************************************** */ 
$app->run();

?>	