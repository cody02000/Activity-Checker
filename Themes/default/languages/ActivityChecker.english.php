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

//Menu and Descriptions  
$txt['activity_checker_label'] = 'Activity Checker';
$txt['activity_checker_desc'] = 'Check when the last post of members were and change their membergroup.';
$txt['activity_checker_general'] = 'Activity Checker Settings';
$txt['activity_checker_inactive_list'] = 'Newly Inactive Member List';
$txt['activity_checker_active_list'] = 'Newly Active Member List';
$txt['activity_checker_no_posts_list'] = 'Never Posted Member Lists';
$txt['activity_checker_general_desc'] = 'Membergroup and Category Settings.';
$txt['activity_checker_inactive_desc'] = 'A list of members in the active membergroup who are currently inactive.';
$txt['activity_checker_active_desc'] = 'A list of members in the inactive membergroup who are now active again.';
$txt['activity_checker_no_posts_desc'] = 'A list of members who have never made a post in the specified categories.';
$txt['activity_checker_pm_email_settings'] = 'Inactive PM and Member Deleted Email Settings';
$txt['activity_checker_pm_email_desc'] = 'Settings for sending a PM when a member goes inactive and for sending an email to a member who has been deleted via ' . $txt['activity_checker_no_posts_list'] . '.';

//General Settings
$txt['activity_checker_inactive_time'] = 'Inactive Weeks Threshold';
$txt['activity_checker_inactive_time_help'] = 'Inactive time is set by number of weeks, enter any number.';
$txt['activity_checker_settings'] = 'Activity Checker Settings';
//Membergroup Settings
$txt['activity_checker_membergroup_settings_title'] = 'Membergroups Settings';
$txt['activity_checker_membergroup_col'] = 'Membergroup';
$txt['activity_checker_description_col'] = 'Description';
$txt['activity_checker_activeGroup_col'] = 'Active Group';
$txt['activity_checker_inactiveGroup_col'] = 'Inactive Group';
$txt['activity_checker_membergroup_desc'] = 'Select the active and inactive group.
<ul>
<li>The <span style="font-style: italic;">' . $txt['activity_checker_inactive_list'] . '</span> checks for inactive members in the active group.</li>
<li>The <span style="font-style: italic;">' . $txt['activity_checker_active_list'] . '</span> checks for active members in the inactive group.</li>
</ul>

The membergroup options do not include protected type groups, the default admin and moderators group, and post count based groups.';
$txt['activity_checker_category_desc'] = 'Select the categories to check for the activity checks to check for posts in.';

//Category settings
$txt['activity_checker_category_settings_title'] = 'Categories to Check Activity In';
$txt['activity_checker_category_col'] = 'Category';
$txt['activity_checker_catChecked_col'] = 'Check for Activity';

//PM and Email Settings
$txt['activity_checker_inactive_pm_enable'] = 'Send PM to members on move to inactive group.';
$txt['activity_checker_inactive_pm_from'] = 'From:';
$txt['activity_checker_inactive_pm_bcc'] = 'BCC: (Usernames seperated by commas)';
$txt['activity_checker_inactive_pm_subject'] = 'Inactive PM Subject:';
$txt['activity_checker_inactive_pm_message'] = 'Inactive PM Message:';
$txt['activity_checker_inactive_pm_message_desc'] = 'This is the Personal Message that is sent to the member when their account is moved to the inactive group.';
$txt['activity_checker_email_enable'] = 'Send Email to members who have been deleted via the No Posts List.';
$txt['activity_checker_email_subject'] = 'Member Deleted Email Subject:';
$txt['activity_checker_email_message'] = 'Member Deleted Email Message:';
$txt['activity_checker_email_message_desc'] = 'This is the email message that is sent to the user when their account is deleted. To insert the username of the user just insert "<b>{RECPNAME}</b>".  For forum name use "<b>{FORUM}</b>".';

//List Pages
$txt['no_membergroup'] = 'No primary membergroup';
$txt['last-post'] = 'Last Post';
$txt['activity_checker_member_name_title'] = 'Member Name';
$txt['activity_checker_cutoff'] = 'Current activity cutoff date: ';
$txt['activity_checker_weeks'] = ' Weeks';

//Inactive List
$txt['activity_checker_inactive_list_title'] = 'Inactive Member List';
$txt['activity_checker_no_inactive'] = 'There are no currently inactive members in the active group.';
$txt['activity_checker_mark_inactive'] = 'Move Selected Members to Inactive Group';
$txt['activity_checker_inactive_pm_enabled'] = 'A Personal Message Will Be Sent to Members Moved to Inactive Group.';


//Active List
$txt['activity_checker_active_list_title'] = 'Active Member List';
$txt['activity_checker_no_active'] = 'There are no currently active members in the inactive group.';
$txt['activity_checker_mark_active'] = 'Move Selected Members to Active Group';

//No Post List
$txt['activity_checker_no_post_list_title'] = 'Members with No Posts on Counted Boards List and With No Posts Anywhere';
$txt['activity_checker_no_no_posts'] = 'There are no members who have never posted ever or never on a counted board.';
$txt['activity_checker_email_enabled'] = 'An Email Will Be Sent to Members Who are Deleted.';

//Errors
$txt['activity_checker_error']['no_active_group'] = 'No active group set. <a href="' . $scripturl . '?action=admin;area=activity_checker;sa=settings" title="Activity Checker Settings">Please set active group</a> to use Activity Checker.';
$txt['activity_checker_error']['no_inactive_group'] = 'No inactive group set.  <a href="' . $scripturl . '?action=admin;area=activity_checker;sa=settings" title="Activity Checker Settings">Please set inactive group</a> to use Activity Checker.';
$txt['activity_checker_error']['no_inactive_time'] = 'No inactive after number of weeks set.  <a href="' . $scripturl . '?action=admin;area=activity_checker;sa=settings" title="Activity Checker Settings">Please set number of weeks</a> to use Actiivty Checker.';
$txt['activity_checker_error']['no_categories'] = 'No categories are set to be checked for activity.  <a href="' . $scripturl . '?action=admin;area=activity_checker;sa=settings" title="Activity Checker Settings">Please set categories to check for activity in</a> to use Activity Checker.';

//default PM and Email
$txt['activity_checker_default_pm_subject'] = 'Account Inactive';
$txt['activity_checker_default_pm_message'] = 'This account has been moved to the inactive group.';
$txt['activity_checker_default_email_subject'] = 'Account Deleted';
$txt['activity_checker_default_email_message'] = 'This account {RECPNAME} has been deleted due to lack of posts from {FORUM}.';