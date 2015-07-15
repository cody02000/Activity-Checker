<?php
/**
* @package RPG Date and Calendar Mod
*
* @author Cody Williams
* @copyright 2015
* @version 1.2
* @license BSD 3-clause
*/

// Editing or adding holidays.




function template_general_settings()
{	
	//	Show the confiq_vars.
	template_show_settings();
	
	//	Put in a spacer to make it look better.
	echo '
	<br />';

	//	Show the list.
	template_show_list();

}