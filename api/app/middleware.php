<?php
/**
 * SlimBean middleware register.
 *
 * @author  Elijah Hatem <elias.hatem@gmail.com>
 * @license MIT
 */

// Get $logger
$logger = $app->getContainer()->get('logger');

/* ***************************************************************************************************
** MIDDLEWARE - TEST ORM CONNECTION ******************************************************************
*************************************************************************************************** */ 
$app->add(function ($request, $response, $next) use ($logger) {
	if (R::testConnection() == TRUE) {
		return $response = $next($request, $response);
	}
	else {
		$err = array('error' => true, 'message' => getMessage('DB_CONN_FAIL'));
		$logger->alert($err['message']);
		return $response->withJson($err)->withStatus(503);
	}
});

?>