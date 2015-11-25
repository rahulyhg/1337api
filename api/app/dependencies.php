<?php 
/**
 * eApi dependencies instantiation.
 *
 * @author  Elijah Hatem <elias.hatem@gmail.com>
 * @license MIT
 */

/* ***************************************************************************************************
** ORM REDBEAN - INIT ********************************************************************************
*************************************************************************************************** */ 

if (R::testConnection() == TRUE) {

	// DEBUG MODE
	if ($config['api']['debug']) {
		R::debug( TRUE, 1 );
	}

	// INIT REDBEANPHP
	R::setAutoResolve( TRUE );
	R::freeze( TRUE );

	// INSPECT TABLES
	$config['edges']['list'] = R::inspect();

	// INSERT MANY-TO-MANY TABLES AT BLACKLIST
	foreach ($config['edges']['list'] as $k => $edge) {
		if (strpos($edge, '_') !== FALSE) {
			array_push($config['edges']['blacklist'], $edge);
			array_push($config['edges']['relations'], $edge);
		}
	}

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
	$logger->pushProcessor(new \Monolog\Processor\WebProcessor());
	$logger->pushProcessor(new \Monolog\Processor\UidProcessor());
	$logger->pushHandler(new \Monolog\Handler\RotatingFileHandler($settings['logger']['path'], \Monolog\Logger::DEBUG));
	return $logger;
};

// Error Handler Classes
// -----------------------------------------------------------------------------

// Override the default Error Handler
$c['errorHandler'] = function ($c) {
	return function ($request, $response, $exception) use ($c) {
		global $config;

		// default error payload
		$err = array(
			'error' => true, 
			'code' => 500,
			'message' => $exception->getMessage()
		);

		// handle common exceptions messages to be user-friendly
		if (strpos($exception->getMessage(), '1062 Duplicate entry')) {
			$err['message'] = getMessage('UNIQUE_FAIL');
		}

		// add stack trace if API debug is true	
		if ($config['api']['debug']) {
			$err['debug'] = array(
				'code' 		=> $exception->getCode(),
				'message' 	=> $exception->getMessage(),
				'file' 		=> $exception->getFile(),
				'line' 		=> $exception->getLine(),
				'trace' 	=> explode("\n", $exception->getTraceAsString())
			);
		}

		// log and return response
		$c['logger']->error($exception->getMessage());
		$c['logger']->debug($exception->getTraceAsString());
		return $c['response']->withJson($err)->withStatus(500);
	};
};

// Override the default Not Found Handler
$c['notFoundHandler'] = function ($c) {
	return function ($request, $response) use ($c) {

		// default error payload
		$err = array(
			'error' => true,
			'code' => 404,
			'message' => getMessage('NOT_FOUND')
		);

		// log and return response
		$c['logger']->notice($err['message']);
		return $c['response']->withJson($err)->withStatus(404);
	};
};

// Override the default Method Not Allowed Handler
$c['notAllowedHandler'] = function ($c) {
	return function ($request, $response, $methods) use ($c) {

		// default error payload
		$err = array(
			'error' => true,
			'code' => 405,
			'message' => getMessage('NOT_ALLOWED') . ' (Tente Novamente utilizando: ' . implode(', ', $methods) . ')'
		);

		// log and return response
		$c['logger']->notice($err['message']);
		return $c['response']->withJson($err)->withStatus(405)->withHeader('Allow', implode(', ', $methods));
	};
};

// \eApi\ Classes
// -----------------------------------------------------------------------------
$c['eApi\Api'] = function ($c) {
	global $config;
	return new eApi\Api($config, $c->get('logger'));
};

$c['eApi\Auth'] = function ($c) {
	global $config;
	return new eApi\Auth($config, $c->get('logger'));
};

?>