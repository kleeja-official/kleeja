<?php
/**
*
* @package adm
* @copyright (c) 2007 Kleeja.com
* @license ./docs/license.txt
*
*/

// not for directly open
if (!defined('IN_ADMIN'))
{
	exit();
}

$stylee	= "admin_check_update";
$current_smt	= preg_replace('/[^a-z0-9_]/i', '', g('smt', 'str', 'general'));
$error = false;
$update_link = $config['siteurl'] . 'install/update.php?lang=' . $config['language'];

#to prevent getting the url data for all cats
if($current_smt == 'general'):

//get data from kleeja database
$b_url	= empty($_SERVER['SERVER_NAME']) ? $config['siteurl'] : $_SERVER['SERVER_NAME'];
$b_data = fetch_remote_file('https://raw.githubusercontent.com/awssat/kleeja/master/includes/version.php', false, 6);



if ($b_data === false && !ig('show_msg'))
{
	$text	= $lang['ERROR_CHECK_VER'];
	$error	= true;
}
else
{
	preg_match_all('/define\(\'KLEEJA_VERSION\',\s{1,4}\'([^\']+)\'\);/', $b_data, $matches, PREG_SET_ORDER, 0);

	if (empty($matches[0][1])) 
	{
		$text = $lang['ERROR_CHECK_VER'];
		$error = true;
	}
}


if(!$error)
{
	$version_data = trim(htmlspecialchars($matches[0][1]));

	if (version_compare(strtolower(KLEEJA_VERSION), strtolower($version_data), '<'))
	{
		$text	= sprintf($lang['UPDATE_NOW_S'] , KLEEJA_VERSION, strtolower($version_data)) . '<br /><br />' . $lang['UPDATE_KLJ_NOW'];
		$error	= true;
	}
	else if (version_compare(strtolower(KLEEJA_VERSION), strtolower($version_data), '='))
	{
		$text	= $lang['U_LAST_VER_KLJ'];
	}
	else if (version_compare(strtolower(KLEEJA_VERSION), strtolower($version_data), '>'))
	{
		$text	= $lang['U_USE_PRE_RE'];
	}

	//lets recore it
	$v = @unserialize($config['new_version']);

	//To prevent expected error [ infinit loop ]
	if(ig('show_msg'))
	{
		$query_get	= array(
							'SELECT'	=> '*',
							'FROM'		=> "{$dbprefix}config",
							'WHERE'		=> "name = 'new_version'"
						);

		$result_get =  $SQL->build($query_get);

		if(!$SQL->num_rows($result_get))
		{
			//add new config value
			add_config('new_version', '');
		}
	}

	$data	= array(
					'version_number'	=> $version_data,
					'last_check'		=> time(),
					'msg_appeared'		=> ig('show_msg') ? true : false
				);

	$data = serialize($data);

	update_config('new_version', $SQL->real_escape($data), false);
	delete_cache('data_config');
}

//then go back  to start
if(ig('show_msg'))
{
	redirect(basename(ADMIN_PATH) . '?update_done=1');
	$SQL->close();
	exit;
}

#end current_smt == general
endif;

//secondary menu
$go_menu = array(
				'general' => array('name'=>$lang['R_CHECK_UPDATE'], 'link'=> basename(ADMIN_PATH) . '?cp=p_check_update&amp;smt=general', 'goto'=>'general', 'current'=> $current_smt == 'general'),
				'howto' => array('name'=>$lang['HOW_UPDATE_KLEEJA'], 'link'=> basename(ADMIN_PATH) . '?cp=p_check_update&amp;smt=howto', 'goto'=>'howto', 'current'=> $current_smt == 'howto'),
				'site' => array('name'=>'Kleeja.com', 'link'=> 'http://www.kleeja.com', 'goto'=>'site', 'current'=> $current_smt == 'site'),
	);
