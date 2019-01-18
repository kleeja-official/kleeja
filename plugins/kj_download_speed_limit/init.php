<?php
# kleeja plugin
# 
# version: 1.0
# developer: kleeja team

# prevent illegal run
if (!defined('IN_PLUGINS_SYSTEM')) {
    exit;
}

# plugin basic information
$kleeja_plugin['kj_download_speed_limit']['information'] = array(
    # the casual name of this plugin, anything can a human being understands
    'plugin_title' => array(
        'en' => 'KJ Download Speed Limit',
        'ar' => 'تحديد سرعة التحميل'
    ),
    # who wrote this plugin?
    'plugin_developer' => 'kleeja.com',
    # this plugin version
    'plugin_version' => '1.1',
    # explain what is this plugin, why should i use it?
    'plugin_description' => array(
        'en' => 'Limit files download speed for each group',
        'ar' => 'تحديد سرعة تحميل الملفات لكل مجموعة'
    ),

    # min version of kleeja that's required to run this plugin
    'plugin_kleeja_version_min' => '2.3',
    # max version of kleeja that support this plugin, use 0 for unlimited
    'plugin_kleeja_version_max' => '3.9',
    # should this plugin run before others?, 0 is normal, and higher number has high priority
    'plugin_priority' => 0
);

//after installation message, you can remove it, it's not requiered
$kleeja_plugin['kj_download_speed_limit']['first_run']['ar'] = "
تجد خيار تحديد سرعة التحميل في إعدادات كل مجموعة <br><br>
شكراً لاستخدامك الإضافة، قم بمراسلتنا بالأخطاء عند ظهورها على البريد: <br>
info@kleeja.com
";

$kleeja_plugin['kj_download_speed_limit']['first_run']['en'] = "
You will find the ability to change speed limit in each group settings.<Br><br>
Thank you for using our plugin, if you encounter any bugs and errors, contact us: <br>
info@kleeja.com
";

# plugin installation function
$kleeja_plugin['kj_download_speed_limit']['install'] = function ($plg_id) {
    //new options
    $options = array(
        'kj_download_speed_limit_number' =>
            array(
                'value' => '30',
                'html' => configField('kj_download_speed_limit_number'),
                'plg_id' => $plg_id,
                'type' => 'groups',
                'order' => '1',
            ),
    );


    add_config_r($options);


    //new language variables
    add_olang(array(
        'KJ_DOWNLOAD_SPEED_LIMIT_NUMBER' => 'سرعة التحميل (كيلوبايت/ثانية)'
    ),
        'ar',
        $plg_id);

    add_olang(array(
        'KJ_DOWNLOAD_SPEED_LIMIT_NUMBER' => 'Download Speed (KB/Second)',
    ),
        'en',
        $plg_id);
};


//plugin update function, called if plugin is already installed but version is different than current
$kleeja_plugin['kj_download_speed_limit']['update'] = function ($old_version, $new_version) {
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
$kleeja_plugin['kj_download_speed_limit']['uninstall'] = function ($plg_id) {
    //delete options
    delete_config(array(
        'kj_download_speed_limit_number',
    ));


    //delete language variables
    foreach (['ar', 'en'] as $language) {
        delete_olang(null, $language, $plg_id);
    }
};


# plugin functions
$kleeja_plugin['kj_download_speed_limit']['functions'] = array(
    'down_go_page' => function ($args) {
        global $config;

        $givenSize = floatval(trim($config['kj_download_speed_limit_number']));

        if($givenSize === 0){
            return;
        }

        define('TrottleLimit', true);

        $chunksize = round($givenSize * 1024);
        
        return compact('chunksize');
    }
);

