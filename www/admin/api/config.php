<?php

	/* CONFIG DB - MySQL */
	$config['db']['host']				 = 'mysql:host=186.202.152.193; dbname=umstudiohomolo12';
	$config['db']['user']				 = 'umstudiohomolo12';
	$config['db']['pass']				 = 'studio0001';

	/* CONFIG API - GLOBALS */
	$config['api']['debug']				 = FALSE;
	$config['api']['get']['whitelist']	 = array('hi','inspect','search','read');
	$config['api']['post']['whitelist']	 = array('create','update','destroy');

?>