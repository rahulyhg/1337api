<?php

	/* CONFIG DB - MySQL */
	$config['db']['host']				 = 'mysql:host=186.202.152.193; dbname=umstudiohomolo12';
	$config['db']['user']				 = 'umstudiohomolo12';
	$config['db']['pass']				 = 'studio0001';

	/* CONFIG API - GLOBALS */
	$config['api']['debug']				 = FALSE;
	$config['api']['get']['whitelist']	 = array('hi','edges','inspect','search','read', 'count');
	$config['api']['put']['whitelist']	 = array('create','update','destroy');

?>