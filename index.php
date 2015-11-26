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

// EAPI APP SETUP
require __DIR__ . '/app/helpers.php';
require __DIR__ . '/app/dependencies.php';
require __DIR__ . '/app/middleware.php';

// SLIM ROUTER VALIDATE REGEX ARRAY
$validate = array(
	'edges' => implode('|', $edges['list'])
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

			$this->get('', 							'eApi\Api:retrieve'	); 
			$this->get('/{id:[0-9]+}', 				'eApi\Api:read'		);
			$this->get('/{id:[0-9]+}/exists', 		'eApi\Api:exists'	); 
			$this->get('/count', 					'eApi\Api:count'	);
			$this->get('/{id:[0-9]+}/{child}/count', 'eApi\Api:count'	);
			$this->get('/schema', 					'eApi\Api:schema'	);
			$this->get('/export', 					'eApi\Api:export'	); 

			$this->post('', 						'eApi\Api:create'	);
			$this->put('/{id:[0-9]+}', 				'eApi\Api:update'	);
			$this->delete('/{id:[0-9]+}', 			'eApi\Api:destroy'	); 
		});

		// AUX ROUTES
		$this->get('/hi', 							'eApi\Api:hi'		);
		$this->get('/edges', 						'eApi\Api:edges'	); 
		$this->post('/upload', 						'eApi\Api:upload'	);
		$this->put('/users/{id:[0-9]+}/password',	'eApi\Api:updatePassword'); 
		$this->patch('/users/{id:[0-9]+}/password', 'eApi\Api:updatePassword'); 

	})->add('eApi\Auth:isAuth');

	// PUBLIC ROUTE GROUP
	$this->group('/public', function () use ($validate){

		$this->get('/polls', 						'eApi\Poll:retrievePolls'	); 
		$this->get('/polls/{id:[0-9]+}', 			'eApi\Poll:readPoll'		); 
		$this->get('/test',							'eApi\Poll:test'			);

	});

	// AUTH ROUTE GROUP
	$this->group('/auth', function () {
		$this->post('/signin', 'eApi\Auth:signin');
	});

});

/* ***************************************************************************************************
** SLIM RUN! *****************************************************************************************
*************************************************************************************************** */ 
$app->run();

?>	