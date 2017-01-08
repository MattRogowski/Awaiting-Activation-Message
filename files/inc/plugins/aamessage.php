<?php
/**
 * Awaiting Activation Message 1.8.1

 * Copyright 2017 Matthew Rogowski

 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at

 ** http://www.apache.org/licenses/LICENSE-2.0

 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
**/

if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

$plugins->add_hook("global_intermediate", "aamessage_global_intermediate");

global $templatelist;

if($templatelist)
{
	$templatelist .= ',';
}
$templatelist .= 'aamessage';

function aamessage_info()
{
	return array(
		"name" => "Awaiting Activation Message",
		"description" => "Shows a message to people awaiting activation by email or admin.",
		"website" => "https://github.com/MattRogowski/Awaiting-Activation-Message",
		"author" => "Matt Rogowski",
		"authorsite" => "https://matt.rogow.ski",
		"version" => "1.8.1",
		"compatibility" => "18*",
		"codename" => "aamessage"
	);
}

function aamessage_activate()
{
	global $db;

	require_once MYBB_ROOT . "inc/adminfunctions_templates.php";

	aamessage_deactivate();

	$templates = array();
	$templates[] = array(
		"title" => "aamessage",
		"template" => "<table width=\"100%\" border=\"0\" cellspacing=\"{\$theme['borderwidth']}\" cellpadding=\"{\$theme['tablespace']}\" class=\"tborder\">
	<tr>
		<td class=\"thead\">
			<strong>{\$aamessage_title}</strong>
		</td>
	</tr>
	<tr>
		<td class=\"trow1\">
			{\$aamessage_message}
		</td>
	</tr>
</table>
<br/>"
	);
	foreach($templates as $template)
	{
		$insert = array(
			"title" => $db->escape_string($template['title']),
			"template" => $db->escape_string($template['template']),
			"sid" => "-1",
			"version" => "1800",
			"dateline" => TIME_NOW
		);
		$db->insert_query("templates", $insert);
	}

	find_replace_templatesets("header", "#".preg_quote('{$unreadreports}')."#i", '{$unreadreports}{$aamessage}');
}

function aamessage_deactivate()
{
	global $db;

	require_once MYBB_ROOT . "inc/adminfunctions_templates.php";

	$templates = array(
		"aamessage"
	);
	$templates = "'" . implode("','", $templates) . "'";
	$db->delete_query("templates", "title IN ({$templates})");

	find_replace_templatesets("header", "#".preg_quote('{$aamessage}')."#i", '', 0);
}

function aamessage_global_intermediate()
{
	global $mybb, $lang, $templates, $theme, $aamessage;

	$lang->load("aamessage");

	if($mybb->user['usergroup'] == 5)
	{
		switch($mybb->settings['regtype'])
		{
			case 'admin': // if an admin has to activate them
				$aamessage_title = $lang->aamessage_title_admin;
				$aamessage_message = $lang->sprintf($lang->aamessage_message_admin, $mybb->user['username']);
				break;
			case 'verify': // if they have to verify via email
				$aamessage_title = $lang->aamessage_title_verify;
				$aamessage_message = $lang->sprintf($lang->aamessage_message_verify, $mybb->user['username'], $mybb->settings['bburl']);
				break;
			case 'both': // both are required
				$aamessage_title = $lang->aamessage_title_both;
				$aamessage_message = $lang->sprintf($lang->aamessage_message_verify, $mybb->user['username'], $mybb->settings['bburl']).'<br /><br />'.$lang->aamessage_message_both;
				break;
			default: // if the setting has been changed to either instant or random password, show a generic message
				$aamessage_title = $lang->aamessage_title_default;
				$aamessage_message = $lang->sprintf($lang->aamessage_message_default, $mybb->user['username']);
				break;
		}
		$aamessage_message .= '<br /><br />'.$lang->aamessage_end_posting.'<br /><br />'.$lang->aamessage_end_contacting;

		if(substr($mybb->version, 0, 3) == '1.6')
		{
			// 1.6 compatibility - $theme not available in global_start, spoof default table settings
			$theme = array('borderwidth' => 1, 'tablespace' => 4);
		}

		eval("\$aamessage = \"".$templates->get('aamessage')."\";");
	}
}
?>
