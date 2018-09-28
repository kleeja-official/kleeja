<?php
# kleeja plugin
# kj_recaptcha
# version: 1.3
# developer: kleeja team


# prevent illegal run
if (!defined('IN_PLUGINS_SYSTEM')) {
    exit();
}


# plugin basic information
$kleeja_plugin['kj_recaptcha']['information'] = array(
    # the casucal name of this plugin, anything can a human being understands
    'plugin_title' => array(
        'en' => 'KJ reCaptcha',
        'ar' => 'كليجا ريكابتشا'
    ),
    # who wrote this plugin?
    'plugin_developer' => 'kleeja.com',
    # this plugin version
    'plugin_version' => '1.3',
    # explain what is this plugin, why should i use it?
    'plugin_description' => array(
        'en' => 'Add reCaptcha to Kleeja',
        'ar' => 'إضافة ريكابتشا لكليجا'
    ),
    # min version of kleeja that's required to run this plugin
    'plugin_kleeja_version_min' => '2.0',
    # max version of kleeja that support this plugin, use 0 for unlimited
    'plugin_kleeja_version_max' => '3.0',
    # should this plugin run before others?, 0 is normal, and higher number has high priority
    'plugin_priority' => 0
);

//after installation message, you can remove it, it's not requiered
$kleeja_plugin['kj_recaptcha']['first_run']['ar'] = "
شكراً لاستخدامك إضافة الريكابتشا لكليجا، قم بمراسلتنا بالأخطاء عند ظهورها على البريد: <br>
info@kleeja.com
<br>
للحصول على مفتاح وكود ريكابتشا السري سجل في موقعهم:
<a href='https://www.google.com/recaptcha'>google.com/reCaptcha</a>.
<br>
ثم أضفها في إعدادات -> خيارات الريكابتشا
";

$kleeja_plugin['kj_recaptcha']['first_run']['en'] = "
Thank you for using our plugin, if you encounter any bugs and errors, contact us: <br>
info@kleeja.com
<br>
to get the reCaptcha sitekey and secret code, visit: 
<a href='https://www.google.com/recaptcha'>google.com/reCaptcha</a>.
<br>
then configure this plugin from: settings -> reCaptcha settings
";

# plugin installation function
$kleeja_plugin['kj_recaptcha']['install'] = function ($plg_id) {
    //new options
    $options = array(
        'kj_recaptcha_sitekey' =>
            array(
                'value' => '',
                'html' => configField('kj_recaptcha_sitekey'),
                'plg_id' => $plg_id,
                'type' => 'kj_recaptcha'
            ),
        'kj_recaptcha_secret' =>
            array(
                'value' => '',
                'html' => configField('kj_recaptcha_secret'),
                'plg_id' => $plg_id,
                'type' => 'kj_recaptcha'
            ),
        'kj_recaptcha_invisible' =>
            array(
                'value' => '0',
                'html' => configField('kj_recaptcha_invisible', 'yesno'),
                'plg_id' => $plg_id,
                'type' => 'kj_recaptcha'
            ),
    );


    add_config_r($options);


    //new language variables
    add_olang(array(
        'KJ_RECAPTCHA_SITEKEY' => 'مفتاح الرياكبتشا | sitekey',
        'KJ_RECAPTCHA_SECRET' => 'الكود السري للريكابتشا | secret',
        'KJ_RECAPTCHA_INVISIBLE' => 'نوع الريكابتشا: مخفية invisible',
        'CONFIG_KLJ_MENUS_KJ_RECAPTCHA' => 'خيارات ريكابتشا',
    ),
        'ar',
        $plg_id);

    add_olang(array(
        'KJ_RECAPTCHA_SITEKEY' => 'reCaptcha sitekey',
        'KJ_RECAPTCHA_SECRET' => 'reCaptcha secret',
        'KJ_RECAPTCHA_INVISIBLE' => 'reCaptcha Type: Invisible',
        'CONFIG_KLJ_MENUS_KJ_RECAPTCHA' => 'reCaptcha Settings',
    ),
        'en',
        $plg_id);
};


//plugin update function, called if plugin is already installed but version is different than current
$kleeja_plugin['kj_recaptcha']['update'] = function ($old_version, $new_version) {

    $plg_id = Plugins::getInstance()->installed_plugin_info('kj_recaptcha');

    if(version_compare($old_version, '1.3', '<')){
        $options = array(
            'kj_recaptcha_invisible' =>
                array(
                    'value' => '0',
                    'html' => configField('kj_recaptcha_invisible', 'yesno'),
                    'plg_id' => $plg_id,
                    'type' => 'kj_recaptcha'
                ),
        );


        add_config_r($options);


        //new language variables
        add_olang(array(
            'KJ_RECAPTCHA_INVISIBLE' => 'نوع الريكابتشا: مخفية invisible',
        ),
            'ar',
            $plg_id);

        add_olang(array(
            'KJ_RECAPTCHA_INVISIBLE' => 'reCaptcha Type: Invisible',
        ),
            'en',
            $plg_id);
     }


    //
    // if(version_compare($old_version, '0.6', '<')){
    // 	//... update to 0.6
    // }

    //you could use update_config, update_olang
};


# plugin uninstalling, function to be called at uninstalling
$kleeja_plugin['kj_recaptcha']['uninstall'] = function ($plg_id) {
    //delete options
    delete_config(array(
        'kj_recaptcha_sitekey',
        'kj_recaptcha_secret',
        'kj_recaptcha_invisible'
    ));


    foreach (array('ar', 'en') as $language) {
        delete_olang(null, $language, $plg_id);
    }
};


# plugin functions
$kleeja_plugin['kj_recaptcha']['functions'] = array(
    'Saaheader_links_func' => function ($args) {
        global $config;
        $extra = $args['extra'] . "\n" . getReCaptchaInputHeadHtml();
        return compact('extra');
    },

    'before_display_template_admin_page' => function ($args) {
        global $config;
        $extra_header_admin_login = $args['extra_header_admin_login'] . "\n" . getReCaptchaInputHeadHtml();
        $show_captcha = false;
        return compact('extra_header_admin_login', 'show_captcha');
    },

    'style_parse_func' => function($args) {
        global $config;


        if(in_array($args['template_name'], array('call', 'report', 'register', 'login')) && defined('reCaptcha_all')){
            $html = preg_replace(
                '/(<IF\s{1,}NAME="config.enable_captcha==1">)/',
                getReCaptchaInputHtml().'$1',
                $args['html']);

            return compact('html');
        }else if($args['template_name'] == 'index_body' && defined('reCaptcha_index')){
                $html = preg_replace(
                    '/(<IF\s{1,}NAME="config.safe_code">)/',
                    getReCaptchaInputHtml().'$1',
                    $args['html']);
            return compact('html');
        }else if($args['template_name'] == 'admin_login'){

                $html = preg_replace(
                    '/(<IF\s{1,}NAME="show_captcha">)/',
                    getReCaptchaInputHtml().'$1',
                    $args['html']);

            unset($_SESSION['SHOW_CAPTCHA']);
            return compact('html');
        }
    },

    'admin_login_submit' => function($args){
        global $lang;

        if(!isReCaptchaValid()) {
            $ERRORS = $args['ERRORS'];
            $ERRORS['recaptcha'] = $lang['WRONG_VERTY_CODE'];
            return compact('ERRORS');
        }
    },

    'end_common' => function($args){
        global  $config;
        if($config['safe_code'] == 1){
            define('reCaptcha_index', true);
        }

        if($config['enable_captcha'] == 1){
            define('reCaptcha_all', true);
        }

        $config['enable_captcha'] = 0;
        $config['safe_code'] = 0;
    },

    'submit_report_go_page' => function ($args){
        global $lang;

        if(!isReCaptchaValid()) {
            $ERRORS = $args['ERRORS'];
            $ERRORS['recaptcha'] = $lang['WRONG_VERTY_CODE'];
            return compact('ERRORS');
        }
    },

    'register_submit' => function ($args){
        global $lang;

        if(!isReCaptchaValid()) {
            $ERRORS = $args['ERRORS'];
            $ERRORS['recaptcha'] = $lang['WRONG_VERTY_CODE'];
            return compact('ERRORS');
        }
    },


    'login_after_submit' => function ($args){
        global $lang;

        if(!isReCaptchaValid()) {
            $ERRORS = $args['ERRORS'];
            $ERRORS['recaptcha'] = $lang['WRONG_VERTY_CODE'];
            return compact('ERRORS');
        }
    },

    'submit_call_go_page' => function($args){
        global $lang;

        if(!isReCaptchaValid()) {
            $ERRORS = $args['ERRORS'];
            $ERRORS['recaptcha'] = $lang['WRONG_VERTY_CODE'];
            return compact('ERRORS');
        }
    },

    'defaultUploader_upload_1st' => function($args){
        global $lang;

        if(!defined('reCaptcha_index')){
            return null;
        }

        $captcha_enabled = true;

        return compact('captcha_enabled');

    },


    'kleeja_check_captcha_func' => function($args){

        if(defined('IN_REAL_INDEX') || defined('IN_ADMIN')){
            $return = isReCaptchaValid();
            return compact('return');
        }



        if(!defined('IN_ADMIN')) {
            $return = true;
            return compact('return');
        }

    },

    'ftpUploader_upload_1st' => function($args){
        global $lang;

        if(!defined('reCaptcha_index')){
            return null;
        }

        $captcha_enabled = true;

        return compact('captcha_enabled');
    },

);

//


/**
 * special functions
 */

if(! function_exists('getReCaptchaInputHtml'))
{
    function getReCaptchaInputHtml()
    {
        global $config;

        if($config['kj_recaptcha_invisible'] == 1)
        {
           return '<div id="aarecaptcha" style="margin: 10px 0; text-align: center; max-width: 255px;"></div>';
        }
        else
        {
           return '<div class="g-recaptcha" data-sitekey="' . $config['kj_recaptcha_sitekey'] . '" style="margin: 10px 0; text-align: center; max-width: 255px;"></div>';
        }

    }
}

if(! function_exists('getReCaptchaInputHeadHtml'))
{
    function getReCaptchaInputHeadHtml()
    {
        global $config;

        if($config['kj_recaptcha_invisible'] == 1)
        {
           return '<script src="https://www.google.com/recaptcha/api.js?onload=onloadCallback&render=explicit&hl=' . $config['language'] . '"></script>'.
               '<script>
                
                var disableSubmit = function(state){
                    
                    var children = document.getElementsByTagName("form").childNodes;
                    
                    var parent = null;
                    
                    for(child in children){
                         if (children.hasOwnProperty(child)) {
                                if(child.getElementById("aarecaptcha") !== null){
                                    parent = child;
                                }
                            }
                    }
                    
                    
                    if(parent === null){
                        return;
                    }

           
                    parent.querySelectorAll("input[type=submit]").disabled = state;
                    
                };
                
               var onloadCallback = function(){
                        
                   disableSubmit(true);
                  
                    grecaptcha.render("aarecaptcha", {
                        "sitekey" : "'.$config['kj_recaptcha_sitekey'].'",
                        "badge" : "inline",
                        "size" : "invisible",
                        "callback" : function(token){
                              disableSubmit(false);
                           }
                      });
                    
                    
                     grecaptcha.execute();
               };
               
                              

                 </script>';
        }
        else
        {
           return '<script src="https://www.google.com/recaptcha/api.js?hl=' . $config['language'] . '"></script>';
        }

    }
}

if (!function_exists('isReCaptchaValid')) {
    function isReCaptchaValid()
    {
        global $config;

        if(empty($config['kj_recaptcha_sitekey']) || empty($config['kj_recaptcha_secret'])){
            return true;
        }

        if (!ip('g-recaptcha-response') || empty(p('g-recaptcha-response'))) {
            return false;
        }

        try {
            $data = array(
                'secret'   => $config['kj_recaptcha_secret'],
                'response' => p('g-recaptcha-response'),
                'remoteip' => $_SERVER['REMOTE_ADDR']
            );


            $url = 'https://www.google.com/recaptcha/api/siteverify?' . http_build_query($data);

            $result = fetch_remote_file($url);
//
//            if (function_exists('curl_version')) {
//                $curl = curl_init($url . "?" . http_build_query($data));
//                curl_setopt($curl, CURLOPT_HEADER, false);
//                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
//                curl_setopt($curl, CURLOPT_TIMEOUT, 1);
//                curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
//                $result = curl_exec($curl);
//
//            } else {
////                $options = array(
////                    'http' => array(
////                        'header' => "Content-type: application/x-www-form-urlencoded\r\n",
////                        'method' => 'POST',
////                        'content' => http_build_query($data)
////                    )
////                );
////
////                $context = stream_context_create($options);
////
////                //TODO use our function for fallback
//                $result = file_get_contents($url, false, $context);
//            }


            if (empty($result) || is_null($result)) {
                return false;
            }

            return json_decode($result)->success;
        }
        catch (Exception $e) {
            return null;
        }
    }
}
