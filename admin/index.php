<?php
/**
*
* @package adm
* @copyright (c) 2007 Kleeja.com
* @license ./docs/license.txt
*
*/


/**
 * @ignore
 */
define('IN_KLEEJA', true);
define ('PATH' , '../');
define ('IN_ADMIN' , true);
require_once PATH . 'includes/common.php';



$go_to		= ig('cp') ? g('cp') : 'start';
$username	= $usrcp->name();
$AJAX_ACP	= defined('AJAX_ACP');
$config['enable_captcha'] = ! defined('STOP_CAPTCHA');


#for security
if (!$username)
{
    is_array($plugin_run_result = Plugins::getInstance()->run('user_not_admin_admin_page', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook
	redirect(PATH . 'ucp.php?go=login&return=' . urlencode(ADMIN_PATH . '?cp=' . $go_to));
}

#get language of admin
get_lang('acp');

//
//need to login again
//
if(
	(empty($_SESSION['ADMINLOGIN']) || $_SESSION['ADMINLOGIN'] != md5(sha1($config['h_key']) . $usrcp->name() . $config['siteurl'])) || 
	(empty($_SESSION['USER_SESS']) || $_SESSION['USER_SESS'] != session_id()) ||
	(empty($_SESSION['ADMINLOGIN_T']) || $_SESSION['ADMINLOGIN_T'] < time())	 
)
{
	if(ig('go') && g('go') == 'login')
	{
		if (ip('submit'))
		{
			//login
			$ERRORS	= array();
			$pass_field = 'lpass_' .  preg_replace('/[^0-9]/', '', sha1($klj_session . sha1($config['h_key']) . p('kid')));


            if(!empty($_SESSION['SHOW_CAPTCHA']))
            {
                if(!kleeja_check_captcha())
                {
                    $ERRORS[] = $lang['WRONG_VERTY_CODE'];
                }
            }

            if (empty(p('lname')) || empty(p($pass_field)))
			{
				$ERRORS[] = $lang['EMPTY_FIELDS'];
			}
			elseif(!user_can('enter_acp'))
			{
				$ERRORS[] = $lang['U_NOT_ADMIN'];
			}
			elseif(!kleeja_check_form_key('admin_login'))
			{
				$ERRORS[] = $lang['INVALID_FORM_KEY'];
			}

            is_array($plugin_run_result = Plugins::getInstance()->run('admin_login_submit', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook


            if(empty($ERRORS))
			{
				if($f = $usrcp->data(p('lname'), p($pass_field), false, 3600*6, true))
				{
					$_SESSION['USER_SESS'] = session_id();
					$_SESSION['ADMINLOGIN'] = md5(sha1($config['h_key']) . $usrcp->name() . $config['siteurl']);
					//to make sure, sometime setting time from functions doesn't work
                    $_SESSION['ADMINLOGIN_T'] = time() + 18000;
                    unset($_SESSION['SHOW_CAPTCHA']);

					redirect('./' . basename(ADMIN_PATH) . '?cp=' . $go_to);
					$SQL->close();
					exit;
				}
				else
				{
					//Wrong entries
					$ERRORS[] = $lang['LOGIN_ERROR'];
                    $_SESSION['SHOW_CAPTCHA'] = function_exists('gd_info') && ! defined('STOP_CAPTCHA');
				}
			}

			//let's see if there is errors
			if(sizeof($ERRORS))
			{
				$errs =	'';
				foreach($ERRORS as $r)
				{
					$errs .= '- ' . $r . '. <br />';
				}
			}
		}
	}

	//show template login .
	$action	= './' . basename(ADMIN_PATH) . '?go=login&amp;cp=' . $go_to;
	$H_FORM_KEYS	= kleeja_add_form_key('admin_login');
	$KEY_FOR_WEE	= sha1(microtime() . sha1($config['h_key']));
	$KEY_FOR_PASS	= preg_replace('/[^0-9]/', '', sha1($klj_session . sha1($config['h_key']) . $KEY_FOR_WEE)); 
	$not_you		= sprintf($lang['USERNAME_NOT_YOU'], '<a href="' .$config['siteurl'] . 'ucp.php?go=logout">', '</a>');

    $show_captcha = !empty($_SESSION['SHOW_CAPTCHA']);

    $extra_header_admin_login = '';
    $err = false;
	if(!empty($errs))
	{
		$err = true;
	}

    is_array($plugin_run_result = Plugins::getInstance()->run('before_display_template_admin_page', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook

	header('HTTP/1.0 401 Unauthorized');
	if (ig('_ajax_') || ig('check_msgs')) 
	{
		echo_ajax(401, $lang['HV_NOT_PRVLG_ACCESS']);
	}
	else
	{
        echo $tpl->display('admin_login');
	}

	$SQL->close();
	exit;
}#end login



//ummm let's say it's illegal action
if ($_SERVER['REQUEST_METHOD'] == 'POST' && defined('STOP_CSRF'))
{
	$t_reff = explode('/', $_SERVER['HTTP_REFERER']);
	$t_host = explode('/', $_SERVER['HTTP_HOST']);
	if ($t_reff[2] != $t_host[0])
	{
		$usrcp->logout_cp();

		redirect($config['siteurl']);
		$SQL->close();
		exit;
	}
}


//current admin theme color
if(ig('change_theme'))
{
    $admin_theme_color = g('change_theme', 'str', 'dark');

    if(in_array($admin_theme_color, array('dark', 'light')))
    {
        $usrcp->kleeja_set_cookie('klj_adm_theme_color', $admin_theme_color, time() + 31536000);
    }
    else
    {
        $admin_theme_color = 'dark';
    }
}
else
{
    if (!($admin_theme_color = $usrcp->kleeja_get_cookie('klj_adm_theme_color')))
    {
        $admin_theme_color = 'dark';
    }
}


(!defined('LAST_VISIT')) ? define('LAST_VISIT', time() - 3600 * 12) : null;
//last visit
$last_visit		= defined('LAST_VISIT') && preg_match('/[0-9]{10}/', LAST_VISIT) ? kleeja_date(LAST_VISIT) : false;

//
//exceptional
//it won't be included in the menu list
//
$ext_expt	= array(
    'start',
    'b_lgoutcp',
    'i_exts'
    );

//confirm message
$ext_confirm	= array();


//formkey extension, CSRF protection
$GET_FORM_KEY_GLOBAL = kleeja_add_form_key_get('GLOBAL_FORM_KEY');
$ext_formkey	= array();


//default icons
$ext_icons = array(
    'configs' => 'sliders',
    'files' => 'folder-open-o',
    'img_ctrl' => 'image',
    'calls' => 'envelope',
    'reports' => 'bell',
    'users' => 'user-o',
    'search' => 'search',
    'plugins' => 'plug',
    'ban' => 'minus-circle',
    'rules' => 'institution',
    'styles' => 'paint-brush',
    'extra' => 'window-restore',
    'check_update' => 'download',
    'repair' => 'wrench',

);



//
//We hide list of admin menu and show only if there is auth.
//
$SHOW_LIST = true;

//get adm extensions
$adm_extensions = array();

if (($dh = @opendir(ADM_FILES_PATH)) !== false)
{
	while (($file = readdir($dh)) !== false)
	{
		if(strpos($file, '.php') !== false)
		{
			$adm_extensions[] = str_replace('.php', '', $file);
		}
	}
	closedir($dh);
}

//no extensions ?
if(!$adm_extensions || !is_array($adm_extensions))
{
	if(ig('_ajax_'))
	{
		echo_ajax(888, 'Error while loading admin extensions!.');
	}

	big_error('No Extensions', 'Error while loading admin extensions !');
}


is_array($plugin_run_result = Plugins::getInstance()->run('begin_admin_page', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook



/**
* Exception of 406 ! dirty hosting
* 'configs' word listed as dangrous requested word
* so we replaced this word with 'options' instead. 
*/
if($go_to == 'options')
{
	$go_to = 'a_configs';
}

//no request or wrong !
if(!$go_to || empty($go_to) ||  !in_array($go_to, $adm_extensions))
{
	$go_to = 'start';
}

//make array for menu 
$adm_extensions_menu =	$adm_topmenu = array();


//sort the items as alphabetic !
sort($adm_extensions);
$i = 0;
$cr_time = LAST_VISIT > 0 ? LAST_VISIT : time() - 3600*12;


// check calls and reports numbers
if(ig('check_msgs') || !ig('_ajax_')):

//small bubble system 
//any item can show what is inside it as unread messages
$kbubbles = array();

//for calls and reports
foreach(array('call'=>'calls', 'reports'=>'reports') as $table=>$n)
{
	$query	= array(
					'SELECT'	=> 'COUNT(' . $table[0] . '.id) AS total_rows',
					'FROM'		=> "`{$dbprefix}" . $table . "` " . $table[0]
				);

	$fetched = $SQL->fetch_array($SQL->build($query));

	$kbubbles[$n] = $fetched['total_rows'];

	$SQL->freeresult();
}

#if ajax, echo differntly
if(ig('check_msgs'))
{
	$SQL->close();
	exit($kbubbles['calls'] . '::' . $kbubbles['reports']);
}

//add your own bubbles here
is_array($plugin_run_result = Plugins::getInstance()->run('kbubbles_admin_page', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook

endif;


foreach($adm_extensions as $m)
{
	//some exceptions
	if(@in_array($m, $ext_expt))
	{
		continue;
	}

    is_array($plugin_run_result = Plugins::getInstance()->run('foreach_ext_admin_page', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook

	$s = $m;
	$m = isset($m[1]) && $m[1] == '_' ? substr($m , 2) : $m;


	++$i;
	$adm_extensions_menu[$i]	= array(
                                        'm'         => $m,
										'i'			=> $i+1,
										'i2'		=> $i+2,
										'icon'		=> !empty($ext_icons[$m]) ? $ext_icons[$m] : 'puzzle-piece',

										'lang'		=> !empty($lang['R_'. strtoupper($m)]) ? $lang['R_'. strtoupper($m)] : (!empty($olang['R_' . strtoupper($m)]) ? $olang['R_' . strtoupper($m)] : strtoupper($m)),
										'link'		=> './' . basename(ADMIN_PATH) . '?cp=' . ($m == 'configs' ? 'options' : $s) . (@in_array($m, $ext_formkey) ? '&amp;' . $GET_FORM_KEY_GLOBAL : ''),
										'confirm'	=> (@in_array($m, $ext_confirm)) ? true : false,
										'current'	=> ($s == $go_to) ? true : false,
										'goto'		=> str_replace('a_configs', 'options', $s),
										'bubble'	=> !emptY($kbubbles[$m]) ? '<span class="badge badge-pill badge-warning bubble_' . $m . '"' . ($kbubbles[$m] == 0 ? ' style="display:none"' : '') . '>' . $kbubbles[$m] . '</span>' : '',
										'counter'	=> !emptY($kbubbles[$m]) ?  $kbubbles[$m] : ''
									);

	//add another item to array for title='' in href or other thing
	$adm_extensions_menu[$i]['title'] = $adm_extensions_menu[$i]['lang'];


    is_array($plugin_run_result = Plugins::getInstance()->run('endforeach_ext_admin_page', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook
}


#to attach kleeja version in the menu start item
$assigned_klj_ver = preg_replace('!#([a-z0-9]+)!', '', KLEEJA_VERSION);

#for plugins
$styleePath = null;

//get it 
if (file_exists(ADM_FILES_PATH . '/' . $go_to . '.php'))
{
    $include = true;

    is_array($plugin_run_result = Plugins::getInstance()->run("require_admin_page_begin_{$go_to}", get_defined_vars())) ? extract($plugin_run_result) : null; //run hook

   if($include)
   {
       include_once ADM_FILES_PATH . '/' . $go_to . '.php';
   }

    is_array($plugin_run_result = Plugins::getInstance()->run("require_admin_page_end_{$go_to}", get_defined_vars())) ? extract($plugin_run_result) : null; //run hook

}
else
{
    $include_alternative = null;

    is_array($plugin_run_result = Plugins::getInstance()->run("not_exists_{$go_to}", get_defined_vars())) ? extract($plugin_run_result) : null; //run hook

    if(!empty($include_alternative) && file_exists($include_alternative))
    {
        include_once $include_alternative;
    }
    else
    {
        if (ig('_ajax_'))
        {
            echo_ajax(888, 'Error while loading : ' . $go_to);
        }

        big_error('In Loading !', 'Error while loading : ' . $go_to);
    }
}



//no style defined
if(empty($stylee))
{
	$text = $lang['NO_TPL_SHOOSED'];
	$stylee = 'admin_info';
}


$go_menu_html = '';
if(isset($go_menu))
{
	foreach($go_menu as $m=>$d)
	{
        $go_menu_html .= '<li class="' . ($d['current'] ? 'active' : '') . '" id="c_' . $d['goto'] . '">' .
            '<a' .  ($m == 'site' ? ' target="_blank" ' : ' ') . 'href="' . $d['link'] . '" ' . ($d['confirm'] ? ' onclick="javascript:return confirm_form();"' : '') . '>' .
            $d['name'] . '</a></li>';
	}
}

//add extra html to header or footer
$extra_admin_header_code = $extra_admin_footer_code = '';

is_array($plugin_run_result = Plugins::getInstance()->run('end_admin_page', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook


//header
if(!ig('_ajax_'))
{
	echo $tpl->display("admin_header");
}


//body
if(!ig('_ajax_'))
{
	$is_ajax = 'no';
	echo $tpl->display($stylee, $styleePath);
}
else
{
	$is_ajax = 'yes';

	echo_ajax(1, 
			empty($adminAjaxContent) ? $tpl->display($stylee, $styleePath) : $adminAjaxContent, 
			$go_menu_html
	);
}

//footer
if(!ig('_ajax_'))
{
	echo $tpl->display("admin_footer");
}
//close db
$SQL->close();
exit;
