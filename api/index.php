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

// API v1 ROUTE GROUP
$app->group('/v1', function () use ($validate) {

	// PRIVATE ROUTE GROUP - REQUIRE AUTH
	$this->group('/private', function () use ($validate) {

		// EDGES ROUTE GROUP
		$this->group('/{edge:' . $validate['edges'] . '}', function () use ($validate) {

			$this->get('', 						'SlimBean\Api:retrieve'	); 
			$this->get('/{id:[0-9]+}', 			'SlimBean\Api:read'		);
			$this->get('/{id:[0-9]+}/exists', 	'SlimBean\Api:exists'	); 
			$this->get('/count', 				'SlimBean\Api:count'	);
			$this->get('/schema', 				'SlimBean\Api:schema'	);
			$this->get('/export', 				'SlimBean\Api:export'	); 

			$this->post('', 					'SlimBean\Api:create'	);
			$this->post('/upload', 				'SlimBean\Api:upload'	);

			$this->put('/{id:[0-9]+}', 			'SlimBean\Api:update'	);
			$this->delete('/{id:[0-9]+}', 		'SlimBean\Api:destroy'	); 
		});

		// AUX ROUTES
		$this->get('/hi', 							'SlimBean\Api:hi');
		$this->get('/edges', 						'SlimBean\Api:edges'); 
		$this->put('/users/{id:[0-9]+}/password',	'SlimBean\Api:updatePassword'); 
		$this->patch('/users/{id:[0-9]+}/password', 'SlimBean\Api:updatePassword'); 

	})->add('SlimBean\Auth:isAuth');

	// PUBLIC ROUTE GROUP
	$this->group('/public', function () use ($validate){
		$this->get('/', 	'SlimBean\Api:soon'	);
		$this->get('/hi', 	'SlimBean\Api:hi'	);
		$this->get('/test',	'SlimBean\Api:test'	);
	});

	// AUTH ROUTE GROUP
	$this->group('/auth', function () {
		$this->post('/signin', 'SlimBean\Auth:signin');
	});

});

/* ***************************************************************************************************
** SLIM RUN! *****************************************************************************************
*************************************************************************************************** */ 
$app->run();

?>	