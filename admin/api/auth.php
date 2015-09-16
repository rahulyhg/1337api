<?php
use \Firebase\JWT\JWT;

/* ***************************************************************************************************
** AUTH INIT *****************************************************************************************
*************************************************************************************************** */ 

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	if(!empty($req)){
		$req['content'] = json_decode(file_get_contents("php://input"), true);
		api_signin($req);		
	}
	else{
		header('HTTP/1.0 400 Bad Request');
		$res = array('error' => true, 'message' => 'HTTP/1.0 400 Bad Request');
		echo json_encode($res);
		die();
	}
} 
else{
	header('HTTP/1.0 405 Method Not Allowed');
	$res = array('error' => true, 'message' => 'HTTP/1.0 405 Method Not Allowed');
	echo json_encode($res);
	die();
};

/* ***************************************************************************************************
** AUTH SIGNIN FUNCTIONS *****************************************************************************
*************************************************************************************************** */ 

function api_signin($req){
	global $config;

	// VALIDATE CREDENTIALS
	$userCredentials = array(
		'email' 	=> $req['content']['email'],
		'password' 	=> md5($req['content']['password']),
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
		$data = [
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
			$data, 			// Data to be encoded in the JWT
			$secretKey, 	// The signing key
			'HS512' 		// Algorithm used to sign the token
		);

		// OUTPUT
 		$res['token'] = $jwt;
		api_output($res);

	}

	// IF USER DOES NOT EXIST
	else {
		api_error('AUTH_USERPASS_FAIL');
	}

};

?>