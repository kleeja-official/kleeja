<?php
/**
*
* @package Kleeja
* @copyright (c) 2007 Kleeja.net
* @license ./docs/license.txt
*
*/



/**
 * We are in index.php file, useful for exceptions
 */
define('IN_REAL_INDEX', true);

/**
 * We are in the middle of the uploading process, useful for exceptions
 */
define('IN_SUBMIT_UPLOADING', isset($_POST['submitr']) || isset($_POST['submittxt']));


/**
 * @ignore
 */
define('IN_KLEEJA', true);
require_once 'includes/common.php';
require_once 'includes/KleejaUploader.php';

//current uploading method
$uploadingMethodClass = 'includes/up_methods/defaultUploader.php';


is_array($plugin_run_result = Plugins::getInstance()->run('begin_index_page', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook


require_once $uploadingMethodClass;

//
//Is kleeja only for members?
//
if (empty($d_groups[2]['exts']) && ! $usrcp->name())
{
    // Send a 503 HTTP response code to prevent search bots from indexing this message
    //header('HTTP/1.1 503 Service Temporarily Unavailable');
    kleeja_info($lang['SITE_FOR_MEMBER_ONLY'], $lang['HOME']);
}


$action = $config['siteurl'];


/** @var KleejaUploader $uploader */
$uploadingMethodClassBaseName = basename($uploadingMethodClass, '.php');
$uploader                     = new $uploadingMethodClassBaseName;

$uploader->setAllowedFileExtensions($d_groups[$userinfo['group_id']]['exts']);
$uploader->setUploadFieldsLimit($config['filesnum']);




if (ip('submitr'))
{
    $uploader->upload();
}


//file input fields
$FILES_NUM_LOOP = [];

if ($config['filesnum'] > 0)
{
    foreach (range(1, $config['filesnum']) as $i)
    {
        $FILES_NUM_LOOP[] = ['i' => $i, 'show'=>($i == 1 || (! empty($config['filesnum_show']) && (int) $config['filesnum_show'] == 1) ? '' : 'display: none')];
    }
}
else
{
    $text = $lang['PLACE_NO_YOU'];
}


//show errors and info
$info = [];

foreach ($uploader->getMessages() as $t => $s)
{
    $info[] = [
        't' => $s[1] == 'error' ? 'index_err' : 'index_info', //for old Kleeja versions
        'i' => $s[0], //#for old Kleeja versions


        'message_content' => $s[0],
        'message_type'    => $s[1],
    ];
}


//some words for template
$welcome_msg       = $config['welcome_msg'];
$filecp_link       = $usrcp->id() ? $config['siteurl'] . ($config['mod_writer'] ? 'filecp.html' : 'ucp.php?go=filecp') : false;
$terms_msg         = sprintf($lang['AGREE_RULES'], '<a href="' . ($config['mod_writer'] ? 'rules.html' : 'go.php?go=rules') . '">', '</a>');
$link_avater       = sprintf($lang['EDIT_U_AVATER_LINK'], '<a href="https://www.gravatar.com/" target="_blank">', '</a>');


$js_allowed_extensions_types = "['" . implode("', '", array_keys($d_groups[$userinfo['group_id']]['exts'])) . "']";
$js_allowed_extensions_sizes = '[' . implode(', ', array_values($d_groups[$userinfo['group_id']]['exts'])) . ']';



//
//who's online right now..
//I don't like this feature and I prefer that you disable it
//
$show_online = $config['allow_online'] == 1 ? true : false;

if ($show_online)
{
    $current_online_users       = 0;
    $online_names               = [];
    $timeout                    = 60; //30 second
    $timeout2                   = time()-$timeout;

    //put another bot name
    is_array($plugin_run_result = Plugins::getInstance()->run('anotherbots_online_index_page', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook

    $query = [
        'SELECT'       => 'u.name',
        'FROM'         => "{$dbprefix}users u",
        'WHERE'        => "u.last_visit > $timeout2"
    ];

    is_array($plugin_run_result = Plugins::getInstance()->run('qr_select_online_index_page', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook

    $result    = $SQL->build($query);

    while ($row=$SQL->fetch_array($result))
    {
        is_array($plugin_run_result = Plugins::getInstance()->run('while_qr_select_online_index_page', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook

        $current_online_users++;
        $online_names[$row['name']] = $row['name'];
    }//while

    $SQL->freeresult($result);

    //make names as array to print them in template
    $show_names        = [];
    $show_names_sizeof = sizeof($online_names);

    foreach ($online_names as $k)
    {
        $show_names[] = ['name' => $k, 'separator' => $show_names_sizeof ? ',' : ''];
    }

    //some variables must be destroyed here
    unset($online_names, $timeout, $timeout2);

    //check & update most ever users and visitors were online
    if (empty($config['most_user_online_ever']) || trim($config['most_user_online_ever']) == '')
    {
        $most_online = $current_online_users;
        $online_time = time();
    }
    else
    {
        list($most_online, $online_time) = @explode(':', $config['most_user_online_ever']);
    }

    if ($most_online < $current_online_users || empty($config['most_user_online_ever']))
    {
        update_config('most_user_online_ever', $current_online_users . ':' . time());
    }

    $online_time = kleeja_date($online_time, true, 'd-m-Y h:i a');


    //before 1.8, styles computability
    $usersnum  = $current_online_users;
    $shownames = $show_names;


    is_array($plugin_run_result = Plugins::getInstance()->run('if_online_index_page', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook
}//allow_online


$show_style = true;

is_array($plugin_run_result = Plugins::getInstance()->run('end_index_page', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook


//is ajax
if (ip('ajax'))
{
    if (! empty($info))
    {
        header('Content-Type: text/plain; charset=utf-8');
        echo json_encode($info);
    }

    exit;
}


//show style
if ($show_style)
{
    Saaheader();
    echo $tpl->display(($config['filesnum'] > 0 ? 'index_body' : 'info'));
    Saafooter();
}
