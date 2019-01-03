<?php

// not for directly open
if (!defined('IN_ADMIN'))
{
	exit;
}

if (intval($userinfo['founder']) !== 1) {
    kleeja_admin_err($lang['HV_NOT_PRVLG_ACCESS'], ADMIN_PATH.'?cp='.basename(__FILE__, '.php'));
    exit;
}

#current case
$current_case = g('case');

#current template
$stylee = 'admin_kjftp';

#template folder path
$styleePath = dirname(__FILE__);


$action = basename(ADMIN_PATH) . '?cp=' . basename(__file__, '.php');

$H_FORM_KEYS	= kleeja_add_form_key('adm_kj_ftp');


if(ip('submit'))
{
    $current_case = p('type') == 'new' ? 'new' : 'edit';

    if(!kleeja_check_form_key('adm_kj_ftp', 3600))
    {
        kleeja_admin_err($lang['INVALID_FORM_KEY'], true, $lang['ERROR'], true, $action, 1);
    }
}


$ERRORS = false;

switch ($current_case)
{
    /**
     * show a list of current ftp accounts
     */
    default:
    case 'list':

        #TODO show a error if no active account exists
        //There is no active FTP account! activate an account or disable this plugin to use standard local files uploading system.

        $query	= array(
            'SELECT'	=> 'k.*',
            'FROM'		=> "`{$dbprefix}kj_ftp_info` k",
            'ORDER BY'	=> 'k.id ASC'
        );


        $result = $SQL->build($query);

        $result_number = $SQL->num_rows($result);

        $ftp_accounts = array();

        if($result_number > 0)
        {
            while($row=$SQL->fetch_array($result))
            {
                unset($row['password']);

                $ftp_accounts[] = $row;
            }
        }

        $SQL->free();


        break;


    /**
     * no need!
     */
    case 'delete':
        break;


    /**
     * add new ftp account
     */
    case 'new':

    
        //is this enough ?
        $unique_name = uniqid();


        $insert_query	= array(
            'INSERT'	=> 'name, host',
            'INTO'		=> "{$dbprefix}kj_ftp_info",
            'VALUES'	=> "'$unique_name', 'example.com'"
        );

        if ($SQL->build($insert_query)) {
            $last_user_id = $SQL->insert_id();

            kleeja_admin_info($olang['KJ_FTP_ACCOUNT_ADDED'], true, '', true, $action, 3);
        }

        break;



    case 'edit':

        #save, show info
        $data = array(
            'name'      => p('name'),
            'host'      => p('host'),
            'username'  => p('username'),
            'password'  => p('password'),
            'link'  => p('link'),
            'port'      => p('port', 'int'),
            'root'      => p('root'),
            'passive'   => ip('passive') ? 1 : 0,
            'active'    => ip('active') ? 1 : 0,
            'ssl'       => ip('ssl') ? 1 : 0,
        );


        if($data['port'] == 0){
            $data['port'] = 21;
        }

        if(strpos($data['host'], 'ftp://') !== false){
            $data['host'] = str_replace('ftp://', '', $data['host']);
        }

        if(strpos($data['host'], 'http://') !== false){
            $data['host'] = str_replace('http://', '', $data['host']);
        }

        if(strpos($data['host'], 'https://') !== false){
            $data['host'] = str_replace('https://', '', $data['host']);
        }

        if ($data['password'] === '') {
            unset($data['password']);
        }


        if(
            $SQL->num_rows(
                $SQL->query("SELECT * FROM {$dbprefix}kj_ftp_info WHERE id<>" . p('id') . " AND name='" . $SQL->escape($data['name']) . "'")
            )
        )
        {
            kleeja_admin_err($olang['KJ_FTP_ACCOUNT_NAME_CONFLICT'], true, '', true,  $action);
        }


        $updateSet = '';
        foreach ($data as $n=>$v){
            $updateSet .= ($updateSet == '' ? '' : ', '). "`$n`='" . $SQL->escape($v) .  "'";
        }

        $update_query = array(
            'UPDATE'	=> "{$dbprefix}kj_ftp_info",
            'SET'		=> $updateSet,
            'WHERE'		=> "id=". p('id')
        );

        $SQL->build($update_query);


        $cache->clean('klj_ftp::ftp_names');

        kleeja_admin_info($olang['KJ_FTP_ACCOUNT_UPDATED'], true, '', true, $action, 2);


        break;

}



