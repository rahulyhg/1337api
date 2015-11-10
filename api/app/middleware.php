<?php
// Application middleware

/* ***************************************************************************************************
** ORM - TEST CONNECTION *****************************************************************************
*************************************************************************************************** */ 
$app->add(function ($request, $response, $next) {

	if(R::testConnection() == TRUE){
		return $response = $next($request, $response);
	}
	else {
		$err = array('error' => true, 'message' => getMessage('DB_CONN_FAIL'));
		return $response->withJson($err)->withStatus(400);
	}
});

?>