<?php

	/* CONFIG DB - MySQL */
	$config['db'] = array(
		'host' => 'mysql:host=186.202.152.193; dbname=umstudiohomolo12',
		'user' => 'umstudiohomolo12',
		'pass' => 'studio0001',
	);

	/* CONFIG API - GLOBALS */
	$config['api'] = array(
		'debug' 	=> FALSE,
		'actions' 	=> array(
			'get' 	=> array('hi','edges','search','read', 'count', 'schema'),
			'post' 	=> array('create'),
			'put' 	=> array('update'),
			'del' 	=> array('destroy'),
		),
		'messages'	=> array(
			'hi' 		=> 'Hi Elijah, your API is UP!',
			'forbidden' => 'elijah says: NO.'
		),
	);

	/* CONFIG FORM BUILDER - GLOBALS */
	$config['schema']['fields'] = array(
		'blacklist' => array('id','created','modified')
	);

	/* CONFIG API - FORMAT */

	$config['schema']['custom'] = array(
/*		'title' => array(
			'type' 		=> 'batatinha',
			'format' 	=> 'quando nasce',
			'title' 	=> 'se esparrama',
			'required' 	=> true,
			'minLength' => 'pelo chão',
		),*/
	);



?>