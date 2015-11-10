<?php
use \Firebase\JWT\JWT;

/* ***************************************************************************************************
** AUTH FUNCTIONS ************************************************************************************
*************************************************************************************************** */ 

function auth_signin ($request, $response, $args) {
	global $config;

	// FORM DATA
	$formData = $request->getParsedBody();

	// VALIDATE CREDENTIALS
	$userCredentials = array(
		'email' 	=> $formData['email'],
		'password' 	=> md5($formData['password'])
	);

	$user = R::findOne('user', 'email = :email AND password = :password AND active = true', $userCredentials );

	// IF USER EXISTS
	if(!empty($user)){

		// build jwt token variables
		$tokenId    = base64_encode(mcrypt_create_iv(32));
		$issuedAt   = strtotime(R::isoDateTime());					// Right Now
		$notBefore  = $issuedAt; 									// Instant. Right Now
		$expire     = $notBefore + $config['auth']['jwtExpire']; 	// Retrieve the expiration time from config file
		$serverName = $config['auth']['jwtIssuer']; 				// Retrieve the server name from config file
	
		// create the token as an array
		$token = [
			'iat'  => $issuedAt, 									// Issued at: time when the token was generated
			'jti'  => $tokenId, 									// Json Token Id: an unique identifier for the token
			'iss'  => $serverName, 									// Issuer
			'nbf'  => $notBefore, 									// Not before
			'exp'  => $expire, 										// Expire
			'data' => [ 											// Data related to the signer user from the users table
				'id'   	=> $user['id'],
				'name' 	=> $user['name'],
				'email' => $user['email'],
			]
		];

		// extract the secret key from the config file. 
		$secretKey = base64_decode($config['auth']['jwtKey']);
	
		// encode the array to a JWT string.
		// the output string can be validated at http://jwt.io/
		$jwt = JWT::encode(
			$token, 			// Data to be encoded in the JWT
			$secretKey, 	// The signing key
			'HS512' 		// Algorithm used to sign the token
		);

		// OUTPUT
 		$data['token'] = $jwt;
		$response->withJson($data);
	}

	// IF USER DOES NOT EXIST
	else {
		api_error('AUTH_USERPASS_FAIL');
	}

};

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