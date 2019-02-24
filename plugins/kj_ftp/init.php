<?php
# kleeja plugin
# kj_ftp
# version: 1.0
# developer: kleeja team

# prevent illegal run
if (!defined('IN_PLUGINS_SYSTEM')) {
    exit();
}



# 1- create_folder, generate htaccess: done
# 2- kleeja_unlink: done



# plugin basic information
$kleeja_plugin['kj_ftp']['information'] = array(
    # the casucal name of this plugin, anything can a human being understands
    'plugin_title' => array(
            'en' => 'Kleeja Multi-FTP Uploading',
            'ar' => 'تحميل FTP متعدد'
    ),
    # who is developing this plugin?
    'plugin_developer' => 'kleeja.com',
    # this plugin version
    'plugin_version' => '1.1',
    # explain what is this plugin, why should i use it?
    'plugin_description' => array(
        'en' => 'Add Multi-FTP support to Kleeja',
        'ar' => 'إضافة دعم التحميل لعدة FTP  في كليجا'
    ),
    # min version of kleeja that's required to run this plugin
    'plugin_kleeja_version_min' => '2.0',
    # max version of kleeja that support this plugin, use 0 for unlimited
    'plugin_kleeja_version_max' => '3.9',
    # should this plugin run before others?, 0 is normal, and higher number has high priority
    'plugin_priority' => 0
);

//after installation message, you can remove it, it's not required
$kleeja_plugin['kj_ftp']['first_run']['ar'] = "
شكراً لاستخدامك إضافة الـFTP المتعدد لكليجا، قم بمراسلتنا بالأخطاء عند ظهورها على البريد: <br>
info@kleeja.com
";

$kleeja_plugin['kj_ftp']['first_run']['en'] = "
Thank you for using our plugin, if you encounter any bugs and errors, contact us: <br>
info@kleeja.com
";

# plugin installation function
$kleeja_plugin['kj_ftp']['install'] = function ($plg_id) {
    global $dbprefix, $SQL;

    //create table
    $sql = "CREATE TABLE IF NOT EXISTS `{$dbprefix}kj_ftp_info` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(20) COLLATE utf8_bin DEFAULT NULL,
  `host` varchar(199) COLLATE utf8_bin DEFAULT NULL,
  `username` varchar(199) COLLATE utf8_bin DEFAULT NULL,
  `password` varchar(199) COLLATE utf8_bin DEFAULT NULL,
  `port` int(4) DEFAULT '21',
  `root` varchar(199) COLLATE utf8_bin DEFAULT '',
  `passive` tinyint(1) DEFAULT '1',
  `ssl` tinyint(1) DEFAULT '1',
  `timeout` int(4) DEFAULT '60',
  `link` varchar(199) COLLATE utf8_bin DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '0',
  `group` int(4) DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;";

    $SQL->query($sql);


    //new language variables
    add_olang(array(
        'R_KJ_FTP_OPTIONS' => 'تحميل الـFTP المتعدد',

        #
        'KJ_FTP_OPT_NAME' => 'الإسم الثابت',
        'KJ_FTP_OPT_NAME_HELP' => 'كليجا تستخدم الإسم الثابت لحفظ وجلب الملفات، عند تغيير الإسم الثابت لن نستطيع جلب الملفات! عدل الإسم في حالة واحدة فقط، وهي لربط ملفات سابقة بحساب قديم تم حذفه.',
        'KJ_FTP_OPT_HOST' => 'الخادم',
        'KJ_FTP_OPT_HOST_HELP' => ' مثل: kleeja.com أو سب دومين sub.kleeja.com أو آي بي 188.54.12.11',
        'KJ_FTP_OPT_USERNAME' => 'اسم المستخدم',
        'KJ_FTP_OPT_PASSWORD' => 'كلمة المرور (مخفية، أكتب جديدة فقط لو أردت التغيير)',
        'KJ_FTP_OPT_PORT' => 'منفذ',
        'KJ_FTP_OPT_ROOT' => 'المسار الجذر',
        'KJ_FTP_OPT_ROOT_HELP' => 'المسار الذي ستقوم كليجا بإنشاء مجلد uploads داخله، غالباً دعه فارغ',
        'KJ_FTP_OPT_PASSIVE_HELP' => 'وضع FTP الآمن, قد يكون أبطئ',
        'KJ_FTP_OPT_ACTIVE' => 'مُفعل',
        'KJ_FTP_OPT_ACTIVE_HELP' => 'الحساب غير المفعل لن يستلم تحميلات جديدة ولكن سيتم جلب الملفات التي تم تحميلها مسبقاً منه.',

        'KJ_FTP_OPT_LINK' => 'الرابط الفعلي',
        'KJ_FTP_OPT_LINK_HELP' => 'مثل: http://kleeja.com أو https://www.example.com/ftp1، لو ترك فارغاً سيتم إستخدام إنتاج رابط من الخادم ومجلد الروت.',

        'KJ_FTP_ADD_NEW_ACCOUNT' => 'أضف حساب FTP جديد',
        'KJ_FTP_ADD_NEW_ACCOUNT_EXP' => ' هل أنت متأكد من إضافة حساب FTP جديد؟<br><small>يمكنك إضافة بيانات الحساب بعد الإضافة وتعديل الحساب.</small>',

        'KJ_FTP_ACCOUNT_ADDED' => 'تم إضافة الحساب بنجاح',
        'KJ_FTP_ACCOUNT_UPDATED' => 'تم تحديث الحساب بنجاح',

        'KJ_FTP_ACCOUNT_NAME_CONFLICT' => 'الأسم الثابت موجود في حساب آخر! لايمكنك إستخدام اسم ثابت واحد في حسابين',
        #

        'KJ_FTP_NO_ACTIVE_ACCOUNTS_NOTE' => 'لآنه لايوجد أي حساب FTP تم تفعيله للتحميل، فإن كليجا ستستخدم نظام التحميل الإفتراضي المحلي. لجعل كليجا تقوم بالتحميل بإستخدام الإضافة، <a href="./?cp=kj_ftp_options">قم بتفعيل حساب FTP للتحميل</a> الآن!',

    ),
        'ar',
        $plg_id);

    add_olang(array(
        'R_KJ_FTP_OPTIONS' => 'KJ - Multi-FTP Uploading',

        #
        'KJ_FTP_OPT_NAME' => 'Unique Name',
        'KJ_FTP_OPT_NAME_HELP' => 'Kleeja uses this name to identify that a file is related to this FTP account. Changing it will ruin the old files connection. Only edit this if you want to recover a connection of an old FTP account that has been deleted.',
        'KJ_FTP_OPT_HOST' => 'Host',
        'KJ_FTP_OPT_HOST_HELP' => ' Like: kleeja.com or a subdomain: sub.kleeja.com or an IP:  188.54.12.11',
        'KJ_FTP_OPT_USERNAME' => 'Username',
        'KJ_FTP_OPT_PASSWORD' => 'Password (hidden, type a new password only you want to change it)',
        'KJ_FTP_OPT_PORT' => 'Port',
        'KJ_FTP_OPT_ROOT' => 'Root Path',
        'KJ_FTP_OPT_ROOT_HELP' => 'The path where Kleeja will create "uploads" folder in. Usually keeping it empty is fine.',
        'KJ_FTP_OPT_PASSIVE_HELP' => 'Secure Passive FTP mode, slower.',

        'KJ_FTP_OPT_ACTIVE' => 'Active',
        'KJ_FTP_OPT_ACTIVE_HELP' => 'Inactive account will not receive new uploads to it, but will continue serving previous upload from it.',

        'KJ_FTP_OPT_LINK' => 'Direct Link',
        'KJ_FTP_OPT_LINK_HELP' => 'Link: http://kleeja.com or https://www.example.com/ftp1; If left empty, we will try to generate a link from the ftp host and given root folder.',

        'KJ_FTP_ADD_NEW_ACCOUNT' => 'Add New FTP Account',
        'KJ_FTP_ADD_NEW_ACCOUNT_EXP' => 'Are you sure of adding a new FTP account? <br><small>You can edit the account information after adding it.</small>',

        'KJ_FTP_ACCOUNT_ADDED' => 'The FTP account has been added successfully!',
        'KJ_FTP_ACCOUNT_UPDATED' => 'The FTP account has been updated successfully!',
        'KJ_FTP_ACCOUNT_NAME_CONFLICT' => 'The unique name is existed before, you can not have two accounts with same unique name!',

        #
        'KJ_FTP_NO_ACTIVE_ACCOUNTS_NOTE' => 'Because you did not activate any FTP account, Kleeja will fallback to the default local uploading method. To make Kleeja Use FTP uploading method, <a href="./?cp=kj_ftp_options">activate an FTP account</a> now!',

         ),
        'en',
        $plg_id);
};


//plugin update function, called if plugin is already installed but version is different than current
$kleeja_plugin['kj_ftp']['update'] = function ($old_version, $new_version) {
    // if(version_compare($old_version, '0.5', '<')){
    // 	//... update to 0.5
    // }
    //
    // if(version_compare($old_version, '0.6', '<')){
    // 	//... update to 0.6
    // }

    //you could use update_config, update_olang
};


# plugin uninstalling, function to be called at uninstalling
$kleeja_plugin['kj_ftp']['uninstall'] = function ($plg_id) {
    //delete options
//    delete_config(array(
//        'kj_ftp_home_meta_description',
//        'kj_ftp_home_meta_keywords'
//    ));

    //delete language variables
    foreach (['ar', 'en'] as $language) {
        delete_olang(null, $language, $plg_id);
    }
};


# plugin functions
$kleeja_plugin['kj_ftp']['functions'] = array(

    //add to admin menu
    'begin_admin_page' => function ($args)
    {
        $adm_extensions = $args['adm_extensions'];
        $ext_icons = $args['ext_icons'];
        $adm_extensions[] = 'kj_ftp_options';
        $ext_icons['kj_ftp_options'] = 'cloud-upload';
        return compact('adm_extensions', 'ext_icons');
    },

    'not_exists_kj_ftp_options' => function()
    {
        $include_alternative = dirname(__FILE__) . '/kj_ftp_options.php';

        return compact('include_alternative');
    },


    'begin_index_page' => function()
    {
        if(defined('DISABLE_KLJ_FTP')){
            return;
        }

        $uploadingMethodClass = dirname(__FILE__) . '/ftpUploader.php';

        return compact('uploadingMethodClass');
    },

    'kleeja_get_link_func2' => function($args)
    {
        if(defined('DISABLE_KLJ_FTP')){
            return;
        }

        global $config;
        if(($args['pid'] == 'image' || $args['pid'] == 'thumb') && $config['id_form_img'] == 'direct'){

            $realFolder = str_replace(array_keys($args['extra']), array_values($args['extra']), $args['links'][$args['pid']]);

            $uniqueName = '';

            if(strpos($realFolder, 'ftp://') !== false)
            {
                $afterFTP = explode('ftp://',  $realFolder, 2);


                $folder = explode(':', $afterFTP[1], 2);
                $uniqueName = $folder[0];
                $realFolder = $folder[1];
                
                
            if(empty($uniqueName)){
                $return_link = getKleejaFtpInstance()->getLink('') . $realFolder;
            }else {
                $return_link = getKleejaFtpLink($uniqueName, $realFolder);
            }

            return compact('return_link');

            }

            
        }
    },


    'down_go_page' => function($args)
    {
        if(defined('DISABLE_KLJ_FTP')){
            return;
        }

        if(strpos($args['f'], 'ftp://') !== false)
        {
            define('MAKE_DOPHP_301_HEADER', true);

            $afterFTP = explode('ftp://', $args['f'], 2);

            $filename = $args['n'];
            $folder = explode(':', $afterFTP[1], 2);
            $uniqueName = $folder[0];
            $realFolder = ig('thmb') || ig('thmbf') ? $folder[1] . '/thumbs' : $folder[1];

            $path_file = getKleejaFtpLink($uniqueName, $realFolder, $filename);

            return compact('path_file');
        }
    },

    'kleeja_unlink_func' => function($args)
    {
        if(defined('DISABLE_KLJ_FTP')){
            return;
        }

        if(strpos($args['filePath'], 'ftp://') !== false)
        {
            $afterFTP = explode('ftp://', $args['filePath'], 2);

            $path = explode(':', $afterFTP[1], 2);
            $uniqueName = $path[0];
            $filePath = $path[1];


            $ftpAccount = getKleejaFtpAccount($uniqueName);

            getKleejaFtpAccountInstance($ftpAccount)->delete($filePath);
//            getKleejaFtpAccountInstance($ftpAccount)->close();
            $return = true;
            return compact('return');
        }
    },

    'end_common' => function()
    {
        global $dbprefix, $SQL;
        if(!
            $SQL->num_rows(
                $SQL->query("SELECT active FROM {$dbprefix}kj_ftp_info WHERE active=1")
            )
        ){
           define('DISABLE_KLJ_FTP', true);
        }
    },


    'stats_start_admin' => function($args)
    {
        $ADM_NOTIFICATIONS = $args['ADM_NOTIFICATIONS'];

        if(defined('DISABLE_KLJ_FTP')) {
            global $lang, $olang;

            $ADM_NOTIFICATIONS['kljFtpNoActive']  = array(
                'id' => 'kljFtpNoActive',
                'msg_type'=> 'info',
                'title'=> $lang['NOTE'] . ' (' . $olang['R_KJ_FTP_OPTIONS'] . ')',
                'msg'=> $olang['KJ_FTP_NO_ACTIVE_ACCOUNTS_NOTE']
            );

        }else{
            unset($ADM_NOTIFICATIONS['htaccess_u'], $ADM_NOTIFICATIONS['htaccess_t'], $ADM_NOTIFICATIONS['no_thumbs']);
        }

         return compact('ADM_NOTIFICATIONS');
    },


    //support video plugin
    'plugin:video_player:do_display' => function($args){

        $folder = $args['file_info']['folder'];
        $filename = $args['file_info']['name'];

        if(strpos($folder, 'ftp://') !== false)
        {
            $afterFTP = explode('ftp://', $folder, 2);

            $path = explode(':', $afterFTP[1], 2);
            $uniqueName = $path[0];
            $realFolder = $path[1];


            $video_path = getKleejaFtpLink($uniqueName, $realFolder, $filename);

            return compact('video_path');
        }
    },

    //support pdf plugin
    'plugin:pdf_viewer:do_display' => function($args){

        $folder = $args['file_info']['folder'];
        $filename = $args['file_info']['name'];

        if(strpos($folder, 'ftp://') !== false)
        {
            $afterFTP = explode('ftp://', $folder, 2);

            $path = explode(':', $afterFTP[1], 2);
            $uniqueName = $path[0];
            $realFolder = $path[1];


            $pdf_path = getKleejaFtpLink($uniqueName, $realFolder, $filename);

            return compact('pdf_path');
        }
    }

);




if(!function_exists('getKleejaFtpInstance'))
{
    /**
     * @return kleeja_ftp|null
     */
    function getKleejaFtpInstance()
    {
        if (!class_exists('kleeja_ftp'))
        {
            require_once dirname(__FILE__) . '/ftp.php';
        }

        /** @var kleeja_ftp $kljFtp */
        static $kljFtp = null;

        if (is_null($kljFtp))
        {
            global $dbprefix, $SQL, $cache;


            ##### ------ > cached random, solve inadequate mySQL RAND.
            if (!($ftp_accounts = $cache->get('klj_ftp::ftp_names')))
            {
                $query = array(
                    'SELECT' => 'k.name',
                    'FROM' => "`{$dbprefix}kj_ftp_info` k",
                    'WHERE' => 'k.active = 1'
                );

                $result = $SQL->build($query);

                $ftp_accounts = array();

                while($row=$SQL->fetch_array($result))
                {
                    array_push($ftp_accounts, $row['name']);
                }

                $SQL->freeresult($result);

                $cache->save('klj_ftp::ftp_names', $ftp_accounts);
            }


            if (sizeof($ftp_accounts) == 0) {
                kleeja_show_error(102, 'NO FTP ACCOUNT FOUND!', __FILE__, __LINE__);
                return null;
            }

            ####


            //random
            shuffle($ftp_accounts);
            shuffle($ftp_accounts);
            shuffle($ftp_accounts);

            $ftp_account = getKleejaFtpAccount($ftp_accounts[0]);

            $kljFtp = new kleeja_ftp();

           $connect = $kljFtp->open(
                    $ftp_account['host'],
                    $ftp_account['username'],
                    $ftp_account['password'],
                    $ftp_account['port'],
                    $ftp_account['root'],
                    $ftp_account['passive'] == 1,
                    $ftp_account['ssl'] == 1,
                    $ftp_account['timeout']
                );

           if(!$connect){
                kleeja_show_error(102, 'FTP ACCOUNT CAN NOT CONNECT (' . $ftp_account['name'] . ')!', __FILE__, __LINE__);
            }


            $kljFtp->setUniqueName($ftp_account['name']);
            $kljFtp->setLink($ftp_account['link']);

            register_shutdown_function(function() { getKleejaFtpInstance()->close(); } );
        }



        return $kljFtp;
    }
}


if(!function_exists('getKleejaFtpAccountInstance'))
{
    /**
     * @return kleeja_ftp|null
     */
    function getKleejaFtpAccountInstance($ftp_account)
    {
        if (!class_exists('kleeja_ftp'))
        {
            require_once dirname(__FILE__) . '/ftp.php';
        }


        /** @var kleeja_ftp $kljFtp */
        static $kljFtp = null;
//
        if ($kljFtp === null || $kljFtp->getUniqueName() !== $ftp_account['name'])
        {

            $kljFtp = new kleeja_ftp();

            $connect = $kljFtp->open(
                $ftp_account['host'],
                $ftp_account['username'],
                $ftp_account['password'],
                $ftp_account['port'],
                $ftp_account['root'],
                $ftp_account['passive'] == 1,
                $ftp_account['ssl'] == 1,
                $ftp_account['timeout']
            );

            if(!$connect){
                kleeja_show_error(102, 'FTP ACCOUNT CAN NOT CONNECT (' . $ftp_account['name'] . ')!', __FILE__, __LINE__);
            }


            $kljFtp->setUniqueName($ftp_account['name']);
            $kljFtp->setLink($ftp_account['link']);


            register_shutdown_function(function() use($kljFtp) { $kljFtp->close(); } );
        }


        return $kljFtp;
    }
}


if(!function_exists('getKleejaFtpAccount'))
{
    function getKleejaFtpAccount($uniqueName)
    {
        global $dbprefix, $SQL;

        $query = array(
            'SELECT' => 'k.*',
            'FROM' => "`{$dbprefix}kj_ftp_info` k",
            'WHERE' => "k.name = '"  . $SQL->escape($uniqueName) . "'",
            'LIMIT' => '1'
        );

        $result = $SQL->build($query);

        if($SQL->num_rows($result))
        {
            return $SQL->fetch_array($result);
        }

        return false;
    }
}

if(!function_exists('getKleejaFtpLink'))
{
    function getKleejaFtpLink($uniqueName, $folder, $filename = '')
    {
        $ftp_account = getKleejaFtpAccount($uniqueName);
        if($ftp_account == false)
        {
            return '...' . $uniqueName . '...';
        }


        $link = !empty($ftp_account['link']) ? trim($ftp_account['link']) : '';
        $host = trim($ftp_account['host']);
        $rootPath = trim($ftp_account['root']);

        if($link != '')
        {
            return ltrim($link, '/') . '/' . $folder . ($filename !== '' ? '/' . $filename : '');
        }


        if ($rootPath != '')
        {
            if (substr($rootPath, -1, 1) == '/')
            {
                $rootPath = substr($rootPath, 0, -1);
            }
        }

        return $host . '/' . $rootPath . '/' . $folder . ($filename !== '' ? '/' . $filename : '');
    }
}

