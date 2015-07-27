<?php

/* ***************************************************************************************************
** INIT **********************************************************************************************
*************************************************************************************************** */ 
require 'rb-p533.php';
require 'config.php';

R::setup($config['db']['host'], $config['db']['user'], $config['db']['pass']);
R::setAutoResolve( TRUE );

/* ***************************************************************************************************
** BEANS *********************************************************************************************
*************************************************************************************************** */ 




/* ***************************************************************************************************
** GET ROUTES ****************************************************************************************
*************************************************************************************************** */ 

if( !empty($_GET) && in_array($_GET['action'], $config['api']['actionlist']) ){
	$api['action']	 = $_GET['action'];
	$api['edge']	 = $_GET['edge'];
	$api['param']	 = $_GET['param'];

	if($config['api']['debug']){
		echo 'elijah says: you are trying to ' . $api['action'] . '<br />';
		if (!empty($api['edge'])){echo '@ edge: ' . $api['edge']; if (!empty($api['param'])){echo ' w/ param: ' . $api['param'];}};
		echo '<hr />';		
	}

} 

else {
	die('elijah says: you shall not pass.<br /><hr />');
}

	switch($api['action']) {

		case 'hi':
			$result['message'] = 'Hi, Elijah!';
			output($result);
		break;

		case 'inspect':
			$result[$api['edge']] = R::inspect($api['edge']);
			output($result);
		break;

		case 'search':
			$result['message'] = 'in development: action "search"';
			output($result);
		break;

		case 'create':
			$result['message'] = 'in development: action "create"';
			output($result);
		break;

		case 'read':
			$result['message'] = 'in development: action "read"';
			output($result);
		break;

		case 'update':
			$result['message'] = 'in development: action "update"';
			output($result);

		break;

		case 'destroy':
			$result['message'] = 'in development: action "destroy"';
			output($result);
		break;

		default:
			$result['message'] = 'action not supported.';
			output($result);

		break;
	}

/* ***************************************************************************************************
** POST ROUTES ***************************************************************************************
*************************************************************************************************** */ 



/* ***************************************************************************************************
** RETURN FUNCTIONS **********************************************************************************
*************************************************************************************************** */ 


function output($result){
	echo json_encode($result);
}

?>	