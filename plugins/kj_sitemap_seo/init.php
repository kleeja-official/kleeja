<?php
# Kleeja Plugin
# kj_sitemap_seo
# Version: 1.0
# Developer: Kleeja team

# Prevent illegal run
if (!defined('IN_PLUGINS_SYSTEM')) {
    exit();
}


# Plugin Basic Information
$kleeja_plugin['kj_sitemap_seo']['information'] = array(
    # The casucal name of this plugin, anything can a human being understands
    'plugin_title' => array('en' => 'KJ SiteMap SEO', 'ar' => 'كليجا سايت ماب سيو'),
    # Who wrote this plugin?
    'plugin_developer' => 'Kleeja.com',
    # This plugin version
    'plugin_version' => '1.0',
    # Explain what is this plugin, why should I use it?
    'plugin_description' => array(
        'en' => 'Generate a sitemap and promote your content by notifying (ping) the search engine once at every new content addition to Kleeja',
        'ar' => 'إنشاء سايت ماب لكليجا وإمكانية بينق محركات البحث بعد كل إضافة محتوى جديد'
    ),
    # Min version of Kleeja that's requiered to run this plugin
    'plugin_kleeja_version_min' => '2.0',
    # Max version of Kleeja that support this plugin, use 0 for unlimited
    'plugin_kleeja_version_max' => '4.0',
    # Should this plugin run before others?, 0 is normal, and higher number has high priority
    'plugin_priority' => 0
);

//after installation message, you can remove it, it's not requiered
$kleeja_plugin['kj_sitemap_seo']['first_run']['ar'] = "
شكراً لاستخدامك إضافة السايت ماب لكليجا، قم بمراسلتنا بالأخطاء عند ظهورها على البريد: <br>
info@kleeja.com
<hr>
<br>
<h3>لاحظ:</h3>
لأداء أفضل للسايت ماب، قم بإضافة السطر التالي لملف  : robots.txt <br>
هذا الملف يكون في مجلد كليجا الأساسي وإن لم يكن موجود فقم بإضافته ثم إضافة السطر التالي له:
<br>
<br>
<b>Sitemap:</b> " . (!empty($config['siteurl']) ? $config['siteurl'] : 'http://example.com/') . "go.php?go=sitemap
 <br> أو:
 <br> 
 " . (!empty($config['siteurl']) ? $config['siteurl'] : 'http://example.com/') . "sitemap.xml

<hr>
<br>
<b>تجد إعدادات الإضافة في : إعدادات المركز->خيارات سايب ماب سيو</b>
";

$kleeja_plugin['kj_sitemap_seo']['first_run']['en'] = "
Thank you for using our plugin, if you encounter any bugs and errors, contact us: <br>
info@kleeja.com
<hr>
<br>
<h3>Note:</h3>
For better results, a file named 'robots.txt' should be existed at Kleeja root folder (same folder as config.php file), and has this line: <br>
 <b>Sitemap:</b> <br> " . (!empty($config['siteurl']) ? $config['siteurl'] : 'http://example.com/') . "go.php?go=sitemap 
 <br> or:
 <br> 
 " . (!empty($config['siteurl']) ? $config['siteurl'] : 'http://example.com/') . "sitemap.xml
<hr>
<br>
<b>For Plugin's settings: General Settings->SiteMap SEO Settings</b>
";

# Plugin Installation function
$kleeja_plugin['kj_sitemap_seo']['install'] = function ($plg_id) {
    //new options
    $options = array(
        'kj_sitemap_seo_sitemap_ping_enable' =>
            array(
                'value' => '0',
                'html' => configField('kj_sitemap_seo_sitemap_ping_enable', 'yesno'),
                'plg_id' => $plg_id,
                'type' => 'kj_sitemap_seo'
            ),
        'kj_sitemap_seo_sitemap_ping_time' =>
            array(
                'value' => '0',
                'plg_id' => $plg_id,
                'type' => 'kj_sitemap_seo'
            ),
    );

    //TODO add an option to let the use decide the interval between pings

    add_config_r($options);


    //new language variables
    add_olang(array(
        'KJ_SITEMAP_SEO_SITEMAP_PING_ENABLE' => 'تفعيل إعلام محركات البحث عن المحتوى الجديد',
        'CONFIG_KLJ_MENUS_KJ_SITEMAP_SEO' => 'خيارات  سايب ماب سيو',
    ),
        'ar',
        $plg_id);

    add_olang(array(
        'KJ_SITEMAP_SEO_SITEMAP_PING_ENABLE' => 'Notify (ping) search engine about new content',
        'CONFIG_KLJ_MENUS_KJ_SITEMAP_SEO' => 'SiteMap SEO Settings',
    ),
        'en',
        $plg_id);



    if(function_exists('add_to_serve_rules')){
        add_to_serve_rules("'^sitemap\.xml$' => ['file' => 'go.php', 'args' => 'go=sitemap'],", 'kj_sitemap_seo');
    }

};


//Plugin update function, called if plugin is already installed but version is different than current
$kleeja_plugin['kj_sitemap_seo']['update'] = function ($old_version, $new_version) {
    // if(version_compare($old_version, '0.5', '<')){
    // 	//... update to 0.5
    // }
    //
    // if(version_compare($old_version, '0.6', '<')){
    // 	//... update to 0.6
    // }

    //you could use update_config, update_olang
};


# Plugin Uninstall, function to be called at uninstalling
$kleeja_plugin['kj_sitemap_seo']['uninstall'] = function ($plg_id) {
    //delete options
    delete_config(array(
        'kj_sitemap_seo_sitemap_ping_enable',
        'kj_sitemap_seo_sitemap_ping_time'
    ));


    //delete language variables
    delete_olang(null, null, $plg_id);


    //remove rules
    if(function_exists('remove_from_serve_rules')) {
        remove_from_serve_rules('kj_sitemap_seo');
    }
};


# Plugin functions
$kleeja_plugin['kj_sitemap_seo']['functions'] = array(


    //new page
    'default_go_page' => function () {
        global $SQL, $dbprefix, $config;

        if (g('go') == 'sitemap') {
            header('Content-Type: application/xml; charset=utf-8');
            echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
            echo '<urlset xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1" xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd" xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

            $query = array(
                'SELECT' => 'f.id, f.name, f.real_filename, f.folder, f.type, f.time',
                'FROM' => "{$dbprefix}files f",
                'ORDER BY' => 'f.id DESC',
                'LIMIT' => '100'
            );

            $result = $SQL->build($query);


            while ($file = $SQL->fetch($result)) {
                $file_info = array('::ID::' => $file['id'], '::NAME::' => $file['name'], '::DIR::' => $file['folder'], '::FNAME::' => $file['real_filename']);

                echo '<url>' . "\n";
                echo "  " . '<loc>' . kleeja_get_link(is_image($file['type']) ? 'image' : 'file', $file_info) . '</loc>' . "\n";
                if (is_image($file['type'])) {

                    echo "  " . '<image:image><image:loc>' . kleeja_get_link('thumb', $file_info) . '</image:loc><image:caption>' . (trim($file['real_filename']) !== '' ? xml_entities(htmlspecialchars($file['real_filename'])) : xml_entities(htmlspecialchars($file['name']))) . '</image:caption></image:image>' . "\n";
                }
                echo "  " . '<lastmod>' . date('c', $file['time']) . '</lastmod>' . "\n";
                echo '</url>' . "\n";
            }
            echo '</urlset>';

            $SQL->free($result);

            #at end, close sql connections & etc
            $SQL->close();

            // #tell kleeja that we have a request from go=sitemap
            // $no_request = false;
            exit;
        }
    },

    //output in header
    'Saaheader_links_func' => function ($args) {
        global $config;
        $extra = $args['extra'] . "\n" .
            '<link rel="sitemap" type="application/xml" title="Sitemap" href="' . rtrim($config['siteurl'], '/') . '/go.php?go=sitemap">';
        return compact('extra');
    },

    //ping after uploading files?
    'kljuploader_process_after_loop' => function ($args) {
        if (isset($args['check'])) {
            send_a_ping();
        }
    }
);


/**
 * special functions
 */

if (!function_exists('xml_entities')) {
    function xml_entities($string)
    {
        return strtr(
            $string,
            array(
                "<" => "&lt;",
                ">" => "&gt;",
                '"' => "&quot;",
                "'" => "&apos;",
                "&" => "&amp;",
            )
        );
    }
}

if (!function_exists('is_image')) {
    function is_image($pre_ext)
    {
        return in_array(strtolower(trim($pre_ext)), array('gif', 'jpg', 'jpeg', 'bmp', 'png')) ? true : false;
    }
}

if (!function_exists('send_a_ping')) {
    function send_a_ping()
    {
        global $config;

        //dev ?
        // if(defined('DEV_STAGE')){
        // 	// return;
        // }

        if (!$config['kj_sitemap_seo_sitemap_ping_enable']) {
            return;
        }

        //last time? 3 hours in between
        if ((int)$config['kj_sitemap_seo_sitemap_ping_time'] > 0 && (int)time() - $config['kj_sitemap_seo_sitemap_ping_time'] < 3600 * 3) {
            return;
        }

        $services = array(
            "http://www.bing.com/webmaster/ping.aspx?siteMap=",
            "http://www.bing.com/ping?sitemap=",
            "http://submissions.ask.com/ping?sitemap=",
            "http://www.google.com/webmasters/sitemaps/ping?sitemap=",
            "http://api.moreover.com/ping?sitemap=",
        );

        foreach ($services as $sv) {
            $curl_handle = curl_init();
            curl_setopt($curl_handle, CURLOPT_URL, $sv . rtrim($config['siteurl'], '/') . '/go.php?go=sitemap');
            curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 2);
            curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
            $buffer = curl_exec($curl_handle);
            curl_close($curl_handle);
        }

        update_config('kj_sitemap_seo_sitemap_ping_time', time());

        //TODO add last time of ping to admin start page
    }
}
