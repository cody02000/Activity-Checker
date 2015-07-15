<?php
/**
* @package Activity Checker
*
* @author Cody Williams
* @copyright 2015
* @version 1.2
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
  $admin_areas['config']['areas']['activity_checker']=array(
    'label' => $txt['activity_checker_label'],
    'file' => 'ActivityChecker.php',
    'function' => 'activityChecker_adminMain',
    'custom_url' => $scripturl . '?action=admin;area=activity_checker',
    'icon' => 'calendar.gif',
	);
}

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
		'list' => 'activityChecker_list',
	);
	
	

	$_REQUEST['sa'] = isset($_REQUEST['sa']) && isset($subActions[$_REQUEST['sa']]) ? $_REQUEST['sa'] : 'list';

	$subActions[$_REQUEST['sa']]();
}

// The function that handles adding, and deleting holiday data
function activityChecker_list($return_config = false)
{
	global $sourcedir, $txt, $scripturl, $context, $settings, $sc, $modSettings, $smcFunc;
	require_once($sourcedir . '/ManageServer.php');
	// Submitting something...
	if (isset($_REQUEST['mark_inactive']))
	{
		checkSession();

	}
	else {
		$config_vars = array(
			array('text', 'activityChecker_NotMembergroups', 10),
		);

		$listOptions = array(
			'id' => 'activity_list',
			'title' => $txt['activity_checker_list_title'],
			'items_per_page' => 10,
			'base_href' => $scripturl . '?action=admin;area=activity_checker',
			'default_sort_col' => 'last_post',
			'get_items' => array(
				'function' => 'list_ActivityChecker',
			),
			'get_count' => array(
				'function' => 'activityCheckerListGetNumEvents',
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
							'format' => '<input type="checkbox" name="member[%1$d]" class="input_check" />',
							'params' => array(
								'id_member' => false,
							),
						),
						'style' => 'text-align: center',
					),
				),
			),
			'form' => array(
				'href' => $scripturl . '?action=admin;area=activity_checker;sa=list',
			),
			'additional_rows' => array(
				array(
					'position' => 'below_table_data',
					'value' => '
						<a href="' . $scripturl . '?action=admin;area=rpg_cal;sa=edit_event" style="margin: 0 1em">[' . $txt['rpg_cal_event_add'] . ']</a>
						<input type="submit" name="delete" value="' . $txt['quickmod_delete_selected'] . '" class="button_submit" />',
					'style' => 'text-align: right;',
				),
			),
		);

		require_once($sourcedir . '/Subs-List.php');
		createList($listOptions);
		
		if ($return_config)
			return $config_vars;
		
		if(isset($_GET['update'])) {
			//	Make sure that an admin is doing the updating.
			checkSession();	
			//	Save the config vars.
			writeLog();
			saveDBSettings($config_vars);
			redirectexit("action=admin;area=activity_checker;sa=list;");
			}
		
		$context['page_title'] = $txt['activity_checker_label'];
		//	Set up the variables needed by the template.
		$context['settings_title'] = $txt['activity_checker_settings'];	
		$context['default_list'] = 'activity_list';
		$context['post_url'] = $scripturl . '?action=admin;area=activity_checker;sa=list;update';
		loadTemplate('ActivityChecker');
		
		$context['sub_template'] = 'general_settings';
		//	Finally prepare the settings array to be shown by the 'show_settings' template.
		prepareDBSettingContext($config_vars);
	}
}

function activityCheckerListGetNumEvents()
{
	global $smcFunc, $txt, $scripturl, $modSettings;
	
	$memberGroups=array_map('intval',explode(',',$modSettings['activityChecker_NotMembergroups']));
	
	$result = $smcFunc['db_query']('', '
		SELECT id_member
			FROM {db_prefix}members
			WHERE id_group NOT IN ({array_int:membergroups})',
		array(
			'membergroups' => $memberGroups,
		)
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
		GROUP BY id_member',
		array(
			'members' => $members,
		)
	);
	
	// Make a list of the 'lastest posts' for each member
	$last_posts = array();
	while($row = $smcFunc['db_fetch_assoc']($result))
		$last_posts[] = $row['id_msg'];
	
	// Free the db resource.
	$smcFunc['db_free_result']($result);
	
	$request = $smcFunc['db_query']('', '
		SELECT mem.id_member, mem.real_name, mem.usertitle, mem.date_registered,mem.id_group,
			msg.id_member, msg.id_msg, msg.id_topic, msg.poster_time
		FROM {db_prefix}messages AS msg
			INNER JOIN {db_prefix}members AS mem ON (mem.id_member=msg.id_member)
		WHERE msg.id_msg IN ({array_int:last_posts})
			AND mem.id_group NOT IN ({array_int:member_groups})
			AND msg.poster_time >= {int:inactive_weeks}',
		array(
			'member_groups' => $memberGroups,
			'last_posts' => $last_posts,
			'inactive_weeks' => time()-(60*60*24*(7*6)),
		)
	);
	$activity_checker = array();
	while ($row = $smcFunc['db_fetch_assoc']($request))
	{
		$activity_checker[] = array(
			'id_member' => $row['id_member'],
			'member_link' => '<a href="' . $scripturl . '?action=profile;u=' . $row['id_member'] . '">' . $row['real_name'] . '</a>',
			'last_post_link' => '<a href="' . $scripturl . '?topic=' . $row['id_topic'] . '.msg' . $row['id_msg'] . '#msg' . $row['id_msg'] . '">' . $row['poster_time'] . '</a>',
			'last_post_time' => $row['poster_time'],
		);
	}
	$smcFunc['db_free_result']($request);
  
  
  
 $num_items = count($activity_checker);
 var_dump($num_items);

  return $num_items;
}

function list_ActivityChecker($start, $items_per_page, $sort)
{
	global $smcFunc, $txt, $scripturl, $modSettings;
	
	$memberGroups=array_map('intval',explode(',',$modSettings['activityChecker_NotMembergroups']));
	var_dump($memberGroups);
	
	$result = $smcFunc['db_query']('', '
		SELECT id_member
			FROM {db_prefix}members
			WHERE id_group NOT IN ({array_int:membergroups})
			ORDER BY id_member',
		array(
			'membergroups' => $memberGroups,
		)
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
		GROUP BY id_member
		ORDER BY id_member',
		array(
			'members' => $members,
		)
	);
	
	// Make a list of the 'lastest posts' for each member
	$last_posts = array();
	while($row = $smcFunc['db_fetch_assoc']($result))
		$last_posts[] = $row['id_msg'];
	
	// Free the db resource.
	$smcFunc['db_free_result']($result);
	
	$request = $smcFunc['db_query']('', '
		SELECT mem.id_member, mem.real_name, mem.usertitle, mem.date_registered,mem.id_group,
			msg.id_member, msg.id_msg, msg.id_topic, msg.poster_time
		FROM {db_prefix}messages AS msg
			INNER JOIN {db_prefix}members AS mem ON (mem.id_member=msg.id_member)
		WHERE msg.id_msg IN ({array_int:last_posts})
			AND mem.id_group NOT IN ({array_int:member_groups})
			AND msg.poster_time >= {int:inactive_weeks}
		ORDER BY {raw:sort}
		LIMIT ' . $start . ', ' . $items_per_page,		
		array(
			'member_groups' => $memberGroups,
			'last_posts' => $last_posts,
			'sort' => $sort,
			'inactive_weeks' => time()-(60*60*24*(7*6)),
		)
	);
	$activity_checker = array();
	while ($row = $smcFunc['db_fetch_assoc']($request))
	{
		$activity_checker[] = array(
			'id_member' => $row['id_member'],
			'member_link' => '<a href="' . $scripturl . '?action=profile;u=' . $row['id_member'] . '">' . $row['real_name'] . '</a>',
			'last_post_link' => '<a href="' . $scripturl . '?topic=' . $row['id_topic'] . '.msg' . $row['id_msg'] . '#msg' . $row['id_msg'] . '">' . $row['poster_time'] . '</a>',
			'last_post_time' => $row['poster_time'],
		);
	}
	$smcFunc['db_free_result']($request);
	foreach ($activity_checker as $key => $val) {
		echo $key .', '.$val['id_member'].', '.$val['member_link'] .'<br />';
	}
	return $activity_checker;
}