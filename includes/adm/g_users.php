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


//for style ..
$stylee               = 'admin_users';
$current_smt          = preg_replace('/[^a-z0-9_]/i', '', g('smt', 'str', 'general'));

$action            = basename(ADMIN_PATH) . '?cp=' . basename(__file__, '.php') . (ig('page')  ? '&amp;page=' . g('page', 'int') : '');
$action            .= (ig('search_id') ? '&amp;search_id=' . g('search') : '');
$action            .= (ig('qg') ? '&amp;qg=' . g('qg', 'int') : '') . '&amp;smt=' . $current_smt;
$action_all        = basename(ADMIN_PATH) . '?cp=' . basename(__file__, '.php') . '&amp;smt=' . $current_smt . (ig('page') ? '&amp;page=' . g('page', 'int') : '');

$cp_users_url = basename(ADMIN_PATH) . '?cp=' . basename(__file__, '.php');

//if not normal user system
$user_not_normal    = (int) $config['user_system'] != 1 ?  true : false;
$is_search          = $affected       = false;
$GET_FORM_KEY       = kleeja_add_form_key_get('adm_users');
$H_FORM_KEYS        = kleeja_add_form_key('adm_users');
$H_FORM_KEYS2       = kleeja_add_form_key('adm_users_newuser');
$H_FORM_KEYS3       = kleeja_add_form_key('adm_users_newgroup');
$H_FORM_KEYS4       = kleeja_add_form_key('adm_users_delgroup');
$H_FORM_KEYS5       = kleeja_add_form_key('adm_users_editacl');
$H_FORM_KEYS6       = kleeja_add_form_key('adm_users_editdata');
$H_FORM_KEYS7       = kleeja_add_form_key('adm_users_editexts');
$H_FORM_KEYS8       = kleeja_add_form_key('adm_users_edituser');

//
// Check form key
//
if (ip('submit'))
{
    if (! kleeja_check_form_key('adm_users'))
    {
        kleeja_admin_err($lang['INVALID_FORM_KEY'], true, $lang['ERROR'], true, $action, 1);
    }
}

if (ip('newuser'))
{
    if (! kleeja_check_form_key('adm_users_newuser'))
    {
        kleeja_admin_err($lang['INVALID_FORM_KEY'], true, $lang['ERROR'], true, $action, 1);
    }
}

if (ip('edituser'))
{
    if (! kleeja_check_form_key('adm_users_edituser'))
    {
        kleeja_admin_err($lang['INVALID_FORM_KEY'], true, $lang['ERROR'], true, $action . '&uid=' . p('uid', 'int'), 1);
    }
}

if (ip('delgroup'))
{
    if (! kleeja_check_form_key('adm_users_delgroup'))
    {
        kleeja_admin_err($lang['INVALID_FORM_KEY'], true, $lang['ERROR'], true, $action, 1);
    }
}

if (ip('newgroup'))
{
    if (! kleeja_check_form_key('adm_users_newgroup'))
    {
        kleeja_admin_err($lang['INVALID_FORM_KEY'], true, $lang['ERROR'], true, $action, 1);
    }
}

if (ip('editacl'))
{
    if (! kleeja_check_form_key('adm_users_editacl'))
    {
        kleeja_admin_err($lang['INVALID_FORM_KEY'], true, $lang['ERROR'], true, $action, 1);
    }
}

if (ip('editdata'))
{
    if (! kleeja_check_form_key('adm_users_editdata'))
    {
        kleeja_admin_err($lang['INVALID_FORM_KEY'], true, $lang['ERROR'], true, $action, 1);
    }
}

if (ip('newext') or ip('editexts'))
{
    if (! kleeja_check_form_key('adm_users_editexts'))
    {
        kleeja_admin_err($lang['INVALID_FORM_KEY'], true, $lang['ERROR'], true, $action, 1);
    }
}


//
//delete all user files [only one user]
//
if (ig('deleteuserfile'))
{
    //check _GET Csrf token
    if (! kleeja_check_form_key_get('adm_users'))
    {
        kleeja_admin_err($lang['INVALID_GET_KEY'], true, $lang['ERROR'], true, $action_all, 2);
    }

    //is exists ?
    if (! $SQL->num_rows($SQL->query("SELECT * FROM {$dbprefix}users WHERE id=" . g('deleteuserfile', 'int'))))
    {
        redirect($action_all);
    }

    $query = [
        'SELECT'       => 'size, name, folder',
        'FROM'         => "{$dbprefix}files",
        'WHERE'        => 'user=' . g('deleteuserfile', 'int'),
    ];

    $result = $SQL->build($query);

    $sizes = $num = 0;
    while ($row=$SQL->fetch_array($result))
    {
        //delete from folder ..
        kleeja_unlink(PATH . $row['folder'] . '/' . $row['name']);
        //delete thumb
        if (file_exists(PATH . $row['folder'] . '/thumbs/' . $row['name']))
        {
            kleeja_unlink(PATH . $row['folder'] . '/thumbs/' . $row['name']);
        }

        $num++;
        $sizes += $row['size'];
    }

    $SQL->freeresult($result);

    if ($num == 0)
    {
        kleeja_admin_err($lang['ADMIN_DELETE_NO_FILE'], true, '', true, $action_all, 2);
    }
    else
    {
        //update number of stats
        $update_query    = [
            'UPDATE'       => "{$dbprefix}stats",
            'SET'          => "sizes=sizes-$sizes, files=files-$num",
        ];

        $SQL->build($update_query);

        if ($SQL->affected())
        {
            delete_cache('data_stats');
        }

        //delete all files in just one query
        $d_query    = [
            'DELETE'       => "{$dbprefix}files",
            'WHERE'        => 'user=' . g('deleteuserfile', 'int'),
        ];

        $SQL->build($d_query);

        kleeja_admin_info($lang['ADMIN_DELETE_FILE_OK'], true, '', true, $action_all, 3);
    }
}
//
//Delete a user
//
if (ig('del_user'))
{
    //check _GET Csrf token
    if (! kleeja_check_form_key_get('adm_users'))
    {
        kleeja_admin_err($lang['INVALID_GET_KEY'], true, $lang['ERROR'], true, $action_all, 2);
    }

    //is exists ?
    if (! $SQL->num_rows($SQL->query("SELECT * FROM {$dbprefix}users WHERE id=" . g('del_user', 'int'))))
    {
        redirect($action_all);
    }

    //delete all files in just one query
    $d_query    = [
        'DELETE'       => "{$dbprefix}users",
        'WHERE'        => 'id=' . g('del_user', 'int'),
    ];

    $SQL->build($d_query);

    kleeja_admin_info($lang['USER_DELETED'], true, '', true, './');
}


//
//add new user
//
elseif (ip('newuser'))
{
    if (trim(p('lname')) == '' || trim(p('lpass')) == '' || trim(p('lmail')) == '')
    {
        $ERRORS[] = $lang['EMPTY_FIELDS'];
    }
    elseif (! preg_match("/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,4})$/i", trim(strtolower(p('lmail')))))
    {
        $ERRORS[] = $lang['WRONG_EMAIL'];
    }
    elseif (strlen(trim(p('lname'))) < 2 || strlen(trim(p('lname'))) > 25)
    {
        $ERRORS[] = str_replace('4', '2', $lang['WRONG_NAME']);
    }
    elseif ($SQL->num_rows($SQL->query("SELECT * FROM {$dbprefix}users WHERE clean_name='" . trim($SQL->escape($usrcp->cleanusername(p('lname')))) . "'")) != 0)
    {
        $ERRORS[] = $lang['EXIST_NAME'];
    }
    elseif ($SQL->num_rows($SQL->query("SELECT * FROM {$dbprefix}users WHERE mail='" . trim($SQL->escape(strtolower(p('lmail')))) . "'")) != 0)
    {
        $ERRORS[] = $lang['EXIST_EMAIL'];
    }

    //no errors, lets do process
    if (empty($ERRORS))
    {
        $name                 = (string) $SQL->escape(trim(p('lname')));
        $user_salt            = (string) substr(kleeja_base64_encode(pack('H*', sha1(mt_rand()))), 0, 7);
        $pass                 = (string) $usrcp->kleeja_hash_password($SQL->escape(trim(p('lpass'))) . $user_salt);
        $mail                 = (string) trim(strtolower(p('lmail')));
        $clean_name           = (string) $usrcp->cleanusername($name);
        $group                = (int) p('lgroup');

        $insert_query    = [
            'INSERT'       => 'name ,password, password_salt ,group_id, mail,founder, session_id, clean_name',
            'INTO'         => "{$dbprefix}users",
            'VALUES'       => "'$name', '$pass', '$user_salt', $group , '$mail', 0 , '', '$clean_name'"
        ];

        if ($SQL->build($insert_query))
        {
            $last_user_id = $SQL->insert_id();

            //update number of stats
            $update_query    = [
                'UPDATE'       => "{$dbprefix}stats",
                'SET'          => "users=users+1, lastuser='$name'",
            ];

            $SQL->build($update_query);

            if ($SQL->affected())
            {
                delete_cache('data_stats');
            }
        }

        //User added ..
        kleeja_admin_info($lang['USER_ADDED'], true, '', true, basename(ADMIN_PATH) . '?cp=g_users', 3);
    }
    else
    {
        $errs =    '';

        foreach ($ERRORS as $r)
        {
            $errs .= '- ' . $r . '. <br />';
        }

        $current_smt = 'new_u';
        //kleeja_admin_err($errs, true, '', true, $action_all, 3);
    }
}

//
//edit user
//
if (ip('edituser'))
{
    $userid = p('uid', 'int');


    //is exists ?
    if (! $SQL->num_rows($SQL->query("SELECT id FROM {$dbprefix}users WHERE id=" . $userid)))
    {
        kleeja_admin_err('ERROR-NO-ID', true, '', true, basename(ADMIN_PATH) . '?cp=' . basename(__file__, '.php'));
    }

    $query = [
        'SELECT'       => 'name, mail, clean_name, group_id, founder, show_my_filecp',
        'FROM'         => "{$dbprefix}users",
        'WHERE'        => 'id=' . $userid,
    ];

    $result = $SQL->build($query);
    $udata  = $SQL->fetch_array($result);
    $SQL->freeresult($result);

    $new_clean_name = trim($SQL->escape($usrcp->cleanusername(p('l_name'))));

    $new_name = $new_mail = false;
    $pass     = '';

    if (trim(p('l_name')) == '')
    {
        $ERRORS[] = $lang['EMPTY_FIELDS'] . ' (' . $lang['USERNAME'] . ')';
    }
    elseif (trim(p('l_mail')) == '')
    {
        $ERRORS[] = $lang['EMPTY_FIELDS'] . ' (' . $lang['EMAIL'] . ')';
    }
    elseif (! preg_match("/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,4})$/i", trim(strtolower(p('l_mail')))))
    {
        $ERRORS[] = $lang['WRONG_EMAIL'];
    }
    elseif ($udata['clean_name'] != $new_clean_name)
    {
        $new_name = true;

        if (strlen(trim(p('l_name'))) < 2 || strlen(trim(p('l_name'))) > 100)
        {
            $ERRORS[] = str_replace('4', '2', $lang['WRONG_NAME']);
        }
        elseif ($SQL->num_rows($SQL->query("SELECT * FROM {$dbprefix}users WHERE clean_name='" . $new_clean_name . "'")) != 0)
        {
            $ERRORS[] = $lang['EXIST_NAME'];
        }
    }
    elseif ($udata['mail'] != trim(p('l_mail')))
    {
        $new_mail = true;

        if ($SQL->num_rows($SQL->query("SELECT * FROM {$dbprefix}users WHERE mail='" . trim($SQL->escape(strtolower(p('lmail')))) . "'")) != 0)
        {
            $ERRORS[] = $lang['EXIST_EMAIL'];
        }
    }
    elseif (trim(p('l_pass')) != '')
    {
        $user_salt       = substr(kleeja_base64_encode(pack('H*', sha1(mt_rand()))), 0, 7);
        $pass            = "password = '" . $usrcp->kleeja_hash_password(trim(p('l_pass')) . $user_salt) . "', password_salt='" . $user_salt . "',";
    }

    //no errors, lets do process
    if (empty($ERRORS))
    {
        $update_query    = [
            'UPDATE'       => "{$dbprefix}users",
            'SET'          => ($new_name ? "name = '" . $SQL->escape(p('l_name')) . "', clean_name='" . $SQL->escape($new_clean_name) . "', " : '') .
                            ($new_mail ? "mail = '" . $SQL->escape(p('l_mail')) . "'," : '') .
                            $pass .
                            (ip('l_founder') ? 'founder=' . p('l_founder', 'int') . ',' : '') .
                            'group_id=' . p('l_group', 'int') . ',' .
                            'show_my_filecp=' . p('l_show_filecp', 'int'),
            'WHERE'        => 'id=' . $userid
        ];

        $SQL->build($update_query);

        if ($SQL->affected())
        {
            kleeja_admin_info($lang['USER_UPDATED'], true, '', true, basename(ADMIN_PATH) . '?cp=g_users&smt=show_group&qg=' . p('l_qg', 'int') . '&page=' . p('l_page', 'int'), 2);
        }
        else
        {
            kleeja_admin_info($lang['NO_UP_CHANGE_S'], true, '', true, basename(ADMIN_PATH) . '?cp=g_users&smt=show_group&qg=' . p('l_qg', 'int') . '&page=' . p('l_page', 'int'), 2);
        }
    }
    else
    {
        $errs =    '';

        foreach ($ERRORS as $r)
        {
            $errs .= '- ' . $r . '. <br />';
        }

        $current_smt  = 'edit_user';
        $_GET['uid']  = $userid;
        $_GET['page'] = p('l_page');
        //kleeja_admin_err($errs, true, '', true, $action_all, 3);
    }
}


//
//add new group
//
if (ip('newgroup'))
{
    if (trim(p('gname')) == '' || trim(p('gname')) == '' || trim(p('gname')) == '')
    {
        $ERRORS[] = $lang['EMPTY_FIELDS'];
    }
    elseif (strlen(trim(p('gname'))) < 2 || strlen(trim(p('gname'))) > 100)
    {
        $ERRORS[] = str_replace('4', '1', $lang['WRONG_NAME']);
    }
    elseif ($SQL->num_rows($SQL->query("SELECT * FROM {$dbprefix}groups WHERE group_name='" . trim($SQL->escape(p('gname'))) . "'")) != 0)
    {
        $ERRORS[] = $lang['EXIST_NAME'];
    }
    elseif (in_array(trim(p('gname')), [$lang['ADMINS'], $lang['GUESTS'], $lang['USERS']]))
    {
        $ERRORS[] = $lang['TAKEN_NAMES'];
    }

    //no errors, lets do process
    if (empty($ERRORS))
    {
        //Insert the group ..
        $insert_query    = [
            'INSERT'       => 'group_name',
            'INTO'         => "{$dbprefix}groups",
            'VALUES'       => "'" . trim($SQL->escape(p('gname'))) . "'"
        ];

        $SQL->build($insert_query);
        //Then, get the ID
        $new_group_id = $SQL->insert_id();
        $org_group_id = p('cfrom', 'int');

        if (! $new_group_id or ! $org_group_id)
        {
            kleeja_admin_err('ERROR-NO-ID', true, '', true, basename(ADMIN_PATH) . '?cp=' . basename(__file__, '.php'));
        }

        if ($org_group_id == -1)
        {
            $org_group_id = (int) $config['default_group'];
        }

        //copy acls from the other group to this group
        $query = [
            'SELECT'         => 'acl_name, acl_can',
            'FROM'           => "{$dbprefix}groups_acl",
            'WHERE'          => 'group_id=' . $org_group_id,
            'ORDER BY'       => 'acl_name ASC'
        ];
        $result = $SQL->build($query);

        while ($row=$SQL->fetch_array($result))
        {
            $insert_query    = [
                'INSERT'       => 'acl_name, acl_can, group_id',
                'INTO'         => "{$dbprefix}groups_acl",
                'VALUES'       => "'" . $row['acl_name'] . "', " . $row['acl_can'] . ', ' . $new_group_id
            ];
            $SQL->build($insert_query);
        }
        $SQL->free($result);

        //copy configs from the other group to this group
        $query = [
            'SELECT'         => 'd.name, d.value',
            'FROM'           => "{$dbprefix}groups_data d",
            'WHERE'          => 'd.group_id=' . $org_group_id,
            'ORDER BY'       => 'd.name ASC'
        ];
        $result = $SQL->build($query);

        while ($row=$SQL->fetch_array($result))
        {
            $insert_query    = [
                'INSERT'       => 'name, value, group_id',
                'INTO'         => "{$dbprefix}groups_data",
                'VALUES'       => "'" . $row['name'] . "', '" . $SQL->escape($row['value']) . "', " . $new_group_id
            ];
            $SQL->build($insert_query);
        }
        $SQL->free($result);

        //copy exts from the other group to this group
        $query = [
            'SELECT'         => 'e.ext, e.size',
            'FROM'           => "{$dbprefix}groups_exts e",
            'WHERE'          => 'e.group_id=' . $org_group_id,
            'ORDER BY'       => 'e.ext_id ASC'
        ];
        $result = $SQL->build($query);

        while ($row=$SQL->fetch_array($result))
        {
            $insert_query    = [
                'INSERT'       => 'ext, size, group_id',
                'INTO'         => "{$dbprefix}groups_exts",
                'VALUES'       => "'" . $row['ext'] . "', " . $row['size'] . ', ' . $new_group_id
            ];
            $SQL->build($insert_query);
        }
        $SQL->free($result);

        //show group-is-added message
        delete_cache('data_groups');
        kleeja_admin_info(sprintf($lang['GROUP_ADDED'], p('gname')), true, '', true, basename(ADMIN_PATH) . '?cp=g_users');
    }
    else
    {
        $errs =    '';

        foreach ($ERRORS as $r)
        {
            $errs .= '- ' . $r . '. <br />';
        }

        kleeja_admin_err($errs, true, '', true, $action, 3);
    }
}

//
//delete group
//
if (ip('delgroup'))
{
    $from_group = ip('dgroup') ? p('dgroup', 'int') : 0;
    $to_group   = ip('tgroup') ? p('tgroup', 'int') : 0;

    //if missing IDs of groups, deleted one and transfering-to one.
    if (! $from_group or ! $to_group)
    {
        kleeja_admin_err('ERROR-NO-ID', true, '', true, basename(ADMIN_PATH) . '?cp=g_users');
    }

    //We can not move users to the same group we deleting ! that's stupid pro!
    if ($from_group  == $to_group)
    {
        kleeja_admin_err($lang['NO_MOVE_SAME_GRP'], true, '', true, basename(ADMIN_PATH) . '?cp=g_users');
    }

    //to_group = '-1' : means default group .. so now we get the real ID.
    if ($to_group == -1)
    {
        $to_group = (int) $config['default_group'];
    }

    //you can not delete default group !
    if ($from_group == (int) $config['default_group'])
    {
        kleeja_admin_err($lang['DEFAULT_GRP_NO_DEL'], true, '', true, basename(ADMIN_PATH) . '?cp=g_users');
    }

    //delete the exts
    $query_del    = [
        'DELETE'       => "{$dbprefix}groups_exts",
        'WHERE'        => 'group_id=' . $from_group
    ];

    $SQL->build($query_del);
    //then, delete the configs
    $query_del    = [
        'DELETE'       => "{$dbprefix}groups_data",
        'WHERE'        => 'group_id=' . $from_group
    ];

    $SQL->build($query_del);
    //then, delete acls
    $query_del    = [
        'DELETE'       => "{$dbprefix}groups_acl",
        'WHERE'        => 'group_id=' . $from_group
    ];

    $SQL->build($query_del);
    //then, delete the group itself
    $query_del    = [
        'DELETE'       => "{$dbprefix}groups",
        'WHERE'        => 'group_id=' . $from_group
    ];

    $SQL->build($query_del);
    //then, move users to the dest. group
    $update_query = [
        'UPDATE'       => "{$dbprefix}users",
        'SET'          => 'group_id=' . $to_group,
        'WHERE'        => 'group_id=' . $from_group
    ];

    $SQL->build($update_query);

    //get those groups name
    $group_name_from    = str_replace(['{lang.ADMINS}', '{lang.USERS}', '{lang.GUESTS}'],
                                    [$lang['ADMINS'], $lang['USERS'], $lang['GUESTS']],
                                    $d_groups[$from_group]['data']['group_name']);
    $group_name_to        =str_replace(['{lang.ADMINS}', '{lang.USERS}', '{lang.GUESTS}'],
                                [$lang['ADMINS'], $lang['USERS'], $lang['GUESTS']],
                                $d_groups[$to_group]['data']['group_name']);

    //delete cache ..
    delete_cache('data_groups');
    kleeja_admin_info(sprintf($lang['GROUP_DELETED'], $group_name_from, $group_name_to), true, '', true, basename(ADMIN_PATH) . '?cp=g_users');
}

//
//begin of default users page
//
$query        = [];
$show_results = false;
switch ($current_smt):

case 'general':

    $query = [
        'SELECT'         => 'COUNT(group_id) AS total_groups',
        'FROM'           => "{$dbprefix}groups",
        'ORDER BY'       => 'group_id ASC'
    ];

    $result = $SQL->build($query);

    $nums_rows     = 0;
    $n_fetch       = $SQL->fetch_array($result);
    $nums_rows     = $n_fetch['total_groups'];
    $no_results    = false;
    $e_groups      = $c_groups   = [];
    $l_groups      = [];

    $groups_background_color = [
        1 => ['background' => 'dark',  'icon' => ' fa-star'],
        2 => ['background' => 'secondary', 'icon' => 'fa-user-secret'],
        3 => ['background' => 'primary', 'icon' => 'fa-user-circle'],
    ];



    if ($nums_rows > 0)
    {
        $query['SELECT'] =    'group_id, group_name, group_is_default, group_is_essential';

        $result = $SQL->build($query);

        while ($row=$SQL->fetch_array($result))
        {
            $r = [
                'id'      => $row['group_id'],
                'name'    => str_replace(['{lang.ADMINS}', '{lang.USERS}', '{lang.GUESTS}'],
                            [$lang['ADMINS'], $lang['USERS'], $lang['GUESTS']],
                            $row['group_name']),
                'style' => ! empty($groups_background_color[$row['group_id']])
                                    ? $groups_background_color[$row['group_id']]
                                    : ['background' => 'secondary', 'icon' => ''],
                'is_default'    => (int) $row['group_is_default'] ? true : false
            ];

            if ((int) $row['group_is_essential'] == 1)
            {
                $e_groups[] = $r;
            }
            else
            {
                $c_groups[] = $r;
            }
        }
    }

    if ($user_not_normal)
    {
        $c_groups = false;
    }

    $SQL->freeresult($result);

break;

//handling editing ACLs(permissions) for the requesting groups
case 'group_acl':
    $req_group = ig('qg') ? g('qg', 'int') : 0;

    if (! $req_group)
    {
        kleeja_admin_err('ERROR-NO-ID', true, '', true, basename(ADMIN_PATH) . '?cp=g_users');
    }

    $group_name    = str_replace(['{lang.ADMINS}', '{lang.USERS}', '{lang.GUESTS}'],
                                [$lang['ADMINS'], $lang['USERS'], $lang['GUESTS']],
                                $d_groups[$req_group]['data']['group_name']);

    $query = [
        'SELECT'         => 'acl_name, acl_can',
        'FROM'           => "{$dbprefix}groups_acl",
        'WHERE'          => 'group_id=' . $req_group,
        'ORDER BY'       => 'acl_name ASC'
    ];

    $result = $SQL->build($query);

    $acls = $submitted_on_acls = $submitted_off_acls = [];
    while ($row=$SQL->fetch_array($result))
    {
        //if submit
        if (ip('editacl'))
        {
            if (ip($row['acl_name']))
            {
                $submitted_on_acls[] = $row['acl_name'];
            }
            elseif (! ip($row['acl_name']))
            {
                $submitted_off_acls[] = $row['acl_name'];
            }
        }

        if ($req_group == 2 && in_array($row['acl_name'], ['access_fileuser', 'enter_acp']))
        {
            continue;
        }

        $acls[] = [
            'acl_title'    => ! empty($lang['ACLS_' . strtoupper($row['acl_name'])]) ? $lang['ACLS_' . strtoupper($row['acl_name'])] : $olang['ACLS_' . strtoupper($row['acl_name'])],
            'acl_name'     => $row['acl_name'],
            'acl_can'      => (int) $row['acl_can']
        ];
    }
    $SQL->freeresult($result);

    //if submit
    if (ip('editacl'))
    {
        //update 'can' acls
        if (sizeof($submitted_on_acls))
        {
            $update_query = [
                'UPDATE'       => "{$dbprefix}groups_acl",
                'SET'          => 'acl_can=1',
                'WHERE'        => "acl_name IN ('" . implode("', '", $submitted_on_acls) . "') AND group_id=" . $req_group
            ];

            $SQL->build($update_query);
        }

        //update 'can not' acls
        if (sizeof($submitted_off_acls))
        {
            $update_query2 = [
                'UPDATE'       => "{$dbprefix}groups_acl",
                'SET'          => 'acl_can=0',
                'WHERE'        => "acl_name IN ('" . implode("', '", $submitted_off_acls) . "') AND group_id=" . $req_group
            ];

            $SQL->build($update_query2);
        }

        //delete cache ..
        delete_cache('data_groups');
        kleeja_admin_info($lang['CONFIGS_UPDATED'], true, '', true, basename(ADMIN_PATH) . '?cp=g_users');
    }

break;

//handling editing settings for the requested group
case 'group_data':
    $req_group = ig('qg') ? g('qg', 'int') : 0;

    if (! $req_group)
    {
        kleeja_admin_err('ERROR-NO-ID', true, '', true, basename(ADMIN_PATH) . '?cp=g_users');
    }


    // When user change language from start page, hurry hurry section, he comes here
    if (ig('lang_change'))
    {
        //check _GET Csrf token
        if (! kleeja_check_form_key_get('adm_start_actions'))
        {
            kleeja_admin_err($lang['INVALID_GET_KEY'], true, $lang['ERROR'], true, basename(ADMIN_PATH) . '?cp=start', 2);
        }

        $got_lang = preg_replace('[^a-zA-Z0-9]', '', g('lang_change'));

        // -1 means all
        if ($req_group == -1)
        {
            //general
            update_config('language', $got_lang);

            //all groups
            foreach ($d_groups as $group_id => $group_info)
            {
                update_config('language', $got_lang, true, $group_id);
            }

            $group_name = $lang['ALL'];
        }
        else
        {
            update_config('language', $got_lang, true, $req_group);
            $group_name    = str_replace(
                            ['{lang.ADMINS}', '{lang.USERS}', '{lang.GUESTS}'],
                            [$lang['ADMINS'], $lang['USERS'], $lang['GUESTS']],
                            $d_groups[$req_group]['data']['group_name']
                            );
        }


        delete_cache('data_lang' . $got_lang);


        //msg, done
        kleeja_admin_info($lang['CONFIGS_UPDATED'] . ', ' . $lang['LANGUAGE'] . ':' . $got_lang . ' - ' . $lang['FOR'] . ':' . $group_name,
                true, '', true, basename(ADMIN_PATH) . '?cp=start');
    }


    $group_name    = str_replace(['{lang.ADMINS}', '{lang.USERS}', '{lang.GUESTS}'],
                                    [$lang['ADMINS'], $lang['USERS'], $lang['GUESTS']],
                                    $d_groups[$req_group]['data']['group_name']);
    $gdata        = $d_groups[$req_group]['data'];

    $query = [
        'SELECT'         => 'c.name, c.option',
        'FROM'           => "{$dbprefix}config c",
        'WHERE'          => "c.type='groups'",
        'ORDER BY'       => 'c.display_order ASC'
    ];

    $result = $SQL->build($query);

    $data          = [];
    $cdata         = $d_groups[$req_group]['configs'];
    $STAMP_IMG_URL = file_exists(PATH . 'images/watermark.gif') ? PATH . 'images/watermark.gif' : PATH . 'images/watermark.png';

    while ($row=$SQL->fetch_array($result))
    {
        //submit, why here ? dont ask me just accept it as it.
        if (ip('editdata'))
        {
            is_array($plugin_run_result = Plugins::getInstance()->run('after_submit_adm_users_groupdata', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook

            $new[$row['name']] = p($row['name'], 'str', $row['value']);

            $update_query = [
                'UPDATE'       => "{$dbprefix}groups_data",
                'SET'          => "value='" . $SQL->escape($new[$row['name']]) . "'",
                'WHERE'        => "name='" . $row['name'] . "' AND group_id=" . $req_group
            ];

            $SQL->build($update_query);

            continue;
        }

        if ($row['name'] == 'language')
        {
            //get languages
            if ($dh = @opendir(PATH . 'lang'))
            {
                while (($file = readdir($dh)) !== false)
                {
                    if (strpos($file, '.') === false && $file != '..' && $file != '.')
                    {
                        $lngfiles .= '<option ' . ($d_groups[$req_group]['configs']['language'] == $file ? 'selected="selected"' : '') . ' value="' . $file . '">' . $file . '</option>' . "\n";
                    }
                }
                @closedir($dh);
            }
        }

        if ($req_group == 2 && in_array($row['name'], ['enable_userfile']))
        {
            continue;
        }

        $data[] = [
            'option'         =>
            str_replace(
                ['<input ', '<select ', '<td>', '</td>', '<label>', '<tr>', '</tr>'],
                ['<input class="form-control" ', '<select class="form-control" ', '<div class="form-group">', '</div>', '<label class="form-check-label">', '', ''],
                '<div class="form-group">' . "\n" .
                            '<label for="' . $row['name'] . '">' . (! empty($lang[strtoupper($row['name'])]) ? $lang[strtoupper($row['name'])] : $olang[strtoupper($row['name'])]) . '</label>' . "\n" .
                            '<div class="box">' . (empty($row['option']) ? '' : $tpl->admindisplayoption(preg_replace(['!{con.[a-z0-9_]+}!', '!NAME="con.!'], ['{cdata.' . $row['name'] . '}', 'NAME="cdata.'], $row['option']))) . '</div>' . "\n" .
                            '</div>' . "\n" . '<div class="clearfix"></div>')

        ];
    }
    $SQL->freeresult($result);

    //submit
    if (ip('editdata'))
    {
        //Remove group_is_default from the current one
        if (p('group_is_default', 'int') == 1)
        {
            $update_query = [
                'UPDATE'       => "{$dbprefix}groups",
                'SET'          => 'group_is_default=0',
                'WHERE'        => 'group_is_default=1'
            ];
            $SQL->build($update_query);

            //update config value of the current default group
            update_config('default_group', $req_group);
            delete_cache('data_config');
        }

        //update not-configs data
        $update_query = [
            'UPDATE'       => "{$dbprefix}groups",
            'SET'          => 'group_is_default=' . p('group_is_default', 'int') . (ip('group_name') ? ", group_name='" . $SQL->escape(p('group_name')) . "'" : ''),
            'WHERE'        => 'group_id=' . $req_group
        ];
        $SQL->build($update_query);

        //delete cache ..
        delete_cache('data_groups');
        kleeja_admin_info($lang['CONFIGS_UPDATED'], true, '', true, basename(ADMIN_PATH) . '?cp=g_users');
    }

break;

//handling adding-editing allowed file extensions for requested group
case 'group_exts':
    $req_group = ig('qg') ? g('qg', 'int') : 0;

    if (! $req_group)
    {
        kleeja_admin_err('ERROR-NO-ID', true, '', true, basename(ADMIN_PATH) . '?cp=' . basename(__file__, '.php'));
    }

    $group_name    =str_replace(['{lang.ADMINS}', '{lang.USERS}', '{lang.GUESTS}'],
                        [$lang['ADMINS'], $lang['USERS'], $lang['GUESTS']],
                        $d_groups[$req_group]['data']['group_name']);


    //check if there is klj_exts which means this is an upgraded website !
    if (empty($config['exts_upraded1_5']))
    {
        $ex_exts = $SQL->query("SHOW TABLES LIKE '{$dbprefix}exts';");

        if ($SQL->num_rows($ex_exts))
        {
            $xquery = [
                'SELECT'       => 'ext, gust_size, user_size, gust_allow, user_allow',
                'FROM'         => "{$dbprefix}exts",
                'WHERE'        => 'gust_allow=1 OR user_allow=1',
            ];

            $xresult = $SQL->build($xquery);

            $xexts = '';
            while ($row=$SQL->fetch_array($xresult))
            {
                if ($row['gust_allow'])
                {
                    $xexts .= ($xexts == '' ? '' : ',') . "('" . $SQL->escape($row['ext']) . "', 2, " . $row['gust_size'] . ')';
                }

                if ($row['user_allow'])
                {
                    $xexts .= ($xexts == '' ? '' : ',') . "('" . $SQL->escape($row['ext']) . "', 3, " . $row['user_size'] . ')';
                }
            }

            $SQL->freeresult($result);

            //delete prev exts before adding
            $query_del    = [
                'DELETE'       => "{$dbprefix}groups_exts",
                'WHERE'        => 'group_id=2 OR group_id=3'
            ];

            $SQL->build($query_del);

            $SQL->query("INSERT INTO {$dbprefix}groups_exts (ext, group_id, size) VALUES " . $xexts . ';');

            add_config('exts_upraded1_5', 'done');
        }
    }

    //delete ext?
    $DELETED_EXT = $GE_INFO =  false;

    if (ig('del'))
    {
        //check _GET Csrf token
        if (! kleeja_check_form_key_get('adm_users'))
        {
            kleeja_admin_err($lang['INVALID_GET_KEY'], true, $lang['ERROR'], true, $action, 2);
        }

        $req_ext = ig('del') ? g('del', 'int') : 0;

        if (! $req_ext)
        {
            kleeja_admin_err('ERROR-NO-EXT-ID', true, '', true, $action, 2);
        }

        $query_del    = [
            'DELETE'       => "{$dbprefix}groups_exts",
            'WHERE'        => 'ext_id=' . $req_ext
        ];

        $SQL->build($query_del);

        //done
        $DELETED_EXT = $GE_INFO = 2;
        delete_cache('data_groups');
    }

    //add ext?
    $ADDED_EXT = false;

    if (ip('newext'))
    {
        $new_ext = ip('extisnew') ? preg_replace('/[^a-z0-9]/', '', strtolower(p('extisnew'))) : false;

        if (! $new_ext)
        {
            kleeja_admin_err($lang['EMPTY_EXT_FIELD'], true, '', true, basename(ADMIN_PATH) . '?cp=g_users&smt=group_exts&qg=' . $req_group);
        }

        //check if it's welcomed one
        //if he trying to be smart, he will add like ext1.ext2.php
        //so we will just look at last one
        $new_ext   = explode('.', $new_ext);
        $new_ext   = array_pop($new_ext);
        $check_ext = strtolower($new_ext);

        $not_welcomed_exts = ['php', 'php3', 'php5', 'php4', 'asp', 'aspx', 'shtml', 'html', 'htm', 'xhtml', 'phtml', 'pl', 'cgi', 'ini', 'htaccess', 'sql', 'txt'];

        if (in_array($check_ext, $not_welcomed_exts))
        {
            kleeja_admin_err(sprintf($lang['FORBID_EXT'], $check_ext), true, '', true, $action);
        }

        //check if there is any exists of this ext in db
        $query = [
            'SELECT'       => '*',
            'FROM'         => "{$dbprefix}groups_exts",
            'WHERE'        => "ext='" . $new_ext . "' and group_id=" . $req_group,
        ];

        $result = $SQL->build($query);

        if ($SQL->num_rows($result))
        {
            kleeja_admin_err(sprintf($lang['NEW_EXT_EXISTS_B4'], $new_ext), true, '', true, $action);
        }

        //add
        $default_size    = '2097152';//bytes
        $insert_query    = [
            'INSERT'       => 'ext ,group_id, size',
            'INTO'         => "{$dbprefix}groups_exts",
            'VALUES'       => "'$new_ext', $req_group, $default_size"
        ];

        $SQL->build($insert_query);

        //done
        $ADDED_EXT = $GE_INFO =  2;
        delete_cache('data_groups');
    }

    //if submit/update
    if (ip('editexts'))
    {
        $ext_ids = $_POST['size']; //is an array

        if (is_array($ext_ids))
        {
            foreach ($ext_ids as $e_id=>$e_val)
            {
                $update_query = [
                    'UPDATE'       => "{$dbprefix}groups_exts",
                    'SET'          => 'size=' . (intval($e_val)*1024),
                    'WHERE'        => 'ext_id=' . intval($e_id) . ' AND group_id=' . $req_group
                ];
                $SQL->build($update_query);
            }

            //delete cache ..
            delete_cache('data_groups');
            kleeja_admin_info($lang['UPDATED_EXTS'], true, '', true, $action);
        }
    }

    //show exts
    $query = [
        'SELECT'         => 'ext_id, ext, size',
        'FROM'           => "{$dbprefix}groups_exts",
        'WHERE'          => 'group_id=' . $req_group,
        'ORDER BY'       => 'ext_id ASC'
    ];

    $result = $SQL->build($query);

    $exts = [];
    while ($row=$SQL->fetch_array($result))
    {
        //handle big int
        $size = preg_match('/^[0-9]+/', $row['size'], $matches) ? $matches[0] : 0;

        $exts[] = [
            'ext_id'      => $row['ext_id'],
            'ext_name'    => $row['ext'],
            'ext_size'    => round($size / 1024),
            'ext_icon'    => file_exists(PATH . 'images/filetypes/' . $row['ext'] . '.png') ? PATH . 'images/filetypes/' . $row['ext'] . '.png' : PATH . 'images/filetypes/file.png'
        ];
    }
    $SQL->freeresult($result);


break;

//show users (from search keyword)
case 'show_su':

    $filter = get_filter(g('search_id'), 'user_search', false, 'filter_uid');

    if (! $filter)
    {
        kleeja_admin_err($lang['ERROR_TRY_AGAIN'], true, $lang['ERROR'], true, basename(ADMIN_PATH) . '?cp=h_search&smt=users', 1);
    }

    $search    = unserialize(htmlspecialchars_decode($filter['filter_value']));

    $usernamee     = $search['username'] != '' ? 'AND (name  LIKE \'%' . $SQL->escape($search['username']) . '%\' OR clean_name LIKE \'%' . $SQL->escape($search['username']) . '%\') ' : '';
    $usermailee    = $search['usermail'] != '' ? 'AND mail  LIKE \'%' . $SQL->escape($search['usermail']) . '%\' ' : '';
    $is_search     = true;

    $query['WHERE']    =    "name <> '' $usernamee $usermailee";

//show users (for requested group)
case 'show_group':
    if ($current_smt != 'show_su')
    {
        $is_search     = true;
        $req_group     = ig('qg') ? g('qg', 'int') : 0;
        $group_name    =str_replace(['{lang.ADMINS}', '{lang.USERS}', '{lang.GUESTS}'],
                        [$lang['ADMINS'], $lang['USERS'], $lang['GUESTS']],
                        $d_groups[$req_group]['data']['group_name']);

        $query['WHERE']    = "name != '' AND group_id =  " . $req_group;
    }

//show users (all)
case 'users':

    $query['SELECT']         = 'COUNT(id) AS total_users';
    $query['FROM']           = "{$dbprefix}users";
    $query['ORDER BY']       = 'id ASC';

    $result = $SQL->build($query);


    $nums_rows = 0;
    $n_fetch   = $SQL->fetch_array($result);
    $nums_rows = $n_fetch['total_users'];

    //pagination
    $currentPage          = ig('page') ? g('page', 'int') : 1;
    $Pager                = new Pagination($perpage, $nums_rows, $currentPage);
    $start                = $Pager->getStartRow();

    $no_results = false;

    if ($nums_rows > 0)
    {
        $query['SELECT']    =    'id, name, founder, group_id, last_visit';
        $query['LIMIT']     =    "$start, $perpage";

        $result = $SQL->build($query);

        while ($row=$SQL->fetch_array($result))
        {
            $userfile =  $config['siteurl'] . ($config['mod_writer'] ? 'fileuser-' . $row['id'] . '.html' : 'ucp.php?go=fileuser&amp;id=' . $row['id']);

            $arr[]    = [
                'id'                          => $row['id'],
                'name'                        => $row['name'],
                'userfile_link'               => $userfile,
                'delusrfile_link'             => $row['founder'] && (int) $userinfo['founder'] == 0 ? false : basename(ADMIN_PATH) . '?cp=' . basename(__file__, '.php') . '&amp;deleteuserfile=' . $row['id'] . (ig('page') ? '&amp;page=' . g('page', 'int') : ''),
                'delusr_link'                 => $userinfo['id'] == $row['id'] || ($row['founder'] && (int) $userinfo['founder'] == 0) ? false : basename(ADMIN_PATH) . '?cp=' . basename(__file__, '.php') . '&amp;del_user=' . $row['id'] . (ig('page') ? '&amp;page=' . g('page', 'int') : ''),
                'editusr_link'                => basename(ADMIN_PATH) . '?cp=' . basename(__file__, '.php') . '&amp;smt=edit_user&amp;uid=' . $row['id'] . (ig('page') ? '&amp;page=' . g('page', 'int') : ''),
                'founder'                     => (int) $row['founder'],
                'last_visit'                  => empty($row['last_visit']) ? $lang['NOT_YET'] : kleeja_date($row['last_visit']),
                'group'                       => str_replace(['{lang.ADMINS}', '{lang.USERS}', '{lang.GUESTS}'],
                                            [$lang['ADMINS'], $lang['USERS'], $lang['GUESTS']],
                                            $d_groups[$row['group_id']]['data']['group_name'])
            ];
        }

        $SQL->freeresult($result);
    }
    else
    { //num rows
        $no_results = true;
    }

    //pages
    $total_pages        = $Pager->getTotalPages();
    $page_nums          = $Pager->print_nums(
                                basename(ADMIN_PATH) . '?cp=' . basename(__file__, '.php') . (ig('search_id') ? '&search_id=' . g('search_id') : '')
                                . (ig('qg') ? '&qg=' . g('qg', 'int') : '') . (ig('smt') ? '&smt=' . $current_smt : '')
                            );

    $show_results = true;

break;

//editing a user, form
case 'edit_user':

    //is exists ?
    if (! isset($userid))
    {
        $userid = g('uid', 'int');

        if (! $SQL->num_rows($SQL->query("SELECT * FROM {$dbprefix}users WHERE id=" . $userid)))
        {
            kleeja_admin_err('ERROR-NO-USER-FOUND', true, '', true, basename(ADMIN_PATH) . '?cp=' . basename(__file__, '.php'));
        }
    }

    $query = [
        'SELECT'       => 'name, mail, group_id, founder, show_my_filecp',
        'FROM'         => "{$dbprefix}users",
        'WHERE'        => 'id=' . $userid,
    ];

    $result = $SQL->build($query);
    $udata  = $SQL->fetch_array($result);
    $SQL->freeresult($result);

    //If founder, just founder can edit him;
    $u_founder        = ip('l_founder') ? p('l_founder', 'int') : (int) $udata['founder'];
    $im_founder       = (int) $userinfo['founder'];
    $u_group          = ip('l_group') ? p('l_group', 'int') : $udata['group_id'];
    $u_qg             = ip('l_qg') ? p('u_qg', 'int') : $udata['group_id'];

    if ($u_founder && ! $im_founder)
    {
        kleeja_admin_err($lang['HV_NOT_PRVLG_ACCESS'], true, '', true, basename(ADMIN_PATH) . '?cp=g_users&smt=show_group&qg=' . $u_group);
    }

    $errs = isset($errs) ? $errs : false;
    //prepare them for the template
    $title_name    = $udata['name'];
    $u_name        = p('l_name', 'str', $udata['name']);
    $u_mail        = p('l_mail', 'str', $udata['mail']);

    $u_show_filecp = p('l_show_filecp', 'int', $udata['show_my_filecp']);

    $u_page = ig('page') ? g('page', 'int') : 0;

    $k_groups = array_keys($d_groups);
    $u_groups = [];

    foreach ($k_groups as $id)
    {
        $u_groups[] = [
            'id'          => $id,
            'name'        => str_replace(['{lang.ADMINS}', '{lang.USERS}', '{lang.GUESTS}'],
                            [$lang['ADMINS'], $lang['USERS'], $lang['GUESTS']],
                            $d_groups[$id]['data']['group_name']),
            'default'     => $config['default_group'] == $id ? true : false,
            'selected'    => $id == $u_group
        ];
    }

break;


//new user adding form
case 'new_u':

    //preparing the template
    $errs     = isset($errs) ? $errs : false;
    $uname    = p('lname');
    $umail    = p('lmail');

    $k_groups = array_keys($d_groups);
    $u_groups = [];

    foreach ($k_groups as $id)
    {
        $u_groups[] = [
            'id'          => $id,
            'name'        => str_replace(['{lang.ADMINS}', '{lang.USERS}', '{lang.GUESTS}'],
                                [$lang['ADMINS'], $lang['USERS'], $lang['GUESTS']],
                                $d_groups[$id]['data']['group_name']),
            'default'     => $config['default_group'] == $id ? true : false,
            'selected'    => ip('lgroup') ? p('lgroup') == $id : $id == $config['default_group']
        ];
    }

break;

endswitch;


//after submit
if (ip('submit'))
{
    $g_link = basename(ADMIN_PATH) . '?cp=' . basename(__file__, '.php') . '&amp;page=' . (ig('page')  ? g('page', 'int') : 1) .
                (ig('search_id') ? '&amp;search_id=' . g('search_id') : '') . '&amp;smt=' . $current_smt;

    $text    = ($affected ? $lang['USERS_UPDATED'] : $lang['NO_UP_CHANGE_S']) .
                '<script type="text/javascript"> setTimeout("get_kleeja_link(\'' . str_replace('&amp;', '&', $g_link) . '\');", 2000);</script>' . "\n";
    $stylee    = 'admin_info';
}


//secondary menu
$go_menu = [
    'general' => ['name'=>$lang['R_GROUPS'], 'link'=> basename(ADMIN_PATH) . '?cp=g_users&amp;smt=general', 'goto'=>'general', 'current'=> $current_smt == 'general'],
    //'users' => array('name'=>$lang['R_USERS'], 'link'=> basename(ADMIN_PATH) . '?cp=g_users&amp;smt=users', 'goto'=>'users', 'current'=> $current_smt == 'users'),
    'show_su' => ['name'=>$lang['SEARCH_USERS'], 'link'=> basename(ADMIN_PATH) . '?cp=h_search&amp;smt=users', 'goto'=>'show_su', 'current'=> $current_smt == 'show_su'],
];

//user adding is not allowed in integration
if (! $user_not_normal)
{
    $go_menu['new_u'] = ['name'=>$lang['NEW_USER'], 'link'=> basename(ADMIN_PATH) . '?cp=g_users&amp;smt=new_u', 'goto'=>'new_u', 'current'=> $current_smt == 'new_u'];
}
