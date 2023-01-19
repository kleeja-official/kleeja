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
    'folders_table'   => "CREATE TABLE `{$dbprefix}folders` (`id` int(11) unsigned NOT NULL auto_increment PRIMARY KEY, `name` varchar(300) collate utf8_bin NOT NULL DEFAULT '', `parent` int(11) unsigned NOT NULL DEFAULT '0', `user` int(11)  NOT NULL DEFAULT '-1', `time` int(11) unsigned NOT NULL DEFAULT '0', KEY `name` (`name`(300)), KEY `user` (`user`), KEY `time` (`time`)) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_bin;",
    'about_files'     => "ALTER TABLE `{$dbprefix}files` ADD `about` LONGTEXT NULL DEFAULT NULL AFTER `real_filename`;",
    'files_folder_id' => "ALTER TABLE `{$dbprefix}files` ADD `fld_id` int(11) unsigned NOT NULL DEFAULT '0' AFTER `real_filename`;",
];