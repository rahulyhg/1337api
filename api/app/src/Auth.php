<?php 
/**
 * SlimBean
 * @author  Elijah Hatem <elias.hatem@gmail.com>
 * @license MIT
 */
namespace SlimBean;

use \RedBeanPHP\Facade as R;
use \Firebase\JWT\JWT;
use Psr\Log\LoggerInterface;

/**
 * SlimBean core Auth class.
 * Provides default functions to JWT.io Authentication
 * integrated with Firebase JWT, Slim Framework, RedBeanPHP and Monolog.
 *
 * @author  Elijah Hatem <elias.hatem@gmail.com>
 * @license MIT
 */
class Auth {

	/**
	 * @var array $config Global settings values. 
	 */
	private $config;

	/**
	 * @var Psr\Log\LoggerInterface $logger Logger handler. 
	 */
	private $logger;

	public function __construct($config, LoggerInterface $logger)
	{
		$this->config = $config;
		$this->logger = $logger;
	}

	/**
	  * Authentication signin method.
	  *
	  * @param Psr\Http\Message\ServerRequestInterface $request Request Object
	  * @param Psr\Http\Message\ResponseInterface $response Response Object
	  * @param array $args Wildcard arguments from Request URI
	  *
	  * @return Psr\Http\Message\ResponseInterface
	  */
	public function signin ($request, $response, $args) {

		// FORM DATA
		$data = $request->getParsedBody();

		// VALIDATE CREDENTIALS
		$credentials = array(
			'email' 	=> $data['email'],
			'password' 	=> md5($data['password'])
		);

		$user = R::findOne('users', 'email = :email AND password = :password AND active = true', $credentials );

		// IF USER EXISTS
		if (!empty($user)) {

			// build jwt token variables
			$tokenId    = base64_encode(mcrypt_create_iv(32));
			$issuedAt   = strtotime(R::isoDateTime());							// Right Now
			$notBefore  = $issuedAt; 											// Instant. Right Now
			$expire     = $notBefore + $this->config['auth']['jwt']['expire']; 	// Retrieve the expiration time from config file
			$serverName = $this->config['auth']['jwt']['issuer']; 				// Retrieve the server name from config file
		
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
			$secretKey = base64_decode($this->config['auth']['jwt']['key']);
		
			// encode the array to a JWT string.
			// the output string can be validated at http://jwt.io/
			$jwt = JWT::encode(
				$token, 			// Data to be encoded in the JWT
				$secretKey, 		// The signing key
				'HS512' 			// Algorithm used to sign the token
			);

			// build api response payload
			$payload = array(
				'token' => $jwt
			);
			
			// output response payload
			return $response->withJson($payload);
		}

		// IF USER DOES NOT EXIST
		else {
			$err = array('error' => true, 'message' => getMessage('AUTH_USERPASS_FAIL'));
			$this->logger->notice($err['message'], $args);
			return $response->withJson($err);
		}
	}

	/**
	  * Authentication Middleware to check authorization token method.
	  *
	  * @param Psr\Http\Message\ServerRequestInterface $request Request Object
	  * @param Psr\Http\Message\ResponseInterface $response Response Object
	  *
	  * @return Psr\Http\Message\ResponseInterface
	  */
	public function isAuth($request, $response, $next) {

		// CHECK AUTHORIZATION HEADER
		if (!empty($request->getHeader('Authorization')[0])) {

			try {
				
				// extract the JWT from the Bearer
				list($jwt) 	= sscanf( $request->getHeader('Authorization')[0], 'Bearer %s');
				$secretKey 	= base64_decode($this->config['auth']['jwt']['key']);
				$token 		= JWT::decode($jwt, $secretKey, array('HS512'));

				// if token is valid, go on
				if ($token) {
					return $response = $next($request, $response);
				}

			} 
			catch (\UnexpectedValueException $e) {
				// @throws UnexpectedValueException :: Provided JWT was invalid
				$err = array('error' => true, 'message' => getMessage('AUTH_FAIL_TOKEN_INVALID'), 'debug' => 'JWT:: exception: ' . $e->getMessage());
				$this->logger->notice($err['message'], $args);				
				return $response->withJson($err)->withStatus(401);
			}
			catch (\DomainException $e) {
				// @throws DomainException :: Algorithm was not provided
				$err = array('error' => true, 'message' => getMessage('AUTH_FAIL_TOKEN_INVALID'), 'debug' => 'JWT:: exception: ' . $e->getMessage());
				$this->logger->notice($err['message'], $args);
				return $response->withJson($err)->withStatus(401);				
			}
			catch (\SignatureInvalidException $e) {
				// @throws SignatureInvalidException :: Provided JWT was invalid because the signature verification failed
				$err = array('error' => true, 'message' => getMessage('AUTH_FAIL_TOKEN_INVALID'), 'debug' => 'JWT:: exception: ' . $e->getMessage());
				$this->logger->notice($err['message'], $args);
				return $response->withJson($err)->withStatus(401);				
			}
			catch (\BeforeValidException $e) {
				// @throws BeforeValidException :: Provided JWT is trying to be used before it's eligible as defined by 'nbf'
				$err = array('error' => true, 'message' => getMessage('AUTH_FAIL_TOKEN_INVALID'), 'debug' => 'JWT:: exception: ' . $e->getMessage());
				$this->logger->notice($err['message'], $args);
				return $response->withJson($err)->withStatus(401);				
			}
			catch (\ExpiredException $e) {
				// @throws ExpiredException :: Provided JWT has since expired, as defined by the 'exp' claim
				$err = array('error' => true, 'message' => getMessage('AUTH_FAIL_TOKEN_EXPIRED'), 'debug' => 'JWT:: exception: ' . $e->getMessage());
				$this->logger->notice($err['message'], $args);
				return $response->withJson($err)->withStatus(401);				
			}
			catch (Exception $e) {
				// @throws Exception :: Generic try catch Exception
				$err = array('error' => true, 'message' => getMessage('AUTH_FAIL'));
				$this->logger->notice($err['message'], $args);
				return $response->withJson($err)->withStatus(401);
			}
		}
		else {
			$err = array('error' => true, 'message' => getMessage('AUTH_FAIL_HEADER_MISSING'));
			$this->logger->notice($err['message'], $args);
			return $response->withJson($err)->withStatus(401);
		}
	}

}
/* .end Auth.php */