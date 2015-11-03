<?php

function api_hi(){
	global $caption;

	if(!empty($caption['messages'])) {
		$res = array('message' => getMessage('HI'));
	} 
	else{
		$res = array('error' => true, 'message' => 'Arquivo de mensagens não encontrado.');
	}

	return $res;

	//output response
	api_output($res);

};


/* ***************************************************************************************************
** COMING SOON ***************************************************************************************
*************************************************************************************************** */ 
	$res['message'] = 'elijah says: public API still in development.';

	// OUTPUT
	api_output($res);

?>