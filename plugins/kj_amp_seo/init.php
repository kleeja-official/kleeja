<?php
# kleeja plugin
# kj_amp_seo
# developer: kleeja team

# prevent illegal run
if (!defined('IN_PLUGINS_SYSTEM')) {
    exit();
}


# plugin basic information
$kleeja_plugin['kj_amp_seo']['information'] = array(
    # the casucal name of this plugin, anything can a human being understands
    'plugin_title' => array(
        'en' => 'KJ AMP SEO',
        'ar' => 'AMP سيو'
    ),
    # who wrote this plugin?
    'plugin_developer' => 'kleeja.com',
    # this plugin version
    'plugin_version' => '1.1',
    # explain what is this plugin, why should i use it?
    'plugin_description' => array(
        'en' => 'Add AMP support to download pages to enhance SEO for Kleeja',
        'ar' => 'دعم الـ AMP لصفحات التحميل لدعم السيو في كليجا'
    ),
    # min version of kleeja that's required to run this plugin
    'plugin_kleeja_version_min' => '2.0',
    # max version of kleeja that support this plugin, use 0 for unlimited
    'plugin_kleeja_version_max' => '3.9',
    # should this plugin run before others?, 0 is normal, and higher number has high priority
    'plugin_priority' => 0
);

//after installation message, you can remove it, it's not requiered
$kleeja_plugin['kj_amp_seo']['first_run']['ar'] = "
شكراً لاستخدامك إضافة كليجا هذه، قم بمراسلتنا بالأخطاء عند ظهورها على البريد: <br>
info@kleeja.com
";

$kleeja_plugin['kj_amp_seo']['first_run']['en'] = "
Thank you for using our plugin, if you encounter any bugs and errors, contact us: <br>
info@kleeja.com
";

# plugin installation function
$kleeja_plugin['kj_amp_seo']['install'] = function ($plg_id) {
    //new options
    $options = array(
        'kj_amp_seo_enable' =>
            array(
                'value' => '1',
                'html' => configField('kj_amp_seo_enable', 'yesno'),
                'plg_id' => $plg_id,
                'type' => 'kj_amp_seo',
                'order' => '1',
            ),
        'kj_amp_seo_share_buttons' =>
            array(
                'value' => '1',
                'html' => configField('kj_amp_seo_share_buttons', 'yesno'),
                'plg_id' => $plg_id,
                'type' => 'kj_amp_seo',
                'order' => '2',
            ),
        'kj_amp_seo_top_ad' =>
            array(
                'value' => '',
                'html' => configField('kj_amp_seo_top_ad'),
                'plg_id' => $plg_id,
                'type' => 'kj_amp_seo',
                'order' => '3',
            ),
        'kj_amp_seo_middle_ad' =>
            array(
                'value' => '',
                'html' => configField('kj_amp_seo_middle_ad'),
                'plg_id' => $plg_id,
                'type' => 'kj_amp_seo',
                 'order' => '4',
            ),
        'kj_amp_seo_sticky_ad' =>
            array(
                'value' => '',
                'html' => configField('kj_amp_seo_sticky_ad'),
                'plg_id' => $plg_id,
                'type' => 'kj_amp_seo',
                'order' => '5',
            ),

        'kj_amp_seo_adsense_client' =>
            array(
                'value' => '',
                'html' => configField('kj_amp_seo_adsense_client'),
                'plg_id' => $plg_id,
                'type' => 'kj_amp_seo',
                'order' => '6',
            ),
    );

    add_config_r($options);


    //new language variables
    add_olang(array(
        'CONFIG_KLJ_MENUS_KJ_AMP_SEO' => 'خيارات AMP سيو',
        'KJ_AMP_SEO_ENABLE' => 'تفعيل صفحات AMP المسرعة ',
        'KJ_AMP_SEO_SHARE_BUTTONS' => 'عرض أزرار المشاركة',
        'KJ_AMP_SEO_TOP_AD' => 'كود slot لإعلان AMP أدسنس علوي،  دعه فارغ للتعطيل',
        'KJ_AMP_SEO_MIDDLE_AD' => 'كود slot لإعلان AMP أدسنس وسطي،  دعه فارغ للتعطيل',
        'KJ_AMP_SEO_STICKY_AD' => 'كود slot لإعلان AMP أدسنس لاصق،  دعه فارغ للتعطيل',
        'KJ_AMP_SEO_ADSENSE_CLIENT' => 'كود client العام لحساب ادسنس الخاص بالإعلانات',
    ),
        'ar',
        $plg_id);

    add_olang(array(
        'CONFIG_KLJ_MENUS_KJ_AMP_SEO' => 'AMP SEO Options',
        'KJ_AMP_SEO_ENABLE' => 'Enable fast AMP pages',
        'KJ_AMP_SEO_SHARE_BUTTONS' => 'Enable social share buttons',
        'KJ_AMP_SEO_TOP_AD' => 'Slot code for Top AdSense AMP ad, keep it empty to disable',
        'KJ_AMP_SEO_MIDDLE_AD' => 'Slot code for Middle AdSense AMP ad, keep it empty to disable',
        'KJ_AMP_SEO_STICKY_AD' => 'Slot code for Sticky AdSense AMP ad, keep it empty to disable',
        'KJ_AMP_SEO_ADSENSE_CLIENT' => 'Client code for AdSense ads',
    ),
        'en',
        $plg_id);
};


//plugin update function, called if plugin is already installed but version is different than current
$kleeja_plugin['kj_amp_seo']['update'] = function ($old_version, $new_version) {
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
$kleeja_plugin['kj_amp_seo']['uninstall'] = function ($plg_id) {
    //delete options
    delete_config(array(
        'kj_amp_seo_enable',
        'kj_amp_seo_share_buttons',
        'kj_amp_seo_top_ad',
        'kj_amp_seo_middle_ad',
        'kj_amp_seo_sticky_ad',
        'kj_amp_seo_adsense_client',
    ));


    //delete language variables
    foreach (['ar', 'en'] as $language) {
        delete_olang(null, $language, $plg_id);
    }
};


# plugin functions
$kleeja_plugin['kj_amp_seo']['functions'] = array(
    'Saaheader_links_func' => function ($args) {
        if(defined('IN_DOWNLOAD') && !ig('amp_page')){
            global $config;
            $current_url = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
            $current_url .= strpos($current_url, '?') === false ?  '?' :  '&';

            $current_url .= 'amp_page=1';

            $extra = $args['extra'] . '<link rel="amphtml" href="'.htmlspecialchars($current_url).'">';
            return compact('extra');
        }
    },

    'b4_showsty_downlaod_id_filename' => function($args){


        if(!ig('amp_page')){
            return;
        }

        global $tpl, $lang;

        extract($args);

        $dir = $lang['DIR'];

        $side_menu = array(
            1 => array('name' => 'profile', 'title' => $lang['PROFILE'], 'url' => $config['mod_writer'] ? 'profile.html' : 'ucp.php?go=profile', 'show' => $user_is),
            2 => array('name' => 'fileuser', 'title' => $lang['YOUR_FILEUSER'], 'url' => $config['mod_writer'] ? 'fileuser.html' : 'ucp.php?go=fileuser', 'show' => $config['enable_userfile'] && user_can('access_fileuser')),
            3 => $user_is
                ? array('name' => 'logout', 'title' => $lang['LOGOUT'], 'url' => $config['mod_writer'] ? 'logout.html' : 'ucp.php?go=logout', 'show' => true)
                : array('name' => 'login', 'title' => $lang['LOGIN'], 'url' => $config['mod_writer'] ? 'login.html' : 'ucp.php?go=login', 'show' => true),
            4 => array('name' => 'register', 'title' => $lang['REGISTER'], 'url' => $config['mod_writer'] ? 'register.html' : 'ucp.php?go=register', 'show' => !$user_is && $config['register']),
        );

        $top_menu = array(
            1 => array('name' => 'index', 'title' => $lang['INDEX'], 'url' => $config['siteurl'], 'show' => true),
            2 => array('name' => 'rules', 'title' => $lang['RULES'], 'url' => $config['mod_writer'] ? 'rules.html' : 'go.php?go=rules', 'show' => true),
            3 => array('name' => 'guide', 'title' => $lang['GUIDE'], 'url' => $config['mod_writer'] ? 'guide.html' : 'go.php?go=guide', 'show' => true),
            4 => array('name' => 'stats', 'title' => $lang['STATS'], 'url' => $config['mod_writer'] ? 'stats.html' : 'go.php?go=stats', 'show' => $config['allow_stat_pg'] && user_can('access_stats')),
            5 => array('name' => 'report', 'title' => $lang['REPORT'], 'url' => $config['mod_writer'] ? 'report.html' : 'go.php?go=report', 'show' => user_can('access_report')),
            6 => array('name' => 'call', 'title' => $lang['CALL'], 'url' => $config['mod_writer'] ? 'call.html' : 'go.php?go=call', 'show' => user_can('access_call')),
        );

        $tpl->assign("side_menu", $side_menu);
        $tpl->assign("top_menu", $top_menu);
        $tpl->assign("dir", $dir);



        $current_url = 'http://'.$_SERVER['HTTP_HOST']
            . preg_replace('/[\&amp;|\?|\/]amp_page=\d/', '', $_SERVER['REQUEST_URI']);

        $tpl->assign("amp_canonical", $current_url);


        //ads
        $tpl->assign("top_ad", trim($config['kj_amp_seo_top_ad']) == '' ? false : trim($config['kj_amp_seo_top_ad']));
        $tpl->assign("middle_ad", trim($config['kj_amp_seo_middle_ad'])  == '' ? false : trim($config['kj_amp_seo_middle_ad']));
        $tpl->assign("sticky_ad", trim($config['kj_amp_seo_sticky_ad']) == '' ? false : trim($config['kj_amp_seo_sticky_ad']));
        $tpl->assign("adsense_client", trim($config['kj_amp_seo_adsense_client']));

        header('Content-type: text/html; charset=UTF-8');
        header('Cache-Control: private, no-cache="set-cookie"');
        header('Pragma: no-cache');
        header('x-frame-options: SAMEORIGIN');
        header('x-xss-protection: 1; mode=block');

        echo $tpl->display('amp', __DIR__);

        return ['show' => false];
    }
);

