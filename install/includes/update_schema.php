<?php
/**
*
* @package install
* @copyright (c) 2007 Kleeja.net
* @license ./docs/license.txt
*
*/

// not for directly open
if (! defined('IN_COMMON'))
{
    exit();
}


// drop older versions update support after 2 year of release or as appropriate in order to
// make this file smaller in size.
$update_schema   = [];

$update_schema[9]['sql'] = [
    'files_size_big'   => "ALTER TABLE `{$dbprefix}files` CHANGE `size` `size` BIGINT(20)  NOT NULL  DEFAULT '0';",
    'group_size_big'   => "ALTER TABLE `{$dbprefix}groups_exts` CHANGE `size` `size` BIGINT(20)  NOT NULL  DEFAULT '0';",
    'files_index_type' => "ALTER TABLE `{$dbprefix}files` ADD INDEX `type` (`type`);",
    'id_form_img'      => 'INSERT INTO `' . $dbprefix . 'config` (`name`, `value`, `option`, `display_order`, `type`, `plg_id`, `dynamic`) VALUES (\'id_form_img\', X\'6964\', \'<select id=\"id_form_img\" name=\"id_form_img\">\r\n <option <IF NAME=\"con.id_form_img==id\">selected=\"selected\"</IF> value=\"id\">{lang.IDF_IMG}</option>\r\n <option <IF NAME=\"con.id_form_img ==filename\">selected=\"selected\"</IF> value=\"filename\">{lang.IDFF_IMG}</option>\r\n<option <IF NAME=\"con.id_form_img ==direct\">selected=\"selected\"</IF> value=\"direct\">{lang.IDFD_IMG}</option>\r\n </select>\n\', \'21\', X\'75706C6F6164\', \'0\', \'0\');',
];


// $update_schema[9]['functions'] = [
//     function () {
//     },
//     function () {
//     },
// ];

$update_schema[10]['sql'] = [
    'about_files'           => "ALTER TABLE `{$dbprefix}files` ADD `about` LONGTEXT NULL DEFAULT NULL AFTER `real_filename`;",
    'enable_multipart'      => "INSERT INTO `{$dbprefix}groups_data` (`group_id`, `name`, `value`) SELECT `group_id`, 'enable_multipart', 1 FROM `{$dbprefix}groups`;",
    'user_storage_size'     => "ALTER TABLE `{$dbprefix}users` ADD `storage_size` bigint(20) NOT NULL DEFAULT '0' AFTER `hash_key`;",
    'group_max_storage'     => "INSERT INTO `{$dbprefix}groups_data` (`group_id`, `name`, `value`) SELECT `group_id`, 'max_storage', 0 FROM `{$dbprefix}groups`;",
    'multipart_config'      => 'INSERT INTO `' . $dbprefix . 'config` (`name`, `value`, `option`, `display_order`, `type`, `plg_id`, `dynamic`) VALUES (\'enable_multipart\', 1, \'<label>{lang.YES}<input type=\"radio\" id=\"enable_multipart\" name=\"enable_multipart\" value=\"1\"  <IF NAME=\"con.enable_multipart==1\"> checked=\"checked\"</IF> /></label>\r\n <label>{lang.NO}<input type=\"radio\" id=\"enable_multipart\" name=\"enable_multipart\" value=\"0\"  <IF NAME=\"con.enable_multipart==0\"> checked=\"checked\"</IF> /></label>\', 45, \'groups\', 0, 0);',
    'max_storage_config'    => 'INSERT INTO `' . $dbprefix . 'config` (`name`, `value`, `option`, `display_order`, `type`, `plg_id`, `dynamic`) VALUES (\'max_storage\', 0, \'<input type=\"text\" id=\"max_storage\" name=\"max_storage\" value=\"{con.max_storage}\" size=\"20\" style=\"direction:ltr\" />\', 11, \'groups\', 0, 0);',
];
