<?php
# Kleeja Plugin
# kj_x_sendfile
# Version: 1.0
# Developer: Kleeja team

# Prevent illegal run
if (!defined('IN_PLUGINS_SYSTEM'))
{
	exit();
}

//
// this plugin is under heavy testing, 
// you should also test it and send your test results to us. (github.com/awssat/kleeja)
//



# Plugin Basic Information
$kleeja_plugin['kj_x_sendfile']['information'] = array(
	# The casucal name of this plugin, anything can a human being understands
	'plugin_title' => array(
        'en' => 'Kleeja X SendFile',
        'ar' => 'كليجا إكس سيند فايل'
    ),
	# Who wrote this plugin?
	'plugin_developer' => 'Kleeja.com',
	# This plugin version
	'plugin_version' => '1.0',
	# Explain what is this plugin, why should I use it?
	'plugin_description' => array(
        'en' => 'Enable x-sendfile or X-Accel-Redirect for both Apache or Nginx for better performance.',
        'ar' => 'كليجا إكس سيند فايل'
	),
	# Min version of Kleeja that's requiered to run this plugin
	'plugin_kleeja_version_min' => '3.0',
	# Max version of Kleeja that support this plugin, use 0 for unlimited
	'plugin_kleeja_version_max' => '3.9',
	# Should this plugin run before others?, 0 is normal, and higher number has high priority
	'plugin_priority' => 0
);

//after installation message, you can remove it, it's not requiered
$kleeja_plugin['kj_x_sendfile']['first_run']['en'] = "
Thanks for using our klj_x_sendfile plugin. 
\n To report bugs reach us on: \nhttps://github.com/awssat/kleeja/issues
\n\n
You can find configurations on settings->kleeja x sendfile.

\n\n
This plugin require that xsendfile is enabled on either Apache or Nginx:\n
- Apache: https://tn123.org/mod_xsendfile/ \n
- Nginx: https://www.nginx.com/resources/wiki/start/topics/examples/xsendfile/

\n\n
Do NOT use this plugin if you are not certain of your server configuration.
";
$kleeja_plugin['kj_x_sendfile']['first_run']['ar'] = "
شكراً لإستخدام إضافة كليجا إكس سيند فايل. للإبلاغ عن الأخطاء : \n
https://github.com/awssat/kleeja/issues
\n\n
You can find configurations on settings->kleeja x sendfile.

\n\n
لاحظ أن هذه الإضافة تتطلب وجود sendfile مفعل على إما خادم انجين اكس او اباتشي:\n
- Apache: https://tn123.org/mod_xsendfile/ \n
- Nginx: https://www.nginx.com/resources/wiki/start/topics/examples/xsendfile/

\n\n
لا تستخدم هذه الإضافة إذا كنت غير متأكد من إعدادات خادمك وتوفر المتطلبات.
";

# Plugin Installation function
$kleeja_plugin['kj_x_sendfile']['install'] = function($plg_id)
{
	//new options
	$options = array(
			'kj_x_sendfile_enable' =>
				array(
					'value'=> '0',
					'html' => configField('kj_x_sendfile_enable', 'yesno'),
					'plg_id' => $plg_id,
					'type' => 'kj_x_sendfile',
				),
			'kj_x_sendfile_type' =>
				array(
					'value'=> 'apache',
					'html' => configField('kj_x_sendfile_type', 'select', ['Apache' => 'apache', 'Nginx' => 'nginx']),
					'plg_id' => $plg_id,
					'type' => 'kj_x_sendfile',
				),
	);

	add_config_r($options);

	    //new language variables
    add_olang(array(
        'KJ_X_SENDFILE_ENABLE' => 'تفعيل هيدر x-Sendfile/X-Accel-Redirect',
		'KJ_X_SENDFILE_TYPE' => 'نوع الخادم',
		'CONFIG_KLJ_MENUS_KJ_X_SENDFILE' => 'كليجا إكس سيند فايل',
    ),
        'ar',
        $plg_id);

    add_olang(array(
        'KJ_X_SENDFILE_ENABLE' => 'Enable x-Sendfile/X-Accel-Redirect',
		'KJ_X_SENDFILE_TYPE' => 'Current Server for your hosting',
		'CONFIG_KLJ_MENUS_KJ_X_SENDFILE' => 'Kleeja X Sendfile',
    ),
        'en',
		$plg_id);
};


//Plugin update function, called if plugin is already installed but version is different than current
$kleeja_plugin['kj_x_sendfile']['update'] = function($old_version, $new_version)
{
	// if(version_compare($old_version, '0.5', '<')){
	// 	//... update to 0.5
	// }
	//
	// if(version_compare($old_version, '0.6', '<')){
	// 	//... update to 0.6
	// }
};


# Plugin Uninstallation, function to be called at unistalling
$kleeja_plugin['kj_x_sendfile']['uninstall'] = function($plg_id)
{
	//delete options
	delete_config(array(
		'kj_x_sendfile_enable',
		'kj_x_sendfile_type'
	));

    //delete language variables
    foreach (['ar', 'en'] as $language) {
        delete_olang(null, $language, $plg_id);
    }
};



# Plugin functions
$kleeja_plugin['kj_x_sendfile']['functions'] = array(

	'do_page_before_headers_set' => function($args){
		global $config;

		if($config['kj_x_sendfile_enable'] == 0)
			return;

		if($config['kj_x_sendfile_type'] == 'apache')
			header('X-Sendfile: ' . $args['path_file']);
		else
			header('X-Accel-Redirect: ' . ltrim($args['path_file'], '.'));
	}
);
