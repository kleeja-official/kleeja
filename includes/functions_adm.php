<?php
/**
*
* @package adm
* @copyright (c) 2007 Kleeja.net
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
 */
function kleeja_admin_err(string $message, bool $navigation = true, string $title = '', bool $exit = true, bool $redirect = false, $redirect_in_m_second = 3, string $style = 'admin_err'): void
{
    global $text, $tpl, $SHOW_LIST, $adm_extensions, $adm_extensions_menu;
    global $STYLE_PATH_ADMIN, $lang, $SQL, $MINI_MENU;


    if (is_string($navigation))
    {
        $redirect = $navigation;
    }

    is_array($plugin_run_result = Plugins::getInstance()->run('kleeja_admin_err_func', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook

    //Exception for ajax
    if (ig('_ajax_'))
    {
        $text = $message . ($redirect ? "\n" . '<script type="text/javascript">setTimeout("get_kleeja_link(\'' . str_replace('&amp;', '&', $redirect) . '\');", ' . ($redirect_in_m_second * 1000) . ');</script>' : '');
        echo_ajax(1, $tpl->display($style));
        $SQL->close();

        exit();
    }

    // assign {text} in err template
    $text            = $message . ($redirect !== false ? redirect($redirect, false, false, $redirect_in_m_second, true) : '');
    $SHOW_LIST       = $navigation;

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
 */
function kleeja_admin_info(string $message, bool $navigation = true, string $title = '', bool $exit = true, bool $redirect = false, $redirect_in_m_second = 2): void
{
    extract(runHook('kleeja_admin_info_func', get_defined_vars()));

    kleeja_admin_err($message, $navigation, $title, $exit, $redirect, $redirect_in_m_second, 'admin_info');
}

/**
 * generate a filter..
 */
function insert_filter(string $type, string $value, bool $time = false, bool $user = false, string $status = '', bool $uid = false)
{
    global $SQL, $dbprefix, $userinfo;

    $user = ! $user ? $userinfo['id'] : $user;
    $time = ! $time ? time() : $time;
    $uid  = $uid ? $uid : uniqid();

    $insert_query    = [
        'INSERT'       => 'filter_uid, filter_type ,filter_value ,filter_time ,filter_user, filter_status',
        'INTO'         => "{$dbprefix}filters",
        'VALUES'       => "'" . $uid . "', '" . $SQL->escape($type) . "','" . $SQL->escape($value) . "', " . intval($time) . ',' . intval($user) . ",'" . $SQL->escape($status) . "'"
    ];
    is_array($plugin_run_result = Plugins::getInstance()->run('insert_sql_insert_filter_func', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook

    $SQL->build($insert_query);

    return $SQL->insert_id() ? $uid : false;
}


/**
 * Update filter value..
 */
function update_filter(string $id_or_uid, string $value, string $filter_type = 'general', bool $filter_status = false, bool $user_id = false): bool
{
    echo '<pre>';
    print_r(get_defined_vars());
    echo '</pre>';
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

    $SQL->freeresult($result);

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

    global $SQL, $dbprefix, $config;

    $search['filename']       = ! isset($search['filename']) ? '' : $search['filename'];
    $search['username']       = ! isset($search['username']) ? '' : $search['username'];
    $search['than']           = ! isset($search['than']) ? '' : $search['than'];
    $search['size']           = ! isset($search['size']) ? '' : $search['size'];
    $search['ups']            = ! isset($search['ups']) ? '' : $search['ups'];
    $search['uthan']          = ! isset($search['uthan']) ? '' : $search['uthan'];
    $search['rep']            = ! isset($search['rep']) ? '' : $search['rep'];
    $search['rthan']          = ! isset($search['rthan']) ? '' : $search['rthan'];
    $search['lastdown']       = ! isset($search['lastdown']) ? '' : $search['lastdown'];
    $search['ext']            = ! isset($search['ext']) ? '' : $search['ext'];
    $search['user_ip']        = ! isset($search['user_ip']) ? '' : $search['user_ip'];

    //if searched by a username
    $usernamee = '';

    if (! empty($search['username']) && (int) $config['user_system'] == 1)
    {
        $query = [
            'SELECT'       => 'u.id',
            'FROM'         => "{$dbprefix}users u",
            'WHERE'        => "u.name LIKE '%" . $SQL->escape($search['username']) . "%'"
        ];

        is_array($plugin_run_result = Plugins::getInstance()->run('qr_select_usersids_in_build_search_query', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook
        $result = $SQL->build($query);

        while ($row=$SQL->fetch_array($result))
        {
            $usernamee .= ($usernamee != '' ? ' OR ' : '') . 'f.user=' . $row['id'];
        }

        $SQL->freeresult($result);

        if (! empty($usernamee))
        {
            $usernamee = 'AND (' . $usernamee . ')';
        }
    }

    //build query
    $file_namee       = $search['filename'] != '' ? 'AND (f.real_filename LIKE \'%' . $SQL->escape($search['filename']) . '%\' OR f.name LIKE \'%' . $SQL->escape($search['filename']) . '%\')' : '';
    $size_than        = ' f.size ' . ($search['than'] != 1 ? '<=' : '>=') . (intval($search['size']) * 1024) . ' ';
    $ups_than         = $search['ups']      != '' ? 'AND f.uploads ' . ($search['uthan']!=1 ? '<' : '>') . intval($search['ups']) . ' ' : '';
    $rep_than         = $search['rep']      != '' ? 'AND f.report ' . ($search['rthan']!=1 ? '<' : '>') . intval($search['rep']) . ' ' : '';
    $lstd_than        = $search['lastdown'] != '' ? 'AND f.last_down =' . (time()-(intval($search['lastdown']) * (24 * 60 * 60))) . ' ' : '';
    $exte             = $search['ext']      != '' ? "AND f.type IN ('" . implode("', '", @explode(',', $SQL->escape($search['ext']))) . "')" : '';
    $ipp              = $search['user_ip']  != '' ? 'AND f.user_ip LIKE \'%' . $SQL->escape($search['user_ip']) . '%\' ' : '';


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

    $query    = [
        'SELECT'       => 'MIN(f.id) as min_file_id, MAX(f.id) as max_file_id',
        'FROM'         => "{$dbprefix}files f",
    ];

    //!files == images
    $img_types      = ['gif','jpg','png','bmp','jpeg','GIF','JPG','PNG','BMP','JPEG'];
    $query['WHERE'] = 'f.type' . ($files  ? ' NOT' : '') . " IN ('" . implode("', '", $img_types) . "')";

    $result       = $SQL->build($query);
    $v            = $SQL->fetch($result);
    $SQL->freeresult($result);

    //if no data, turn them to number
    $min_id = (int) $v['min_file_id'];
    //    $max_id = (int) $v['max_file_id'];

    //every time batch
    $batch_size = 1500;

    //no start? start = min
    $first_loop    = ! $start ? true : false;
    $start         = ! $start ? $min_id : $start;
    $end           = $start + $batch_size;

    //now lets get this step's files number
    unset($v, $result);

    $query['SELECT'] = 'COUNT(f.id) as num_files';
    $query['WHERE'] .= ' AND f.id BETWEEN ' . $start . ' AND ' . $end;

    $result       = $SQL->build($query);
    $v            = $SQL->fetch($result);
    $SQL->freeresult($result);

    $this_step_count = $v['num_files'];

    if ($this_step_count == 0)
    {
        return false;
    }

    //update stats table

    $update_query = [
        'UPDATE'    => "{$dbprefix}stats"
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
        'SELECT'       => 's.' . $name,
        'FROM'         => "{$dbprefix}stats s"
    ];

    $result       = $SQL->build($query);
    $v            = $SQL->fetch($result);

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
