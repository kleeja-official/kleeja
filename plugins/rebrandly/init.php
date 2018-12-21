<?php
# Kleeja Plugin
# rebrandly
# Version: 1.0
# Developer: Kleeja team

# Prevent illegal run
if (!defined('IN_PLUGINS_SYSTEM')) {
    exit();
}


# Plugin Basic Information
$kleeja_plugin['rebrandly']['information'] = array(
    # The casucal name of this plugin, anything can a human being understands
    'plugin_title' => array(
        'en' => 'Rebrandly for Kleeja',
        'ar' => 'Rebrandly لكليجا'
    ),
    # Who wrote this plugin?
    'plugin_developer' => 'Kleeja.com',
    # This plugin version
    'plugin_version' => '1.0',
    # Explain what is this plugin, why should I use it?
    'plugin_description' => array(
        'en' => 'Generate a short links using Rebrandly service',
        'ar' => 'إنشاء روابط قصيرة من خدمة Rebrandly '
    ),
    # Min version of Kleeja that's requiered to run this plugin
    'plugin_kleeja_version_min' => '2.0',
    # Max version of Kleeja that support this plugin, use 0 for unlimited
    'plugin_kleeja_version_max' => '3.0',
    # Should this plugin run before others?, 0 is normal, and higher number has high priority
    'plugin_priority' => 0
);

//after installation message, you can remove it, it's not requiered
$kleeja_plugin['rebrandly']['first_run']['ar'] = "
شكراً لاستخدامك إضافة rebrandly ، قم بمراسلتنا بالأخطاء عند ظهورها على البريد: <br>
info@kleeja.com
<hr>
<br>
<h3>لاحظ:</h3>
<b>تجد إعدادات الإضافة في : إعدادات المركز->خيارات Rebrandly</b>
<br>
<br>
-------
<h3>معلومات قد تفيدك: </h3>
- <a href='https://www.rebrandly.com'>إضغط هنا للتسجيل في Rebrandly</a> <br>
";

$kleeja_plugin['rebrandly']['first_run']['en'] = "
Thanks for using rebrandly plugin, to report bugs contact us: 
<br>
info@kleeja.com
<hr>
<br>
<h3>Note:</h3>
<b>You can find the settings at: Settings -> Rebrandly Settings</b>
<br>
<br>
-------
<h3>Extra Info: </h3>
- <a href='https://www.rebrandly.com' target='_blank'>Create your rebrandly account now!</a> <br>
";


# Plugin Installation function
$kleeja_plugin['rebrandly']['install'] = function ($plg_id) {
    //new options
    $options = array(
        'rebrandly_enable' =>
            array(
                'value' => '0',
                'html' => configField('rebrandly_enable', 'yesno'),
                'plg_id' => $plg_id,
                'type' => 'rebrandly'
            ),
        'rebrandly_domain' =>
            array(
                'value' => 'rebrand.ly',
                'html' => configField('rebrandly_domain'),
                'plg_id' => $plg_id,
                'type' => 'rebrandly'
            ),
        'rebrandly_api_code' =>
            array(
                'value' => '',
                'html' => configField('rebrandly_api_code'),
                'plg_id' => $plg_id,
                'type' => 'rebrandly'
            ),
    );


    add_config_r($options);


    //new language variables
    add_olang(array(
        'REBRANDLY_ENABLE' => 'تفعيل Rebrandly',
        'REBRANDLY_API_CODE' => 'كود الربط API لـ Rebrandly',
        'REBRANDLY_DOMAIN' => 'الدومين المستخدم',
        'CONFIG_KLJ_MENUS_REBRANDLY' => 'خيارات Rebrandly',
    ),
        'ar',
        $plg_id);

    add_olang(array(
        'REBRANDLY_ENABLE' => 'Enable Rebrandly',
        'REBRANDLY_API_CODE' => 'Your API code of Rebrandly',
        'REBRANDLY_DOMAIN' => 'Domain of Rebrandly',
        'CONFIG_KLJ_MENUS_REBRANDLY' => 'Rebrandly Settings',
    ),
        'en',
        $plg_id);
};


//Plugin update function, called if plugin is already installed but version is different than current
$kleeja_plugin['rebrandly']['update'] = function ($old_version, $new_version) {
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
$kleeja_plugin['rebrandly']['uninstall'] = function ($plg_id) {
    //delete options
    delete_config(array(
        'rebrandly_enable',
        'rebrandly_api_code',
        'rebrandly_domain'
    ));

    //delete language variables
    foreach (array('ar', 'en') as $language) {
        delete_olang(null, $language, $plg_id);
    }
};


# Plugin functions
$kleeja_plugin['rebrandly']['functions'] = array(
    'kleeja_get_link_func2' => function ($args) {
        global $config;

        if (!$config['rebrandly_enable']) {
            return;
        }


        if(defined('IN_REAL_INDEX'))
        {
            $link = $args['return_link'];
            $current_pid  = $args['pid'];
            if($current_pid === 'file') {

                $return_link = generate_rebrandly_link($link);

                return compact('return_link');
            }
        }
    }
);


/**
 * special functions
 */

if (!function_exists('genera_rebrandly_link')) {
    function generate_rebrandly_link($link)
    {
        global $config;


        if (!$config['rebrandly_enable']) {
            return $link;
        }


        if (empty($config['rebrandly_api_code'])) {
            return $link;
        }


        if (empty($config['rebrandly_domain'])) {
            return $link;
        }


        $post_data["destination"] = $link;
        $post_data["domain"] = array('fullName' => $config['rebrandly_domain']);;

        $ch = curl_init("https://api.rebrandly.com/v1/links");
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "apikey: " . $config['rebrandly_api_code'],
            "Content-Type: application/json"
        ));
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post_data));
        $result = curl_exec($ch);
        curl_close($ch);
        $response = json_decode($result, true);

        if(!empty($response["shortUrl"])){
            return $response["shortUrl"];
        }


        return $link;
    }
}
