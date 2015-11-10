<?php 

// -----------------------------------------------------------------------------
// SlimBean Instantiation
// -----------------------------------------------------------------------------

// TEST FUNCTION TO LOAD MY CLASS
$container = $app->getContainer();
$container['SlimBean\Api'] = function ($c) {
	global $config;
	global $caption;
	return new SlimBean\Api($config, $caption);
};
// END TEST FUNCTION TO LOAD MY CLASS

// -----------------------------------------------------------------------------
// Error handlers
// -----------------------------------------------------------------------------

// DIC configuration
$c = $app->getContainer();

// Override the default Error Handler
$c['errorHandler'] = function ($c) {
	return function ($request, $response, $exception) use ($c) {
		global $config;

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

?>