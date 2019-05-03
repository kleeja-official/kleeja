<?php
/**
*
* @package adm
* @copyright (c) 2007 Kleeja.com
* @license ./docs/license.txt
*
*/


//no for directly open
if (! defined('IN_COMMON'))
{
    exit();
}

/**
* Print cp error function handler
*
* For admin
*/
function kleeja_admin_err($msg, $navigation = true, $title='', $exit = true, $redirect = false, $rs = 3, $style = 'admin_err')
{
    global $text, $tpl, $SHOW_LIST, $adm_extensions, $adm_extensions_menu;
    global $STYLE_PATH_ADMIN, $lang, $olang, $SQL, $MINI_MENU;


    if (is_string($navigation))
    {
        $redirect = $navigation;
    }

    is_array($plugin_run_result = Plugins::getInstance()->run('kleeja_admin_err_func', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook

    //Exception for ajax
    if (ig('_ajax_'))
    {
        $text = $msg . ($redirect ? "\n" . '<script type="text/javascript">setTimeout("get_kleeja_link(\'' . str_replace('&amp;', '&', $redirect) . '\');", ' . ($rs * 1000) . ');</script>' : '');
        echo_ajax(1, $tpl->display($style));
        $SQL->close();

        exit();
    }

    // assign {text} in err template
    $text		    = $msg . ($redirect != false ? redirect($redirect, false, false, $rs, true) : '');
    $SHOW_LIST	= $navigation;

    //header
    echo $tpl->display('admin_header');
    //show tpl
    echo $tpl->display($style);
    //footer
    echo $tpl->display('admin_footer');

    if ($exit)
    {
        $SQL->close();

        exit();
    }
}


/**
 * Print information message on admin panel
 *
 * @adm
 * @param string $msg        information message
 * @param bool   $navigation show navigation menu or not
 * @param string $title      information heading title
 * @param bool   $exit       if true, then halt after message
 * @param bool   $redirect   redirect after showing the message
 * @param int    $rs         delay the redirect in seconds
 */
function kleeja_admin_info($msg, $navigation=true, $title='', $exit=true, $redirect = false, $rs = 2)
{
    is_array($plugin_run_result = Plugins::getInstance()->run('kleeja_admin_info_func', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook

    kleeja_admin_err($msg, $navigation, $title, $exit, $redirect, $rs, 'admin_info');
}

/**
 * generate a filter..
 * @adm
 * @param  string|integer  $type   filter_id or filter_uid
 * @param  string          $value  filter value
 * @param  bool            $time   filter time
 * @param  bool            $user   user Id
 * @param  string          $status filter status
 * @param  bool            $uid    filter unique id
 * @return bool|int|string
 */
function insert_filter($type, $value, $time = false, $user = false, $status = '', $uid = false)
{
    global $SQL, $dbprefix, $userinfo;

    $user = ! $user ? $userinfo['id'] : $user;
    $time = ! $time ? time() : $time;
    $uid  = $uid ? $uid : uniqid();

    $insert_query	= [
        'INSERT'	=> 'filter_uid, filter_type ,filter_value ,filter_time ,filter_user, filter_status',
        'INTO'		 => "{$dbprefix}filters",
        'VALUES'	=> "'" . $uid . "', '" . $SQL->escape($type) . "','" . $SQL->escape($value) . "', " . intval($time) . ',' . intval($user) . ",'" . $SQL->escape($status) . "'"
    ];
    is_array($plugin_run_result = Plugins::getInstance()->run('insert_sql_insert_filter_func', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook

    $SQL->build($insert_query);

    return $SQL->insert_id() ? $uid : false;
}


/**
 * Update filter value..
 *
 * @param  int|string  $id_or_uid     Number of filter_id or the unique id string of filter_uid
 * @param  string      $value         The modified value of filter
 * @param  string      $filter_type   if given, use it with sql where
 * @param  bool|string $filter_status if given, update the filter status
 * @param  bool        $user_id
 * @return bool
 */
function update_filter($id_or_uid, $value, $filter_type = 'general', $filter_status = false, $user_id = false)
{
    global $SQL, $dbprefix;

    $update_query = [
        'UPDATE' => "{$dbprefix}filters",
        'SET'    => "filter_value='" . $SQL->escape($value) . "'" . ($filter_status ? ", filter_status='" . $SQL->escape($filter_status) . "'" : ''),
        'WHERE'  => (strval(intval($id_or_uid)) == strval($id_or_uid) ? 'filter_id=' . intval($id_or_uid) : "filter_uid='" . $SQL->escape($id_or_uid) . "'")
            . ($filter_type ? " AND filter_type='" . $SQL->escape($filter_type) . "'" : '')
            . ($user_id ? ' AND filter_user=' . intval($user_id) . '' : '')
    ];

    is_array($plugin_run_result = Plugins::getInstance()->run('update_filter_func', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook

    $SQL->build($update_query);

    if ($SQL->affected())
    {
        return true;
    }

    return false;
}


/**
 * Get filter from db..
 *
 * @param  string|int  $item        The value of $get_by, to get the filter depend on it
 * @param  bool|string $filter_type if given, use it with sql where
 * @param  bool        $just_value  If true the return value should be just filter_value otherwise all filter rows
 * @param  string      $get_by      The name of filter column we want to get the filter value from
 * @param  bool        $user_id
 * @return mixed
 */
function get_filter($item, $filter_type = false, $just_value = false, $get_by = 'filter_uid', $user_id = false)
{
    global $dbprefix, $SQL;

    $valid_filter_columns = ['filter_id', 'filter_uid', 'filter_user', 'filter_status'];

    if (! in_array($get_by, $valid_filter_columns))
    {
        $get_by = 'filter_uid';
    }

    $query = [
        'SELECT' => $just_value ? 'f.filter_value' : 'f.*',
        'FROM'   => "{$dbprefix}filters f",
        'WHERE'  => 'f.' . $get_by . ' = ' . ($get_by == 'filter_id' ? intval($item) : "'" . $SQL->escape($item) . "'")
            . ($filter_type ? " AND f.filter_type='" . $SQL->escape($filter_type) . "'" : '')
            . ($user_id ? ' AND f.filter_user=' . intval($user_id) . '' : '')
    ];

    is_array($plugin_run_result = Plugins::getInstance()->run('get_filter_func', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook


    $result = $SQL->build($query);
    $v      = $SQL->fetch($result);

    $SQL->free($result);

    if ($just_value)
    {
        return $v['filter_value'];
    }

    return $v;
}

/**
 * check if filter exists or not
 *
 * @param  string|int $item        The value of $get_by, to find the filter depend on it
 * @param  string     $get_by      The name of filter column we want to get the filter from
 * @param  bool       $filter_type
 * @param  bool       $user_id
 * @return bool|int
 */
function filter_exists($item, $get_by = 'filter_id', $filter_type = false, $user_id = false)
{
    global $dbprefix, $SQL;

    $query = [
        'SELECT' => 'f.filter_id',
        'FROM'   => "{$dbprefix}filters f",
        'WHERE'  => 'f.' . $get_by . ' = ' . ($get_by == 'filter_id' ? intval($item) : "'" . $SQL->escape($item) . "'")
            . ($filter_type ? " AND f.filter_type='" . $SQL->escape($filter_type) . "'" : '')
            . ($user_id ? ' AND f.filter_user=' . intval($user_id) . '' : '')

    ];

    is_array($plugin_run_result = Plugins::getInstance()->run('filter_exists_func', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook

    $result = $SQL->build($query);
    return $SQL->num_rows($result);
}



/**
 * costruct a query for the searches..
 * @adm
 * @param  array  $search Search options
 * @return string
 */
function build_search_query($search)
{
    if (! is_array($search))
    {
        return '';
    }

    global $SQL;

    $search['filename'] = ! isset($search['filename']) ? '' : $search['filename']; 
    $search['username'] = ! isset($search['username']) ? '' : $search['username'];
    $search['than']		   = ! isset($search['than']) ? '' : $search['than'];
    $search['size']		   = ! isset($search['size']) ? '' : $search['size'];
    $search['ups']		    = ! isset($search['ups']) ? '' : $search['ups'];
    $search['uthan']	   = ! isset($search['uthan']) ? '' : $search['uthan'];
    $search['rep']		    = ! isset($search['rep']) ? '' : $search['rep'];
    $search['rthan']	   = ! isset($search['rthan']) ? '' : $search['rthan'];
    $search['lastdown'] = ! isset($search['lastdown']) ? '' : $search['lastdown'];
    $search['ext']		    = ! isset($search['ext']) ? '' : $search['ext'];
    $search['user_ip']	 = ! isset($search['user_ip']) ? '' : $search['user_ip'];

    $file_namee	= $search['filename'] != '' ? 'AND (f.real_filename LIKE \'%' . $SQL->escape($search['filename']) . '%\' OR f.name LIKE \'%' . $SQL->escape($search['filename']) . '%\')' : ''; 
    $usernamee	 = $search['username'] != '' ? 'AND u.name LIKE \'%' . $SQL->escape($search['username']) . '%\'' : ''; 
    $size_than	 = ' f.size ' . ($search['than']!=1 ? '<=' : '>=') . (intval($search['size']) * 1024) . ' ';
    $ups_than	  = $search['ups']      != '' ? 'AND f.uploads ' . ($search['uthan']!=1 ? '<' : '>') . intval($search['ups']) . ' ' : '';
    $rep_than	  = $search['rep']      != '' ? 'AND f.report ' . ($search['rthan']!=1 ? '<' : '>') . intval($search['rep']) . ' ' : '';
    $lstd_than	 = $search['lastdown'] != '' ? 'AND f.last_down =' . (time()-(intval($search['lastdown']) * (24 * 60 * 60))) . ' ' : '';
    $exte		     = $search['ext']      != '' ? "AND f.type IN ('" . implode("', '", @explode(',', $SQL->escape($search['ext']))) . "')" : '';
    $ipp		      = $search['user_ip']  != '' ? 'AND f.user_ip LIKE \'%' . $SQL->escape($search['user_ip']) . '%\' ' : '';

    return "$size_than $file_namee $ups_than $exte $rep_than $usernamee $lstd_than $exte $ipp";
}

/**
 * To re-count the total files, without making the server goes down haha
 * @param  bool     $files
 * @param  bool     $start
 * @return bool|int
 */
function sync_total_files($files = true, $start = false)
{
    global $SQL, $dbprefix;

    $query	= [
        'SELECT'	=> 'MIN(f.id) as min_file_id, MAX(f.id) as max_file_id',
        'FROM'		 => "{$dbprefix}files f",
    ];

    //!files == images
    $img_types      = ['gif','jpg','png','bmp','jpeg','GIF','JPG','PNG','BMP','JPEG'];
    $query['WHERE'] = 'f.type' . ($files  ? ' NOT' : '') . " IN ('" . implode("', '", $img_types) . "')";

    $result	= $SQL->build($query);
    $v		    = $SQL->fetch($result);
    $SQL->freeresult($result);

    //if no data, turn them to number
    $min_id = (int) $v['min_file_id'];
    //	$max_id = (int) $v['max_file_id'];

    //every time batch
    $batch_size = 1500;

    //no start? start = min
    $first_loop = ! $start ? true : false;
    $start	     = ! $start ? $min_id : $start;
    $end	       = $start + $batch_size;

    //now lets get this step's files number 
    unset($v, $result);

    $query['SELECT'] = 'COUNT(f.id) as num_files';
    $query['WHERE'] .= ' AND f.id BETWEEN ' . $start . ' AND ' . $end;

    $result	= $SQL->build($query);
    $v		    = $SQL->fetch($result);
    $SQL->freeresult($result);

    $this_step_count = $v['num_files'];

    if ($this_step_count == 0)
    {
        return false;
    }

    //update stats table

    $update_query = [
        'UPDATE'	=> "{$dbprefix}stats"
    ];

    //make it zero, firstly
    if ($first_loop)
    {
        $update_query['SET'] = ($files ? 'files' : 'imgs') . '= 0'; 
        $SQL->build($update_query);
    }

    $update_query['SET'] = ($files ? 'files' : 'imgs') . '=' . ($files ? 'files' : 'imgs') . '+' . $this_step_count;
    $SQL->build($update_query);


    return $end;
}

/**
 * get the *right* now number of the given stat fro stats table
 * @param  string $name Stat name
 * @return int
 */
function get_actual_stats($name)
{
    global $dbprefix, $SQL;

    $query = [
        'SELECT'	=> 's.' . $name,
        'FROM'		 => "{$dbprefix}stats s"
    ];

    $result	= $SQL->build($query);
    $v		    = $SQL->fetch($result);

    is_array($plugin_run_result = Plugins::getInstance()->run('get_actual_stats_func', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook

    $SQL->freeresult($result);

    return $v[$name];
}

/**
 * check wether a start box is hidden or not  
 * @param  string $name box name
 * @return bool
 */
function adm_is_start_box_hidden($name)
{
    global $config;

    if (! isset($config['hidden_start_boxes']))
    {
        add_config('hidden_start_boxes', '');

        return false;
    }

    static $boxes;

    if (empty($boxes))
    {
        $boxes = explode(':', $config['hidden_start_boxes']);
        $boxes = array_filter($boxes);
    }


    is_array($plugin_run_result = Plugins::getInstance()->run('adm_start_boxes_func', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook

    return in_array($name, $boxes);
}

/**
 * delete plugin folder
 * @param  string $dir plugin folder path 
 * @return void
 */
function delete_plugin_folder($dir)
{
    $it    = new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS);
    $files = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);

    foreach ($files as $file)
    {
        if ($file->isLink())
        {
            unlink($file->getPathname());
        }
        elseif ($file->isDir())
        {
            rmdir($file->getPathname());
        }
        else
        {
            unlink($file->getPathname());
        }
    }
    rmdir($dir);
}
