<?php

	/* CONFIG DB - MySQL */
	$config['db']['host']					 		= 'mysql:host=186.202.152.193; dbname=umstudiohomolo12';
	$config['db']['user']					 		= 'umstudiohomolo12';
	$config['db']['pass']					 		= 'studio0001';

	/* CONFIG API - GLOBALS */
	$config['api']['debug']					 		= FALSE;
	$config['api']['get']['whitelist']		 		= array('hi','edges','search','read', 'count', 'schema');
	$config['api']['post']['whitelist']		 		= array('create');
	$config['api']['put']['whitelist']		 		= array('update');
	$config['api']['delete']['whitelist']	 		= array('destroy');
	$config['api']['form']['fields']['blacklist'] 	= array('id','created','modified');

	/* CONFIG API - MESSAGES */
	$config['api']['messages']				 		= array(
														'hi' 		=> 'Hi Elijah, your API is UP!',
														'forbidden' => 'elijah says: NO.'
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