<?php
# Kleeja Plugin
# video_player
# Version: 1.0
# Developer: Kleeja team

# Prevent illegal run
if (!defined('IN_PLUGINS_SYSTEM')) {
    exit();
}


# Plugin Basic Information
$kleeja_plugin['video_player']['information'] = array(
    # The casucal name of this plugin, anything can a human being understands
    'plugin_title' => array(
        'en' => 'Video & Audio Player',
        'ar' => 'مشغل فيديو وصوت'
    ),
    # Who wrote this plugin?
    'plugin_developer' => 'Kleeja.com',
    # This plugin version
    'plugin_version' => '1.1',
    # Explain what is this plugin, why should I use it?
    'plugin_description' => array(
        'en' => 'Integrate a video player in download page',
        'ar' => 'عرض مشغل فيديو في صفحة التحميل'
    ),
    # Min version of Kleeja that's requiered to run this plugin
    'plugin_kleeja_version_min' => '2.0',
    # Max version of Kleeja that support this plugin, use 0 for unlimited
    'plugin_kleeja_version_max' => '3.9',
    # Should this plugin run before others?, 0 is normal, and higher number has high priority
    'plugin_priority' => 10
);

//after installation message, you can remove it, it's not required
$kleeja_plugin['video_player']['first_run']['ar'] = "
شكراً لاستخدامك هذه الإضافة قم بمراسلتنا بالأخطاء عند ظهورها على البريد: <br>
info@kleeja.com
";

$kleeja_plugin['video_player']['first_run']['en'] = "
Thanks for using this plugin, to report bugs contact us: 
<br>
info@kleeja.com
";


# Plugin Installation function
$kleeja_plugin['video_player']['install'] = function ($plg_id)
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
$kleeja_plugin['video_player']['update'] = function ($old_version, $new_version) {
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
$kleeja_plugin['video_player']['uninstall'] = function ($plg_id) {
    //delete language variables
//    foreach (array('ar', 'en') as $language) {
//        delete_olang(null, $language, $plg_id);
//    }
};


# Plugin functions
$kleeja_plugin['video_player']['functions'] = array(

    'Saaheader_links_func' => function($args){

        $extra = $args['extra'];

        $header_codes = '<link href="//vjs.zencdn.net/6.2.7/video-js.css" rel="stylesheet">' . "\n" .
                        '<script src="//vjs.zencdn.net/ie8/1.1.2/videojs-ie8.min.js"></script>' . "\n";


        $extra .= $header_codes;

        return compact('extra');
    },

    'print_Saafooter_func' => function($args){
        $footer = $args['footer'];

        $footer = str_replace('</body>', "<script src=\"//vjs.zencdn.net/6.2.7/video.js\"></script>\n</body>", $footer);
        return compact('footer');
    },

    'style_parse_func' => function($args) {
        global $config;


        if($args['template_name'] == 'download') {

            $x = PHP_EOL . '<IF NAME="show_video_player_code">
                    <div style="clear: both;"></div>
                    <div class="videoplayerbox" style="margin-top: 20px">
                    <video id="my-video" class="video-js vjs-16-9" controls preload="auto" width="640" height="264"
               data-setup="{}" style="margin: 0 auto;">
                    <source src="{video_path}#t=0.1" type=\'{video_mime_type}\'>
                    <p class="vjs-no-js">
                      To view this video please enable JavaScript, and consider upgrading to a web browser that
                      <a href="http://videojs.com/html5-video-support/" target="_blank">supports HTML5 video</a>
                    </p>
                  </video>
                  </div>
                  </IF>';

            $x .= PHP_EOL . '<IF NAME="show_audio_player_code">
                    <div style="clear: both;"></div>
                    <div class="videoplayerbox audiobox" style="margin-top: 20px">
                    <audio id="my-video" class="video-js vjs-16-9" controls preload="auto" width="640" height="264"
               data-setup="{}" style="margin: 0 auto;">
                    <source src="{video_path}#t=0.1" type=\'{video_mime_type}\'>
                    <p class="vjs-no-js">
                      To listen to this audio please enable JavaScript, and consider upgrading to a web browser that
                      <a href="http://videojs.com/html5-video-support/" target="_blank">supports HTML5 video</a>
                    </p>
                  </audio>
                  </div>
                  </IF>';

            $html = $args['html'] . $x;

            return compact('html');
        }
    },

    'b4_showsty_downlaod_id_filename' => function($args){


        $file_info = $args['file_info'];


        $show_video_player_code = false;
        $show_audio_player_code = false;
        $video_path = '';
        $video_thumb = '';
        $video_mime_type = '';

        $type_mimes = array(
            'mp4' => 'video/mp4',
            'webm' => 'video/webm',
//            'webma' => 'audio/webm',
            'ogg' => 'video/ogg',
            'ogv' => 'video/ogg',
            '3gp' => 'video/3gp',
            'flv' => 'video/x-flv',
            'oga' => 'audio/ogg',
            'mp3' => 'audio/mp3',
            'wav' => 'audio/wav',
            'flac' => 'audio/flac',
        );

        if(in_array(strtolower($file_info['type']), array_keys($type_mimes))){

                if(in_array(strtolower($file_info['type']), array('mp3', 'oga', 'wav', 'flac'))){
                    $show_audio_player_code = true;
                }else{
                    $show_video_player_code = true;
                }


            $video_path =  "./{$file_info['folder']}/{$file_info['name']}";

            $video_mime_type = $type_mimes[$file_info['type']];

            is_array($plugin_run_result = Plugins::getInstance()->run('plugin:video_player:do_display', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook

        }

        return compact('show_video_player_code', 'video_path', 'video_thumb', 'video_mime_type', 'show_audio_player_code');
    }
);

