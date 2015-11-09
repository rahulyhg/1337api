<?php 

// TEST FUNCTION TO LOAD MY CLASS
$container = $app->getContainer();
$container['SlimBean\Api'] = function ($c) {
	global $config;
	return new SlimBean\Api($config);
};
// END TEST FUNCTION TO LOAD MY CLASS



// DIC configuration
$c = $app->getContainer();

// -----------------------------------------------------------------------------
// Locale Instantiation
// -----------------------------------------------------------------------------


	/* CONFIG LOCALE */
	use Sinergi\Dictionary\Dictionary;
	$locale_dir = __DIR__ . '/locale';
	$caption = new Dictionary($config['api']['locale'], $locale_dir );



// -----------------------------------------------------------------------------
// Error handlers
// -----------------------------------------------------------------------------

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