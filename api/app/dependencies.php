<?php 

/* ***************************************************************************************************
** ORM REDBEAN - INIT ********************************************************************************
*************************************************************************************************** */ 

if(R::testConnection() == TRUE){

	// DEBUG MODE
	if($config['api']['debug']){
		R::debug( TRUE, 1 );
	}

	// INIT REDBEANPHP
	R::setAutoResolve( TRUE );
	R::freeze( TRUE );

	// INSPECT TABLES
	$config['api']['edges'] = R::inspect();
}

/* ***************************************************************************************************
** LOCALE $CAPTION - INIT ****************************************************************************
*************************************************************************************************** */ 
use Sinergi\Dictionary\Dictionary;

$caption = new Dictionary( $config['locale']['code'], $config['locale']['basepath'] );

/* ***************************************************************************************************
** SLIM CONTAINER INTEROP - INIT *********************************************************************
*************************************************************************************************** */ 
$c = $app->getContainer();

// Log Handler - Monolog Factory
// -----------------------------------------------------------------------------
$c['logger'] = function ($c) {
	$settings = $c->get('settings');
	$logger = new \Monolog\Logger($settings['logger']['name']);
	$logger->pushProcessor(new \Monolog\Processor\UidProcessor());
	$logger->pushHandler(new \Monolog\Handler\StreamHandler($settings['logger']['path'], \Monolog\Logger::DEBUG));
	return $logger;
};

// Error Handler Classes
// -----------------------------------------------------------------------------

// Override the default Error Handler
$c['errorHandler'] = function ($c) {
	return function ($request, $response, $exception) use ($c) {
		global $config;

        $c['logger']->error($exception->getMessage());
        $c['logger']->debug($exception->getTraceAsString());

		$err = array(
			'error' => true, 
			'code' => 500,
			'message' => $exception->getMessage()
		);

		if ( strpos($exception->getMessage(), '1062 Duplicate entry') ) {
			$err['message'] = getMessage('UNIQUE_FAIL');
		}
	
		if ( $config['api']['debug'] ) {
			$err['debug'] = array(
				'code' => $exception->getCode(),
				'message' => $exception->getMessage(),
				'file' => $exception->getFile(),
				'line' => $exception->getLine(),
				'trace' => explode("\n", $exception->getTraceAsString())
			);
		}
		return $c['response']->withJson($err)->withStatus(500);
	};
};

// Override the default Not Found Handler
$c['notFoundHandler'] = function ($c) {
	return function ($request, $response) use ($c) {

		$err = array(
			'error' => true,
			'code' => 404,
			'message' => 'Request Parameters Invalid. (NOT FOUND)'
		);

		return $c['response']->withJson($err)->withStatus(404);
	};
};

// Override the default Method Not Allowed Handler
$c['notAllowedHandler'] = function ($c) {
	return function ($request, $response, $methods) use ($c) {

		$err = array(
			'error' => true,
			'code' => 405,
			'message' => 'Method must be one of: ' . implode(', ', $methods)
		);

		return $c['response']->withJson($err)->withStatus(405)->withHeader('Allow', implode(', ', $methods));
	};
};

// \SlimBean\ Classes
// -----------------------------------------------------------------------------
$c['SlimBean\Api'] = function ($c) {
	global $config;
	return new SlimBean\Api($config, $c->get('logger'));
};

$c['SlimBean\Auth'] = function ($c) {
	global $config;
	return new SlimBean\Auth($config, $c->get('logger'));
};

?>