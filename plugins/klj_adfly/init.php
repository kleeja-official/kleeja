<?php
# Kleeja Plugin
# klj_adfly
# Version: 1.0
# Developer: Kleeja team

# Prevent illegal run
if (!defined('IN_PLUGINS_SYSTEM')) {
    exit();
}


# Plugin Basic Information
$kleeja_plugin['klj_adfly']['information'] = array(
    # The casucal name of this plugin, anything can a human being understands
    'plugin_title' => array(
        'en' => 'adf.ly for Kleeja',
        'ar' => 'روابط adf.ly لكليجا'
    ),
    # Who wrote this plugin?
    'plugin_developer' => 'Kleeja.com',
    # This plugin version
    'plugin_version' => '1.1',
    # Explain what is this plugin, why should I use it?
    'plugin_description' => array(
        'en' => 'Generate a links using adf.ly service',
        'ar' => 'دعم Adf.ly في كليجا وإنشاء روابط ربحية تلقائياً'
    ),
    # Min version of Kleeja that's requiered to run this plugin
    'plugin_kleeja_version_min' => '2.0',
    # Max version of Kleeja that support this plugin, use 0 for unlimited
    'plugin_kleeja_version_max' => '3.9',
    # Should this plugin run before others?, 0 is normal, and higher number has high priority
    'plugin_priority' => 0
);

//after installation message, you can remove it, it's not requiered
$kleeja_plugin['klj_adfly']['first_run']['ar'] = "
شكراً لاستخدامك إضافة adf.ly ، قم بإعلامنا بالأخطاء عند ظهورها على: <br>
https://github.com/awssat/kleeja/issues
<hr>
<br>
<h3>لاحظ:</h3>
<b>تجد إعدادات الإضافة في : إعدادات المركز->خيارات adf.ly</b>
<br>
<br>
-------
<h3>معلومات قد تفيدك: </h3>
- <a href='https://join-adf.ly/17806729'>إضغط هنا للتسجيل في adf.ly</a> <br>
";

$kleeja_plugin['klj_adfly']['first_run']['en'] = "
Thanks for using adf.ly plugin, to report bugs: 
<br>
https://github.com/awssat/kleeja/issues
<hr>
<br>
<h3>Note:</h3>
<b>You can find the settings at: Settings -> adf.ly Settings</b>
<br>
<br>
-------
<h3>Extra Info: </h3>
- <a href='https://join-adf.ly/17806729' target='_blank'>Create your adf.ly now!</a> <br>
";


# Plugin Installation function
$kleeja_plugin['klj_adfly']['install'] = function ($plg_id) {
    //new options
    $options = array(
        'klj_adfly_enable' =>
            array(
                'value' => '0',
                'html' => configField('klj_adfly_enable', 'yesno'),
                'plg_id' => $plg_id,
                'type' => 'klj_adfly'
            ),
        'klj_adfly_images_enable' =>
            array(
                'value' => '0',
                'html' => configField('klj_adfly_images_enable', 'yesno'),
                'plg_id' => $plg_id,
                'type' => 'klj_adfly'
            ),
        'klj_adfly_user_id' =>
            array(
                'value' => '',
                'html' => configField('klj_adfly_user_id'),
                'plg_id' => $plg_id,
                'type' => 'klj_adfly'
            ),
        'klj_adfly_api_code' =>
            array(
                'value' => '',
                'html' => configField('klj_adfly_api_code'),
                'plg_id' => $plg_id,
                'type' => 'klj_adfly'
            ),
    );


    add_config_r($options);


    //new language variables
    add_olang(array(
        'KLJ_ADFLY_ENABLE' => 'تفعيل adf.ly',
        'KLJ_ADFLY_IMAGES_ENABLE' => 'تفعيل adf.ly للصور',
        'KLJ_ADFLY_API_CODE' => 'كود الربط API لـ adf.ly',
        'KLJ_ADFLY_USER_ID' => 'رقم المستخدم لـ adf.ly',
        'CONFIG_KLJ_MENUS_KLJ_ADFLY' => 'خيارات adf.ly',
    ),
        'ar',
        $plg_id);

    add_olang(array(
        'KLJ_ADFLY_ENABLE' => 'Enable adf.ly',
        'KLJ_ADFLY_IMAGES_ENABLE' => 'Enable adf.ly for images',
        'KLJ_ADFLY_API_CODE' => 'Your API code of adf.ly',
        'KLJ_ADFLY_USER_ID' => 'Your user ID of adf.ly',
        'CONFIG_KLJ_MENUS_KLJ_ADFLY' => 'adf.ly Settings',
    ),
        'en',
        $plg_id);
};


//Plugin update function, called if plugin is already installed but version is different than current
$kleeja_plugin['klj_adfly']['update'] = function ($old_version, $new_version) {

    $plg_id = Plugins::getInstance()->installed_plugin_info('klj_adfly');

    if(version_compare($old_version, '1.1', '<')){
        $options = array(
            'klj_adfly_images_enable' =>
                array(
                    'value' => '0',
                    'html' => configField('klj_adfly_images_enable', 'yesno'),
                    'plg_id' => $plg_id,
                    'type' => 'klj_adfly'
                ),
        );


        add_config_r($options);


        //new language variables
        add_olang(array(
            'KLJ_ADFLY_IMAGES_ENABLE' => 'تفعيل adf.ly للصور',
        ),
            'ar',
            $plg_id);

        add_olang(array(
            'KLJ_ADFLY_IMAGES_ENABLE' => 'Enable adf.ly for images',
        ),
            'en',
            $plg_id);
    }
};


# Plugin Uninstallation, function to be called at unistalling
$kleeja_plugin['klj_adfly']['uninstall'] = function ($plg_id) {
    //delete options
    delete_config(array(
        'klj_adfly_enable',
        'klj_adfly_images_enable',
        'klj_adfly_api_code',
        'klj_adfly_user_id'
    ));

    //delete language variables
    foreach (array('ar', 'en') as $language) {
        delete_olang(null, $language, $plg_id);
    }
};


# Plugin functions
$kleeja_plugin['klj_adfly']['functions'] = array(
    'kleeja_get_link_func2' => function ($args) {
        global $config;

        if (!$config['klj_adfly_enable']) {
            return;
        }


        if(defined('IN_REAL_INDEX'))
        {
            $link = $args['return_link'];
            $current_pid  = $args['pid'];


            if($current_pid !== 'file' && $current_pid !== 'image') {
                return;
            }


            if($current_pid === 'image' && !$config['klj_adfly_images_enable']){
                return;
            }


            $return_link = generate_klj_adfly_link($link);

            return compact('return_link');

        }
    }
);


/**
 * special functions
 */

if (!function_exists('generate_klj_adfly_link')) {
    function generate_klj_adfly_link($link)
    {
        global $config;


        if (!$config['klj_adfly_enable']) {
            return $link;
        }


        if (empty($config['klj_adfly_api_code'])) {
            return $link;
        }


        if (empty($config['klj_adfly_user_id'])) {
            return $link;
        }



        //TODO make it option
        $domain = 'adf.ly';
        $advert_type = 'int';


        $api = 'http://api.adf.ly/api.php?';

        // api queries
        $query = array(
            'key' => $config['klj_adfly_api_code'],
            'uid' => $config['klj_adfly_user_id'],
            'advert_type' => $advert_type,
            'domain' => $domain,
            'url' => $link
        );


        $service_url = $api . http_build_query($query);


        $received_data = fetch_remote_file($service_url);


        if(empty($received_data))
        {
           return $link;
        }

        return $received_data;
    }
}
