<?php
/**
*
* @package adm
* @copyright (c) 2007 Kleeja.net
* @license ./docs/license.txt
*
*/


// not for directly open
if (! defined('IN_ADMIN'))
{
    exit();
}


//turn time-limit off
@set_time_limit(0);

//get current case
$case = false;

if (ig('case'))
{
    $case = g('case');
}


//set form ket
$GET_FORM_KEY = kleeja_add_form_key_get('REPAIR_FORM_KEY');


//check _GET Csrf token
if ($case && in_array($case, ['clearc', 'sync_files', 'sync_images', 'sync_users', 'tables', 'sync_sizes', 'status_file']))
{
    if (! kleeja_check_form_key_get('REPAIR_FORM_KEY'))
    {
        kleeja_admin_err($lang['INVALID_GET_KEY'], true, $lang['ERROR'], true, basename(ADMIN_PATH), 2);
    }
}

$text      = '';


switch ($case):

    default:

        // Get real number from database right now
        $all_files  = get_actual_stats('files');
        $all_images = get_actual_stats('imgs');
        $all_users  = get_actual_stats('users');
        $all_sizes  = readable_size(get_actual_stats('sizes'));


        //links
        $del_cache_link           = basename(ADMIN_PATH) . '?cp=r_repair&amp;case=clearc&amp;' . $GET_FORM_KEY;
        $resync_files_link        = $config['siteurl'] . 'go.php?go=resync&amp;case=sync_files';
        $resync_images_link       = $config['siteurl'] . 'go.php?go=resync&amp;case=sync_images';
        $resync_users_link        = basename(ADMIN_PATH) . '?cp=r_repair&amp;case=sync_users&amp;' . $GET_FORM_KEY;
        $resync_sizes_link        = basename(ADMIN_PATH) . '?cp=r_repair&amp;case=sync_sizes&amp;' . $GET_FORM_KEY;
        $repair_tables_link       = basename(ADMIN_PATH) . '?cp=r_repair&amp;case=tables&amp;' . $GET_FORM_KEY;

        $queue_cron_job_url = $config['siteurl'] . 'go.php?go=queue';

        $stylee = 'admin_repair';

        break;


//
        //fix tables ..
//
    case 'tables':

        $query     = 'SHOW TABLE STATUS';
        $result    = $SQL->query($query);

        while ($row=$SQL->fetch_array($result))
        {
            $queryf     =    'REPAIR TABLE `' . $row['Name'] . '`';
            $resultf    = $SQL->query($queryf);

            if ($resultf)
            {
                $text .= '<li>' . $lang['REPAIRE_TABLE'] . $row['Name'] . '</li>';
            }
        }

        $SQL->freeresult($result);

        $text .= '<script type="text/javascript"> setTimeout("get_kleeja_link(\'' . basename(ADMIN_PATH) . '?cp=r_repair' . '\');", 2000);</script>' . "\n";
        $stylee = 'admin_info';


        break;

//
        //re-sync sizes ..
//
    case 'sync_sizes':


        $query_s    = [
            'SELECT'       => 'size',
            'FROM'         => "{$dbprefix}files"
        ];

        $result_s = $SQL->build($query_s);

        $files_number = $files_sizes = 0;

        while ($row=$SQL->fetch_array($result_s))
        {
            $files_number++;
            $files_sizes = $files_sizes+$row['size'];
        }

        $SQL->freeresult($result_s);

        $update_query    = [
            'UPDATE'       => "{$dbprefix}stats",
            'SET'          => 'files=' . $files_number . ', sizes=' . $files_sizes
        ];

        if ($SQL->build($update_query))
        {
            $text .= '<li>' . $lang['REPAIRE_F_STAT'] . '</li>';
        }

        delete_cache('data_stats');

        $stylee = 'admin_info';

        break;


//
        //re-sync total users number ..
//
    case 'sync_users':

        $query_w    = [
            'SELECT'       => 'name',
            'FROM'         => "{$dbprefix}users"
        ];

        $result_w = $SQL->build($query_w);

        $user_number = 0;
        while ($row=$SQL->fetch_array($result_w))
        {
            $user_number++;
        }

        $SQL->freeresult($result_w);

        $update_query    = [
            'UPDATE'       => "{$dbprefix}stats",
            'SET'          => 'users=' . $user_number
        ];

        $result = $SQL->build($update_query);

        delete_cache('data_stats');
        $text = sprintf($lang['SYNCING'], $lang['USERS_ST']);
        $text .= '<script type="text/javascript"> setTimeout("get_kleeja_link(\'' . basename(ADMIN_PATH) . '?cp=r_repair' . '\');", 2000);</script>' . "\n";

        $stylee = 'admin_info';


        break;


//
        //clear all cache ..
//
    case 'clearc':

        //clear cache
        delete_cache('', true);

        //show done, msg
        $text .= '<li>' . $lang['REPAIRE_CACHE'] . '</li>';
        $text .= '<script type="text/javascript"> setTimeout("get_kleeja_link(\'' . basename(ADMIN_PATH) . '?cp=r_repair' . '\');", 2000);</script>' . "\n";

        $stylee = 'admin_info';

        break;

        //toggle admin start boxes
    case 'toggle_start_box':

        if (! kleeja_check_form_key_get('adm_start_actions'))
        {
            header('HTTP/1.1 405 Method Not Allowed');
            $adminAjaxContent = $lang['INVALID_FORM_KEY'];
        }
        else
        {
            $items     = explode(':', $config['hidden_start_boxes']);
            $new_items = $items = array_filter($items);

            $name = g('name');
            $hide = g('toggle', 'int') == 1;

            if (in_array($name, $items) && ! $hide)
            {
                $new_items = array_diff($items, [$name]);
            }
            elseif ($hide)
            {
                $new_items[] = $name;
            }

            if ($new_items != $items)
            {
                update_config('hidden_start_boxes', implode(':', $new_items));
            }

            $adminAjaxContent = $lang['CONFIGS_UPDATED'];
        }

        break;

endswitch;
