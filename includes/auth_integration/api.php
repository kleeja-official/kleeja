<?php
/**
*
* @package auth
* @copyright (c) 2007 Kleeja.com
* @license ./docs/license.txt
*
*/


//no for directly open
if (!defined('IN_COMMON'))
{
	exit();
}

function kleeja_auth_login ($name, $pass, $hashed = false, $expire, $loginadm = false, $return_username = false)
{
	global $lang, $config, $usrcp, $userinfo;
	global $script_path, $script_api_key, $script_cp1256;

	//URL must be begin with http://
	if(empty($script_path) || $script_path[0] != 'h')
	{
		big_error('Forum URL must be begin with http://', sprintf($lang['SCRIPT_AUTH_PATH_WRONG'], 'API'));
	}

	//api key is the key to make the query between the remote script and kleeja more secure !
	//this must be changed in the real use 
	if(empty($script_api_key))
	{
		big_error('api key', 'To connect to the remote script you have to write the API key ...');
	}

	$pass = empty($script_cp1256) || !$script_cp1256 ? $pass : $usrcp->kleeja_utf8($pass, false);
	$name = empty($script_cp1256) || !$script_cp1256 || $hashed ? $name : $usrcp->kleeja_utf8($name, false);

	/*
		@see file : docs/kleeja_(vb,mysmartbb,phpbb)_api.txt
	*/

	$api_http_query = 'api_key=' . kleeja_base64_encode($script_api_key) . '&' . ($hashed ? 'userid' : 'username') . '=' . urlencode($name) . '&pass=' . kleeja_base64_encode($pass);
	//if only username, let tell him in the query
	$api_http_query .= $return_username ? '&return_username=1' : '';


	//get it
	$remote_data = fetch_remote_file($script_path . '?' . $api_http_query);

	//no responde
	//empty or can not connect
	if ($remote_data == false || empty($remote_data)) 
	{
		return false;
	}

	//see kleeja_api.php file
	//split the data , the first one is always 0 or 1 
	//0 : error
	//1: ok
	$user_info = explode('%|%', kleeja_base64_decode($remote_data));

	//omg, it's 0 , 0 : error, lets die here
	if((int)$user_info[0] == 0)
	{
		return false;
	}

	//
	//if we want username only we have to return it quickly and die here
	//
	if($return_username)
	{
		return  empty($script_cp1256) || !$script_cp1256 ? $user_info[1] : $usrcp->kleeja_utf8($user_info[1]);
	}

	//
	//when loggin to admin, we just want a check, no data setup ..
	//
	if(!$loginadm)
	{
		define('USER_ID', $user_info[1]);
		define('GROUP_ID', 3);
		define('USER_NAME', empty($script_cp1256) || !$script_cp1256 ? $user_info[2] : $usrcp->kleeja_utf8($user_info[2]));
		define('USER_MAIL', $user_info[3]);
		define('USER_ADMIN', ((int) $user_info[5] == 1) ? 1 : 0);
	}

	//user ifo
	//and this must be filled with user data comming from url
	$userinfo = array();
	$userinfo['group_id'] = GROUP_ID;
	$user_y = kleeja_base64_encode(serialize(array('id'=>USER_ID, 'name'=>USER_NAME, 'mail'=>USER_MAIL, 'last_visit'=>time())));


	//add cookies
	if(!$loginadm)
	{
		$usrcp->kleeja_set_cookie('ulogu', $usrcp->en_de_crypt($user_info[1] . '|' . $user_info[4] . '|' . $expire . '|' . sha1(md5($config['h_key'] .  $user_info[4]) .  $expire) . '|' . GROUP_ID . '|' . $user_y), $expire);
	}

	//no need after now
	unset($pass);

	//yes ! he is a real user
	return true;
}

//
//return username 
//
function kleeja_auth_username ($user_id)
{
	return kleeja_auth_login($user_id, false, false, false, false, true);
}	

//<-- EOF
