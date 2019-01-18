<?php
# Kleeja Plugin
# kj_ban_admin
# Version: 1.0
# Developer: Kleeja team


# Prevent illegal run
if (!defined('IN_PLUGINS_SYSTEM')) {
    exit();
}


# Plugin Basic Information
$kleeja_plugin['kj_ban_admin']['information'] = array(
    # The casual name of this plugin, anything can a human being understands
    'plugin_title' => array(
            'en' => 'Kleeja Admin Firewall',
            'ar' => 'جدار أمني للوحة كليجا'
    ),
    # Who wrote this plugin?
    'plugin_developer' => 'Kleeja.com',
    # This plugin version
    'plugin_version' => '1.0',
    # Explain what is this plugin, why should I use it?
    'plugin_description' => array(
        'en' => 'Ban a user after so many invalid login attempts to Kleeja control panel',
        'ar' => 'حظر أي مستخدم يحاول الدخول للوحة كليجا بعد عدة محاولات خاطئة'
    ),
    # Min version of Kleeja that's required to run this plugin
    'plugin_kleeja_version_min' => '2.0',
    # Max version of Kleeja that support this plugin, use 0 for unlimited
    'plugin_kleeja_version_max' => '3.9',
    # Should this plugin run before others?, 0 is normal, and higher number has high priority
    'plugin_priority' => 0
);

//after installation message, you can remove it, it's not requiered
$kleeja_plugin['kj_ban_admin']['first_run']['ar'] = "
شكراً لاستخدامك هذه الإضافة، قم بمراسلتنا بالأخطاء عند ظهورها على البريد: <br>
info@kleeja.com
<hr>
<br>
<h3>لاحظ:</h3>
<b>عند حظر عضويتك قم بإزالة الحظر من phpMyAdmin، من جدول klj_stats</b>
";
$kleeja_plugin['kj_ban_admin']['first_run']['en'] = "
Thanks for using this plugin, for bugs reports, contact us at: <br>
info@kleeja.com
<hr>
<br>
<h3>Note:</h3>
<b>If your user account got banned, remove it using phpMyAdmin, from klj_stats table.</b>
";

# Plugin Installation function
$kleeja_plugin['kj_ban_admin']['install'] = function ($plg_id) {

};


//Plugin update function, called if plugin is already installed but version is different than current
$kleeja_plugin['kj_ban_admin']['update'] = function ($old_version, $new_version) {
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
$kleeja_plugin['kj_ban_admin']['uninstall'] = function ($plg_id) {
};


# Plugin functions
$kleeja_plugin['kj_ban_admin']['functions'] = array(

    'admin_login_submit_admin_page' => function ($args) {
                $ERRORS = $args['ERRORS'];
                if(sizeof($ERRORS)){
                    $_SESSION['kj_ban_admin_attemps'] = !empty($_SESSION['kj_ban_admin_attemps'])
                                ? intval($_SESSION['kj_ban_admin_attemps'])+1
                                : 1;
                }

                if(!empty($_SESSION['kj_ban_admin_attemps']) && $_SESSION['kj_ban_admin_attemps'] > 10)
                {
                    global $SQL, $dbprefix, $config;

                    $query	= array(
                        'SELECT'	=> 'ban',
                        'FROM'		=> "{$dbprefix}stats"
                    );

                    $result = $SQL->build($query);

                    $current_ban_data = $SQL->fetch_array($result);
                    $current_ban_data = $current_ban_data['ban'];

                    if(trim($current_ban_data) == ''){
                        $current_ban_data = get_ip();
                    }else{
                        $current_ban_data = rtrim($current_ban_data, '|') . '|'. get_ip();
                    }

                    $update_query	= array(
                        'UPDATE'	=> "{$dbprefix}stats",
                        'SET'		=> "ban='" . $SQL->escape($current_ban_data) . "'"
                    );

                    $SQL->build($update_query);
                    if($SQL->affected())
                    {
                        delete_cache('data_ban');
                    }

                    unset($_SESSION['kj_ban_admin_attemps']);
                    redirect($config['siteurl']);
                }
    }
);

