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
$app = new \Slim\App;

// SLIMBEAN APP SETUP
require __DIR__ . '/app/helpers.php';
require __DIR__ . '/app/dependencies.php';
require __DIR__ . '/app/middleware.php';

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

})->add('SlimBean\Auth:isAuth');


// PUBLIC ROUTES
$app->group('/public', function () use ($api){

	$this->get('/', 'SlimBean\Api:soon');
	$this->get('/test', 'SlimBean\Api:test');


});

// AUTH ROUTES
$app->group('/auth', function () use ($api){

	$this->post('', 'SlimBean\Auth:signin');

});

/* ***************************************************************************************************
** SLIM RUN! *****************************************************************************************
*************************************************************************************************** */ 
$app->run();

?>	