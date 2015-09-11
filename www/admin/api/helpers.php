<?php

// LOCALE HELPERS

function getCaption($context, $term){

	if ($context == 'edges') {

		echo $caption['edges']['title'][$term];

		// checks if title caption exists in dictionary
		if(!empty($caption['edges']['title'][$term])) {
			return $caption['edges']['title'][$term];

		} 
		else {
			return ucfirst($term);
		}

	}




};






?>