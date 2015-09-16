<?php

/* ***************************************************************************************************
** LOCALE HELPER FUNCTIONS ***************************************************************************
*************************************************************************************************** */ 

function getCaption($context, $edge, $term){
	global $caption;

	switch ($context) {
		
		case 'edges':
			// checks if title caption exists in dictionary
			if(!empty($caption['edges']['title'][$term])) {
				return $caption['edges']['title'][$term];
			} 
			else {
				return ucfirst($term);
			}		
			break;

		case 'fields':
			// checks if title caption exists in dictionary
			if (!empty($caption['fields'][$edge][$term])) {
					return $caption['fields'][$edge][$term];
				} elseif (!empty($caption['fields']['default'][$term])) {
					return $caption['fields']['default'][$term];
				} else {
					return ucfirst($term);
				}
			break;

		case 'icon':
			// checks if icon caption exists in dictionary
			if (!empty($caption['edges']['icon'][$term])) {
				return $caption['edges']['icon'][$term];
			} else {
				return 'th-list';
			}
			break;

		default:
			return 'CONTEXT NOTFOUND';
			break;
	};

};

function getMessage($term) {
	global $caption;

	if (!empty($caption['messages'][$term])) {
		return $caption['messages'][$term];
	}
	else {
		return $term . ' MESSAGE NOT FOUND.';
	}
};

?>