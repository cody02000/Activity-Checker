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
	global $context, $settings, $options, $scripturl, $txt, $modSettings,$themeurl;

	
	echo '<form action="' . $scripturl . '?action=admin;area=activity_checker;sa=settings" method="post" name="ActivityCheckerSettings" id="ActivityCheckerSettings" accept-charset="'. $context['character_set'] . '">
	 <dl class="settings">';

 echo '<dt>
      <a id="activity_checker_inactive_time" href="' . $scripturl . '?action=helpadmin;help=' . $txt['activity_checker_inactive_time_help'] . '" onclick="return reqWin(this.href);" class="help"><img src="'. $settings['images_url'] .'/helptopics.gif" class="icon" alt="Help"></a> <span><label for="activity_checker_inactive_time">'.$txt['activity_checker_inactive_time'].'</label></span>
    </dt>
    <dd>
      <input type="text" name="activity_checker_inactive_time" id="activity_checker_inactive_time"  value="' . $modSettings['activity_checker_inactive_time'] . '" class="input_text" />
    </dd>';

echo '	</dl>';

	//	Show the list.
	template_show_list('activity_checker_membergroup_selector');
	
	template_show_list('activity_checker_category_selector');
	
	echo '		<div style="text-align: right;"><input type="submit" name="save" value="Save" class="button_submit"></div>
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