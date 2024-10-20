<?php
/**
*
* @package adm
* @copyright (c) 2007 Kleeja.net
* @license ./docs/license.txt
*
*/

// not for directly open
if (! defined('IN_ADMIN'))
{
    exit();
}


//for style ..
$stylee            = 'admin_configs';
$current_smt       = preg_replace('/[^a-z0-9_]/i', '', g('smt', 'str', 'general'));
//words
$action           = basename(ADMIN_PATH) . '?cp=options&amp;smt=' . $current_smt;
$n_submit         = $lang['UPDATE_CONFIG'];
$options          = '';
//$current_type    = ig('type') ? g('type') : 'general';
$CONFIGEXTEND    = false;
$H_FORM_KEYS     = kleeja_add_form_key('adm_configs');

//secondary menu
$query    = [
    'SELECT'       => 'DISTINCT(c.type), c.display_order, p.plg_disabled, c.plg_id',
    'FROM'         => "{$dbprefix}config c",
    'JOINS'        => [
        [
            'LEFT JOIN' => "{$dbprefix}plugins p",
            'ON'        => 'p.plg_id=c.plg_id'
        ]
    ],
    'WHERE'          => "c.option <> '' AND c.type <> 'groups'",
    'ORDER BY'       => 'c.display_order'
];

$result = $SQL->build($query);

while ($row = $SQL->fetch_array($result))
{
    if ($row['type'] == 'KLIVE')
    {
        continue;
    }

    if ($row['plg_id'] > 0 && (is_null($row['plg_disabled']) || $row['plg_disabled'] == 1))
    {
        continue;
    }

    $name                  = ! empty($lang['CONFIG_KLJ_MENUS_' . strtoupper($row['type'])]) ? $lang['CONFIG_KLJ_MENUS_' . strtoupper($row['type'])] : (! empty($olang['CONFIG_KLJ_MENUS_' . strtoupper($row['type'])]) ? $olang['CONFIG_KLJ_MENUS_' . strtoupper($row['type'])] : $lang['CONFIG_KLJ_MENUS_OTHER']);
    $go_menu[$row['type']] = ['name'=>$name, 'link'=>$action . '&amp;smt=' . $row['type'], 'goto'=>$row['type'], 'current'=> $current_smt == $row['type']];
}

$go_menu['all'] = ['name'=>$lang['CONFIG_KLJ_MENUS_ALL'], 'link'=>$action . '&amp;smt=all', 'goto'=>'all', 'current'=> $current_smt == 'all'];

//
// Check form key
//
if (ip('submit'))
{
    if (! kleeja_check_form_key('adm_configs'))
    {
        kleeja_admin_err($lang['INVALID_FORM_KEY'], true, $lang['ERROR'], true, $action, 1);
    }
}



//general varaibles
//$action        = basename(ADMIN_PATH) . '?cp=options&amp;type=' .$current_type;
$STAMP_IMG_URL        = file_exists(PATH . 'images/watermark.gif') ? PATH . 'images/watermark.gif' : PATH . 'images/watermark.png';
$stylfiles            = $lngfiles            = $authtypes         =  $time_zones         = '';
$optionss             = [];
$n_googleanalytics    = '<a href="http://www.google.com/analytics">Google Analytics</a>';

$query    = [
    'SELECT'         => '*',
    'FROM'           => "{$dbprefix}config",
    'ORDER BY'       => 'display_order, type ASC'
];

$CONFIGEXTEND        = $SQL->escape($current_smt);
$CONFIGEXTENDLANG    = $go_menu[$current_smt]['name'];

if ($current_smt != 'all')
{
    $query['WHERE'] = "type = '" . $SQL->escape($current_smt) . "' OR type = ''";

    if ($current_smt == 'interface')
    {
        $query['WHERE'] .= " OR name='language'";
    }
}
elseif ($current_smt == 'all')
{
    $query['WHERE'] = "(type <> 'groups' OR type = '') AND type <> '0'";
}

$result = $SQL->build($query);

$thumbs_are = get_config('thmb_dims');

while ($row=$SQL->fetch_array($result))
{
    if ($row['type'] == 'KLIVE')
    {
        continue;
    }

    if ($row['name'] == 'language' && $current_smt == 'interface')
    {
        $row['type'] = 'interface';
    }


    //make new lovely array !!
    $con[$row['name']] = $row['value'];

    if ($row['name'] == 'thumbs_imgs')
    {
        list($thmb_dim_w, $thmb_dim_h) = array_map('trim', @explode('*', $thumbs_are));
    }
    elseif ($row['name'] == 'time_zone')
    {
        $zones = time_zones();

        foreach ($zones as $z=>$t)
        {
            $gmt_diff = $t < 0 ? $t : '+' . $t;
            $time_zones .= '<option ' . ($con['time_zone'] == $z ? 'selected="selected"' : '') . ' value="' . $z . '">' . $z . " (GMT{$gmt_diff})" . '</option>' . "\n";
        }
    }
    elseif ($row['name'] == 'language')
    {
        //get languages
        if ($dh = @opendir(PATH . 'lang'))
        {
            while (($file = readdir($dh)) !== false)
            {
                if (strpos($file, '.') === false && $file != '..' && $file != '.')
                {
                    $lngfiles .= '<option ' . ($con['language'] == $file ? 'selected="selected"' : '') . ' value="' . $file . '">' . $file . '</option>' . "\n";
                }
            }
            @closedir($dh);
        }
    }
    elseif (in_array($row['name'], ['user_system', 'www_url']))
    {
        continue;
    }

    is_array($plugin_run_result = Plugins::getInstance()->run('while_fetch_adm_config', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook

    //options from database [UNDER TEST]
    if (! empty($row['option']))
    {
        $optionss[$row['name']] = [
            'option'         => '<div class="form-group">' . "\n" .
                                '<label for="' . $row['name'] . '">' . (! empty($lang[strtoupper($row['name'])]) ? $lang[strtoupper($row['name'])] : $olang[strtoupper($row['name'])]) . '</label>' . "\n" .
                                '<div class="box">' . (empty($row['option']) ? '' : $tpl->admindisplayoption($row['option'])) . '</div>' . "\n" .
                                '</div>' . "\n" . '<div class="clear"></div>',
            'type'                   => $row['type'],
            'display_order'          => $row['display_order'],
        ];
    }

    //when submit
    if (ip('submit'))
    {
        //-->
        $new[$row['name']] = p($row['name'], 'str', $con[$row['name']]);

        //save them as you want ..
        if ($row['name'] == 'thumbs_imgs')
        {
            if (p('thmb_dim_w', 'int') < 10)
            {
                $_POST['thmb_dim_w'] = 10;
            }

            if (p('thmb_dim_h', 'int') < 10)
            {
                $_POST['thmb_dim_h'] = 10;
            }

            $thumbs_were = p('thmb_dim_w', 'int') . '*' . p('thmb_dim_h', 'int');
            update_config('thmb_dims', $thumbs_were);
        }
        elseif ($row['name'] == 'livexts')
        {
            $new['livexts'] = implode(',', array_map('trim', explode(',', p('livexts'))));
        }
        elseif ($row['name'] == 'prefixname')
        {
            $new['prefixname'] = preg_replace('/[^a-z0-9_\-\}\{\:\.]/', '', strtolower(p('prefixname')));
        }
        elseif ($row['name'] == 'siteurl')
        {
            if (p('siteurl')[strlen(p('siteurl'))-1] != '/')
            {
                $new['siteurl'] .= '/';
            }
        }
        elseif ($row['name'] == 'mod_writer')
        {
            if ($new['mod_writer'] == 1)
            {
                if (! file_exists(PATH . '.htaccess') && file_exists(PATH . 'htaccess.txt') && function_exists('rename'))
                {
                    if (! rename(PATH . 'htaccess.txt', PATH . '.htaccess'))
                    {
                        chmod(PATH . 'htaccess.txt', K_FILE_CHMOD);
                        rename(PATH . 'htaccess.txt', PATH . '.htaccess');
                    }
                }
            }
        }
        elseif ($row['name'] == 'language')
        {
            $got_lang = preg_replace('[^a-zA-Z0-9]', '', $new[$row['name']]);

            //all groups
            foreach ($d_groups as $group_id => $group_info)
            {
                update_config('language', $got_lang, true, $group_id);
            }

            delete_cache('data_lang' . $got_lang);
        }

        is_array($plugin_run_result = Plugins::getInstance()->run('after_submit_adm_config', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook

        $update_query = [
            'UPDATE'       => "{$dbprefix}config",
            'SET'          => "value='" . $SQL->escape($new[$row['name']]) . "'",
            'WHERE'        => "name='" . $row['name'] . "'"
        ];

        if ($current_smt != 'all')
        {
            $query['WHERE'] .= " AND type = '" . $SQL->escape($current_smt) . "'";
        }

        $SQL->build($update_query);
    }
}

$SQL->freeresult($result);
$types = [];

foreach ($optionss as $key => $option)
{
    if (empty($types[$option['type']]))
    {
        $types[$option['type']] = '<ol class="breadcrumb">' .
                '<li class="breadcrumb-item"><a href="#">' . $lang['R_CONFIGS'] . '</a></li>' .
                '<li class="breadcrumb-item active">' . $go_menu[$option['type']]['name'] . '</li>' .
                '</ol>';
    }
}

foreach ($types as $typekey => $type)
{
    $options .= $type;

    foreach ($optionss as $key => $option)
    {
        if ($option['type'] == $typekey)
        {
            $options .= str_replace(
                ['<input ', '<select ', '<td>', '</td>', '<label>', '<tr>', '</tr>'],
                ['<input class="form-control" ', '<select class="form-control" ', '<div class="form-group">', '</div>', '<label class="form-check-label">', '', ''],
                $option['option']
            );
        }
    }
}

//after submit
if (ip('submit'))
{
    //some configs need refresh page ..
    $need_refresh_configs = ['language'];


    is_array($plugin_run_result = Plugins::getInstance()->run('after_submit_adm_config', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook

    //empty ..
    /*
    if (empty(p('sitename')) || empty(p('siteurl')) || empty(p('foldername')) || empty(p('filesnum')))
    {
        $text    = $lang['EMPTY_FIELDS'];
        $stylee    = "admin_err";
    }
    elseif (!is_numeric(p('filesnum')) || !is_numeric(p('sec_down')))
    {
        $text    = $lang['NUMFIELD_S'];
        $stylee    = "admin_err";
    }
    else
    {
    */

    //delete cache ..
    delete_cache('data_config');


    foreach ($need_refresh_configs as $l)
    {
        if (ip($l) && p($l) != $config[$l])
        {
            header('Location: ' . basename(ADMIN_PATH));

            exit();
        }
    }

    kleeja_admin_info($lang['CONFIGS_UPDATED'], true, '', true, $action, 3);
    //}
}//submit
