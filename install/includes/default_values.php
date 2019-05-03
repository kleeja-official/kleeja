<?php
/**
*
* @package install
* @copyright (c) 2007 Kleeja.com
* @license ./docs/license.txt
*
*/

// not for directly open
if (! defined('IN_COMMON'))
{
    exit();
}


//
// Configuration values
//

$config_values = [];

// do it like this : 
//$config_values = array('name', 'value', 'option', 'display_order', 'type', 'plg_id', 'dynamic');

// General settings
$config_values[] = ['sitename', $config_sitename, '<input type=\"text\" id=\"sitename\" name=\"sitename\" value=\"{con.sitename}\" size=\"50\" />', 1, 'general', 0, 0];
$config_values[] = ['siteurl', $config_siteurl, '<input type=\"text\" id=\"siteurl\" name=\"siteurl\" value=\"{con.siteurl}\" size=\"50\" style=\"direction:ltr\" />', 2, 'general', 0, 0];
$config_values[] = ['sitemail', $config_sitemail, '<input type=\"text\" id=\"sitemail\" name=\"sitemail\" value=\"{con.sitemail}\" size=\"25\" style=\"direction:ltr\" />', 3, 'general', 0, 0];
$config_values[] = ['sitemail2', $config_sitemail, '<input type=\"text\" id=\"sitemail2\" name=\"sitemail2\" value=\"{con.sitemail2}\" size=\"25\" style=\"direction:ltr\" />', '4', 'general', 0, 0];
$config_values[] = ['del_f_day', '0', '<input type=\"text\" id=\"del_f_day\" name=\"del_f_day\" value=\"{con.del_f_day}\" size=\"6\" style=\"text-align:center\" />{lang.DELF_CAUTION}', 5, 'advanced', 0, 0];
$config_values[] = ['language', getlang(), '<select name=\"language\" id=\"language\">\r\n {lngfiles}\r\n </select>', 6, 'groups', 0, 0];
$config_values[] = ['time_zone', $config_time_zone, '<select name=\"time_zone\" id=\"time_zone\">\r\n {time_zones}\r\n </select>', 10, 'general', 0, 0];
$config_values[] = ['siteclose', '0', '<label>{lang.YES}<input type=\"radio\" id=\"siteclose\" name=\"siteclose\" value=\"1\"  <IF NAME=\"con.siteclose==1\"> checked=\"checked\"</IF> /></label><label>{lang.NO}<input type=\"radio\" id=\"siteclose\" name=\"siteclose\" value=\"0\"  <IF NAME=\"con.siteclose==0\"> checked=\"checked\"</IF> /></label>', 7, 'general', 0, 0];
$config_values[] = ['closemsg', 'sits is closed now', '<input type=\"text\" id=\"closemsg\" name=\"closemsg\" value=\"{con.closemsg}\" size=\"68\" />', 8, 'general', 0, 0];
$config_values[] = ['user_system', '1', '<select id=\"user_system\" name=\"user_system\">{authtypes}</select>', 9, 'advanced', 0, 0];
$config_values[] = ['register', '1', '<label>{lang.YES}<input type=\"radio\" id=\"register\" name=\"register\" value=\"1\"  <IF NAME=\"con.register==1\"> checked=\"checked\"</IF> /></label>\r\n <label>{lang.NO}<input type=\"radio\" id=\"register\" name=\"register\" value=\"0\"  <IF NAME=\"con.register==0\"> checked=\"checked\"</IF> /></label>', 10, 'general', 0, 0];
$config_values[] = ['enable_userfile', '1', '<label>{lang.YES}<input type=\"radio\" id=\"enable_userfile\" name=\"enable_userfile\" value=\"1\"  <IF NAME=\"con.enable_userfile==1\"> checked=\"checked\"</IF> /></label>\r\n <label>{lang.NO}<input type=\"radio\" id=\"enable_userfile\" name=\"enable_userfile\" value=\"0\"  <IF NAME=\"con.enable_userfile==0\"> checked=\"checked\"</IF> /></label>', 11, 'groups', 0, 0];
$config_values[] = ['mod_writer', '0', '<label>{lang.YES}<input type=\"radio\" id=\"mod_writer\" name=\"mod_writer\" value=\"1\"  <IF NAME=\"con.mod_writer==1\"> checked=\"checked\"</IF> /></label>\r\n <label>{lang.NO}<input type=\"radio\" id=\"mod_writer\" name=\"mod_writer\" value=\"0\"  <IF NAME=\"con.mod_writer==0\"> checked=\"checked\"</IF> /></label>\r\n   [ {lang.MOD_WRITER_EX} ]', 12, 'advanced', 0, 0];

// Cookies settings
$cookie_data     = get_cookies_settings();
$config_values[] = ['cookie_name', $cookie_data['cookie_name'], '<input type=\"text\" id=\"cookie_name\" name=\"cookie_name\" value=\"{con.cookie_name}\" size=\"20\" style=\"direction:ltr\" />', '13', 'advanced', 0, 0];
$config_values[] = ['cookie_path', $cookie_data['cookie_path'], '<input type=\"text\" id=\"cookie_path\" name=\"cookie_path\" value=\"{con.cookie_path}\" size=\"20\" style=\"direction:ltr\" />', '14', 'advanced', 0, 0];
$config_values[] = ['cookie_domain', $cookie_data['cookie_domain'], '<input type=\"text\" id=\"cookie_domain\" name=\"cookie_domain\" value=\"{con.cookie_domain}\" size=\"20\" style=\"direction:ltr\" />', '15', 'advanced', 0, 0];
$config_values[] = ['cookie_secure', ($cookie_data['cookie_secure'] ? '1' : '0'), '<label>{lang.YES}<input type=\"radio\" id=\"cookie_secure\" name=\"cookie_secure\" value=\"1\"  <IF NAME=\"con.cookie_secure==1\"> checked=\"checked\"</IF> /></label>\r\n <label>{lang.NO}<input type=\"radio\" id=\"cookie_secure\" name=\"cookie_secure\" value=\"0\"  <IF NAME=\"con.cookie_secure==0\"> checked=\"checked\"</IF> /></label>', '16', 'advanced', 0, 0];

// Upload settings 
$config_values[] = ['total_size', '10000000000', '<input type=\"text\" id=\"total_size\" name=\"total_size\" value=\"{con.total_size}\" size=\"20\" style=\"direction:ltr\" />', 17, 'upload', 0, 0];
$config_values[] = ['foldername', 'uploads', '<input type=\"text\" id=\"foldername\" name=\"foldername\" value=\"{con.foldername}\" size=\"20\" style=\"direction:ltr\" />', 18, 'upload', 0, 0];
$config_values[] = ['prefixname', '', '<input type=\"text\" id=\"prefixname\" name=\"prefixname\" value=\"{con.prefixname}\" size=\"20\" style=\"direction:ltr\" />', 19, 'upload', 0, 0];
$config_values[] = ['decode', '1', '<select id=\"decode\" name=\"decode\">\r\n <option <IF NAME=\"con.decode==0\">selected=\"selected\"</IF> value=\"0\">{lang.NO_CHANGE}</option>\r\n <option <IF NAME=\"con.decode==2\">selected=\"selected\"</IF> value=\"2\">{lang.CHANGE_MD5}</option>\r\n <option <IF NAME=\"con.decode==1\">selected=\"selected\"</IF> value=\"1\">{lang.CHANGE_TIME}</option>\r\n				<!-- another config decode options -->\r\n </select>', 20, 'upload', 0, 0];
$config_values[] = ['id_form', $config_urls_type, '<select id=\"id_form\" name=\"id_form\">\r\n <option <IF NAME=\"con.id_form==id\">selected=\"selected\"</IF> value=\"id\">{lang.IDF}</option>\r\n <option <IF NAME=\"con.id_form==filename\">selected=\"selected\"</IF> value=\"filename\">{lang.IDFF}</option>\r\n<option <IF NAME=\"con.id_form==direct\">selected=\"selected\"</IF> value=\"direct\">{lang.IDFD}</option>\r\n </select>', 21, 'upload', 0, 0];
$config_values[] = ['id_form_img', $config_urls_type, '<select id=\"id_form_img\" name=\"id_form_img\">\r\n <option <IF NAME=\"con.id_form_img==id\">selected=\"selected\"</IF> value=\"id\">{lang.IDF_IMG}</option>\r\n <option <IF NAME=\"con.id_form_img==filename\">selected=\"selected\"</IF> value=\"filename\">{lang.IDFF_IMG}</option>\r\n<option <IF NAME=\"con.id_form_img==direct\">selected=\"selected\"</IF> value=\"direct\">{lang.IDFD_IMG}</option>\r\n </select>', 21, 'upload', 0, 0];
$config_values[] = ['filesnum', '3', '<input type=\"text\" id=\"filesnum\" name=\"filesnum\" value=\"{con.filesnum}\" size=\"6\" style=\"text-align:center\" />', 22, 'groups', 0, 0];
$config_values[] = ['sec_down', '5', '<input type=\"text\" id=\"sec_down\" name=\"sec_down\" value=\"{con.sec_down}\" size=\"6\" style=\"text-align:center\" />', 23, 'groups', 0, 0];
$config_values[] = ['del_url_file', '1', '<label>{lang.YES}<input type=\"radio\" id=\"del_url_file\" name=\"del_url_file\" value=\"1\"  <IF NAME=\"con.del_url_file==1\"> checked=\"checked\"</IF> /></label>\r\n <label>{lang.NO}<input type=\"radio\" id=\"del_url_file\" name=\"del_url_file\" value=\"0\"  <IF NAME=\"con.del_url_file==0\"> checked=\"checked\"</IF> /></label>', 24, 'upload', 0, 0];
$config_values[] = ['safe_code', '0', '<label>{lang.YES}<input type=\"radio\" id=\"safe_code\" name=\"safe_code\" value=\"1\"  <IF NAME=\"con.safe_code==1\"> checked=\"checked\"</IF> /></label>\r\n <label>{lang.NO}<input type=\"radio\" id=\"safe_code\" name=\"safe_code\" value=\"0\"  <IF NAME=\"con.safe_code==0\"> checked=\"checked\"</IF> /></label>', 25, 'upload', 0, 0];
$config_values[] = ['www_url', '0', '<label>{lang.YES}<input type=\"radio\" id=\"www_url\" name=\"www_url\" value=\"1\"  <IF NAME=\"con.www_url==1\"> checked=\"checked\"</IF> /></label>\r\n <label>{lang.NO}<input type=\"radio\" id=\"www_url\" name=\"www_url\" value=\"0\"  <IF NAME=\"con.www_url==0\"> checked=\"checked\"</IF> /></label>', 26, 'upload', 0, 0];
$config_values[] = ['thumbs_imgs', '1', '<input type=\"text\" id=\"thmb_dim_w\" name=\"thmb_dim_w\" value=\"{thmb_dim_w}\" size=\"2\" style=\"text-align:center\" /> * <input type=\"text\" id=\"thmb_dim_h\" name=\"thmb_dim_h\" value=\"{thmb_dim_h}\" size=\"2\" style=\"text-align:center\" /> ', 27, 'upload', 0, 0];
$config_values[] = ['write_imgs', '0' , '<label>{lang.YES}<input type=\"radio\" id=\"write_imgs\" name=\"write_imgs\" value=\"1\"  <IF NAME=\"con.write_imgs==1\"> checked=\"checked\"</IF> /></label>\r\n <label>{lang.NO}<input type=\"radio\" id=\"write_imgs\" name=\"write_imgs\" value=\"0\"  <IF NAME=\"con.write_imgs==0\"> checked=\"checked\"</IF> /></label>\r\n <br /><img src=\"{STAMP_IMG_URL}\" alt=\"Seal photo\" style=\"margin-top:4px;border:1px groove #FF865E;\" />\r\n ', 28, 'groups', 0, 0];
$config_values[] = ['livexts', 'swf', '<input type=\"text\" id=\"livexts\" name=\"livexts\" value=\"{con.livexts}\" size=\"62\" style=\"direction:ltr\" />{lang.COMMA_X}', '29', 'upload', 0, 0];
$config_values[] = ['usersectoupload', '10', '<input type=\"text\" id=\"usersectoupload\" name=\"usersectoupload\" value=\"{con.usersectoupload}\" size=\"10\" />', 44, 'groups', 0, 0];
$config_values[] = ['filesnum_show', '1', '<label>{lang.YES}<input type=\"radio\" id=\"filesnum_show\" name=\"filesnum_show\" value=\"1\"  <IF NAME=\"con.filesnum_show==1\"> checked=\"checked\"</IF> /></label>\r\n <label>{lang.NO}<input type=\"radio\" id=\"filesnum_show\" name=\"filesnum_show\" value=\"0\"  <IF NAME=\"con.filesnum_show==0\"> checked=\"checked\"</IF> /></label>', 22, 'upload', 0, 0];

//KLIVE
//$config_values[] = array('imagefolder', 'uploads', '<input type=\"text\" id=\"imagefolder\" name=\"imagefolder\" value=\"{con.imagefolder}\" size=\"40\">', '10', 'KLIVE', '0', '0');
//$config_values[] = array('imagefolderexts', '', '<input type=\"text\" id=\"imagefolderexts\" name=\"imagefolderexts\" value=\"{con.imagefolderexts}\" size=\"80\">', '20', 'KLIVE', '0', '0');
//$config_values[] = array('imagefoldere', '1', '<label>{lang.YES}<input type=\"radio\" id=\"imagefoldere\" name=\"imagefoldere\" value=\"1\"  <IF NAME=\"con.imagefoldere\"> checked=\"checked\"</IF>></label><label>{lang.NO}<input type=\"radio\" id=\"imagefoldere\" name=\"imagefoldere\" value=\"0\"  <IF NAME=\"con.imagefoldere\"> <ELSE> checked=\"checked\"</IF>></label>', '30', 'KLIVE', '0', '0');

// Interface settings 
$config_values[] = ['welcome_msg', $lang['INST_MSGINS'], '<input type=\"text\" id=\"welcome_msg\" name=\"welcome_msg\" value=\"{con.welcome_msg}\" size=\"68\" />', 30, 'interface', 0, 0];
$config_values[] = ['allow_stat_pg', '1', '<label>{lang.YES}<input type=\"radio\" id=\"allow_stat_pg\" name=\"allow_stat_pg\" value=\"1\"  <IF NAME=\"con.allow_stat_pg==1\"> checked=\"checked\"</IF> /></label>\r\n <label>{lang.NO}<input type=\"radio\" id=\"allow_stat_pg\" name=\"allow_stat_pg\" value=\"0\"  <IF NAME=\"con.allow_stat_pg==0\"> checked=\"checked\"</IF> /></label>', 31, 'interface', 0, 0];
$config_values[] = ['allow_online', '0', '<label>{lang.YES}<input type=\"radio\" id=\"allow_online\" name=\"allow_online\" value=\"1\"  <IF NAME=\"con.allow_online==1\"> checked=\"checked\"</IF> /></label>\r\n <label>{lang.NO}<input type=\"radio\" id=\"allow_online\" name=\"allow_online\" value=\"0\"  <IF NAME=\"con.allow_online==0\"> checked=\"checked\"</IF> /></label>', 32, 'interface', 0, 0];
$config_values[] = ['statfooter', '0' , '<label>{lang.YES}<input type=\"radio\" id=\"statfooter\" name=\"statfooter\" value=\"1\"  <IF NAME=\"con.statfooter==1\"> checked=\"checked=\"</IF> /></label>\r\n <label>{lang.NO}<input type=\"radio\" id=\"statfooter\" name=\"statfooter\" value=\"0\"  <IF NAME=\"con.statfooter==0\"> checked=\"checked\"</IF> /></label>', 33, 'interface', 0, 0];
//$config_values[] = array('gzip', '0', '<label>{lang.YES}<input type=\"radio\" id=\"gzip\" name=\"gzip\" value=\"1\"  <IF NAME=\"con.gzip==1\"> checked=\"checked\"</IF> /></label>\r\n <label>{lang.NO}<input type=\"radio\" id=\"gzip\" name=\"gzip\" value=\"0\"  <IF NAME=\"con.gzip==0\"> checked=\"checked\"</IF> /></label>', 34, 'interface', 0, 0);
$config_values[] = ['googleanalytics', '', '<input type=\"text\" id=\"googleanalytics\" name=\"googleanalytics\" value=\"{con.googleanalytics}\" size=\"10\" />', 35, 'interface', 0, 0];
$config_values[] = ['enable_captcha', '1', '<label>{lang.YES}<input type=\"radio\" id=\"enable_captcha\" name=\"enable_captcha\" value=\"1\"  <IF NAME=\"con.enable_captcha==1\"> checked=\"checked\"</IF> /></label>\r\n <label>{lang.NO}<input type=\"radio\" id=\"enable_captcha\" name=\"enable_captcha\" value=\"0\"  <IF NAME=\"con.enable_captcha==0\"> checked=\"checked\"</IF> /></label>', 36, 'interface', 0, 0];

// System settings [ invisible configs ]
$config_values[] = ['thmb_dims', '100*100', '', 0, 0, 0];
$config_values[] = ['style', 'bootstrap', '', 0, '0', 0, 0];
$config_values[] = ['new_version', '', '', 0, 0, 0];
$config_values[] = ['db_version', LAST_DB_VERSION, '', 0, 0, 0];
$config_values[] = ['last_online_time_update', time(), '', 0, 0, 1];
$config_values[] = ['klj_clean_files_from', '0', '', 0, 0, 1];
$config_values[] = ['style_depend_on', '', '', 0, 0, 0];
$config_values[] = ['most_user_online_ever', '', '', 0, 0, 1];
$config_values[] = ['expand_menu', '0', '', 0, 0, 1];
$config_values[] = ['firstime', '0', '', 0, 0, 1];
$config_values[] = ['ftp_info', '', '', 0, 0, 0];
$config_values[] = ['queue', '', '', 0, 0, 1];
$config_values[] = ['default_group', '3', '', 0, 0, 1];

//
// Extensions
//

// do it like this : 
//$ext_values[group_id] = array('ext'=>sizeInKB);
$ext_values = [];

//admins
$ext_values[1] = [
    'gif'  => 2097152,
    'png'  => 2097152,
    'jpg'  => 2097152,
    'jpeg' => 2097152,
    'bmp'  => 2097152,
    'zip'  => 2097152,
    'rar'  => 2097152,
];
//guests
$ext_values[2] = [
    'gif'  => 2097152,
    'png'  => 2097152,
    'jpg'  => 2097152,
    'jpeg' => 2097152,
    'bmp'  => 2097152,
    'zip'  => 2097152,
    'rar'  => 2097152,
];
//users
$ext_values[3] = [
    'gif'  => 2097152,
    'png'  => 2097152,
    'jpg'  => 2097152,
    'jpeg' => 2097152,
    'bmp'  => 2097152,
    'zip'  => 2097152,
    'rar'  => 2097152,
];


//
// ACLs
//

$acls_values = [];

//$acls_values['name of acl'] = array(admins, guests, users);
$acls_values['enter_acp']        = [1, 0, 0];
$acls_values['access_fileuser']  = [1, 0, 1];
$acls_values['access_fileusers'] = [1, 1, 1];
$acls_values['access_stats']     = [1, 1, 1];
$acls_values['access_call']      = [1, 1, 1];
$acls_values['access_report']    = [0, 0, 0];
