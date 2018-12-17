<?php
# Kleeja Plugin
# pdf_viewer
# Version: 1.0
# Developer: Kleeja team

# Prevent illegal run
if (!defined('IN_PLUGINS_SYSTEM')) {
    exit();
}


# Plugin Basic Information
$kleeja_plugin['pdf_viewer']['information'] = array(
    # The casucal name of this plugin, anything can a human being understands
    'plugin_title' => array(
        'en' => 'PDF Viewer',
        'ar' => 'عارض بي دي أف'
    ),
    # Who wrote this plugin?
    'plugin_developer' => 'Kleeja.com',
    # This plugin version
    'plugin_version' => '1.1',
    # Explain what is this plugin, why should I use it?
    'plugin_description' => array(
        'en' => 'Integrate a PDF viewer in download page',
        'ar' => 'عرض عارض ملفات بي دي اف في صفحة التحميل'
    ),
    # Min version of Kleeja that's requiered to run this plugin
    'plugin_kleeja_version_min' => '2.0',
    # Max version of Kleeja that support this plugin, use 0 for unlimited
    'plugin_kleeja_version_max' => '3.0',
    # Should this plugin run before others?, 0 is normal, and higher number has high priority
    'plugin_priority' => 10
);

//after installation message, you can remove it, it's not required
$kleeja_plugin['pdf_viewer']['first_run']['ar'] = "
شكراً لاستخدامك هذه الإضافة قم بمراسلتنا بالأخطاء عند ظهورها على البريد: <br>
info@kleeja.com
";

$kleeja_plugin['pdf_viewer']['first_run']['en'] = "
Thanks for using this plugin, to report bugs contact us: 
<br>
info@kleeja.com
";


# Plugin Installation function
$kleeja_plugin['pdf_viewer']['install'] = function ($plg_id)
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
$kleeja_plugin['pdf_viewer']['update'] = function ($old_version, $new_version) {
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
$kleeja_plugin['pdf_viewer']['uninstall'] = function ($plg_id) {
    //delete language variables
//    foreach (array('ar', 'en') as $language) {
//        delete_olang(null, $language, $plg_id);
//    }
};


# Plugin functions
$kleeja_plugin['pdf_viewer']['functions'] = array(


    'style_parse_func' => function($args) {
        global $config;


        if($args['template_name'] == 'download') {


            $x = PHP_EOL . '<IF NAME="show_pdf_viewer_code">
                    <div style="clear: both;"></div>
                   <div id="pdfViewer" style="margin-top: 20px; position: relative; height: 0; overflow: hidden; padding-bottom: 80%;">
                        <iframe style="position: absolute; top:0; left: 0; width: 100%; height: 100%;" src = "' . $config['siteurl'] . 'plugins/pdf_viewer/v/#{pdf_path}" allowfullscreen webkitallowfullscreen></iframe>
                  </div> 
                  </IF>';


            $html = $args['html'] . $x;

            return compact('html');
        }
    },

    'b4_showsty_downlaod_id_filename' => function($args){


        $file_info = $args['file_info'];

        $show_pdf_viewer_code = false;

        $type_mimes = array(
            'pdf'
        );

        if(in_array(strtolower($file_info['type']), $type_mimes)){

            $show_pdf_viewer_code = true;

            global $config;

            $pdf_path =  $config['siteurl'] . "{$file_info['folder']}/{$file_info['name']}";

            is_array($plugin_run_result = Plugins::getInstance()->run('plugin:pdf_viewer:do_display', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook
        }

        return compact('show_pdf_viewer_code', 'pdf_path');
    }
);

