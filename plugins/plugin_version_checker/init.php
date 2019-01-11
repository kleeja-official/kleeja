<?php
# kleeja plugin
# 
# version: 1.0
# developer: Mitan Omar

# prevent illegal run
if (!defined('IN_PLUGINS_SYSTEM')) {
    exit;
}

# plugin basic information
$kleeja_plugin['plugin_version_checker']['information'] = array(
    # the casual name of this plugin, anything can a human being understands
    'plugin_title' => array(
        'en' => 'Plugin Version Checker',
        'ar' => 'فاحص اصدار الاضافات'
    ),
    # who wrote this plugin?
    'plugin_developer' => 'Mitan Omar',
    # this plugin version
    'plugin_version' => '1.0',
    /*
     * plugin_check_version_link : if your plugin is not hosted in kleeja packages ,
     * you can write a link to get the last version from it .
     * you dont need to write your scource code if you have a paid version from your plugin
     * you can only write : 
                            'plugin_version' => '1.0',
     * and kleeja will read it .
     * if you did not select it , we will check from kleeja github link
     */
    'plugin_check_version_link' => 'https://raw.githubusercontent.com/MitanOmar/Plugin_Version_Checker/master/init.php',
    # explain what is this plugin, why should i use it?
    'plugin_description' => array(

        'en' => '· Checks the version of the plugins and identifies the plugins that need to be updated',

        'ar' => 'فاحص لاصدار الاضافات و تحديد الاضافات التي تحتاج الى تحديث'
    ),

    # min version of kleeja that's required to run this plugin
    'plugin_kleeja_version_min' => '2.4',
    # max version of kleeja that support this plugin, use 0 for unlimited
    'plugin_kleeja_version_max' => "3.5",
    # should this plugin run before others?, 0 is normal, and higher number has high priority
    'plugin_priority' => 0
);

//after installation message, you can remove it, it's not requiered
$kleeja_plugin['plugin_version_checker']['first_run']['ar'] = "
شكراً لاستخدامك هذه الإضافة قم بمراسلتنا بالأخطاء عند ظهورها على الرابط: <br>
https://github.com/awssat/kleeja/issues
<hr>
<br>
<h3>لاحظ:</h3>
<b>الاضافة تقوم بالتحقق من الاضافات الموجودة لديك ( المفعلة و غير المفعلة )</b>
<br>
<b>تجد الإضافة في صفحة: فحص عن تحديثات-> تحقق من تحديث الاضافات</b>
";

$kleeja_plugin['plugin_version_checker']['first_run']['en'] = "
Thanks for using this plugin, to report bugs contact us: 
    <br>
    https://github.com/awssat/kleeja/issues
    <hr>
    <br>
    <h3>Note:</h3>
    <b>The plugin checks your existing plugins (active and inactive)</b>
    <br>
    <b>You can find the plugin at: Check for updates -> Check Plugins Update</b>
";

# plugin installation function
$kleeja_plugin['plugin_version_checker']['install'] = function ($plg_id) {

    // add language varibles
    add_olang(array(
        'CHECK_PLUGIN_UPDATE' => 'Check Plugins Update',
        'ERR_CONN' => 'Error in the connection with github , please try later',
        'U_PLG_OK' => 'you have the last version .',
        'U_PLG_NEW' => 'Error : your version is newer from our version , please contact us .',
        'U_PLG_OLD' => 'Error : you have a old version please update it . ',
        'PLG_GITHUB_ERR' => 'Error : we did not find this plugin in github .',
        'NO_INIT_FILE' => 'Error : we did not find ( init.php ) file in the plugin folder',
        'U_VERSION' => 'your version',
        'AP_VERSION' => 'Approved version',
    ),
        'en',
        $plg_id);


    add_olang(array(
        'CHECK_PLUGIN_UPDATE' => 'تحقق من تحديث الاضافات',
        'ERR_CONN' => 'حدث خطأ في الاتصال بـ github ، يرجى المحاولة لاحقًا',
        'U_PLG_OK' => 'لديك الإصدار الأخير.',
        'U_PLG_NEW' => 'خطأ: الإصدار الخاص بك أحدث من إصدارنا ، يرجى الاتصال بنا.',
        'U_PLG_OLD' => 'خطأ: لديك نسخة قديمة , يرجى تحديثها.',
        'PLG_GITHUB_ERR' => 'خطأ: لم نعثر على هذا الاضافة في github.',
        'NO_INIT_FILE' => 'خطأ: لم نعثر على ملف (init.php) في مجلد الاضافة',
        'U_VERSION' => 'الاصدار الخاص بك',
        'AP_VERSION' => 'الاصدار المعتمد',
    ),
        'ar',
        $plg_id);


    
};


//plugin update function, called if plugin is already installed but version is different than current
$kleeja_plugin['plugin_version_checker']['update'] = function ($old_version, $new_version) {
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
$kleeja_plugin['plugin_version_checker']['uninstall'] = function ($plg_id) {

    delete_olang(null, null, $plg_id);

};


# plugin functions
$kleeja_plugin['plugin_version_checker']['functions'] = array(

    'require_admin_page_end_p_check_update' => function ($args) {

        global $olang;
 // print_r($lang);
        $stylee = 'check_plugin_update';

        $styleePath = dirname(__FILE__);

        $go_menu = $args["go_menu"];

        $current_smt = $args["current_smt"];

        $go_menu["smt"] = array('name'=> $olang["CHECK_PLUGIN_UPDATE"], 'link'=> basename(ADMIN_PATH) . '?cp=p_check_update&amp;smt=check_plugin_update', 'goto'=>'check_plugin_update', 'current'=> $current_smt == 'check_plugin_update');
    ///////////////////////////////

if ($args["_GET"]["smt"] == "check_plugin_update") {


    $check_github_connection = fetch_remote_file("https://raw.githubusercontent.com/awssat/kleeja/master/includes/version.php");

    if ( !$check_github_connection ) {

        $connection_error = true;

        $output = null;

    }else {

        require_once dirname(__FILE__) . '/functions.php';

        $connection_error = false;

        $hosted_plugin = get_hosted_plugin( "../" . KLEEJA_PLUGINS_FOLDER );

        $plugin_initFile = get_plugin_init_file( $hosted_plugin , "../" . KLEEJA_PLUGINS_FOLDER );
        
        $plugin_init_github_link = plugin_init_github_link( $plugin_initFile );
        
        $check_plugin_version = check_plugin_version( $plugin_init_github_link );

        $output = $check_plugin_version ;

        
    }

}
    /////////////////////////////////


    return compact("go_menu" , "stylee" , "styleePath" , "output" , "connection_error");   



    },

);

