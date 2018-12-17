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
	'plugin_title' => __('Kleeja X SendFile', 'klj_x_sendfile'),
	# Who wrote this plugin?
	'plugin_developer' => 'Kleeja.com',
	# This plugin version
	'plugin_version' => '1.0',
	# Explain what is this plugin, why should I use it?
	'plugin_description' => __('Enable x-sendfile or X-Accel-Redirect for both Apache or Nginx for better performance.', 'klj_x_sendfile'),

	# Min version of Kleeja that's requiered to run this plugin
	'plugin_kleeja_version_min' => '2.0',
	# Max version of Kleeja that support this plugin, use 0 for unlimited
	'plugin_kleeja_version_max' => '0',
	# Should this plugin run before others?, 0 is normal, and higher number has high priority
	'plugin_priority' => 0
);

//after installation message, you can remove it, it's not requiered
$kleeja_plugin['kj_x_sendfile']['first_run'] = __("Thanks for using our klj_x_sendfile plugin. To report bugs contact us on: \ninfo@kleeja.com");

# Plugin Installation function
$kleeja_plugin['kj_x_sendfile']['install'] = function()
{
	//new options
	$options = array(
			'kj_x_sendfile_enable' =>
				array(
					'title' => 'Enable kj_x_sendfile',
					'value'=> '0',
					'plg_name' => 'kj_x_sendfile',
					'field' => 'yesno',
				),
			'kj_x_sendfile_type' =>
				array(
					'title' => 'Current Server for your hosting',
					'value'=> 'apache',
					'plg_name' => 'kj_x_sendfile',
					'field' => 'select',
				),
	);

	add_config($options);
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
$kleeja_plugin['kj_x_sendfile']['uninstall'] = function()
{
	//delete options
	delete_config(array(
		'kj_x_sendfile_enable',
		'kj_x_sendfile_type'
	));

};



# Plugin functions
$kleeja_plugin['kj_x_sendfile']['functions'] = array(

	//select options for kj_x_sendfile_type
	'option_select_values_func' => function($args){
		if($args[0] == 'kj_x_sendfile_type')
		{
			$args[2] = '<option ' . ($args[1] == 'apache' ? 'selected="selected" ' : '') . 'value="apache">Apache</option>' . "\n" .
			'<option ' . ($args[1] == 'nginx' ? 'selected="selected" ' : '') . 'value="nginx">Nginx</option>';
		}

		//$args[2] is return by reference
	},

	'after_endforeach_admin_page' => function(){
		add_to_adm_menu('kj_x_sendfile', __('kljXsendfile Settings', 'klj_x_sendfile'));
	},
	'do_page_before_headers_set' => function(){
		global $path_file, $config;

		if($config['kj_x_sendfile_enable'] == 0)
			return;

		if($config['kj_x_sendfile_type'] == 'apache')
			header('X-Sendfile: '. $path_file);
		else
			header('X-Accel-Redirect: ' . $path_file);

		// die();
	},

	'do_page_headers_set' => function(){
		//die();
	},
	'adm_xoptions_titles' => function($args){
		$args[0] = array_merge($args[0], array(
			'kj_x_sendfile_enable' => __('Enable x-Sendfile/X-Accel-Redirect: header', 'klj_x_sendfile'),
			'kj_x_sendfile_type' => __('Current Server type for your hosting', 'klj_x_sendfile'),
		));
	},
	'end_common' => function(){
		get_lang('klj_x_sendfile', dirname(__FILE__) . '/languages', true);
	}
);
