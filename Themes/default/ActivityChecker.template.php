<?php
/**
* @package Activity Checker
*
* @author Cody Williams
* @copyright 2016
* @version 1.0.4
* @license BSD 3-clause
*/

// Editing or adding holidays.




function template_general_settings()
{
	global $context, $settings, $options, $scripturl, $txt, $modSettings,$themeurl;

	
	echo '<form action="' , $scripturl , '?action=admin;area=activity_checker;sa=settings" method="post" name="ActivityCheckerSettings" id="ActivityCheckerSettings" accept-charset="', $context['character_set'] , '">
	 <dl class="settings">';

 echo '<dt>
		<a id="activity_checker_inactive_time" href="' , $scripturl , '?action=helpadmin;help=' , $txt['activity_checker_inactive_time_help'] , '" onclick="return reqWin(this.href);" class="help"><img src="', $settings['images_url'] ,'/helptopics.gif" class="icon" alt="Help"></a> <span><label for="activity_checker_inactive_time">', $txt['activity_checker_inactive_time'] , '</label></span>
	</dt>
	<dd>
		<input type="text" name="activity_checker_inactive_time" id="activity_checker_inactive_time"  value="' , $modSettings['activity_checker_inactive_time'] , '" class="input_text" />
	</dd>';

echo '</dl>';

	//	Show the list.
	template_show_list('activity_checker_membergroup_selector');
	
	template_show_list('activity_checker_category_selector');
	
	echo '		<div style="text-align: right;"><input type="submit" name="save" value="', $txt['activity_checker_save_general_settings'], '" class="button_submit"></div>
			<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '" />
		</div>
	</form>';


}

function template_inactive()
{
	template_show_list();
}

function template_active()
{
	template_show_list();
}

function template_no_posts()
{
	template_show_list();
}

function template_pm_email_settings() {
	//	Show the confiq_vars.
	template_show_settings();
}

function template_not_enabled()
{
	global $modSettings,$txt;
	if (empty($modSettings['activity_checker_inactive_group'])) {
		echo '<p class=error>' , $txt['activity_checker_error']['no_inactive_group'] , '</p>';
	}
	if (empty($modSettings['activity_checker_active_group'])) {
		echo '<p class=error>' , $txt['activity_checker_error']['no_active_group'] , '</p>';
	}
	if (empty($modSettings['activity_checker_inactive_time'])) {
		echo '<p class=error>' , $txt['activity_checker_error']['no_inactive_time'] , '</p>';
	}
	if (empty($modSettings['activity_checker_categories'])) {
		echo '<p class=error>' , $txt['activity_checker_error']['no_categories'] , '</p>';
	}
}