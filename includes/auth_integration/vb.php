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

//
//Path of config file in vb
//
if(! defined('SCRIPT_CONFIG_PATH'))
{
	define('SCRIPT_CONFIG_PATH', '/includes/config.php');
}

function kleeja_auth_login ($name, $pass, $hashed = false, $expire, $loginadm = false, $return_name = false)
{
	global $lang, $config, $usrcp, $userinfo;
	global $script_path, $script_cp1256, $script_srv, $script_db, $script_user, $script_pass, $script_prefix, $script_db_charset;

	if(isset($script_path))
	{
		//check for last slash
		if(isset($script_path[strlen($script_path)]) && $script_path[strlen($script_path)] == '/')
		{
			$script_path = substr($script_path, 0, strlen($script_path));
		}

		$configExists = false;
		
		if(file_exists(PATH . $script_path . SCRIPT_CONFIG_PATH))
		{
			$configPath = PATH . $script_path . SCRIPT_CONFIG_PATH;
			$configExists = true;
		}
		else if(file_exists(PATH . $script_path . '/core/includes/config.php'))
		{
			$configPath = PATH . $script_path . '/core/includes/config.php';
			$configExists = true;
			$isVB5 = true;
		}

		//get some useful data from vb config file
		if($configExists)
		{
			require_once $configPath;

			//
			//get config from config file
			//
			$forum_srv	= $config['MasterServer']['servername'];
			$forum_db	= $config['Database']['dbname'];
			$forum_user	= $config['MasterServer']['username'];
			$forum_pass	= $config['MasterServer']['password'];
			$forum_prefix= $config['Database']['tableprefix'];
			if($config['MasterServer']['port'] != 3306)
			{
				$forum_srv .= ':' . $config['MasterServer']['port'];
			}

			//some people change their db charset 
			if(isset($config['Mysqli']['charset']))
			{
				$forum_db_charset = $config['Mysqli']['charset'];
			}
		}
		else
		{
			big_error('Forum path is not correct', sprintf($lang['SCRIPT_AUTH_PATH_WRONG'], 'Vbulletin'));
		}
	}
	else
	{
		//
		//custom config data
		//
		$forum_srv	= $script_srv;
		$forum_db	= $script_db;
		$forum_user	= $script_user;
		$forum_pass	= $script_pass;
		$forum_prefix = $script_prefix;

		//some people change their db charset 
		if(isset($script_db_charset))
		{
			$forum_db_charset = $script_db_charset;
		}
	}

	if(empty($forum_srv) || empty($forum_user) || empty($forum_db))
	{
		return;
	}

    $SQLVB = new KleejaDatabase($forum_srv, $forum_user, $forum_pass, $forum_db, true);


	if(isset($forum_db_charset))
	{	//config
		$SQLVB->set_names($forum_db_charset);
	}
	else //auto
	{
		$SQLVB->set_names('latin1');
	}


	unset($forum_pass); // We do not need this any longer

	$pass = empty($script_cp1256) || !$script_cp1256 ? $pass : $usrcp->kleeja_utf8($pass, false);
	$name = empty($script_cp1256) || !$script_cp1256 || $hashed ? $name : $usrcp->kleeja_utf8($name, false);

	$query_salt = array(
						'SELECT'	=> $hashed ? '*' : ($isVB5 ? 'token' : 'salt'), 
						'FROM'		=> "`{$forum_prefix}user`",
					);

	$query_salt['WHERE'] = $hashed ? "userid=" . intval($name) . " AND ". ($isVB5 ? 'token' : 'password') ."='" . $SQLVB->real_escape($pass) . "' AND usergroupid != '8'" :  "username='" . $SQLVB->real_escape($name) . "' AND usergroupid != '8'";
	
	//if return only name let's ignore the above
	if($return_name)
	{
		$query_salt['SELECT']	= "username";
		$query_salt['WHERE']	= "userid=" . intval($name);
	}

    is_array($plugin_run_result = Plugins::getInstance()->run('qr_select_usrdata_vb_usr_class', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook
	$result_salt = $SQLVB->build($query_salt);

	if ($SQLVB->num_rows($result_salt) > 0) 
	{
		while($row1=$SQLVB->fetch_array($result_salt))
		{
			if($return_name)
			{
				return empty($script_cp1256) || !$script_cp1256 ? $row1['username'] : $usrcp->kleeja_utf8($row1['username']);
			}

			if(!$hashed)
			{
				$pass = ! $isVB5 
						? md5(md5($pass) . $row1['salt'])  // without normal md5
						: crypt(md5($pass), $row1['token']);

				$query	= array(
								'SELECT'	=> '*',
								'FROM'	=> "`{$forum_prefix}user`",
								'WHERE'	=> "username='" . $SQLVB->real_escape($name) . "' AND ".($isVB5 ? 'token' : 'password')."='" . $SQLVB->real_escape($pass) . "' AND usergroupid != '8'"
						);
		
				$result = $SQLVB->build($query);

				if ($SQLVB->num_rows($result) != 0)
				{
					while($row=$SQLVB->fetch_array($result))
					{
						if(!$loginadm)
						{
							define('USER_ID', $row['userid']);
							define('GROUP_ID', ($row['usergroupid'] == 6 ? 1 : 3));
							define('USER_NAME', empty($script_cp1256) || !$script_cp1256 ? $row['username'] : $usrcp->kleeja_utf8($row['username']));
							define('USER_MAIL', $row['email']);
							define('USER_ADMIN', ($row['usergroupid'] == 6 ? 1 : 0));
						}

						//define('LAST_VISIT',$row['last_visit']);

						$userinfo = $row;
						$userinfo['group_id'] =  ($row['usergroupid'] == 6 ? 1 : 3);
						$user_y = kleeja_base64_encode(serialize(array('id'=>$row['userid'], 'name'=>USER_NAME, 'mail'=>$row['email'], 'last_visit'=>time())));
						
						$hash_key_expire = sha1(md5($config['h_key'] . ($isVB5 ? $row['token'] : $row['password'])) .  $expire);

						if(!$loginadm)
						{
							$usrcp->kleeja_set_cookie('ulogu', $usrcp->en_de_crypt(
													$row['userid'] . '|' . 
													($isVB5 ? $row['token'] : $row['password']) . '|' . 
													$expire . '|' . 
													$hash_key_expire . '|' . 
													($row['usergroupid'] == 6 ? 1 : 3) . '|' . 
													$user_y
												), $expire);
						}

                        is_array($plugin_run_result = Plugins::getInstance()->run('qr_while_usrdata_vb_usr_class', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook
					}
					$SQLVB->freeresult($result);
				}#nums_sql2
				else
				{
					$SQLVB->close();
					return false;
				}
			}
			else
			{
				if(!$loginadm)
				{
					define('USER_ID', $row1['userid']);
					define('USER_NAME', empty($script_cp1256) || !$script_cp1256 ? $row1['username'] : $usrcp->kleeja_utf8($row1['username']));
					define('USER_MAIL',$row1['email']);
					define('USER_ADMIN',($row1['usergroupid'] == 6) ? 1 : 0);
					define('GROUP_ID',($row1['usergroupid'] == 6) ? 1 : 3);
					$userinfo = $row1;
					$userinfo['group_id'] = ($row1['usergroupid'] == 6 ? 1 : 3);
				}
			}
		}#whil1

		$SQLVB->freeresult($result_salt); 

		unset($pass);
		$SQLVB->close();

		return true;
	}
	else
	{
		$SQLVB->close();
		return false;
	}
}

function kleeja_auth_username ($user_id)
{
	return kleeja_auth_login ($user_id, false, true, 0, false, true);
}
