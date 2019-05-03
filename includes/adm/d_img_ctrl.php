<?php
/**
*
* @package adm
* @copyright (c) 2007 Kleeja.com
* @license ./docs/license.txt
*
*/

// not for directly open
if (! defined('IN_ADMIN'))
{
    exit();
}

//number of images per page
$images_acp_perpage = defined('ACP_IMAGES_PER_PAGE') ? ACP_IMAGES_PER_PAGE : 20;


//display
$stylee	= 'admin_img';
$action	= basename(ADMIN_PATH) . '?cp=' . basename(__file__, '.php') . (ig('page') ? '&amp;page=' . g('page', 'int') : '') .
            (ig('last_visit') ? '&amp;last_visit=' . g('last_visit', 'int') : '') .
             (ig('smt') ? '&smt=' . g('smt') : '');
$action_search	= basename(ADMIN_PATH) . '?cp=h_search';
$H_FORM_KEYS	  = kleeja_add_form_key('adm_img_ctrl');
$is_search		   = false;

//
// Check form key
//
if (ip('submit'))
{
    if (! kleeja_check_form_key('adm_img_ctrl'))
    {
        kleeja_admin_err($lang['INVALID_FORM_KEY'], true, $lang['ERROR'], true, $action, 1);
    }

    $del = [];
    $num = $sizes = 0;


    foreach ($_POST as $key => $value)
    {
        if (preg_match('/del_(?P<digit>\d+)/', $key))
        {
            $del[$key] = $value;
        }
    }

    //TODO better way
    foreach ($del as $key => $id)
    {
        $query	= [
            'SELECT'	=> '*',
            'FROM'		 => "{$dbprefix}files",
            'WHERE'		=> '`id` = ' . intval($id),
        ];

        $result = $SQL->build($query);

        while ($row=$SQL->fetch_array($result))
        {
            //delete from folder ..
            @kleeja_unlink(PATH . $row['folder'] . '/' . $row['name']);
            //delete thumb
            if (file_exists(PATH . $row['folder'] . '/thumbs/' . $row['name'] ))
            {
                @kleeja_unlink(PATH . $row['folder'] . '/thumbs/' . $row['name'] );
            }
            $ids[] = $row['id'];
            $num++;		
            $sizes += $row['size'];
        }

        $SQL->free($result);
    }


    is_array($plugin_run_result = Plugins::getInstance()->run('submit_imgctrl_admin', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook


    //no files to delete
    if (isset($ids) && sizeof($ids))
    {
        $query_del = [
            'DELETE'	=> "{$dbprefix}files",
            'WHERE'	 => '`id` IN (' . implode(',', $ids) . ')'
        ];

        $SQL->build($query_del);

        //update number of stats
        $update_query	= [
            'UPDATE'	=> "{$dbprefix}stats",
            'SET'		  => "sizes=sizes-$sizes, imgs=imgs-$num",
        ];

        $SQL->build($update_query);

        if ($SQL->affected())
        {
            delete_cache('data_stats');
            $affected = true;
        }
    }

    //after submit 
    $text	= ($affected ? $lang['FILES_UPDATED'] : $lang['NO_UP_CHANGE_S']) .
                '<script type="text/javascript"> setTimeout("get_kleeja_link(\'' . basename(ADMIN_PATH) . '?cp=' . basename(__file__, '.php') . 
                '&page=' . (ig('page') ? g('page', 'int') : '1') . '\');", 2000);</script>' . "\n";

    $stylee	= 'admin_info';
}
else
{
    $query	= [
        'SELECT'	  => 'COUNT(f.id) AS total_files',
        'FROM'		   => "{$dbprefix}files f",
        'ORDER BY'	=> 'f.id DESC'
    ];

    //if user system is default, we use users table
    if ((int) $config['user_system'] == 1)
    {
        $query['JOINS']	=	[
            [
                'LEFT JOIN'	=> "{$dbprefix}users u",
                'ON'		      => 'u.id=f.user'
            ]
        ];
    }

    $img_types = ['gif','jpg','png','bmp','jpeg','GIF','JPG','PNG','BMP','JPEG'];

//
    // There is a bug with IN statement in MySQL and they said it will solved at 6.0 version
    // forums.mysql.com/read.php?10,243691,243888#msg-243888
    // $query['WHERE']	= "f.type IN ('" . implode("', '", $img_types) . "')";
//

    $query['WHERE'] = "(f.type = '" . implode("' OR f.type = '", $img_types) . "')";


    $do_not_query_total_files = false;

    if (ig('last_visit'))
    {
        $query['WHERE']	.= ' AND f.time > ' . g('last_visit', 'int');
    }
    else
    {
        $do_not_query_total_files = true;
    }


    is_array($plugin_run_result = Plugins::getInstance()->run('query_imgctrl_admin', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook


    $nums_rows = 0;

    if ($do_not_query_total_files)
    {
        $nums_rows = get_actual_stats('imgs');
    }
    else
    {
        $result_p  = $SQL->build($query);
        $n_fetch   = $SQL->fetch_array($result_p);
        $nums_rows = $n_fetch['total_files'];
        $SQL->freeresult($result_p);
    }

    //pager
    $currentPage= ig('page') ? g('page', 'int') : 1;
    $Pager		    = new Pagination($images_acp_perpage, $nums_rows, $currentPage);
    $start		    = $Pager->getStartRow();


    $no_results = $affected = $sizes = false;

    if ($nums_rows > 0)
    {
        $query['SELECT'] = 'f.*' . ((int) $config['user_system'] == 1 ? ', u.name AS username' : '');
        $query['LIMIT']	 = "$start, $images_acp_perpage";
        $result          = $SQL->build($query);

        $tdnum = $num = 0;
        //if Kleeja integrated we dont want make alot of queries
        $ids_and_names = [];

        while ($row=$SQL->fetch_array($result))
        {
            $file_info = ['::ID::' => $row['id'], '::NAME::' => $row['name'], '::DIR::' => $row['folder'], '::FNAME::' => $row['real_filename']];

            $url = kleeja_get_link('image', $file_info);

            $url_thumb = kleeja_get_link('thumb', $file_info);


            //for username in integrated user system
            if ($row['user'] != '-1' and (int) $config['user_system'] != 1)
            {
                if (! in_array($row['user'], $ids_and_names))
                {
                    $row['username']             = $usrcp->usernamebyid($row['user']);
                    $ids_and_names[$row['user']] = $row['username'];
                }
                else
                {
                    $row['username'] = $ids_and_names[$row['user']];
                }
            }

            $file_name = $row['real_filename'] == '' ? $row['name'] : $row['real_filename'];


            //make new lovely arrays !!
            $arr[]	= [
                'id'		      => $row['id'],
                'tdnum'		   => $tdnum == 0 ? '<ul>': '',
                'tdnum2'	   => $tdnum == 4 ? '</ul>' : '',
                'name'		    => shorten_text($file_name, 25),
                'ip' 		     => htmlspecialchars($row['user_ip']),
                'href'		    => $url,
                'size'		    => readable_size($row['size']),
                'ups'		     => $row['uploads'],
                'time'		    => date('d-m-Y h:i a', $row['time']),
                'user'		    => (int) $row['user'] == -1 ? $lang['GUST'] : $row['username'],
                'is_user'	  => (int) $row['user'] == -1 ? 0 : 1,
                'thumb_link'=> $url_thumb
            ];

            //fix ... 
            $tdnum = $tdnum == 4 ? 0 : $tdnum+1; 

            $del[$row['id']] = p('del_' . $row['id']);

            is_array($plugin_run_result = Plugins::getInstance()->run('arr_imgctrl_admin', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook
        }

        $SQL->freeresult($result);
    }
    else
    {
        $no_results = true;
    }

    //update f_lastvisit
    if (! $is_search)
    {
        if (filter_exists('i_lastvisit', 'filter_uid', 'lastvisit', $userinfo['id']))
        {
            update_filter('i_lastvisit', time(), 'lastvisit', false, $userinfo['id']);
        }
        else
        {
            insert_filter('i_lastvisit', time(), 'lastvisit', time(), $userinfo['id']);
        }
    }

    //pages
    $total_pages 	= $Pager->getTotalPages(); 
    $page_nums 		 = $Pager->print_nums(basename(ADMIN_PATH) . '?cp=' . basename(__file__, '.php') .
    (ig('last_visit') ? '&last_vists=' . g('last_visit', 'int') : '') .
    (ig('smt') ? '&smt=' . g('smt') : ''), 'onclick="javascript:get_kleeja_link($(this).attr(\'href\'), \'#content\'); return false;"');
    $current_page	= $Pager->getCurrentPage();
}
