<?php
# Kleeja Plugin
# language_switch
# Version: 1.0
# Developer: Kleeja team

# Prevent illegal run
if (!defined('IN_PLUGINS_SYSTEM')) {
    exit();
}


# Plugin Basic Information
$kleeja_plugin['language_switch']['information'] = array(
    # The casucal name of this plugin, anything can a human being understands
    'plugin_title' => array(
        'en' => 'Language Switch',
        'ar' => 'تغيير اللغة'
    ),
    # Who wrote this plugin?
    'plugin_developer' => 'Kleeja.com',
    # This plugin version
    'plugin_version' => '1.1',
    # Explain what is this plugin, why should I use it?
    'plugin_description' => array(
        'en' => 'A language switch box in the top of every page',
        'ar' => 'صندوق تغيير لغة في أعلى كل صفحة'
    ),
    # Min version of Kleeja that's requiered to run this plugin
    'plugin_kleeja_version_min' => '2.0',
    # Max version of Kleeja that support this plugin, use 0 for unlimited
    'plugin_kleeja_version_max' => '3.0',
    # Should this plugin run before others?, 0 is normal, and higher number has high priority
    'plugin_priority' => 0
);

//after installation message, you can remove it, it's not requiered
$kleeja_plugin['language_switch']['first_run']['ar'] = "
شكراً لاستخدامك هذه الإضافة قم بمراسلتنا بالأخطاء عند ظهورها على البريد: <br>
info@kleeja.com
";

$kleeja_plugin['language_switch']['first_run']['en'] = "
Thanks for using this plugin, to report bugs contact us: 
<br>
info@kleeja.com
";


# Plugin Installation function
$kleeja_plugin['language_switch']['install'] = function ($plg_id)
{
//    //new language variables
//    add_olang(array(
//
//    ),
//        'ar',
//        $plg_id);
//
//    add_olang(array(
//
//    ),
//        'en',
//        $plg_id);
};


//Plugin update function, called if plugin is already installed but version is different than current
$kleeja_plugin['language_switch']['update'] = function ($old_version, $new_version) {
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
$kleeja_plugin['language_switch']['uninstall'] = function ($plg_id) {
    //delete language variables
//    foreach (array('ar', 'en') as $language) {
//        delete_olang(null, $language, $plg_id);
//    }
};


# Plugin functions
$kleeja_plugin['language_switch']['functions'] = array(

    'Saaheader_func' => function($args){

        global $config;

        $header = $args['header'];


        $available_languages = language_switch_get_languages();
        $available_languages_options = '';

        $current_language = $config['language'];


       foreach ($available_languages as $lang) {
           $available_languages_options .= '<a href="' . $config['siteurl'] . '?lang=' . $lang . '"><img src="' . $config['siteurl'] . 'lang/' . $lang . '/icon_16.png" alt="' . $lang . '" style="padding:1px;' . ($current_language == $lang ? 'border:1px solid blue;' : 'border:1px solid white;') . '" /></a>';
       }


        $lang_switch_html = '<div id="langSwitch" style="position: relative; z-index: 100">' . "\n" .
                                '<div id="langSwitchInner" style="position: absolute;top: 0;'.($current_language == 'ar' ? 'left':'right').': 80px; background-color: white; padding: 3px">' . "\n" .
                                    $available_languages_options . "\n" .
                                '</div>'. "\n" .
                            '</div>';

        $header = preg_replace('/<body([^\>]*)>/i', "<body\\1>\n<!-- language switch -->\n" . $lang_switch_html . "\n<!-- language switch end -->", $header, 1);

        return compact('header');
    },



    'boot_common' => function ($args){
        global $config, $usrcp;

        $current_language = $config['language'];

        $available_languages = language_switch_get_languages();

        if(ig('lang')){
            $chosen_lang = g('lang');
            if(in_array($chosen_lang, $available_languages)){
                $usrcp->kleeja_set_cookie('sllang', $chosen_lang, time() + 3600*24*30);
                $config['language'] = $chosen_lang;
            }
        }else {
            if (($cookie_lang = $usrcp->kleeja_get_cookie('sllang')) !== false) {
                if (in_array($cookie_lang, $available_languages)) {
                    $config['language'] = $cookie_lang;
                }
            }
        }

    }
);



if(!function_exists('language_switch_get_languages')){
    function language_switch_get_languages(){
        $available_languages = array();

        if ($dh = @opendir(PATH . 'lang'))
        {
            while (($file = @readdir($dh)) !== false)
            {
                if(strpos($file, '.') === false && $file != '..' && $file != '.')
                {
                    $available_languages[] = $file;
                }
            }
            @closedir($dh);
        }

        return $available_languages;
    }
}