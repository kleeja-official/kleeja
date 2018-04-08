<?php
# kleeja plugin
# kj_meta_seo
# version: 1.0
# developer: kleeja team

# prevent illegal run
if (!defined('IN_PLUGINS_SYSTEM')) {
    exit();
}


# plugin basic information
$kleeja_plugin['kj_meta_seo']['information'] = array(
    # the casucal name of this plugin, anything can a human being understands
    'plugin_title' => array(
        'en' => 'KJ Meta SEO',
        'ar' => 'ميتا سيو'
    ),
    # who wrote this plugin?
    'plugin_developer' => 'kleeja.com',
    # this plugin version
    'plugin_version' => '1.0',
    # explain what is this plugin, why should i use it?
    'plugin_description' => array(
        'en' => 'Meta fields plugin to enhance SEO for Kleeja',
        'ar' => 'إضافة الميتا الدسكربشن و الكييورزد لكليجا'
    ),
    # min version of kleeja that's required to run this plugin
    'plugin_kleeja_version_min' => '2.0',
    # max version of kleeja that support this plugin, use 0 for unlimited
    'plugin_kleeja_version_max' => '3.0',
    # should this plugin run before others?, 0 is normal, and higher number has high priority
    'plugin_priority' => 0
);

//after installation message, you can remove it, it's not requiered
$kleeja_plugin['kj_meta_seo']['first_run']['ar'] = "
شكراً لاستخدامك إضافة الميتا لكليجا، قم بمراسلتنا بالأخطاء عند ظهورها على البريد: <br>
info@kleeja.com
";

$kleeja_plugin['kj_meta_seo']['first_run']['en'] = "
Thank you for using our plugin, if you encounter any bugs and errors, contact us: <br>
info@kleeja.com
";

# plugin installation function
$kleeja_plugin['kj_meta_seo']['install'] = function ($plg_id) {
    //new options
    $options = array(
        'kj_meta_seo_home_meta_description' =>
            array(
                'value' => '',
                'html' => configField('kj_meta_seo_home_meta_description'),
                'plg_id' => $plg_id,
                'type' => 'kj_meta_seo',
                'order' => '1',
            ),
        'kj_meta_seo_home_meta_keywords' =>
            array(
                'value' => '',
                'html' => configField('kj_meta_seo_home_meta_keywords'),
                'plg_id' => $plg_id,
                'type' => 'kj_meta_seo',
                'order' => '2',
            ),
        'kj_meta_seo_enable_auto_meta' =>
            array(
                'value' => '1',
                'html' => configField('kj_meta_seo_enable_auto_meta', 'yesno'),
                'plg_id' => $plg_id,
                'type' => 'kj_meta_seo',
                'order' => '3',
            ),
        'kj_meta_seo_enable_download_auto_meta' =>
            array(
                'value' => '1',
                'html' => configField('kj_meta_seo_enable_download_auto_meta', 'yesno'),
                'plg_id' => $plg_id,
                'type' => 'kj_meta_seo',
                'order' => '4',
            ),
        'kj_meta_seo_enable_facebook_meta_tags' =>
            array(
                'value' => '1',
                'html' => configField('kj_meta_seo_enable_facebook_meta_tags', 'yesno'),
                'plg_id' => $plg_id,
                'type' => 'kj_meta_seo',
                'order' => '5',
            ),
        'kj_meta_seo_enable_twitter_meta_tags' =>
            array(
                'value' => '1',
                'html' => configField('kj_meta_seo_enable_twitter_meta_tags', 'yesno'),
                'plg_id' => $plg_id,
                'type' => 'kj_meta_seo',
                 'order' => '6',
            ),
        'kj_meta_seo_image_path' =>
            array(
                'value' => 'iPhone.png',
                'html' => configField('kj_meta_seo_image_path'),
                'plg_id' => $plg_id,
                'type' => 'kj_meta_seo',
                'order' => '7',
            ),
    );


    add_config_r($options);


    //new language variables
    add_olang(array(
        'CONFIG_KLJ_MENUS_KJ_META_SEO' => 'خيارات ميتا سيو',
        'KJ_META_SEO_HOME_META_DESCRIPTION' => 'الميتا دسكربشن/الوصف للبداية',
        'KJ_META_SEO_HOME_META_KEYWORDS' => 'الميتا كيووردز/الكلمات للبداية',
        'KJ_META_SEO_ENABLE_AUTO_META' => 'إنشاء الميتا بشكل تلقائي لباقي الصفحات',
        'KJ_META_SEO_ENABLE_DOWNLOAD_AUTO_META' => 'إنشاء الميتا بشكل تلقائي لصفحة التحميل',
        'KJ_META_SEO_ENABLE_FACEBOOK_META_TAGS' => 'تضمين الميتا أوبن قراف الخاصة بفيس بوك',
        'KJ_META_SEO_ENABLE_TWITTER_META_TAGS' => 'تضمين الميتا كاردز الخاصة بتويتر',
        'KJ_META_SEO_IMAGE_PATH' => 'الصورة المضمنة في الميتا (توضع في مجلد images)',


    ),
        'ar',
        $plg_id);

    add_olang(array(
        'CONFIG_KLJ_MENUS_KJ_META_SEO' => 'Meta SEO Settings',
        'KJ_META_SEO_HOME_META_DESCRIPTION' => 'Meta description',
        'KJ_META_SEO_HOME_META_KEYWORDS' => 'Meta keywords',
        'KJ_META_SEO_ENABLE_AUTO_META' => 'Auto generate meta codes for all pages',
        'KJ_META_SEO_ENABLE_DOWNLOAD_AUTO_META' => 'Auto generate meta codes for download page',
        'KJ_META_SEO_ENABLE_FACEBOOK_META_TAGS' => 'Includes Facebook OpenGraph meta tags',
        'KJ_META_SEO_ENABLE_TWITTER_META_TAGS' => 'Includes Twitter Cards meta tags',
        'KJ_META_SEO_IMAGE_PATH' => 'Included image in meta (from folder: images)',

    ),
        'en',
        $plg_id);
};


//plugin update function, called if plugin is already installed but version is different than current
$kleeja_plugin['kj_meta_seo']['update'] = function ($old_version, $new_version) {
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
$kleeja_plugin['kj_meta_seo']['uninstall'] = function ($plg_id) {
    //delete options
    delete_config(array(
        'kj_meta_seo_home_meta_description',
        'kj_meta_seo_home_meta_keywords',
        'kj_meta_seo_enable_auto_meta',
        'kj_meta_seo_enable_download_auto_meta',
        'kj_meta_seo_enable_facebook_meta_tags',
        'kj_meta_seo_enable_twitter_meta_tags',
        'kj_meta_seo_image_path',
    ));


    delete_olang(null, null, $plg_id);
};


# plugin functions
$kleeja_plugin['kj_meta_seo']['functions'] = array(
    'Saaheader_links_func' => function ($args) {
        $extra = $args['extra'] . kj_meta_seo_out(
                defined('IN_DOWNLOAD') ? 'download' : (defined('IN_REAL_INDEX') ? 'home' : 'any')
            );
        return compact('extra');
    }
);


/**
 * special functions
 */
if (!function_exists('kj_meta_seo_out')) {
    function kj_meta_seo_out($in = '')
    {
        global $config, $title;


        if($in == 'download' && $config['kj_meta_seo_enable_download_auto_meta'] == 0){
            return null;
        }

        if($in == 'any' && $config['kj_meta_seo_enable_auto_meta'] == 0){
            return null;
        }


        $metas = [];

        $desc = '';
        $keywords = '';

        $included_image = rtrim($config['siteurl'], '/') . '/images/' . $config['kj_meta_seo_image_path'];

        switch ($in){
            case 'home':

                $desc = $config['kj_meta_seo_home_meta_description'];
                $keywords = str_replace("،", ",", $config['kj_meta_seo_home_meta_keywords']);

                break;


            default:

                $desc = $title;
                $keywords = implode(', ', kj_meta_seo_keywords_extract($title));

                break;

        }

        $metas = array_merge($metas, array(
            '<meta name="description" content="' . $desc . '">',
            '<meta name="keywords" content="' . $keywords . '" >'
        ));



        $actual_link = htmlspecialchars('http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);


        if($config['kj_meta_seo_enable_facebook_meta_tags'] == 1) {
            $facebook_meta = array(
                '<meta property="og:type" content="website">',
                '<meta property="og:title" content="' . $title . '">',
                '<meta property="og:image" content="' . $included_image . '">',
                '<meta property="og:url" content="' . $actual_link . '">',
                '<meta property="og:site_name" content="' . $config['sitename'] . '">',
                '<meta property="og:description" content="' . htmlspecialchars($desc) . '">'
            );

            $metas = array_merge($metas, $facebook_meta);
        }

        if($config['kj_meta_seo_enable_twitter_meta_tags'] == 1) {
            $twitter_meta = array(
                '<meta name="twitter:card" content="summary">',
                '<meta name="twitter:title" content="'.  $title .'">',
                '<meta name="twitter:description" content="' . htmlspecialchars($desc) . '">',
                '<meta name="twitter:image" content="' . $included_image . '">',
            );

            $metas = array_merge($metas, $twitter_meta);
        }

            return '
    <!-- kj_meta_seo start -->
    ' . implode("\n    ", $metas) . '
    <!-- kj_meta_seo end -->
	';

    }
}

if (!function_exists('kj_meta_seo_keywords_extract')) {
    function kj_meta_seo_keywords_extract($string = '')
    {
        return array_filter(preg_split("!\W!", $string));
    }
}