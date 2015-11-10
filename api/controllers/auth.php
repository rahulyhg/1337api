<?php
use \Firebase\JWT\JWT;

/* ***************************************************************************************************
** AUTH FUNCTIONS ************************************************************************************
*************************************************************************************************** */ 

function auth_check ($request, $response, $next) {
	global $config;

/*	// CHECK AUTHORIZATION HEADER
	if ( !empty($request->getHeader('Authorization')) ) {

			$authHeader = $request->getHeader('Authorization')[0];

			// Extract the jwt from the Bearer
			list($jwt) = sscanf( $authHeader, 'Bearer %s');

			if($jwt) {
				// decode the jwt using the key from config
				$secretKey 	= base64_decode($config['auth']['jwtKey']);
				$token 		= JWT::decode($jwt, $secretKey, array('HS512'));

				if($token){
					$auth = true;
				}

				else {
					throw new Exception('Invalid token found at Authorization Header.', 1);
				}

			} 
			else {
				throw new Exception('Token not found at Authorization Header.', 1);
			}
	}
	else {
		$auth = false;
		throw new Exception('Authorization Header not found.', 1);
	}
*/

	// AUTH OVERRIDE FOR REFACTOR DEV PURPOSES
	$auth = true;

	// IF AUTH PASS
	if($auth){
		return $response = $next($request, $response);
	}
	else{
		return $response->withJson(array('error' => 'true', 'message' => 'not authenticated'))->withStatus(403);
	}
}

?>