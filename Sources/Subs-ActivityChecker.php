<?php
/**
* @package Activity Checker
*
* @author Cody Williams
* @copyright 2015
* @version 1.0.4
* @license BSD 3-clause
*/

// First of all, we make sure we are accessing the source file via SMF so that people can not directly access the file. 
if (!defined('SMF'))
	die('Hack Attempt...');


/**
 *  Sets up admin areas.
 *  
 *  Called by integrate_admin_areas hook.
 *  
 *  @param array $admin_areas
 */
function activityChecker_adminMenu(&$admin_areas)
{
	global $txt, $scripturl;
	loadLanguage('ActivityChecker');
	$admin_areas['members']['areas']['activity_checker']=array(
		'label' => $txt['activity_checker_label'],
		'file' => 'ActivityChecker.php',
		'function' => 'activityChecker_adminMain',
		'custom_url' => $scripturl . '?action=admin;area=activity_checker',
		'icon' => 'calendar.gif',
		'subsections' => array(
			'settings' => array($txt['activity_checker_general'], 'manage_membergroups',),
			'pm_email_settings' => array($txt['activity_checker_pm_email_settings'], 'manage_membergroups',),
			'inactive_list' => array($txt['activity_checker_inactive_list'], 'manage_membergroups',),
			'active_list' => array($txt['activity_checker_active_list'], 'manage_membergroups',),
			'no_posts_list' => array($txt['activity_checker_no_posts_list'], 'manage_membergroups',),
		)
	);
}

/**
 *  Gets items for active and inactive lists.
 *  
 *  @param int $start
 *  @param int $items_per_page
 *  @param string $sort
 *  @param string $membergroup
 *	@param array $boards
 *	@param string $time
 */
function list_getItemsActivityChecker($start, $items_per_page, $sort,$membergroup,$boards,$time)
{
	global $smcFunc, $txt, $scripturl, $modSettings, $activityChecker;
	
	$result = $smcFunc['db_query']('', '
		SELECT id_member
			FROM {db_prefix}members
			ORDER BY id_member'
	);
	$members = array();
	while($row = $smcFunc['db_fetch_assoc']($result))
		$members[] = $row['id_member'];
	// Free the db resource.
	$smcFunc['db_free_result']($result);
	
	// Query to make a list of the 'last post' for each member.
	$result = $smcFunc['db_query']('', '
		SELECT MAX(id_msg) AS id_msg
		FROM {db_prefix}messages
		WHERE id_member IN ({array_int:members})
		AND id_board IN ({array_int:boards})
		GROUP BY id_member
		ORDER BY id_member',
		array(
			'members' => $members,
			'boards' => $boards,
		)
	);
	
	// Make a list of the 'lastest posts' for each member
	$last_posts = array();
	while($row = $smcFunc['db_fetch_assoc']($result))
		$last_posts[] = $row['id_msg'];
	
	// Free the db resource.
	$smcFunc['db_free_result']($result);
	
	$request = $smcFunc['db_query']('', '
		SELECT mem.id_member, mem.real_name, mem.usertitle, mem.date_registered,mem.id_group, mem.additional_groups,
			msg.id_member, msg.id_msg, msg.id_topic, msg.poster_time
		FROM {db_prefix}messages AS msg
			INNER JOIN {db_prefix}members AS mem ON (mem.id_member=msg.id_member)
		WHERE msg.id_msg IN ({array_int:last_posts})
			AND msg.poster_time {raw:inactive_weeks}
			AND (mem.id_group ={raw:member_group} OR (FIND_IN_SET({raw:member_group}, mem.additional_groups ) !=0)) 
		ORDER BY {raw:sort}',
		array(
			'member_group' => $membergroup,
			'last_posts' => $last_posts,
			'sort' => $sort,
			'inactive_weeks' => $time,
		)
	);

	$activity_checker = array();
	while ($row = $smcFunc['db_fetch_assoc']($request))
	{
		$additionalGroups=!empty($row['additional_groups']) ? explode(',',$row['additional_groups']) : null;
		if (!empty($additionalGroups)) {
			$addGroups=array();
			foreach($additionalGroups as $groupID)
				$addGroups[]=$activityChecker['group_names'][$groupID];
			$additionalGroups=' (' . implode(', ', $addGroups) . ')';
		}
		$activity_checker[] = array(
			'id_member' => $row['id_member'],
			'member_link' => '<a href="' . $scripturl . '?action=profile;u=' . $row['id_member'] . '">' . $row['real_name'] . '</a>',
			'last_post_link' => '<a href="' . $scripturl . '?topic=' . $row['id_topic'] . '.msg' . $row['id_msg'] . '#msg' . $row['id_msg'] . '">' . date('F d, Y, h:i:s a',$row['poster_time']) . '</a>',
			'last_post_time' => $row['poster_time'],
			'groups' => $activityChecker['group_names'][$row['id_group']] . $additionalGroups,
		);
	}
	$smcFunc['db_free_result']($request);

	return $activity_checker;
}

/**
 *  Gets items for no posts list.
 *  
 *  @param int $start
 *  @param int $items_per_page
 *  @param string $sort
 */

function list_getItemsNoPostsChecker($start, $items_per_page, $sort)
{
	global $smcFunc, $txt, $scripturl, $modSettings, $activityChecker;
	
	$result = $smcFunc['db_query']('', '
		SELECT id_member, real_name, date_registered, id_group, additional_groups
			FROM {db_prefix}members
			WHERE posts = 0
			ORDER BY id_member'
	);
		$members = array();
		$memberids = array();
	while($row = $smcFunc['db_fetch_assoc']($result)) {
		
		$additionalGroups=!empty($row['additional_groups']) ? explode(',',$row['additional_groups']) : null;
		if (!empty($additionalGroups)) {
			$addGroups=array();
			foreach($additionalGroups as $groupID)
				$addGroups[]=$activityChecker['group_names'][$groupID];
			$additionalGroups=' (' . implode(', ', $addGroups) . ')';
		}
		$members[$row['id_member']] = array( 
			'id_member' => $row['id_member'],
			'member_link' => '<a href="' . $scripturl . '?action=profile;u=' . $row['id_member'] . '">' . $row['real_name'] . '</a>',
			'last_post_link' => 'Never Posted: Registered on ' . date('F d, Y, h:i:s a',$row['date_registered']),
			'last_post_time' => $row['date_registered'],
			'groups' => $activityChecker['group_names'][$row['id_group']] . $additionalGroups,
		);
		$memberids[] = $row['id_member'];
	}
	
	$smcFunc['db_free_result']($result);
	if (empty($memberids)) {
		return $members;
	}
	// Query to make a list of the 'last post' for each member.
	$result = $smcFunc['db_query']('', '
		SELECT MAX(id_msg) AS id_msg, id_member, id_topic, poster_time
		FROM {db_prefix}messages
		WHERE id_member IN ({array_int:members})
		GROUP BY id_member
		ORDER BY id_member',
		array(
			'members' => $memberids,
		)
	);

	// Make a list of the 'lastest posts' for each member
	while($row = $smcFunc['db_fetch_assoc']($result)) {
		$members[$row['id_member']]['last_post_link'] = '<a href="' . $scripturl . '?topic=' . $row['id_topic'] . '.msg' . $row['id_msg'] . '#msg' . $row['id_msg'] . '">' . date('F d, Y, h:i:s a',$row['poster_time']) . '</a>';
		$members[$row['id_member']]['last_post_time'] = $row['poster_time'];
	}
	// Free the db resource.
	$smcFunc['db_free_result']($result);

	return $members;
}


/**
 *  Adds one or more members to a membergroup
 *  
 *  @param array $members
 *  @param int $group
 *  @param string $type
 *  @param boolean $permissionCheckDone
 *  
 *  Supported types:
 *  - only_primary      - Assigns a membergroup as primary membergroup, but only
 *  					  if a member has not yet a primary membergroup assigned,
 *  					  unless the member is already part of the membergroup.
 *  - only_additional   - Assigns a membergroup to the additional membergroups,
 *  					  unless the member is already part of the membergroup.
 *  - force_primary     - Assigns a membergroup as primary membergroup no matter
 *  					  what the previous primary membergroup was.
 *  - auto              - Assigns a membergroup to the primary group if it's still
 *  					  available. If not, assign it to the additional group.
 */
function activityChecker_addMembersToGroup($members, $group, $type = 'auto', $permissionCheckDone = false)
{
	global $smcFunc, $user_info, $modSettings;

	// Show your licence, but only if it hasn't been done yet.
	if (!$permissionCheckDone)
		isAllowedTo('manage_membergroups');

	// Make sure we don't keep old stuff cached.
	updateSettings(array('settings_updated' => time()));

	if (!is_array($members))
		$members = array((int) $members);
	else
	{
		$members = array_unique($members);

		// Make sure all members are integer.
		foreach ($members as $key => $value)
			$members[$key] = (int) $value;
	}
	$group = (int) $group;

	// Do the actual updates.
	if ($type == 'only_additional')
		$smcFunc['db_query']('', '
			UPDATE {db_prefix}members
			SET additional_groups = CASE WHEN additional_groups = {string:blank_string} THEN {string:id_group_string} ELSE CONCAT(additional_groups, {string:id_group_string_extend}) END
			WHERE id_member IN ({array_int:member_list})
				AND id_group != {int:id_group}
				AND FIND_IN_SET({int:id_group}, additional_groups) = 0',
			array(
				'member_list' => $members,
				'id_group' => $group,
				'id_group_string' => (string) $group,
				'id_group_string_extend' => ',' . $group,
				'blank_string' => '',
			)
		);
	elseif ($type == 'only_primary' || $type == 'force_primary')
		$smcFunc['db_query']('', '
			UPDATE {db_prefix}members
			SET id_group = {int:id_group}
			WHERE id_member IN ({array_int:member_list})' . ($type == 'force_primary' ? '' : '
				AND id_group = {int:regular_group}
				AND FIND_IN_SET({int:id_group}, additional_groups) = 0'),
			array(
				'member_list' => $members,
				'id_group' => $group,
				'regular_group' => 0,
			)
		);
	elseif ($type == 'auto')
		$smcFunc['db_query']('', '
			UPDATE {db_prefix}members
			SET
				id_group = CASE WHEN id_group = {int:regular_group} THEN {int:id_group} ELSE id_group END,
				additional_groups = CASE WHEN id_group = {int:id_group} THEN additional_groups
					WHEN additional_groups = {string:blank_string} THEN {string:id_group_string}
					ELSE CONCAT(additional_groups, {string:id_group_string_extend}) END
			WHERE id_member IN ({array_int:member_list})
				AND id_group != {int:id_group}
				AND FIND_IN_SET({int:id_group}, additional_groups) = 0',
			array(
				'member_list' => $members,
				'regular_group' => 0,
				'id_group' => $group,
				'blank_string' => '',
				'id_group_string' => (string) $group,
				'id_group_string_extend' => ',' . $group,
			)
		);
	// Ack!!?  What happened?
	else
		trigger_error('addMembersToGroup(): Unknown type \'' . $type . '\'', E_USER_WARNING);

	// Update their postgroup statistics.
	updateStats('postgroups', $members);

	return true;
}

/**
 *  Remove one of more members from one or more membergroups.
 *  
 *  @param array $members
 *  @param array $groups
 *  @param boolean $permissionCheckDone
 */
// Remove one or more members from one or more membergroups.
function activityChecker_removeMembersFromGroups($members, $groups = null, $permissionCheckDone = false)
{
	global $smcFunc, $user_info, $modSettings;

	// You're getting nowhere without this permission, unless of course you are the group's moderator.
	if (!$permissionCheckDone)
		isAllowedTo('manage_membergroups');

	// Assume something will happen.
	updateSettings(array('settings_updated' => time()));

	// Cleaning the input.
	if (!is_array($members))
		$members = array((int) $members);
	else
	{
		$members = array_unique($members);

		// Cast the members to integer.
		foreach ($members as $key => $value)
			$members[$key] = (int) $value;
	}

	// Just in case.
	if (empty($members))
		return false;
	elseif (!is_array($groups))
		$groups = array((int) $groups);
	else
	{
		$groups = array_unique($groups);

		// Make sure all groups are integer.
		foreach ($groups as $key => $value)
			$groups[$key] = (int) $value;
	}

	// First, reset those who have this as their primary group - this is the easy one.
	$smcFunc['db_query']('', '
		UPDATE {db_prefix}members
		SET id_group = {int:regular_member}
		WHERE id_group IN ({array_int:group_list})
			AND id_member IN ({array_int:member_list})',
		array(
			'group_list' => $groups,
			'member_list' => $members,
			'regular_member' => 0,
		)
	);

	// Those who have it as part of their additional group must be updated the long way... sadly.
	$request = $smcFunc['db_query']('', '
		SELECT id_member, additional_groups
		FROM {db_prefix}members
		WHERE (FIND_IN_SET({raw:additional_groups_implode}, additional_groups) != 0)
			AND id_member IN ({array_int:member_list})
		LIMIT ' . count($members),
		array(
			'member_list' => $members,
			'additional_groups_implode' => implode(', additional_groups) != 0 OR FIND_IN_SET(', $groups),
		)
	);

	$updates = array();
	while ($row = $smcFunc['db_fetch_assoc']($request))
	{
		$updates[$row['additional_groups']][] = $row['id_member'];
	}
	$smcFunc['db_free_result']($request);

	foreach ($updates as $additional_groups => $memberArray)
		$smcFunc['db_query']('', '
			UPDATE {db_prefix}members
			SET additional_groups = {string:additional_groups}
			WHERE id_member IN ({array_int:member_list})',
			array(
				'member_list' => $memberArray,
				'additional_groups' => implode(',', array_diff(explode(',', $additional_groups), $groups)),
			)
		);
		
	// Their post groups may have changed now...
	updateStats('postgroups', $members);

	// Mission successful.
	return true;
}

/**
 *  Gets previous membergroups of members being changed for logging.
 *	Passes them to a global variable.
 *  
 *  @param array $members
 */

function activityChecker_logPreviousGroups($members) {
	global $smcFunc, $user_info, $activityChecker, $txt, $modSettings;
	
	$request = $smcFunc['db_query']('', '
		SELECT id_member, id_group, additional_groups
		FROM {db_prefix}members
		WHERE id_member IN ({array_int:member_list})',
		array(
			'member_list' => $members,
		)
	);
	$activityChecker['previous_groups'] = array();
	while ($row = $smcFunc['db_fetch_assoc']($request))
	{
		if (!empty($row['additional_groups'])) {
			$groups_array=explode(',',$row['additional_groups']);
			$additionalName = array();
			foreach($groups_array as $groupid) {
				$additionalName[]=$activityChecker['group_names'][$groupid];
			}
			$groups_array=implode(',',$additionalName);
			
			$activityChecker['previous_groups'][$row['id_member']]=array(
				'id_group' => $row['id_group'],
				'additional_groups' => $row['additional_groups'],
				'additional_groups_names' => $groups_array,
			);
		}
		else {
			$activityChecker['previous_groups'][$row['id_member']]=array(
				'id_group' => $row['id_group'],
				'additional_groups' => '',
				'additional_groups_names' => '',
			);
		}
	}
		$smcFunc['db_free_result']($request);

		return true;
}

/**
 *  Gets current membergroups of members being changed for logging
 *	and pulls previous group information from global $activityChecker
 *	and logs the information.
 *  
 *  @param array $members
 */

function activityChecker_logMembergroupChange($members) {
	global $smcFunc, $user_info, $activityChecker, $modSettings, $txt;
	$request = $smcFunc['db_query']('', '
		SELECT id_member, id_group, additional_groups
		FROM {db_prefix}members
		WHERE id_member IN ({array_int:member_list})',
		array(
			'member_list' => $members,
		)
	);
	$activityChecker['new_groups'] = array();
	while ($row = $smcFunc['db_fetch_assoc']($request))
	{
		if (!empty($row['additional_groups'])) {
			$groups_array=explode(',',$row['additional_groups']);
			$additionalName = array();
			foreach($groups_array as $groupid) {
				$additionalName[] = $activityChecker['group_names'][$groupid];
			}
			$groups_array=implode(',',$additionalName);
			$activityChecker['new_groups'][$row['id_member']]=array(
				'id_group' => $row['id_group'],
				'additional_groups' => $row['additional_groups'],
				'additional_groups_names' => $groups_array,
			);
		}
		else {
			$activityChecker['new_groups'][$row['id_member']]=array(
			'id_group' => $row['id_group'],
			'additional_groups' => '',
			'additional_groups_names' => '',
			);
		}
	}
	$smcFunc['db_free_result']($request);
	// Log the data.
	$log_inserts = array();
	foreach ($members as $member) {

		if ($activityChecker['previous_groups'][$member]['id_group'] != $activityChecker['new_groups'][$member]['id_group']) {
			$log_inserts[] = array(
			time(), 2, $member, $user_info['ip'], 'id_group',
			0, 0, 0, serialize(array('previous' => $activityChecker['group_names'][$activityChecker['previous_groups'][$member]['id_group']],'new' => $activityChecker['group_names'][$activityChecker['new_groups'][$member]['id_group']] , 'applicator' => $user_info['id'])),
		);
		}
		if ($activityChecker['previous_groups'][$member]['additional_groups'] != $activityChecker['new_groups'][$member]['additional_groups']) {
			$log_inserts[] = array(
			time(), 2, $member, $user_info['ip'], 'additional_groups',
			0, 0, 0, serialize(array('previous' =>  $activityChecker['previous_groups'][$member]['additional_groups_names'],'new' => $activityChecker['new_groups'][$member]['additional_groups_names'] , 'applicator' => $user_info['id'])),
			);
		}
	}
	
	if (!empty($log_inserts) && !empty($modSettings['modlog_enabled']))
		$smcFunc['db_insert']('',
			'{db_prefix}log_actions',
			array(
				'log_time' => 'int', 'id_log' => 'int', 'id_member' => 'int', 'ip' => 'string-16', 'action' => 'string',
				'id_board' => 'int', 'id_topic' => 'int', 'id_msg' => 'int', 'extra' => 'string-65534',
			),
			$log_inserts,
			array('id_action')
		);
		return true;
}

function activityChecker_membergroupNames() {
	global $txt,$smcFunc,$activityChecker;
	
	$request = $smcFunc['db_query']('', '
		SELECT id_group, group_name
		FROM {db_prefix}membergroups'
	);
	while ($row = $smcFunc['db_fetch_assoc']($request))
		$activityChecker['group_names'][$row['id_group']] = $row['group_name'];
	
	$activityChecker['group_names'][0]=$txt['no_membergroup'];
		$smcFunc['db_free_result']($request);
}
