<?php
# Kleeja Plugin
# traidnt_arbah
# Version: 1.0
# Developer: Kleeja team

# Prevent illegal run
if (!defined('IN_PLUGINS_SYSTEM')) {
    exit();
}


# Plugin Basic Information
$kleeja_plugin['traidnt_arbah']['information'] = array(
    # The casual name of this plugin, anything can a human being understands
    'plugin_title' => array(
        'en' => 'Traidnt Arbah',
        'ar' => 'ترايدنت أرباح'
    ),
    # Who wrote this plugin?
    'plugin_developer' => 'Kleeja.com',
    # This plugin version
    'plugin_version' => '1.1',
    # Explain what is this plugin, why should I use it?
    'plugin_description' => array(
        'en' => 'Generate a links using Traidnt Arbah service',
        'ar' => 'دعم ترايدنت أرباح في كليجا وإنشاء روابط ربحية تلقائياً'
    ),
    # Min version of Kleeja that's requiered to run this plugin
    'plugin_kleeja_version_min' => '2.0',
    # Max version of Kleeja that support this plugin, use 0 for unlimited
    'plugin_kleeja_version_max' => '3.0',
    # Should this plugin run before others?, 0 is normal, and higher number has high priority
    'plugin_priority' => 0
);

//after installation message, you can remove it, it's not requiered
$kleeja_plugin['traidnt_arbah']['first_run']['ar'] = "
شكراً لاستخدامك إضافة ترايدنت أرباح، قم بمراسلتنا بالأخطاء عند ظهورها على البريد: <br>
info@kleeja.com
<hr>
<br>
<h3>لاحظ:</h3>
<b>تجد إعدادات الإضافة في : إعدادات المركز->خيارات ترايدنت أرباح</b>
<br>
<br>
-------
<h3>معلومات قد تفيدك في برنامج أرباح: </h3>

- <a href='http://www.traidnt.net/link/index.html?referral=84289' target='_blank'>صفحة أرباح الأساسية - سجل وضعها في المفضلة</a> <br>
- <a href='http://www.traidnt.net/link/rules.html?referral=84289' target='_blank'>شروط وقوانين برنامج أرباح</a> <br>
- <a href='http://www.traidnt.net/page-3.html?title=adsense&amp;referral=84289' target='_blank'>معلومات مهمة عن أدسنس والمشاكل وحلولها مع أرباح</a>

";


# Plugin Installation function
$kleeja_plugin['traidnt_arbah']['install'] = function ($plg_id) {
    //new options
    $options = array(
        'traidnt_arbah_enable' =>
            array(
                'value' => '0',
                'html' => configField('traidnt_arbah_enable', 'yesno'),
                'plg_id' => $plg_id,
                'type' => 'traidnt_arbah'
            ),
        'traidnt_arbah_api_code' =>
            array(
                'value' => '',
                'html' => configField('traidnt_arbah_api_code'),
                'plg_id' => $plg_id,
                'type' => 'traidnt_arbah'
            ),
        'traidnt_arbah_user_id' =>
            array(
                'value' => '',
                'html' => configField('traidnt_arbah_user_id'),
                'plg_id' => $plg_id,
                'type' => 'traidnt_arbah'
            ),
    );


    add_config_r($options);


    //new language variables
    add_olang(array(
        'TRAIDNT_ARBAH_ENABLE' => 'تفعيل ترايدنت أرباح',
        'TRAIDNT_ARBAH_API_CODE' => 'كود الربط لترايدنت أرباح  (تجده في حسابك في أرباح)',
        'TRAIDNT_ARBAH_USER_ID' => 'رقم عضويتك لترايدنت أرباح  (للربط السريع)',
        'CONFIG_KLJ_MENUS_TRAIDNT_ARBAH' => 'خيارات ترايدنت أرباح',
    ),
        'ar',
        $plg_id);

    add_olang(array(
        'TRAIDNT_ARBAH_ENABLE' => 'Enable Traidnt Arbah',
        'TRAIDNT_ARBAH_API_CODE' => 'Your API code of Traidnt Arbah',
        'TRAIDNT_ARBAH_USER_ID' => 'Your user ID of Traidnt Arbah',
        'CONFIG_KLJ_MENUS_TRAIDNT_ARBAH' => 'Traidnt Arbah Settings',
    ),
        'en',
        $plg_id);
};


//Plugin update function, called if plugin is already installed but version is different than current
$kleeja_plugin['traidnt_arbah']['update'] = function ($old_version, $new_version) {
    // if(version_compare($old_version, '0.5', '<')){
    // 	//... update to 0.5
    // }
    //
    // if(version_compare($old_version, '0.6', '<')){
    // 	//... update to 0.6
    // }

    //you could use update_config, update_olang
};


# Plugin Uninstallation, function to be called at unistalling
$kleeja_plugin['traidnt_arbah']['uninstall'] = function ($plg_id) {
    //delete options
    delete_config(array(
        'traidnt_arbah_enable',
        'traidnt_arbah_api_code',
        'traidnt_arbah_user_id'
    ));

    //delete language variables
    foreach (array('ar', 'en') as $language) {
        delete_olang(null, $language, $plg_id);
    }
};


# Plugin functions
$kleeja_plugin['traidnt_arbah']['functions'] = array(

    'default_go_page' => function($args){
        if(g('go') == 'arbah')
        {
            $url = g('url', 'str');
            $no_request = false;
            header('Location:' . generate_traidnt_arbah_link(base64_decode($url)));
            return compact('no_request');
        }
    },

    'kleeja_get_link_func2' => function ($args) {
        global $config;

        if (!$config['traidnt_arbah_enable']) {
            return;
        }

        if(defined('IN_REAL_INDEX'))
        {
            $return_link = $args['return_link'];
            $current_pid  = $args['pid'];
            if($current_pid === 'file') {
                $return_link = $config['siteurl'] . 'go.php?go=arbah&url=' . base64_encode($return_link);
                return compact('return_link');
            }
        }
    }
);


/**
 * special functions
 */

if (!function_exists('generate_traidnt_arbah_link')) {
    function generate_traidnt_arbah_link($link, $by_user_id = false)
    {
        global $config;


        if (!$config['traidnt_arbah_enable']) {
            return $link;
        }


        if (empty($config['traidnt_arbah_api_code'])) {
            return $link;
        }

        if($by_user_id)
        {
            if (empty($config['traidnt_arbah_user_id'])) {
                return $link;
            }else{
                $config['traidnt_arbah_user_id'] = preg_replace('/[^0-9]/', '', $config['traidnt_arbah_user_id']);
                return 'https://traidnt.net/link/fast_link.html?user=' . trim(intval($config['traidnt_arbah_user_id']))
            . '&url=' . trim($link);
            }
        }

        $service_url = "https://www.traidnt.net/link/api.html?url=".trim($link)
            . "&api=".trim($config['traidnt_arbah_api_code'])
            . "&allow_adv=1"
            . "&minipage=1";


        $received_data = fetch_remote_file($service_url);


        if(empty($received_data))
        {
           return $by_user_id ? $link : generate_traidnt_arbah_link($link,  true);
        }


        $decoded_data = json_decode($received_data);

        if($decoded_data->error or $decoded_data->code == '')
        {
            return $by_user_id ? $link : generate_traidnt_arbah_link($link,  true);
        }

        if(!empty($decoded_data->url))
        {
            return $decoded_data->url;
        }

        return $link;
    }
}
