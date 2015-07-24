<?php
/**
* @package RPG Date and Calendar Mod
*
* @author Cody Williams
* @copyright 2015
* @version 1.2
* @license BSD 3-clause
*/

if (file_exists(dirname(__FILE__) . '/SSI.php') && !defined('SMF'))
{
	$ssi = true;
	require_once(dirname(__FILE__) . '/SSI.php');
}
elseif (!defined('SMF'))
	exit('<b>Error:</b> Cannot install - please verify you put this in the same place as SMF\'s index.php.');
	

global $smcFunc, $db_prefix, $modSettings, $sourcedir, $boarddir, $settings, $db_package_log, $package_cache;

  $defaults = array(
    'activity_checker_cats'=> '0',
    'activity_checker_filter_membergroups'=> '0',
	'activity_checker_inactive_group'=> '0',
	'activity_checker_inactive_time'=> 4,
  );
  
  $updates = array(
    'activity_checker_version' => '1.0',
  );
  
  foreach ($defaults as $index => $value)
    if (!isset($modSettings[$index]))
      $updates[$index] = $value;
  
  updateSettings($updates);

?>
