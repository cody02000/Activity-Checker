<?php
/**
* @package Acitivity Checker
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
	'activity_checker_inactive_group'=> '0',
	'activity_checker_inactive_time'=> 4,
	'activity_checker_categories' = > 0,
	'activity_checker_inactive_group' = > 0,
	'activity_checker_active_group' = > 0,
	'activity_checker_inactive_pm_enable' = > 0,
	'activity_checker_inactive_pm_from' = > 0,
	'activity_checker_inactive_pm_bcc' = > 0,
	'activity_checker_inactive_pm_subject' = > 0,
	'activity_checker_inactive_pm_message' = > 0,
	'activity_checker_email_enable' = > 0,
	'activity_checker_email_subject' = > 0,
	'activity_checker_email_message' = > 0,
	'' = > ,
  );
  
  $updates = array(
    'activity_checker_version' => '1.0',
  );
  
  foreach ($defaults as $index => $value)
    if (!isset($modSettings[$index]))
      $updates[$index] = $value;
  
  updateSettings($updates);

?>
