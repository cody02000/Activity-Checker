<?php
/**
* @package Activity Checker
*
* @author Cody Williams
* @copyright 2015
* @version 1.0.1
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

// The main controlling function doesn't have much to do... yet.
function activityChecker_adminMain()
{
	global $context, $txt, $scripturl, $modSettings;
	loadTemplate('ActivityChecker');
	loadLanguage('ActivityChecker');

	// Default text.
	$context['explain_text'] = $txt['activity_checker_desc'];

	// Little short on the ground of functions here... but things can and maybe will change...
	$subActions = array(
		'inactive_list' => 'activityChecker_inactiveList',
		'active_list' => 'activityChecker_activeList',
		'no_posts_list' => 'activityChecker_noPostsList',
		'settings' => 'activityChecker_settings',
		'pm_email_settings' => 'activityChecker_pm_email_settings',
	);
	
	

	$_REQUEST['sa'] = isset($_REQUEST['sa']) && isset($subActions[$_REQUEST['sa']]) ? $_REQUEST['sa'] : 'settings';

	
	  // Set up the two tabs here...
	$context[$context['admin_menu_name']]['tab_data'] = array(
	'title' => $txt['activity_checker_label'],
	'help' => '',
	'description' => $txt['activity_checker_desc'],
	'tabs' => array(
		'settings' => array(
			'description' => $txt['activity_checker_general_desc'],
		),
		'pm_email_settings' => array(
			'description' => $txt['activity_checker_pm_email_desc'],
		),
		'inactive_list' => array(
			'description' => $txt['activity_checker_inactive_desc'],
		),
		'active_list' => array(
			'description' => $txt['activity_checker_active_desc'],
		),
		'no_posts_list' => array(
			'description' => $txt['activity_checker_no_posts_desc'],
		),
		
	),
	);

	$subActions[$_REQUEST['sa']]();
}

/**
 *  Creates a list of inactive members based on being in the active group
 *	and not posting in a given amount of time.
 *
 *	Allows moving those members to designated inactive group, and if enabled
 *	will send personal message to those members moved.
 *  
 *  @param array $return_config
 */
function activityChecker_inactiveList($return_config = false)
{
	global $sourcedir, $txt, $scripturl, $context, $settings, $sc, $modSettings, $smcFunc, $activityChecker;
	require_once($sourcedir . '/ManageServer.php');
	
	if (!empty($modSettings['activity_checker_inactive_group']) && !empty($modSettings['activity_checker_active_group']) && !empty($modSettings['activity_checker_inactive_time']) && !empty($modSettings['activity_checker_categories'])) {
		//get membergroup names for logging and lists.  returns $activityChecker['group_names']
		activityChecker_membergroupNames();
		// Submitting something...
		if (isset($_POST['mark_inactive']))
		{
			$result = $smcFunc['db_query']('', '
				SELECT member_name, real_name
				FROM {db_prefix}members
				WHERE id_member IN ({array_int:members})
				ORDER BY real_name',
				array(
					'members' => $_POST['member'],
				)
			);
			
			
			$realnames = array();
			$membernames = array();
			while($row = $smcFunc['db_fetch_assoc']($result)) {
				$realnames[] = $row['real_name'];
				$membernames[] = $row['member_name'];
			}
			
			// Free the db resource.
			$smcFunc['db_free_result']($result);

			checkSession();

			activityChecker_logPreviousGroups($_POST['member']);
			activityChecker_removeMembersFromGroups($_POST['member'], $modSettings['activity_checker_active_group']);
			activityChecker_addMembersToGroup($_POST['member'], $modSettings['activity_checker_inactive_group'], 'auto');
			activityChecker_logMembergroupChange($_POST['member']);
			logAction('inactive_check',array('members' => implode(', ',$realnames), 'group_from' => $activityChecker['group_names'][$modSettings['activity_checker_active_group']], 'group_to' => $activityChecker['group_names'][$modSettings['activity_checker_inactive_group']]),'admin');
			
			if (!empty($modSettings['activity_checker_inactive_pm_enable']) && !empty($membernames)) {
				$subject = empty($modSettings['activity_checker_inactive_pm_subject']) ? $txt['activity_checker_default_pm_subject'] : $modSettings['activity_checker_inactive_pm_subject'];
				$message = empty($modSettings['activity_checker_inactive_pm_message']) ? $txt['activity_checker_default_pm_message'] : $modSettings['activity_checker_inactive_pm_message'];
				require_once($sourcedir . '/Subs-Post.php');
				$recipients['to'] = $membernames;
				$recipients['bcc'] = empty($modSettings['activity_checker_inactive_pm_bcc']) ? array() : explode(',', $modSettings['activity_checker_inactive_pm_bcc']);
				if (!empty($modSettings['activity_checker_inactive_pm_from'])) {
					list ($from['id'], $from['username'], $from['name']) =  explode(';', $modSettings['activity_checker_inactive_pm_from']);
				}
				else 
					$from=null;
				
				sendpm($recipients, $subject, $message, true, $from, 0);
			}
		}

			$categories = array_map('intval',explode(',',$modSettings['activity_checker_categories']));
			$result = $smcFunc['db_query']('', '
				SELECT id_board
				FROM {db_prefix}boards
				WHERE id_cat IN ({array_int:cats})',
				array(
					'cats' => $categories,
				)
			);
			$boards = array();
			while($row = $smcFunc['db_fetch_assoc']($result))
				$boards[] = $row['id_board'];
		// Free the db resource.
		$smcFunc['db_free_result']($result);
		$posttime=time()-(60*60*24*(7*$modSettings['activity_checker_inactive_time']));
			$listOptions = array(
				'id' => 'activity_list',
				'title' => $txt['activity_checker_inactive_list_title'],
				'base_href' => $scripturl . '?action=admin;area=activity_checker',
				'default_sort_col' => 'last_post',
				'get_items' => array(
					'function' => 'list_getItemsActivityChecker',
					'params' => array(
					(int) $modSettings['activity_checker_active_group'], 
					$boards,
					' <= ' . $posttime, //time()-(60*60*24*(7*$modSettings['activity_checker_inactive_time'])),
					),
				),
				'no_items_label' => $txt['activity_checker_no_inactive'],
				'columns' => array(
					'name' => array(
						'header' => array(
							'value' => $txt['activity_checker_member_name_title'],
						),
						'data' => array(
							'db' => 'member_link',
						),
						'sort' => array(
							'default' => 'real_name',
							'reverse' => 'real_name DESC',
						)
					),
					'groups' => array(
						'header' => array(
							'value' => $txt['activity_checker_groups_title'],
						),
						'data' => array(
							'db' => 'groups',
						),
					),
					'last_post' => array(
						'header' => array(
							'value' => $txt['last-post'],
						),
						'data' => array(
							'db' => 'last_post_link',
						),
						'sort' => array(
							'default' => 'msg.poster_time',
							'reverse' => 'msg.poster_time DESC',
						),
					),
					'check' => array(
						'header' => array(
							'value' => '<input type="checkbox" onclick="invertAll(this, this.form);" class="input_check" />',
						),
						'data' => array(
							'sprintf' => array(
								'format' => '<input type="checkbox" name="member[]" value="%1$d" class="input_check" />',
								'params' => array(
									'id_member' => false,
								),
							),
							'style' => 'text-align: center',
						),
					),
				),
				'form' => array(
					'href' => $scripturl . '?action=admin;area=activity_checker;sa=inactive_list',
				),
				'additional_rows' => array(
					array(
						'position' => 'below_table_data',
						'value' => !empty($modSettings['activity_checker_inactive_pm_enable']) ? $txt['activity_checker_inactive_pm_enabled'] : '',
						'style' => 'text-align: right;',
					),
					array(
						'position' => 'below_table_data',
						'value' => '
							<input type="submit" name="mark_inactive" value="' . $txt['activity_checker_mark_inactive'] . '" class="button_submit" />',
						'style' => 'text-align: right;',
					),
					array(
						'position' => 'top_of_list',
						'value' => $txt['activity_checker_cutoff'] . date('F d, Y, h:i:s a',$posttime) . ' (' . $modSettings['activity_checker_inactive_time'] . $txt['activity_checker_weeks'] .')',
					),
				),
			);

			require_once($sourcedir . '/Subs-List.php');
			createList($listOptions);
			
			$context['page_title'] = $txt['activity_checker_inactive_list'];
			//	Set up the variables needed by the template.
			$context['default_list'] = 'activity_list';
			loadTemplate('ActivityChecker');
			
			$context['sub_template'] = 'inactive';
	}
	else {
		$context['page_title'] = $txt['activity_checker_inactive_list'];
		loadTemplate('ActivityChecker');
			
		$context['sub_template'] = 'not_enabled';
	}
}

/**
 *  Creates a list of active members based on being in the inactive group
 *	and having posted within a given amount a time.
 *
 *	Allows moving those members to designated active group.
 *  
 *  @param array $return_config
 */
function activityChecker_activeList($return_config = false)
{
	global $sourcedir, $txt, $scripturl, $context, $settings, $sc, $modSettings, $smcFunc, $activityChecker;
	require_once($sourcedir . '/ManageServer.php');
	if (!empty($modSettings['activity_checker_inactive_group']) && !empty($modSettings['activity_checker_active_group']) && !empty($modSettings['activity_checker_inactive_time']) && !empty($modSettings['activity_checker_categories'])) {
		//get membergroup names for logging and lists.  returns $activityChecker['group_names']
		activityChecker_membergroupNames();
		// Submitting something...
		if (isset($_POST['mark_active']))
		{
			$result = $smcFunc['db_query']('', '
				SELECT member_name, real_name
				FROM {db_prefix}members
				WHERE id_member IN ({array_int:members})
				ORDER BY real_name',
				array(
					'members' => $_POST['member'],
				)
			);
			
			
			$realnames = array();
			$membernames = array();
			while($row = $smcFunc['db_fetch_assoc']($result)) {
				$realnames[] = $row['real_name'];
				$membernames[] = $row['member_name'];
			}
			
			// Free the db resource.
			$smcFunc['db_free_result']($result);
			
			checkSession();

			activityChecker_logPreviousGroups($_POST['member']);
			activityChecker_removeMembersFromGroups($_POST['member'], $modSettings['activity_checker_inactive_group']);
			activityChecker_addMembersToGroup($_POST['member'], $modSettings['activity_checker_active_group'], 'only_additional');
			activityChecker_logMembergroupChange($_POST['member']);
			logAction('active_check',array('members' => implode(', ',$realnames), 'group_from' => $activityChecker['group_names'][$modSettings['activity_checker_inactive_group']], 'group_to' => $activityChecker['group_names'][$modSettings['activity_checker_active_group']]),'admin');
		}

			$categories = array_map('intval',explode(',',$modSettings['activity_checker_categories']));
			$result = $smcFunc['db_query']('', '
				SELECT id_board
				FROM {db_prefix}boards
				WHERE id_cat IN ({array_int:cats})',
				array(
					'cats' => $categories,
				)
			);
			$boards = array();
			while($row = $smcFunc['db_fetch_assoc']($result))
				$boards[] = $row['id_board'];
		// Free the db resource.
		$smcFunc['db_free_result']($result);
			$posttime=time()-(60*60*24*(7*$modSettings['activity_checker_inactive_time']));
			
			$listOptions = array(
				'id' => 'activity_list',
				'title' => $txt['activity_checker_active_list_title'],
				'base_href' => $scripturl . '?action=admin;area=activity_checker',
				'default_sort_col' => 'last_post',
				'get_items' => array(
					'function' => 'list_getItemsActivityChecker',
					'params' => array(
					(int) $modSettings['activity_checker_inactive_group'], 
					$boards,
					' >= ' . $posttime,
					),
				),
				'no_items_label' => $txt['activity_checker_no_active'],
				'columns' => array(
					'name' => array(
						'header' => array(
							'value' => $txt['activity_checker_member_name_title'],
						),
						'data' => array(
							'db' => 'member_link',
						),
						'sort' => array(
							'default' => 'real_name',
							'reverse' => 'real_name DESC',
						)
					),
					'groups' => array(
						'header' => array(
							'value' => $txt['activity_checker_groups_title'],
						),
						'data' => array(
							'db' => 'groups',
						),
					),
					'last_post' => array(
						'header' => array(
							'value' => $txt['last-post'],
						),
						'data' => array(
							'db' => 'last_post_link',
						),
						'sort' => array(
							'default' => 'msg.poster_time',
							'reverse' => 'msg.poster_time DESC',
						),
					),
					'check' => array(
						'header' => array(
							'value' => '<input type="checkbox" onclick="invertAll(this, this.form);" class="input_check" />',
						),
						'data' => array(
							'sprintf' => array(
								'format' => '<input type="checkbox" name="member[]" value="%1$d" class="input_check" />',
								'params' => array(
									'id_member' => false,
								),
							),
							'style' => 'text-align: center',
						),
					),
				),
				'form' => array(
					'href' => $scripturl . '?action=admin;area=activity_checker;sa=active_list',
				),
				'additional_rows' => array(
					array(
						'position' => 'below_table_data',
						'value' => '
							<input type="submit" name="mark_active" value="' . $txt['activity_checker_mark_active'] . '" class="button_submit" />',
						'style' => 'text-align: right;',
					),
					array(
						'position' => 'top_of_list',
						'value' => $txt['activity_checker_cutoff'] . date('F d, Y, h:i:s a',$posttime) . ' (' . $modSettings['activity_checker_inactive_time'] . $txt['activity_checker_weeks'] .')',
					),
				),
			);
			
			require_once($sourcedir . '/Subs-List.php');
			createList($listOptions);
			
			$context['page_title'] = $txt['activity_checker_active_list'];
			//	Set up the variables needed by the template.
			$context['default_list'] = 'activity_list';		
			$context['sub_template'] = 'inactive';
	}
	else {
		$context['page_title'] = $txt['activity_checker_inactive_list'];
		loadTemplate('ActivityChecker');
			
		$context['sub_template'] = 'not_enabled';
	}
}

/**
 *  Creates a list of members who have 0 post count,
 *	and pulls the last post of members who have posted on
 *	boards that are not counted toward post counts.
 *	
 
 *	Allows deletion of those members, and if enabled
 *	sends email on deletion to those members.
 *  
 *  @param array $return_config
 */

function activityChecker_noPostsList() {
	global $sourcedir, $txt, $scripturl, $context, $settings, $sc, $modSettings, $smcFunc, $activityChecker,$mbname;
	
	//get membergroup names for logging and lists.  returns $activityChecker['group_names']
	activityChecker_membergroupNames();
		
	if (isset($_POST['delete_members']) && !empty($_POST['member']))
	{
		checkSession();
		$request = $smcFunc['db_query']('', '
		SELECT id_member, member_name, real_name, email_address
		FROM {db_prefix}members
		WHERE id_member IN ({array_int:members})',
			array(
				'members' => $_POST['member'],
			)
		);
		if (!empty($modSettings['activity_checker_email_enable'])) {
			$member_info = array();
			// Fill the info array.
			while ($row = $smcFunc['db_fetch_assoc']($request))
			{
				$members[] = $row['id_member'];
				$member_info[] = array(
					'id' => $row['id_member'],
					'username' => $row['member_name'],
					'name' => $row['real_name'],
					'email' => $row['email_address'],
				);
			}
			$smcFunc['db_free_result']($request);
			
			require_once($sourcedir . '/Subs-Post.php');
			
			// Send email telling them their account was deleted due to never posting.
			$replacements = array(
					'EMAILSUBJECT' => empty($modSettings['activity_checker_email_subject']) ? $txt['activity_checker_default_email_subject'] : $modSettings['activity_checker_email_subject'],
					'EMAILBODY' => empty($modSettings['activity_checker_email_message']) ? $txt['activity_checker_default_email_message'] : $modSettings['activity_checker_email_message'],
					'FORUM' => $mbname,
					);
			foreach ($member_info as $member)
			{
				$replacements['RECPNAME'] = $member['name'];
				
				$emaildata = loadEmailTemplate('send_email', $replacements);
				sendmail($member['email'], $emaildata['subject'], $emaildata['body'], null, null, false, 1);
			}
		}
		require_once($sourcedir . '/Subs-Members.php');
		deleteMembers($_POST['member']);
	}
	
	$context['sub_template'] = 'no_posts';
	$context['page_title'] = $txt['activity_checker_no_posts_list'];
	
	$listOptions = array(
			'id' => 'no_post_list',
			'title' => $txt['activity_checker_no_post_list_title'],
			'base_href' => $scripturl . '?action=admin;area=activity_checker',
			'get_items' => array(
				'function' => 'list_getItemsNoPostsChecker',
			),
			'no_items_label' => $txt['activity_checker_no_no_posts'],
			'columns' => array(
				'name' => array(
					'header' => array(
						'value' => $txt['activity_checker_member_name_title'],
					),
					'data' => array(
						'db' => 'member_link',
					),
				),
				'groups' => array(
					'header' => array(
						'value' => $txt['activity_checker_groups_title'],
					),
					'data' => array(
							'db' => 'groups',
					),
				),
				'last_post' => array(
					'header' => array(
						'value' => $txt['last-post'],
					),
					'data' => array(
						'db' => 'last_post_link',
					),
				),
				'check' => array(
					'header' => array(
						'value' => '<input type="checkbox" onclick="invertAll(this, this.form);" class="input_check" />',
					),
					'data' => array(
						'sprintf' => array(
							'format' => '<input type="checkbox" name="member[]" value="%1$d" class="input_check" />',
							'params' => array(
								'id_member' => false,
							),
						),
						'style' => 'text-align: center',
					),
				),
			),
			'form' => array(
				'href' => $scripturl . '?action=admin;area=activity_checker;sa=no_posts_list',
			),
			'additional_rows' => array(
				array(
						'position' => 'below_table_data',
						'value' => !empty($modSettings['activity_checker_email_enable']) ? $txt['activity_checker_email_enabled'] : '',
						'style' => 'text-align: right;',
				),
				array(
					'position' => 'below_table_data',
					'value' => '<input type="submit" name="delete_members" value="' . $txt['admin_delete_members'] . '" onclick="return confirm(\'' . $txt['confirm_delete_members'] . '\');" class="button_submit" />',
					'style' => 'text-align: right;',
				),
			),
		);
		
		require_once($sourcedir . '/Subs-List.php');
		createList($listOptions);
		
		//	Set up the variables needed by the template.
		$context['default_list'] = 'no_post_list';		
}

/**
 *  General settings for inactive time, inactive group, active group
 *	and categories to check for posts.
 *  
 *
 */

function activityChecker_settings()
{
	global $txt, $scripturl, $context, $settings, $sc, $smcFunc;
	global $modSettings, $sourcedir;

	$context['page_title'] = $txt['activity_checker_settings'];
	$context['sub_template'] = 'general_settings';
	// Are we saving any standard field changes?
	if (isset($_POST['save']))
	{
		checkSession();
			
			$changes['activity_checker_inactive_time'] = isset($_POST['activity_checker_inactive_time']) ? $_POST['activity_checker_inactive_time'] : 0;
			$changes['activity_checker_inactive_group'] = isset($_POST['inactiveGroup']) ? $_POST['inactiveGroup'] : 0;
			$changes['activity_checker_active_group'] = isset($_POST['activeGroup']) ? $_POST['activeGroup'] : 0;
			$changes['activity_checker_categories'] = isset($_POST['catChecked']) ? implode(',', $_POST['catChecked']) : 0;
		
		if (!empty($changes))
			updateSettings($changes);
	}
			
	require_once($sourcedir . '/Subs-List.php');

	$listOptions = array(
		'id' => 'activity_checker_membergroup_selector',
		'title' => $txt['activity_checker_membergroup_settings_title'],
		'base_href' => $scripturl . '?action=admin;area=activity_checker;sa=settings',
		'get_items' => array(
			'function' => 'list_getActivityCheckerSettings',
			'params' => array(
				true,
			),
		),
		'columns' => array(
			'membergroup' => array(
				'header' => array(
					'value' => $txt['activity_checker_membergroup_col'],
					'style' => 'text-align: left;',
				),
				'data' => array(
					'db' => 'membergroup_link',
					'style' => 'width: 30%;',
				),
			),
			'group_description' => array(
				'header' => array(
					'value' => $txt['activity_checker_description_col'],
					'style' => 'text-align: left;',
				),
				'data' => array(
					'db' => 'description',
					'style' => 'width: 50%;',
				),
			),
			'activeGroup' => array(
				'header' => array(
					'value' => $txt['activity_checker_activeGroup_col'],
				),
				'data' => array(
					'function' => create_function('$rowData', '
						$isChecked = $rowData[\'activeGroup\'] ? \' checked="checked"\' : \'\';
						return sprintf(\'<input type="radio" name="activeGroup" id="activeGroup_%1$s" value="%1$s" class="input_radio"%2$s />\', $rowData[\'id_group\'], $isChecked);
					'),
					'style' => 'width: 20%; text-align: center;',
				),
			),
			'inactiveGroup' => array(
				'header' => array(
					'value' => $txt['activity_checker_inactiveGroup_col'],
				),
				'data' => array(
					'function' => create_function('$rowData', '
						$isChecked = $rowData[\'inactiveGroup\'] ? \' checked="checked"\' : \'\';
						return sprintf(\'<input type="radio" name="inactiveGroup" id="inactiveGroup_%1$s" value="%1$s" class="input_radio"%2$s />\', $rowData[\'id_group\'], $isChecked);
					'),
					'style' => 'width: 20%; text-align: center;',
				),
			),
		),
		'additional_rows' => array(
			array(
				'position' => 'after_title',
				'value' => $txt['activity_checker_membergroup_desc'],
				),
		)
	);
	createList($listOptions);

	$listOptions = array(
		'id' => 'activity_checker_category_selector',
		'title' => $txt['activity_checker_category_settings_title'],
		'base_href' => $scripturl . '?action=admin;area=activity_checker;sa=settings',
		'get_items' => array(
			'function' => 'list_getActivityCheckerSettings',
			'params' => array(
				false,
			),
		),
		'columns' => array(
			'category' => array(
				'header' => array(
					'value' => $txt['activity_checker_category_col'],
					'style' => 'text-align: left;',
				),
				'data' => array(
					'db' => 'category_link',
					'style' => 'width: 40%;',
				),
			),
			'catChecked' => array(
				'header' => array(
					'value' => $txt['activity_checker_catChecked_col'],
				),
				'data' => array(
					'function' => create_function('$rowData', '
						$isChecked = $rowData[\'catChecked\'] ? \' checked="checked"\' :\'\';
						return sprintf(\'<input type="checkbox" name="catChecked[]" id="catChecked_%1$s" value="%1$s" class="input_check"%2$s />\', $rowData[\'id_cat\'], $isChecked);
					'),
					'style' => 'width: 60%; text-align: center;',
				),
			),
		),
		'additional_rows' => array(
			array(
				'position' => 'after_title',
				'value' => $txt['activity_checker_category_desc'],
				),
		)
	);
	createList($listOptions);
}

/**
 *  Gets items for general settings membergroup and categories lists.
 *  
 *  @param int $start
 *  @param int $items_per_page
 *  @param string $sort
 *  @param boolean $groups
 */

function list_getActivityCheckerSettings($start, $items_per_page, $sort, $groups)
{
	global $txt, $modSettings, $smcFunc, $scripturl;

	$list = array();

	if ($groups) {
		if (!isset($modSettings['activity_checker_active_group']))
			$modSettings['activity_checker_active_group'] = 0;
		if (!isset($modSettings['activity_checker_inactive_group']))
			$modSettings['activity_checker_inactive_group'] = 0;
		// Load all the fields.
		$request = $smcFunc['db_query']('', '
			SELECT id_group, group_name, description
			FROM {db_prefix}membergroups
			WHERE group_type != {int:protected_group}
			AND min_posts= {int:not_post_group}
			AND id_group NOT IN ({array_int:implicit_groups})',
			array (
				'implicit_groups' => array(-1, 0, 1, 3),
				'not_post_group' => -1,
				'protected_group' => 1,
			)
		);
		while ($row = $smcFunc['db_fetch_assoc']($request)) {
			$list[] = array(
				'membergroup_link' => '<a href="' . $scripturl . '?action=admin;area=membergroups;sa=members;group=' . $row['id_group'] . '">' . $row['group_name'] . '</a>',
				'id_group' => $row['id_group'],
				'description' => $row['description'],
				'activeGroup'=> $row['id_group']==$modSettings['activity_checker_active_group'],
				'inactiveGroup'=> $row['id_group']==$modSettings['activity_checker_inactive_group'],
				
			);
		}
		$smcFunc['db_free_result']($request);
	}

	else {
		$request = $smcFunc['db_query']('', '
			SELECT id_cat, name
			FROM {db_prefix}categories'
		);
		$checkedCat = isset($modSettings['activity_checker_categories']) ? explode(',', $modSettings['activity_checker_categories']) : array();
		while ($row = $smcFunc['db_fetch_assoc']($request)) {
			$list[] = array(
				'category_link' => '<a href="' . $scripturl . '?action=admin;area=manageboards;sa=cat;cat=' . $row['id_cat'] . '">' . $row['name'] . '</a>',
				'id_cat' => $row['id_cat'],
				'catChecked' => in_array($row['id_cat'], $checkedCat),
			);
		}
		
		$smcFunc['db_free_result']($request);
	}
	
	return $list;
}

/**
 *  Settings for sending PM on move to inactive group and for sending
 *	an email when a member is deleted from the no posts list.
 *
 *
 *  @param array $return_config
 */

function activityChecker_pm_email_settings ($return_config = false) {
	global $sourcedir, $txt, $scripturl, $context, $settings, $sc, $modSettings, $smcFunc;
	require_once($sourcedir . '/ManageServer.php');
	
	$request = $smcFunc['db_query']('', '
			SELECT id_group
			FROM {db_prefix}permissions
			WHERE permission LIKE "manage_membergroups"'
		);
		$membergroups=array(1);
		while($row = $smcFunc['db_fetch_assoc']($request)) {
			$membergroups[] = $row['id_group'];
		}
		
	$smcFunc['db_free_result']($request);
	$request = $smcFunc['db_query']('', '
			SELECT id_member, member_name, real_name
			FROM {db_prefix}members
			WHERE id_group IN ({array_int:membergroups})
			ORDER BY real_name',
			array(
				'membergroups' => $membergroups,
			)
	);
	$from_options=array();
	$from_options[0]='User Performing Check';
	while($row = $smcFunc['db_fetch_assoc']($request)) {
		$options=implode(';', array($row['id_member'],$row['member_name'],$row['real_name'],));
		$from_options[$options] = $row['real_name'];
	}
	$smcFunc['db_free_result']($request);

	$context['sub_template'] = 'pm_email_settings';
	$context['page_title'] = $txt['activity_checker_pm_email_settings'];
	
		$config_vars = array(
			array('check', 'activity_checker_inactive_pm_enable'),
			array('select', 'activity_checker_inactive_pm_from', $from_options),
			array('text', 'activity_checker_inactive_pm_bcc', 75),
			array('text', 'activity_checker_inactive_pm_subject', 75),
			array('large_text', 'activity_checker_inactive_pm_message', 10, 'subtext'=>$txt['activity_checker_inactive_pm_message_desc']),
						'',
			array('check', 'activity_checker_email_enable'),
			array('text', 'activity_checker_email_subject', 75),
			array('large_text', 'activity_checker_email_message', 15, 'subtext'=>$txt['activity_checker_email_message_desc']),
		);
		
		if ($return_config)
			return $config_vars;
		
		if(isset($_GET['update'])) {
			$changes['activity_checker_inactive_pm_enable'] = isset($_POST['activity_checker_inactive_pm_enable']) ? $_POST['activity_checker_inactive_pm_enable'] : 0;
			$changes['activity_checker_inactive_pm_bcc'] = $_POST['activity_checker_inactive_pm_bcc'];
			$changes['activity_checker_inactive_pm_subject'] = $_POST['activity_checker_inactive_pm_subject'];
			$changes['activity_checker_inactive_pm_message'] = $_POST['activity_checker_inactive_pm_message'];
			$changes['activity_checker_inactive_pm_from'] = isset($_POST['activity_checker_inactive_pm_from']) ? $_POST['activity_checker_inactive_pm_from'] : null;
			$changes['activity_checker_email_enable'] = isset($_POST['activity_checker_email_enable']) ? $_POST['activity_checker_email_enable'] : 0;
			$changes['activity_checker_email_subject'] = $_POST['activity_checker_email_subject'];
			$changes['activity_checker_email_message'] = $_POST['activity_checker_email_message'];
			//	Make sure that an admin is doing the updating.

			checkSession();	
			//	Save the config vars.
			writeLog();
					
			if (!empty($changes))
				updateSettings($changes);
			}
		
		
	//	Set up the variables needed by the template.
		$context['settings_title'] = $txt['activity_checker_pm_email_settings'];	
		$context['post_url'] = $scripturl . '?action=admin;area=activity_checker;sa=pm_email_settings;update';

		//	Finally prepare the settings array to be shown by the 'show_settings' template.
		prepareDBSettingContext($config_vars);

}