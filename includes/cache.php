<?php
/**
*
* @package Kleeja
* @copyright (c) 2007 Kleeja.com
* @license ./docs/license.txt
*
*/


//no for directly open
if (!defined('IN_COMMON'))
{
	exit;
}

//we are in cache now ..
define('IN_CACHE', true);

//make sure it's utf8 data
$SQL->set_utf8();


//
//In the future here will be a real cache class 
//this codes, it's just a sample and usefull for
//some time ..
//
class cache
{
	function get($name)
	{
	    if(defined('DEV_STAGE'))
	    {
	        return false;
        }

		$name =  preg_replace('![^a-z0-9_]!', '_', $name);
	
		if (file_exists(PATH . 'cache/' . $name . '.php'))
		{
			include PATH . 'cache/' . $name . '.php';
			return  empty($data) ? false : $data;
		}
		else
		{
			return false;
		}
	}
	
	function exists($name)
	{
		$name =  preg_replace('![^a-z0-9_]!', '_', $name);
	
		if (file_exists(PATH . 'cache/' . $name . '.php'))
		{
			return true;
		}
	}
	
	function save($name, $data, $time = 86400)
	{
		//
		//We have problems if APC is enabled, so we disable our cache 
		//system if it's lodoed to prevent those problems, but we will
		//try to fix it in the near future .. I hope that.
		//
		if(defined('APC_CACHE'))
		{
			//return;
		}
		
		$name =  preg_replace('![^a-z0-9_]!i', '_', $name);
		$data_for_save = '<?' . 'php' . "\n";
		$data_for_save .= '//Cache file, generated for Kleeja at ' . gmdate('d-m-Y h:i A') . "\n\n";
		$data_for_save .= '//No direct opening' . "\n";
		$data_for_save .= '(!defined("IN_COMMON") ? exit("hacking attemp!") : null);' . "\n\n";
		$data_for_save .= '//return false after x time' . "\n";	
		$data_for_save .= 'if(time() > ' . (time() + $time) . ') return false;' . "\n\n";	
		$data_for_save .= '$data = ' . var_export($data, true) . ";\n\n//end of cache";

		if($fd = @fopen(PATH . 'cache/' . $name . '.php', 'w'))
		{
			@flock($fd, LOCK_EX); // exlusive look
			@fwrite($fd, $data_for_save);
			@flock($fd, LOCK_UN);
			@fclose($fd);
		}
		return;
	}

	function clean($name)
	{
		if(is_array($name))
		{
			foreach($name as $n)
			{
				$this->clean($n);
			}
			return;
		}

		$name =  preg_replace('![^a-z0-9_]!i', '_', $name);
		kleeja_unlink(PATH . 'cache/' . $name . '.php');
	}
}

$cache = new cache;




//
//get config data from config table  ...
//
if (!($config = $cache->get('data_config')))
{
	$query = array(
					'SELECT'	=> 'c.name, c.value',
					'FROM'		=> "{$dbprefix}config c",
					'WHERE'		=> 'c.dynamic = 0',
				);

    is_array($plugin_run_result = Plugins::getInstance()->run('qr_select_config_cache', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook
			
	$result = $SQL->build($query);

	while($row=$SQL->fetch_array($result))
	{
		$config[$row['name']] = $row['value'];
	}

	$SQL->freeresult($result);

	$cache->save('data_config', $config);
}

//
//get language terms from lang table  ...
//

if (!($olang = $cache->get('data_lang' . $config['language']))) {

	$query = array(
					'SELECT'	=> 'l.word, l.trans',
					'FROM'		=> "{$dbprefix}lang l",
					'WHERE'		=> "l.lang_id='" . $SQL->escape($config['language']) . "'",
				);

    is_array($plugin_run_result = Plugins::getInstance()->run('qr_select_lang_cache', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook

	$result = $SQL->build($query);

	while($row=$SQL->fetch_array($result))
	{
		$olang[$row['word']] = $row['trans'];
	}

	$SQL->freeresult($result);

    $cache->save('data_lang' . $config['language'], $olang);
}


//
//stats to cache
//
if (!($stats = $cache->get('data_stats')))
{
	$query = array(
					'SELECT'	=> 's.files, s.imgs, s.sizes, s.users, s.last_file, s.last_f_del, s.last_google' . 
									', s.last_bing, s.google_num, s.bing_num, s.lastuser',
					'FROM'		=> "{$dbprefix}stats s"
			);

    is_array($plugin_run_result = Plugins::getInstance()->run('qr_select_stats_cache', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook

	$result = $SQL->build($query);

	while($row=$SQL->fetch_array($result))
	{
		$stats = array(
			'stat_files'		=> $row['files'],
			'stat_imgs'			=> $row['imgs'],
			'stat_sizes'		=> $row['sizes'],
			'stat_users'		=> $row['users'],
			'stat_last_file'	=> $row['last_file'],
			'stat_last_f_del'	=> $row['last_f_del'],
			'stat_last_google'	=> $row['last_google'],
			'stat_last_bing'	=> $row['last_bing'],
			'stat_google_num'	=> $row['google_num'],
			'stat_bing_num'		=> $row['bing_num'],
			'stat_last_user'	=> $row['lastuser']
		);

        is_array($plugin_run_result = Plugins::getInstance()->run('while_fetch_stats_in_cache', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook
	}

	$SQL->freeresult($result);

	#save the stats for hour and then refresh them
	$cache->save('data_stats', $stats, 3600);

	#also, save the data for the charts later
	$query = array(
					'SELECT'	=> 'f.filter_uid',
					'FROM'		=> "{$dbprefix}filters f",
					'WHERE'		=> "f.filter_type='stats_for_acp' AND f.filter_uid = '" . date('d-n-Y') . "'"
				);

	$result	= $SQL->build($query);		

	#if already there is stats for this day, just update it, if not insert a new one
	if($SQL->num_rows($result))	
	{
		$f_query	= array(
							'UPDATE'	=> "{$dbprefix}filters",
							'SET'		=> "filter_value='" . implode(':', array($stats['stat_files'], $stats['stat_imgs'], $stats['stat_sizes']))  . "'",
							'WHERE'		=> "filter_type='stats_for_acp' AND filter_uid = '" . date('d-n-Y') . "'"
					);
	}
	else
	{
		$f_query = array(
							'INSERT'	=> 'filter_uid, filter_type ,filter_value ,filter_time',
							'INTO'		=> "{$dbprefix}filters",
							'VALUES'	=> "'" . date('d-n-Y') . "', 'stats_for_acp', '" . implode(':', array($stats['stat_files'], $stats['stat_imgs'], $stats['stat_sizes'])) . "', " . time()
					);
	}

	$SQL->build($f_query);
}


//make them as seperated vars
extract($stats);
unset($stats);

//
//get banned ips data from stats table  ...
//
if (!($banss = $cache->get('data_ban')))
{
	$query = array(
					'SELECT'	=> 's.ban',
					'FROM'		=> "{$dbprefix}stats s"
				);

    is_array($plugin_run_result = Plugins::getInstance()->run('qr_select_ban_cache', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook
	$result = $SQL->build($query);

	$row = $SQL->fetch_array($result);
	$ban1 = $row['ban'];
	$SQL->freeresult($result);

	$banss = array();

	if (!empty($ban1) || $ban1 != ' '|| $ban1 != '  ')
	{
		//seperate ips .. 
		$ban2 = explode('|', $ban1);
		for ($i=0; $i<sizeof($ban2); $i++)
		{
			$banss[$i] = $ban2[$i];
		}
	}



	unset($ban1, $ban2, $gt);

	$cache->save('data_ban', $banss);
}

//	
//get rules data from stats table  ...
//
if (!($ruless = $cache->get('data_rules')))
{
	$query = array(
					'SELECT'	=> 's.rules',
					'FROM'		=> "{$dbprefix}stats s"
				);

    is_array($plugin_run_result = Plugins::getInstance()->run('qr_select_rules_cache', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook
	$result = $SQL->build($query);

	$row = $SQL->fetch_array($result);
	$ruless = $row['rules'];
	$SQL->freeresult($result);

	$cache->save('data_rules', $ruless);
}	


//	
//get ex-header-footer data from stats table  … 
//
if (!($extras = $cache->get('data_extra')))
{
	$query = array(
					'SELECT'	=> 's.ex_header, s.ex_footer',
					'FROM'		=> "{$dbprefix}stats s"
					);

    is_array($plugin_run_result = Plugins::getInstance()->run('qr_select_extra_cache', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook
	$result = $SQL->build($query);

	$row = $SQL->fetch_array($result);
	
	$extras = array(
		'header' => $row['ex_header'],
		'footer' => $row['ex_footer']
	);

	$SQL->freeresult($result);

	$cache->save('data_extra', $extras);
}


//	
//Get groups data
//
if (!($d_groups = $cache->get('data_groups')))
{
	$d_groups = array();
	
	#data
	$query = array(
					'SELECT'	=> 'g.*',
					'FROM'		=> "{$dbprefix}groups g",
					'ORDER_BY'	=> 'g.group_id ASC',
					);

    is_array($plugin_run_result = Plugins::getInstance()->run('qr_select_groups_cache', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook
	$result = $SQL->build($query);
	
	#Initiating
	while($row=$SQL->fetch_array($result))
	{
		$d_groups[$row['group_id']]['data'] = $row;
		$d_groups[$row['group_id']]['configs'] = array(); 
		$d_groups[$row['group_id']]['acls'] = array();
		$d_groups[$row['group_id']]['exts'] = array();
	}
	$SQL->freeresult($result);

	#configs
	$query = array(
					'SELECT'	=> 'g.group_id, g.name, g.value',
					'FROM'		=> "{$dbprefix}groups_data g",
					'ORDER_BY'	=> 'g.group_id ASC',
					);

    is_array($plugin_run_result = Plugins::getInstance()->run('qr_select_groups_data_cache', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook
	$result = $SQL->build($query);

	while($row=$SQL->fetch_array($result))
	{
		$d_groups[$row['group_id']]['configs'][$row['name']] = $row['value'];
	}
	$SQL->freeresult($result);

	#acl
	$query2 = array(
					'SELECT'	=> 'g.group_id, g.acl_name, g.acl_can',
					'FROM'		=> "{$dbprefix}groups_acl g",
					'ORDER_BY'	=> 'g.group_id ASC',
					);

    is_array($plugin_run_result = Plugins::getInstance()->run('qr_select_groups_acls_cache', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook
	$result2 = $SQL->build($query2);

	while($row=$SQL->fetch_array($result2))
	{
		$d_groups[$row['group_id']]['acls'][$row['acl_name']] = (int) $row['acl_can'];
	}
	$SQL->freeresult($result2);

	#exts
	$query3 = array(
					'SELECT'	=> 'g.group_id, g.ext, g.size',
					'FROM'		=> "{$dbprefix}groups_exts g",
					'ORDER_BY'	=> 'g.group_id ASC',
					);

    is_array($plugin_run_result = Plugins::getInstance()->run('qr_select_groups_exts_cache', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook
	$result3 = $SQL->build($query3);

	while($row=$SQL->fetch_array($result3))
	{
		$d_groups[$row['group_id']]['exts'][$row['ext']] = (int) $row['size'];
	}
	$SQL->freeresult($result3);

	unset($query, $query2, $query3, $result, $result2, $result3);

	$cache->save('data_groups', $d_groups);
}


// ummm, does this useful here
is_array($plugin_run_result = Plugins::getInstance()->run('in_cache_page', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook

