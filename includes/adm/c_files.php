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
$files_acp_perpage = defined('ACP_FILES_PER_PAGE') ? ACP_FILES_PER_PAGE : 20;


//display
$stylee        = 'admin_files';

$url_or                = isset($_REQUEST['order_by']) ? '&amp;order_by=' . htmlspecialchars($_REQUEST['order_by']) . (isset($_REQUEST['order_way']) ? '&amp;order_by=1' : '') : '';
$url_or2               = isset($_REQUEST['order_by']) ? '&amp;order_by=' . htmlspecialchars($_REQUEST['order_by'])  : '';
$url_lst               = isset($_REQUEST['last_visit']) ? '&amp;last_visit=' . htmlspecialchars($_REQUEST['last_visit']) : '';
$url_sea               = ig('search_id') ? '&amp;search_id=' . g('search_id') : '';
$url_pg                = ig('page') ? '&amp;page=' . g('page', 'int') : '';
$page_action           = basename(ADMIN_PATH) . '?cp=' . basename(__file__, '.php') . $url_or . $url_sea . $url_lst;
$ord_action            = basename(ADMIN_PATH) . '?cp=' . basename(__file__, '.php') . $url_pg . $url_sea . $url_lst;
$page2_action          = basename(ADMIN_PATH) . '?cp=' . basename(__file__, '.php') . $url_or2 . $url_sea . $url_lst;
$action                = $page_action . $url_pg;
$is_search             = $affected      = false;
$H_FORM_KEYS           = kleeja_add_form_key('adm_files');

//
// Check form key
//

if (ip('submit'))
{
    //wrong form
    if (! kleeja_check_form_key('adm_files'))
    {
        kleeja_admin_err($lang['INVALID_FORM_KEY'], true, $lang['ERROR'], true, $action, 1);
    }

    $del = [];

    //gather to-be-deleted file ids
    foreach ($_POST as $key => $value)
    {
        if (preg_match('/del_(?P<digit>\d+)/', $key))
        {
            $del[$key] = $value;
        }
    }

    //delete them once by once
    $ids       = [];
    $files_num = $imgs_num = $sizes = 0;

    //TODO use IN(...)
    foreach ($del as $key => $id)
    {
        $query    = [
            'SELECT'           => 'f.id, f.name, f.folder, f.size, f.type',
            'FROM'             => "{$dbprefix}files f",
            'WHERE'            => 'f.id = ' . intval($id),
        ];

        $result = $SQL->build($query);

        while ($row=$SQL->fetch_array($result))
        {
            //delete from folder ..
            @kleeja_unlink(PATH . $row['folder'] . '/' . $row['name']);
            //delete thumb
            if (file_exists(PATH . $row['folder'] . '/thumbs/' . $row['name']))
            {
                @kleeja_unlink(PATH . $row['folder'] . '/thumbs/' . $row['name']);
            }

            $is_image = in_array(strtolower(trim($row['type'])), ['gif', 'jpg', 'jpeg', 'bmp', 'png']) ? true : false;

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

    is_array($plugin_run_result = Plugins::getInstance()->run('submit_files_admin', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook


    $SQL->freeresult($result);

    //no files to delete
    if (isset($ids) && sizeof($ids))
    {
        $query_del = [
            'DELETE'    => "{$dbprefix}files",
            'WHERE'     => '`id` IN (' . implode(',', $ids) . ')'
        ];

        $SQL->build($query_del);

        //update number of stats
        $update_query    = [
            'UPDATE'       => "{$dbprefix}stats",
            'SET'          => "sizes=sizes-$sizes, files=files-$files_num, imgs=imgs-$imgs_num",
        ];

        $SQL->build($update_query);

        if ($SQL->affected())
        {
            delete_cache('data_stats');
            $affected = true;
        }
    }

    //show msg now
    $text = ($affected && (isset($ids) && sizeof($ids)) ? $lang['FILES_UPDATED'] : $lang['NO_UP_CHANGE_S']) .
                '<script type="text/javascript"> setTimeout("get_kleeja_link(\'' . str_replace('&amp;', '&', $action) . '\');", 2000);</script>' . "\n";
    $stylee    = 'admin_info';
}
else
{

//
//Delete all user files [only one user]
//
    if (ig('deletefiles'))
    {
        $query    = [
            'SELECT'       => 'f.id, f.size, f.name, f.folder',
            'FROM'         => "{$dbprefix}files f",
        ];

        //get search filter
        $filter = get_filter(g('search_id'), 'file_search', false, 'filter_uid');

        if (! $filter)
        {
            kleeja_admin_err($lang['ADMIN_DELETE_FILES_NOF']);
        }

        $query['WHERE'] = build_search_query(unserialize(htmlspecialchars_decode($filter['filter_value'])));

        if ($query['WHERE'] == '')
        {
            kleeja_admin_err($lang['ADMIN_DELETE_FILES_NOF']);
        }

        $result    = $SQL->build($query);
        $sizes     = false;
        $ids       = [];
        $files_num = $imgs_num = 0;
        while ($row=$SQL->fetch_array($result))
        {
            //delete from folder ..
            @kleeja_unlink(PATH . $row['folder'] . '/' . $row['name']);

            //delete thumb
            if (file_exists(PATH . $row['folder'] . '/thumbs/' . $row['name']))
            {
                @kleeja_unlink(PATH . $row['folder'] . '/thumbs/' . $row['name']);
            }

            $is_image = in_array(strtolower(trim($row['type'])), ['gif', 'jpg', 'jpeg', 'bmp', 'png']) ? true : false;

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

        $SQL->freeresult($result);

        if (($files_num + $imgs_num) == 0)
        {
            kleeja_admin_err($lang['ADMIN_DELETE_FILES_NOF']);
        }
        else
        {
            //update number of stats
            $update_query    = [
                'UPDATE'       => "{$dbprefix}stats",
                'SET'          => "sizes=sizes-$sizes, files=files-$files_num, imgs=imgs-$imgs_num",
            ];

            $SQL->build($update_query);

            if ($SQL->affected())
            {
                delete_cache('data_stats');
            }

            //delete all files in just one query
            $query_del    = [
                'DELETE'    => "{$dbprefix}files",
                'WHERE'     => '`id` IN (' . implode(',', $ids) . ')'
            ];

            $SQL->build($query_del);

            kleeja_admin_info(sprintf($lang['ADMIN_DELETE_FILES_OK'], ($files_num + $imgs_num)));
        }
    }

//
    //begin default files page
//

    $query    = [
        'SELECT'         => 'COUNT(f.id) AS total_files',
        'FROM'           => "{$dbprefix}files f",
        'ORDER BY'       => 'f.id '
    ];

    //if user system is default, we use users table
    if ((int) $config['user_system'] == 1)
    {
        $query['JOINS']    =    [
            [
                'LEFT JOIN'       => "{$dbprefix}users u",
                'ON'              => 'u.id=f.user'
            ]
        ];
    }

    $do_not_query_total_files = false;

    //posts search ..
    if (ig('search_id'))
    {
        //get search filter
        $filter            = get_filter(g('search_id'), 'file_search', false, 'filter_uid');
        $deletelink        = basename(ADMIN_PATH) . '?cp=' . basename(__file__, '.php') . '&deletefiles=' . g('search_id');
        $is_search         = true;
        $query['WHERE']    = build_search_query(unserialize(htmlspecialchars_decode($filter['filter_value'])));
    }
    elseif (isset($_REQUEST['last_visit']))
    {
        $query['WHERE']    = 'f.time > ' . intval($_REQUEST['last_visit']);
    }

    //to-be-deleted
    //it is becoming a headache for a big websites. We do not have the time to figure out a solution

    if (isset($_REQUEST['order_by']) && in_array($_REQUEST['order_by'], ['real_filename', 'size', 'user', 'user_ip', 'uploads', 'time', 'type', 'folder', 'report']))
    {
        $query['ORDER BY'] = 'f.' . $SQL->escape($_REQUEST['order_by']);
    }
    else
    {
        $do_not_query_total_files = true;
    }

    if (! ig('search_id'))
    {
        //display files or display pics and files only in search
        $img_types      = ['gif','jpg','png','bmp','jpeg','GIF','JPG','PNG','BMP','JPEG'];
        $query['WHERE'] = (empty($query['WHERE']) ? '' : $query['WHERE'] . ' AND ') . "f.type NOT IN ('" . implode("', '", $img_types) . "')";
    }
    else
    {
        $do_not_query_total_files = false;
    }



    $query['ORDER BY'] .= (isset($_REQUEST['order_way']) && (int) $_REQUEST['order_way'] == 1) ? ' ASC' : ' DESC';

    $nums_rows = 0;

    if ($do_not_query_total_files)
    {
        $nums_rows = get_actual_stats('files');
    }
    else
    {
        $result_p  = $SQL->build($query);
        $n_fetch   = $SQL->fetch_array($result_p);
        $nums_rows = $n_fetch['total_files'];
        $SQL->freeresult($result_p);
    }


    //pager
    $currentPage      = ig('page') ? g('page', 'int') : 1;
    $Pager            = new Pagination($files_acp_perpage, $nums_rows, $currentPage);
    $start            = $Pager->getStartRow();

    $no_results = false;

    is_array($plugin_run_result = Plugins::getInstance()->run('query_files_admin', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook


    if ($nums_rows > 0)
    {
        $query['SELECT']    = 'f.*' . ((int) $config['user_system'] == 1 ? ', u.name AS username' : '');
        $query['LIMIT']     = "$start, $files_acp_perpage";
        $result             = $SQL->build($query);
        $sizes              = false;
        $num                = 0;
        //if Kleeja integtared we dont want make alot of queries
        $ids_and_names = [];

        while ($row=$SQL->fetch_array($result))
        {
            $userfile =  $config['siteurl'] . ($config['mod_writer'] ? 'fileuser-' . $row['user'] . '.html' : 'ucp.php?go=fileuser&amp;id=' . $row['user']);


            $file_info = ['::ID::' => $row['id'], '::NAME::' => $row['name'], '::DIR::' => $row['folder'], '::FNAME::' => $row['real_filename']];

            $is_image = in_array(strtolower(trim($row['type'])), ['gif', 'jpg', 'jpeg', 'bmp', 'png']) ? true : false;

            $url = kleeja_get_link($is_image ? 'image': 'file', $file_info);


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
            $arr[]    = [
                'id'          => $row['id'],
                'name'        => '<a title="' . $file_name . '" href="' . $url . '" target="blank">' .
                    shorten_text($file_name, 25) . '</a>',
                'fullname'            => $file_name,
                'size'                => readable_size($row['size']),
                'ups'                 => $row['uploads'],
                'direct'              => $row['id_form'] == 'direct' ? true : false,
                'time_human'          => kleeja_date($row['time']),
                'time'                => kleeja_date($row['time'], false),
                'type'                => $row['type'],
                'typeicon'            => file_exists(PATH . 'images/filetypes/' . $row['type'] . '.png') ? PATH . 'images/filetypes/' . $row['type'] . '.png' : PATH . 'images/filetypes/file.png',
                'folder'              => $row['folder'],
                'report'              => $row['report'] > 4 ? '<span style="color:red;font-weight:bold">' . $row['report'] . '</span>':$row['report'],
                'user'                => $row['user'] == '-1' ? $lang['GUST'] :  '<a href="' . $userfile . '" target="_blank">' . $row['username'] . '</a>',
                'ip'                  => '<a href="http://www.ripe.net/whois?form_type=simple&amp;full_query_string=&amp;searchtext=' . $row['user_ip'] . '&amp;do_search=Search" target="_new">' . $row['user_ip'] . '</a>',
                'showfilesbyip'       => basename(ADMIN_PATH) . '?cp=h_search&amp;s_input=1&amp;s_value=' . $row['user_ip']
            ];

            is_array($plugin_run_result = Plugins::getInstance()->run('arr_files_admin', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook

            $del[$row['id']] = p('del_' . $row['id']);
        }

        $SQL->freeresult($result);
    }
    else
    {
        //no result ..
        $no_results = true;
    }


    //update f_lastvisit
    if (! $is_search)
    {
        if (filter_exists('f_lastvisit', 'filter_uid', 'lastvisit', $userinfo['id']))
        {
            update_filter('f_lastvisit', time(), 'lastvisit', false, $userinfo['id']);
        }
        else
        {
            insert_filter('f_lastvisit', time(), 'lastvisit', time(), $userinfo['id']);
        }
    }


    //some vars
    $total_pages     = $Pager->getTotalPages();
    $page_nums       = $Pager->print_nums($page_action);
    $current_page    = $Pager->getCurrentPage();
}
