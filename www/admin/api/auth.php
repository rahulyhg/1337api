<?php
use \Firebase\JWT\JWT;
// docs: http://www.sitepoint.com/php-authorization-jwt-json-web-tokens/
//       http://www.toptal.com/web/cookie-free-authentication-with-json-web-tokens-an-example-in-laravel-and-angularjs

/* ***************************************************************************************************
** API SIGNIN FUNCTIONS ******************************************************************************
*************************************************************************************************** */ 

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $request['content'] = json_decode(file_get_contents("php://input"),true);
	api_signin($request, $config);
};

function api_signin($request, $config){

	// validate credentials
	$userCredentials = array(
		'email' => $request['content']['email'],
		'password' => md5($request['content']['password']),
	);

	$user = R::findOne('user', 'email = :email and password = :password and active = true', $userCredentials );

	if(!empty($user)){

		$tokenId    = base64_encode(mcrypt_create_iv(32));
		$issuedAt   = R::isoDateTime();
		$notBefore  = $issuedAt + 10;             //Adding 10 seconds
		$expire     = $notBefore + 60;            // Adding 60 seconds
		$serverName = 'serverName'; // Retrieve the server name from config file
    
    /*
     * Create the token as an array
     */
    $data = [
        'iat'  => $issuedAt,         // Issued at: time when the token was generated
        'jti'  => $tokenId,          // Json Token Id: an unique identifier for the token
        'iss'  => $serverName,       // Issuer
        'nbf'  => $notBefore,        // Not before
        'exp'  => $expire,           // Expire
        'data' => [                  // Data related to the signer user
            'id'   => $user['id'], // userid from the users table
            'name'   => $user['name'], // userid from the users table
            'email' => $userCredentials['email'], // User name
        ]
    ];

/*
     * Extract the key, which is coming from the config file. 
     * 
     * Best suggestion is the key to be a binary string and 
     * store it in encoded in a config file. 
     *
     * Can be generated with base64_encode(openssl_random_pseudo_bytes(64));
     *
     * keep it secure! You'll need the exact key to verify the 
     * token later.
     */
    $secretKey = base64_decode($config['auth']['jwtKey']);
    
    /*
     * Encode the array to a JWT string.
     * Second parameter is the key to encode the token.
     * 
     * The output string can be validated at http://jwt.io/
     */
    $jwt = JWT::encode(
        $data,      //Data to be encoded in the JWT
        $secretKey, // The signing key
        'HS512'     // Algorithm used to sign the token, see https://tools.ietf.org/html/draft-ietf-jose-json-web-algorithms-40#section-3
        );
        
    $unencodedArray = ['jwt' => $jwt];
 
    $result['token'] = $jwt;


	}
	else{
		$result['msg'] = 'Usuário inválido!';
		$result['HttpResponse'] = 'HTTP_UNAUTHORIZED';

	}	

	// OUTPUT
	api_output($result);

};

?>