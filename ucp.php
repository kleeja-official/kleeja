<?php
/**
*
* @package Kleeja
* @copyright (c) 2007 Kleeja.net
* @license ./docs/license.txt
*
*/



/**
 * @ignore
 */
define('IN_KLEEJA', true);
define('IN_UCP', true);

require_once 'includes/common.php';

is_array($plugin_run_result = Plugins::getInstance()->run('begin_usrcp_page', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook


$extra      = '';
$show_style = true;
$styleePath = null;

switch (g('go'))
{
    //
    //login page
    //
    case 'login' :

        //page info
        $stylee                       = 'login';
        $titlee                       = $lang['LOGIN'];
        $action                       = 'ucp.php?go=login' . (ig('return') ? '&amp;return=' . g('return') : '');
        $forget_pass_link             = 'ucp.php?go=get_pass';
        $H_FORM_KEYS                  = kleeja_add_form_key('login');
        //no error yet
        $ERRORS = false;

        //_post
        $t_lname = p('lname');
        $t_lpass = p('lpass');

        is_array($plugin_run_result = Plugins::getInstance()->run('login_before_submit', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook

        //logon before !
        if ($usrcp->name())
        {
            is_array($plugin_run_result = Plugins::getInstance()->run('login_logon_before', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook

            $errorpage    = true;
            $text         = $lang['LOGINED_BEFORE'] . ' ..<br /> <a href="' . $config['siteurl'] . ($config['mod_writer'] ?  'logout.html' : 'ucp.php?go=logout') . '">' . $lang['LOGOUT'] . '</a>';
            kleeja_info($text);
        }
        elseif (ip('submit'))
        {
            $ERRORS    = [];

            is_array($plugin_run_result = Plugins::getInstance()->run('login_after_submit', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook

            //check for form key
            if (! kleeja_check_form_key('login'))
            {
                $ERRORS['form_key'] = $lang['INVALID_FORM_KEY'];
            }

            if (! kleeja_check_captcha())
            {
                if (function_exists('gd_info'))
                {
                    $ERRORS['captcha'] = $lang['WRONG_VERTY_CODE'];
                }
            }


            if (empty(p('lname')) || empty(p('lpass')))
            {
                $ERRORS['empty_fields'] = $lang['EMPTY_FIELDS'];
            }


            is_array($plugin_run_result = Plugins::getInstance()->run('login_after_submit2', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook

            if (empty($ERRORS))
            {
                if (! $usrcp->data(p('lname'), p('lpass'), false, (! ip('remme') ? false : p('remme'))))
                {
                    $ERRORS['login_check'] = $lang['LOGIN_ERROR'];
                }
                else
                {
                    $errorpage = true;
                    is_array($plugin_run_result = Plugins::getInstance()->run('login_data_no_error', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook

                    if (ig('return'))
                    {
                        redirect(urldecode(g('return')));
                        $SQL->close();

                        exit;
                    }

                    $text = $lang['LOGIN_SUCCESFUL'] . ' <br /> <a href="' . $config['siteurl'] . '">' . $lang['HOME'] . '</a>';
                    kleeja_info($text, '', true, $config['siteurl'], 1);
                }
            }
        }


        break;

        //
        //register page
        //
        case 'register' :

        //page info
        $stylee         = 'register';
        $titlee         = $lang['REGISTER'];
        $action         = 'ucp.php?go=register';
        $H_FORM_KEYS    = kleeja_add_form_key('register');
        //no error yet
        $ERRORS = false;

        //config register
        if ((int) $config['register'] != 1 && (int) $config['user_system'] == 1)
        {
            kleeja_info($lang['REGISTER_CLOSED'], $lang['PLACE_NO_YOU']);
        }
        elseif ($config['user_system'] != '1')
        {
            $goto_forum_link = '...';
            is_array($plugin_run_result = Plugins::getInstance()->run('register_not_default_sys', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook

            kleeja_info('<a href="' . $goto_forum_link . '" title="' . $lang['REGISTER'] . '" target="_blank">' . $lang['REGISTER'] . '</a>', $lang['REGISTER']);
        }

        //logon before !
        if ($usrcp->name())
        {
            is_array($plugin_run_result = Plugins::getInstance()->run('register_logon_before', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook
            kleeja_info($lang['REGISTERED_BEFORE']);
        }


        //_post
        $t_lname  = p('lname');
        $t_lpass  = p('lpass');
        $t_lpass2 = p('lpass2');
        $t_lmail  = p('lmail');

        //no submit
        if (! ip('submit'))
        {
            is_array($plugin_run_result = Plugins::getInstance()->run('register_no_submit', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook
        }
        else
        { // submit
            $ERRORS = [];

            is_array($plugin_run_result = Plugins::getInstance()->run('register_submit', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook

            //check for form key
            if (! kleeja_check_form_key('register'))
            {
                $ERRORS['form_key'] = $lang['INVALID_FORM_KEY'];
            }

            if (! kleeja_check_captcha())
            {
                $ERRORS['captcha'] = $lang['WRONG_VERTY_CODE'];
            }

            if (trim(p('lname')) == '' || trim(p('lpass')) == '' || trim(p('lmail')) == '')
            {
                $ERRORS['empty_fields'] = $lang['EMPTY_FIELDS'];
            }

            if ($t_lpass != $t_lpass2)
            {
                $ERRORS['pass_neq_pass2'] = $lang['PASS_NEQ_PASS2'];
            }

            if (! preg_match("/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,4})$/i", trim(p('lmail'))))
            {
                $ERRORS['lmail'] = $lang['WRONG_EMAIL'];
            }

            if (strlen(trim(p('lname'))) < 3 || strlen(trim(p('lname'))) > 50 || preg_match('/[^\p{L}_-]/u', p('lname')))
            {
                $ERRORS['lname'] = $lang['WRONG_NAME'];
            }
            elseif ($SQL->num_rows($SQL->query("SELECT * FROM {$dbprefix}users WHERE clean_name='" . trim($SQL->escape($usrcp->cleanusername(p('lname')))) . "'")) != 0)
            {
                $ERRORS['name_exists_before'] = $lang['EXIST_NAME'];
            }
            elseif ($SQL->num_rows($SQL->query("SELECT * FROM {$dbprefix}users WHERE mail='" . strtolower(trim($SQL->escape(p('lmail')))) . "'")) != 0)
            {
                $ERRORS['mail_exists_before'] = $lang['EXIST_EMAIL'];
            }

            is_array($plugin_run_result = Plugins::getInstance()->run('register_submit2', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook

            //no errors, lets do process
            if (empty($ERRORS))
            {
                $name              = (string) $SQL->escape(trim(p('lname')));
                $user_salt         = (string) substr(base64_encode(pack('H*', sha1(mt_rand()))), 0, 7);
                $pass              = (string) $usrcp->kleeja_hash_password($SQL->escape(trim(p('lpass'))) . $user_salt);
                $mail              = (string) strtolower(trim($SQL->escape(p('lmail'))));
                $session_id        = (string) constant('KJ_SESSION');
                $clean_name        = (string) $usrcp->cleanusername($name);

                $insert_query    = [
                    'INSERT'       => 'name ,password, password_salt ,mail, register_time, session_id, clean_name, group_id',
                    'INTO'         => "{$dbprefix}users",
                    'VALUES'       => "'$name', '$pass', '$user_salt', '$mail', " . time() . ", '$session_id','$clean_name', " . $config['default_group']
                ];

                is_array($plugin_run_result = Plugins::getInstance()->run('qr_insert_new_user_register', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook

                if ($SQL->build($insert_query))
                {
                    $last_user_id = $SQL->insert_id();
                    $text         = $lang['REGISTER_SUCCESFUL'] . ' <br /> <a href="' . $config['siteurl'] . '">' . $lang['HOME'] . '</a>';

                    //update number of stats
                    $update_query    = [
                        'UPDATE'       => "{$dbprefix}stats",
                        'SET'          => "users=users+1, lastuser='$name'",
                    ];

                    is_array($plugin_run_result = Plugins::getInstance()->run('ok_added_users_register', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook

                    if ($SQL->build($update_query))
                    {
                        //delete cache ..
                        delete_cache('data_stats');
                    }

                    //auto login
                    $usrcp->data($t_lname, $t_lpass, false, false);
                    kleeja_info($text, '', true, $config['siteurl'], 3);
                }
            }
        }

        break;

        //
        //logout action
        //
        case 'logout' :

            is_array($plugin_run_result = Plugins::getInstance()->run('begin_logout', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook

        if ($usrcp->logout())
        {
            $text = $lang['LOGOUT_SUCCESFUL'] . '<br /> <a href="' . $config['siteurl'] . '">' . $lang['HOME'] . '</a>';
            kleeja_info($text, $lang['LOGOUT'], true, $config['siteurl'], 1);
        }
        else
        {
            kleeja_err($lang['LOGOUT_ERROR']);
        }

            is_array($plugin_run_result = Plugins::getInstance()->run('end_logout', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook

        break;

        //
        //files user page
        //
        case 'fileuser' :

         is_array($plugin_run_result = Plugins::getInstance()->run('begin_fileuser', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook

        $stylee         = 'fileuser';
        $H_FORM_KEYS    = kleeja_add_form_key('fileuser');

        $user_id_get           = ig('id') ? g('id', 'int') : false;
        $user_id               = ! $user_id_get && $usrcp->id() ? $usrcp->id() : $user_id_get;
        $user_himself          = $usrcp->id() == $user_id;
        $action                = $config['siteurl'] . 'ucp.php?go=fileuser' . (ig('page') ? '&amp;page=' . g('page', 'int') : '');

        //no logon before
        if (! $usrcp->name() && ! ig('id'))
        {
            kleeja_err($lang['USER_PLACE'], $lang['PLACE_NO_YOU'], true, 'index.php');
        }

        //Not allowed to browse files's folders of other users
        if (! user_can('access_fileusers') && ! $user_himself)
        {
            is_array($plugin_run_result = Plugins::getInstance()->run('user_cannot_access_fileusers', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook
            kleeja_info($lang['HV_NOT_PRVLG_ACCESS'], $lang['HV_NOT_PRVLG_ACCESS']);
        }

        //Not allowed to access this page ?
        if (! user_can('access_fileuser') && $user_himself)
        {
            is_array($plugin_run_result = Plugins::getInstance()->run('user_cannot_access_fileuser', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook
            kleeja_info($lang['HV_NOT_PRVLG_ACCESS'], $lang['HV_NOT_PRVLG_ACCESS']);
        }

        //fileuser is closed ?
        if ((int) $config['enable_userfile'] != 1 && ! user_can('enter_acp'))
        {
            kleeja_info($lang['USERFILE_CLOSED'], $lang['CLOSED_FEATURE']);
        }

        //get user options and name
        $data_user = $config['user_system'] == 1 ? $usrcp->get_data('name, show_my_filecp', $user_id) : ['name' => $usrcp->usernamebyid($user_id), 'show_my_filecp' => '1'];

        //if there is no username, then there is no user at all
        if (empty($data_user['name']))
        {
            kleeja_err($lang['NOT_EXSIT_USER'], $lang['PLACE_NO_YOU']);
        }

            //this user closed his folder, and it's not the current user folder
        if (! $data_user['show_my_filecp'] && ($usrcp->id() != $user_id) && ! user_can('enter_acp'))
        {
            kleeja_info($lang['USERFILE_CLOSED'], $lang['CLOSED_FEATURE']);
        }

        $query    = [
            'SELECT'         => 'f.id, f.name, f.real_filename, f.folder, f.type, f.uploads, f.time, f.size',
            'FROM'           => "{$dbprefix}files f",
            'WHERE'          => 'f.user=' . $user_id,
            'ORDER BY'       => 'f.id DESC'
        ];

        //pager
        $perpage              = 16;
        $result_p             = $SQL->build($query);
        $nums_rows            = $SQL->num_rows($result_p);
        $currentPage          = ig('page') ? g('page', 'int') : 1;
        $Pager                = new Pagination($perpage, $nums_rows, $currentPage);
        $start                = $Pager->getStartRow();

        $your_fileuser       = $config['siteurl'] . ($config['mod_writer'] ? 'fileuser-' . $usrcp->id() . '.html' : 'ucp.php?go=fileuser&amp;id=' . $usrcp->id());
        $total_pages         = $Pager->getTotalPages();
        $linkgoto            = $config['siteurl'] . (
                                    $config['mod_writer']
                                    ?  'fileuser-' . $user_id . ($currentPage > 1  && $currentPage <= $total_pages ? '-' . $currentPage : '') . '.html'
                                    : 'ucp.php?go=fileuser' . (ig('id') ? (g('id', 'int') == $usrcp->id() ? '' : '&amp;id=' . g('id')) : null)
                            );

        $page_nums        = $Pager->print_nums(str_replace('.html', '', $linkgoto));

        $no_results = true;

        if ((int) $config['user_system'] != 1 && $usrcp->id() != $user_id)
        {
            $data_user['name'] = $usrcp->usernamebyid($user_id);
        }

        $user_name = ! $data_user['name'] ? false : $data_user['name'];

        //set page title
        $titlee    = $lang['FILEUSER'] . ': ' . $user_name;
        //there is result ? show them
        if ($nums_rows != 0)
        {
            $no_results = false;

            if (! ip('submit_all_files'))
            { // in delete all files we do not need any limit;
                $query['LIMIT'] = "$start, $perpage";
            }

            is_array($plugin_run_result = Plugins::getInstance()->run('qr_select_files_in_fileuser', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook

            $result    = $SQL->build($query);

            $i      = ($currentPage * $perpage) - $perpage;
            $tdnumi = $num = $files_num = $imgs_num = 0;
            while ($row=$SQL->fetch_array($result))
            {
                ++$i;
                $file_info = ['::ID::' => $row['id'], '::NAME::' => $row['name'], '::DIR::' => $row['folder'], '::FNAME::' => $row['real_filename']];

                $is_image = in_array(strtolower(trim($row['type'])), ['gif', 'jpg', 'jpeg', 'bmp', 'png']) ? true : false;

                $url = $is_image ? kleeja_get_link('image', $file_info) : kleeja_get_link('file', $file_info);

                $url_thumb = kleeja_get_link('thumb', $file_info);

                $url_fileuser = $is_image
                        ? $url
                        : (file_exists('images/filetypes/' . $row['type'] . '.png') ? 'images/filetypes/' . $row['type'] . '.png' : 'images/filetypes/file.png');

                $file_name = $row['real_filename'] == '' ? $row['name'] : $row['real_filename'];

                //make new lovely arrays !!
                $arr[]     = [
                    'id'              => $row['id'],
                    'name_file'       => shorten_text($file_name, 25),
                    'file_type'       => $row['type'],
                    'uploads'         => $row['uploads'],
                    'tdnum'           => $tdnumi == 0 ? '<ul>': '',
                    'tdnum2'          => $tdnumi == 4 ? '</ul>' : '',
                    'href'            => $url,
                    'size'            => readable_size($row['size']),
                    'time'            => ! empty($row['time']) ? kleeja_date($row['time']) : '...',
                    'thumb_link'      => $is_image ? $url_thumb : $url_fileuser,
                    'is_image'        => $is_image,
                ];

                $tdnumi = $tdnumi == 2 ? 0 : $tdnumi+1;

                if (ip('submit_files') && $user_himself)
                {
                    is_array($plugin_run_result = Plugins::getInstance()->run('submit_in_fileuser', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook

                    //check for form key
                    if (! kleeja_check_form_key('fileuser', 1800 /* half hour */))
                    {
                        kleeja_info($lang['INVALID_FORM_KEY']);
                    }

                    if ($_POST['del_' . $row['id']])
                    {
                        //delete from folder ..
                        @kleeja_unlink($row['folder'] . '/' . $row['name']);

                        //delete thumb
                        if (file_exists($row['folder'] . '/thumbs/' . $row['name']))
                        {
                            @kleeja_unlink($row['folder'] . '/thumbs/' . $row['name']);
                        }

                        $ids[] = $row['id'];

                        if ($is_image)
                        {
                            $imgs_num++;
                        }
                        else
                        {
                            $files_num++;
                        }

                        $sizes += $row['size'];
                    }
                }

                if (ip('submit_all_files') && $user_himself)
                {
                    is_array($plugin_run_result = Plugins::getInstance()->run('submit_in_all_fileuser', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook

                    //delete all files
                    foreach ($arr as $row)
                    {
                        @kleeja_unlink($row['folder'] . '/' . $row['name']);

                        //delete thumb
                        if (file_exists($row['folder'] . '/thumbs/' . $row['name']))
                        {
                            @kleeja_unlink($row['folder'] . '/thumbs/' . $row['name']);
                        }

                        $ids[] = $row['id'];

                        if ($is_image)
                        {
                            $imgs_num++;
                        }
                        else
                        {
                            $files_num++;
                        }

                        $sizes += $r['size'];
                    }
                }
            }

            $SQL->freeresult($result_p);
            $SQL->freeresult($result);

            //
            //after submit
            //
            if (ip('submit_files') && $user_himself)
            {
                //no files to delete
                if (isset($ids) && ! empty($ids))
                {
                    $query_del = [
                        'DELETE'       => "{$dbprefix}files",
                        'WHERE'        => 'id IN (' . implode(',', $ids) . ')'
                    ];

                    is_array($plugin_run_result = Plugins::getInstance()->run('qr_del_files_in_filecp', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook
                    $SQL->build($query_del);

                    if (($files_num <= $stat_files) && ($imgs_num <= $stat_imgs))
                    {
                        //update number of stats
                        $update_query    = [
                            'UPDATE'       => "{$dbprefix}stats",
                            'SET'          => "sizes=sizes-$sizes,files=files-$files_num, imgs=imgs-$imgs_num",
                        ];

                        $SQL->build($update_query);
                    }

                    //delete is ok, show msg
                    kleeja_info($lang['FILES_DELETED'], '', true, $linkgoto, 2);
                }
                else
                {
                    //no file selected, show msg
                    kleeja_info($lang['NO_FILE_SELECTED'], '', true, $linkgoto, 2);
                }
            }


            if (ip('submit_all_files') && $user_himself)
            {
                if (isset($ids) && ! empty($ids))
                {
                    $query_del = [
                        'DELETE'       => "{$dbprefix}files",
                        'WHERE'        => 'id IN (' . implode(',', $ids) . ')'
                    ];

                    is_array($plugin_run_result = Plugins::getInstance()->run('qr_del_files_in_filecp', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook
                    $SQL->build($query_del);

                    if (($files_num <= $stat_files) && ($imgs_num <= $stat_imgs))
                    {
                        //update number of stats
                        $update_query    = [
                            'UPDATE'       => "{$dbprefix}stats",
                            'SET'          => "sizes=sizes-$sizes,files=files-$files_num, imgs=imgs-$imgs_num",
                        ];

                        $SQL->build($update_query);
                    }


                    //write  all delete log for current user for last time only
                    $log_msg=$usrcp->name() . ' has deleted all his/her files at this time : ' . date('H:i a, d-m-Y') . "] \r\n" .
                    'files numbers:' . $files_num . "\r\n" .
                    'images numbers:' . $imgs_num . "\r\n";
                    $last_id=PATH . 'cache/' . $usrcp->id() . $usrcp->name();    //based on user id
                    file_put_contents($last_id, $log_msg);

                    //delete all files , show msg
                    kleeja_info($lang['ALL_DELETED'], '', true, $linkgoto, 2);
                }
                else
                {
                    //no file selected, show msg
                    kleeja_info($lang['NO_FILES_DELETE'], '', true, $linkgoto, 2);
                }
            }
        }//num result

        is_array($plugin_run_result = Plugins::getInstance()->run('end_fileuser', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook

        break;

        case 'profile' :

        //not a user
        if (! $usrcp->name())
        {
            kleeja_info($lang['USER_PLACE'], $lang['PLACE_NO_YOU']);
        }

        $stylee        = 'profile';
        $titlee        = $lang['PROFILE'];
        $action        = 'ucp.php?go=profile';
        $name          = $usrcp->name();
        $mail          = $usrcp->mail();
        extract($usrcp->get_data('show_my_filecp, password_salt'));
        $data_forum        = (int) $config['user_system'] == 1;
        $link_avater       = sprintf($lang['EDIT_U_AVATER_LINK'], '<a target="_blank" href="http://www.gravatar.com/">', '</a>');
        $H_FORM_KEYS       = kleeja_add_form_key('profile');
        //no error yet
        $ERRORS = false;

        $goto_forum_link = '...';

        //_post
        $t_pppass_old = p('pppass_old');
        $t_ppass_old  = p('ppass_old');
        $t_ppass_new  = p('ppass_new');
        $t_ppass_new2 = p('ppass_new2');

        is_array($plugin_run_result = Plugins::getInstance()->run('no_submit_profile', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook

        //
        // after submit
        //
        if (ip('submit_data'))
        {
            $ERRORS    = [];

            is_array($plugin_run_result = Plugins::getInstance()->run('submit_profile', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook

            //check for form key
            if (! kleeja_check_form_key('profile'))
            {
                $ERRORS['form_key'] = $lang['INVALID_FORM_KEY'];
            }

            //if there is new pass AND new pass1 = new pass2 AND old pass is exists & true
            if (! empty(p('ppass_new')))
            {
                if (p('ppass_new') != p('ppass_new2'))
                {
                    $ERRORS['pass1_neq_pass2'] = $lang['PASS_O_PASS2'];
                }
                //if current pass is not correct
                elseif (empty(p('ppass_old')) || ! $usrcp->kleeja_hash_password(p('ppass_old') . $password_salt, $userinfo['password']))
                {
                    $ERRORS['curnt_old_pass'] = $lang['CURRENT_PASS_WRONG'];
                }
            }

            //if email is not equal to current email AND email not exists before
            $new_mail = false;

            if ($usrcp->mail() != trim(strtolower(p('pmail'))))
            {
                //if current pass is not correct
                if (empty(p('pppass_old')) || ! $usrcp->kleeja_hash_password(p('pppass_old') . $password_salt, $userinfo['password']))
                {
                    $ERRORS['curnt_old_pass'] = $lang['CURRENT_PASS_WRONG'];
                }
                //If email is not valid
                elseif (! preg_match('/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,4})$/i', trim(p('pmail'))) || trim(p('pmail')) == '')
                {
                    $ERRORS['wrong_email'] = $lang['WRONG_EMAIL'];
                }
                //if email already exists
                elseif ($SQL->num_rows($SQL->query("SELECT * FROM {$dbprefix}users WHERE mail='" . strtolower(trim($SQL->escape(p('pmail')))) . "'")) != 0)
                {
                    $ERRORS['mail_exists_before'] = $lang['EXIST_EMAIL'];
                }

                $new_mail = true;
            }

            is_array($plugin_run_result = Plugins::getInstance()->run('submit_profile2', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook

            //no errors , do it
            if (empty($ERRORS))
            {
                $user_salt        = substr(base64_encode(pack('H*', sha1(mt_rand()))), 0, 7);
                $mail             = $new_mail ? "mail='" . $SQL->escape(strtolower(trim(p('pmail')))) . "'" : '';
                $showmyfile       = p('show_my_filecp', 'int') != $show_my_filecp ?  ($mail == '' ? '': ',') . "show_my_filecp='" . p('show_my_filecp', 'int') . "'" : '';
                $pass             = ! empty(p('ppass_new')) ? ($showmyfile != ''  || $mail != '' ? ',' : '') . "password='" . $usrcp->kleeja_hash_password($SQL->escape(p('ppass_new')) . $user_salt) .
                                "', password_salt='" . $user_salt . "'" : '';
                $id            = (int) $usrcp->id();

                $update_query    = [
                    'UPDATE'       => "{$dbprefix}users",
                    'SET'          => $mail . $showmyfile . $pass,
                    'WHERE'        => 'id=' . $id,
                ];

                is_array($plugin_run_result = Plugins::getInstance()->run('qr_update_data_in_profile', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook

                if (trim($update_query['SET']) == '')
                {
                    $text = $lang['DATA_CHANGED_NO'];
                }
                else
                {
                    $text = $lang['DATA_CHANGED_O_LO'];
                    $SQL->build($update_query);
                }

                kleeja_info($text, '', true, $action);
            }
        }//else submit

        is_array($plugin_run_result = Plugins::getInstance()->run('end_profile', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook

        break;

        //
        //reset password page
        //
        case 'get_pass' :

        //if not default system, let's give him a link for integrated script
        if ((int) $config['user_system'] != 1)
        {
            $forgetpass_link = '...';
            is_array($plugin_run_result = Plugins::getInstance()->run('get_pass_resetpass_link', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook

            $text = '<a href="' . $forgetpass_link . '">' . $lang['LOST_PASS_FORUM'] . '</a>';
            kleeja_info($text, $lang['PLACE_NO_YOU']);
        }

        //page info
        $stylee            = 'get_pass';
        $titlee            = $lang['GET_LOSTPASS'];
        $action            = 'ucp.php?go=get_pass';
        $H_FORM_KEYS       = kleeja_add_form_key('get_pass');
        //no error yet
        $ERRORS = false;

        //after sent mail .. come here
        //example: http://www.moyad.com/up/ucp.php?go=get_pass&activation_key=1af3405662ec373d672d003cf27cf998&uid=1

        if (ig('activation_key') && ig('uid'))
        {
            is_array($plugin_run_result = Plugins::getInstance()->run('get_pass_activation_key', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook

            $h_key = preg_replace('![^a-z0-9]!', '', g('activation_key'));
            $u_id  = g('uid', 'int');

            //if it's empty ?
            if (trim($h_key) == '')
            {
                big_error('No hash key', 'This is not a good link ... try again!');
            }

            $result = $SQL->query("SELECT new_password FROM {$dbprefix}users WHERE hash_key='" . $SQL->escape($h_key) . "' AND id=" . $u_id . '');

            if ($SQL->num_rows($result))
            {
                $npass = $SQL->fetch_array($result);
                $npass = $npass['new_password'];
                //password now will be same as new password
                $update_query = [
                    'UPDATE'   => "{$dbprefix}users",
                    'SET'      => "password = '" . $npass . "', new_password = '', hash_key = ''",
                    'WHERE'    => 'id=' . $u_id,
                ];

                is_array($plugin_run_result = Plugins::getInstance()->run('qr_update_newpass_activation', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook

                $SQL->build($update_query);

                $text = $lang['OK_APPLY_NEWPASS'] . '<br /><a href="' . $config['siteurl'] . ($config['mod_writer'] ?  'login.html' : 'ucp.php?go=login') . '">' . $lang['LOGIN'] . '</a>';
                kleeja_info($text);

                exit;
            }

            //no else .. just do nothing cuz it's wrong and wrong mean spams !
            redirect($config['siteurl'], true, true);

            exit;//i dont trust functions :)
        }

        //logon before ?
        if ($usrcp->name())
        {
            is_array($plugin_run_result = Plugins::getInstance()->run('get_pass_logon_before', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook
            kleeja_info($lang['LOGINED_BEFORE']);
        }

        //_post
        $t_rmail = p('rmail');

        //no submit
        if (! ip('submit'))
        {
            is_array($plugin_run_result = Plugins::getInstance()->run('no_submit_get_pass', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook
        }
        else
        { // submit
            $ERRORS    = [];

            is_array($plugin_run_result = Plugins::getInstance()->run('submit_get_pass', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook
            //check for form key
            if (! kleeja_check_form_key('get_pass'))
            {
                $ERRORS['form_key'] = $lang['INVALID_FORM_KEY'];
            }

            if (! kleeja_check_captcha())
            {
                $ERRORS['captcha'] = $lang['WRONG_VERTY_CODE'];
            }

            if (empty(p('rmail')))
            {
                $ERRORS['empty_fields'] = $lang['EMPTY_FIELDS'];
            }

            if (! preg_match('/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,4})$/i', trim(strtolower(p('rmail')))))
            {
                $ERRORS['rmail'] = $lang['WRONG_EMAIL'];
            }
            elseif ($SQL->num_rows($SQL->query("SELECT name FROM {$dbprefix}users WHERE mail='" . $SQL->escape(strtolower(p('rmail'))) . "'")) == 0)
            {
                $ERRORS['no_rmail'] = $lang['WRONG_DB_EMAIL'];
            }

            is_array($plugin_run_result = Plugins::getInstance()->run('submit_get_pass2', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook

            //no errors, lets do it
            if (empty($ERRORS))
            {
                $query    = [
                    'SELECT'   => 'u.*',
                    'FROM'     => "{$dbprefix}users u",
                    'WHERE'    => "u.mail='" . $SQL->escape(strtolower(trim(p('rmail')))) . "'"
                ];

                is_array($plugin_run_result = Plugins::getInstance()->run('qr_select_mail_get_pass', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook
                $result    =    $SQL->build($query);

                $row = $SQL->fetch_array($result);

                //generate password
                $chars   = 'abcdefghijklmnopqrstuvwxyz0123456789';
                $newpass = '';

                for ($i = 0; $i < 7; ++$i)
                {
                    $newpass .= substr($chars, (mt_rand() % strlen($chars)), 1);
                }

                $hash_key              = md5($newpass . time());
                $pass                  = (string) $usrcp->kleeja_hash_password($SQL->escape($newpass) . $row['password_salt']);
                $to                    = $row['mail'];
                $subject               = $lang['GET_LOSTPASS'] . ':' . $config['sitename'];
                $activation_link       = $config['siteurl'] . 'ucp.php?go=get_pass&activation_key=' . urlencode($hash_key) . '&uid=' . $row['id'];
                $message               = "\n " . $lang['WELCOME'] . ' ' . $row['name'] . "\r\n " . sprintf($lang['GET_LOSTPASS_MSG'], $activation_link, $newpass) . "\r\n\r\n kleeja.net";

                $update_query    = [
                    'UPDATE'   => "{$dbprefix}users",
                    'SET'      => "new_password = '" . $SQL->escape($pass) . "', hash_key = '" . $hash_key . "'",
                    'WHERE'    => 'id=' . $row['id'],
                ];

                is_array($plugin_run_result = Plugins::getInstance()->run('qr_update_newpass_get_pass', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook
                $SQL->build($update_query);

                $SQL->freeresult($result);

                //send it
                $send =  send_mail($to, $message, $subject, $config['sitemail'], $config['sitename']);

                if (! $send)
                {
                    kleeja_err($lang['CANT_SEND_NEWPASS']);
                }
                else
                {
                    $text    = $lang['OK_SEND_NEWPASS'] . '<br /><a href="' . $config['siteurl'] . ($config['mod_writer'] ?  'login.html' : 'ucp.php?go=login') . '">' . $lang['LOGIN'] . '</a>';
                    kleeja_info($text);
                }

                //no need of this var
                unset($newpass);
            }
        }

            is_array($plugin_run_result = Plugins::getInstance()->run('end_get_pass', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook

        break;

        //
        // Wrapper for captcha file
        //
        case 'captcha':
            include PATH . 'includes/captcha.php';

        exit;

        break;;

        //
        //add your own code here
        //
        default:

        $no_request = true;

            is_array($plugin_run_result = Plugins::getInstance()->run('default_usrcp_page', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook

        if ($no_request):
        kleeja_err($lang['ERROR_NAVIGATATION']);
        endif;

        break;
}//end switch

is_array($plugin_run_result = Plugins::getInstance()->run('end_usrcp_page', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook

//
//show style ...
//
$titlee = empty($titlee) ? $lang['USERS_SYSTEM'] : $titlee;
$stylee = empty($stylee) ? 'info' : $stylee;

//show style
if ($show_style)
{
    Saaheader($titlee, $extra);
    echo $tpl->display($stylee, $styleePath);
    Saafooter();
}
