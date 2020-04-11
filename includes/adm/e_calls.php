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
$stylee            = 'admin_calls';
$current_smt       = preg_replace('/[^a-z0-9_]/i', '', g('smt', 'str', 'general'));
$action            = basename(ADMIN_PATH) . '?cp=' . basename(__file__, '.php') . '&amp;page=' . (ig('page') ? g('page', 'int') : 1) . '&amp;smt=' . $current_smt;
$msg_sent          = ig('sent') ? g('sent', 'int') : false;
$H_FORM_KEYS       = kleeja_add_form_key('adm_calls');
$there_queue       = preg_match('!:del_[a-z0-9]{0,3}calls:!i', $config['queue']);

//
// Check form key
//
if (ip('submit'))
{
    if (! kleeja_check_form_key('adm_calls'))
    {
        kleeja_admin_err($lang['INVALID_FORM_KEY'], true, $lang['ERROR'], true, $action, 1);
    }
}


//add delete process to the queue
if ($current_smt == 'del_d30' || $current_smt == 'del_all')
{
    if (strpos($config['queue'], ':' . $current_smt . 'calls:') !== false)
    {
        kleeja_admin_err($lang['DELETE_PROCESS_IN_WORK'], true, $lang['ERROR'], true, basename(ADMIN_PATH) . '?cp=' . basename(__file__, '.php'), 1);
    }
    else
    {
        update_config('queue', $config['queue'] . ':' . $current_smt . 'calls:');
        kleeja_admin_info($lang['DELETE_PROCESS_QUEUED'], true, '', true, basename(ADMIN_PATH) . '?cp=' . basename(__file__, '.php'));
    }
}


$query    = [
    'SELECT'         => 'c.*',
    'FROM'           => "`{$dbprefix}call` c",
    'ORDER BY'       => 'c.id DESC'
];


if ($current_smt == 'show_h24')
{
    $query['WHERE'] = 'c.time > ' . intval(time() - 3600 * 24);
}

$result = $SQL->build($query);

//pagination
$nums_rows            = $SQL->num_rows($result);
$currentPage          = ig('page') ? g('page', 'int') : 1;
$Pager                = new Pagination($perpage, $nums_rows, $currentPage);
$start                = $Pager->getStartRow();


$no_results = false;
$del_nums   = [];

if ($nums_rows > 0)
{
    $query['LIMIT']    = "$start,$perpage";
    $result            = $SQL->build($query);

    while ($row=$SQL->fetch_array($result))
    {
        //make new lovely arrays !!
        $arr[]    = [
            'id'              => $row['id'],
            'name'            => $row['name'],
            'mail'            => $row['mail'],
            'text'            => htmlspecialchars($row['text']),
            'human_time'      => kleeja_date($row['time']),
            'time'            => kleeja_date($row['time'], false),
            'ip'              => $row['ip'],
            'sent'            => $row['id'] == $msg_sent,
            'ip_finder'       => 'http://www.ripe.net/whois?form_type=simple&full_query_string=&searchtext=' . $row['ip'] . '&do_search=Search'
        ];

        $del[$row['id']] = p('del_' . $row['id']);
        $sen[$row['id']] = p('v_' . $row['id']);


        //when submit !!
        if (ip('submit'))
        {
            if ($del[$row['id']])
            {
                $del_nums[] = $row['id'];
            }
        }

        if (ip('reply_submit'))
        {
            if ($sen[$row['id']])
            {
                $to      = $row['mail'];
                $subject = $lang['REPLY_CALL'] . ':' . $config['sitename'];
                $message = "\n " . $lang['REPLY_CALL'] . ' ' . $row['name'] . "\r\n " . $lang['REPLIED_ON_CAL'] . ' : ' . $config['sitename'] . 
                            "\r\n " . $lang['BY_EMAIL'] . ': ' . $row['mail'] . "\r\n" . $lang['ADMIN_REPLIED'] . "\r\n" . $sen[$row['id']] . "\r\n\r\n Kleeja.com ";

                $send =  send_mail($to, $message, $subject, $config['sitemail'], $config['sitename']);

                if ($send)
                {
                    //
                    //We will redirect to pages of results and show info msg there ! 
                    //
                    kleeja_admin_info($lang['IS_SEND_MAIL'], true, '', true, basename(ADMIN_PATH) . '?cp=' . basename(__file__, '.php') . '&page=' . (ig('page') ? g('page', 'int') : 1) . '&sent=' . $row['id']);
                }
                else
                {
                    kleeja_admin_err($lang['ERR_SEND_MAIL'], true, '', true, basename(ADMIN_PATH) . '?cp=' . basename(__file__, '.php') . '&page=' . (ig('page') ? g('page', 'int') : 1) . '&sent=' . $row['id']);
                }
            }
        }
    }
    $SQL->freeresult($result);
}
else
{
    $no_results = true;
}

//if deleted
if (sizeof($del_nums))
{
    $query_del    = [
        'DELETE'       => "`{$dbprefix}call`",
        'WHERE'        => "id IN('" . implode("', '", $del_nums) . "')"
    ];

    $SQL->build($query_del);
}

$total_pages       = $Pager->getTotalPages(); 
$page_nums         = $Pager->print_nums(basename(ADMIN_PATH) . '?cp=' . basename(__file__, '.php'), 'onclick="javascript:get_kleeja_link($(this).attr(\'href\'), \'#content\'); return false;"'); 

//after submit
if (ip('submit'))
{
    $text    = ($SQL->affected() ? $lang['CALLS_UPDATED'] : $lang['NO_UP_CHANGE_S']);
    $text    .= '<script type="text/javascript"> setTimeout("get_kleeja_link(\'' . $action . '\'); check_msg_and_reports();", 2000);</script>' . "\n";
    kleeja_admin_info($text, true, '', true, $action);
}


//secondary menu
$go_menu = [
    'general'  => ['name'=>$lang['R_CALLS'], 'link'=> basename(ADMIN_PATH) . '?cp=e_calls&amp;smt=general', 'goto'=>'general', 'current'=> $current_smt == 'general'],
    'show_h24' => ['name'=>$lang['SHOW_FROM_24H'], 'link'=> basename(ADMIN_PATH) . '?cp=e_calls&amp;smt=show_h24', 'goto'=>'show_h24', 'current'=> $current_smt == 'show_h24'],
    //CHECK IF IT'S ALREADY DONE ?
    'del_d30' => ['name'=>$lang['DELETE_EARLIER_30DAYS'], 'link'=> basename(ADMIN_PATH) . '?cp=e_calls&amp;smt=del_d30', 'goto'=>'del_d30', 'current'=> $current_smt == 'del_d30', 'confirm'=>true],
    'del_all' => ['name'=>$lang['DELETE_ALL'], 'link'=> basename(ADMIN_PATH) . '?cp=e_calls&amp;smt=del_all', 'goto'=>'del_all', 'current'=> $current_smt == 'del_all', 'confirm'=>true],
];
