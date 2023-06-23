<?php
/**
*
* @package install
* @copyright (c) 2007 Kleeja.net
* @license ./docs/license.txt
*
*/

// not for directly open
if (! defined('IN_COMMON')) {
    exit();
}


if (empty($install_sqls) || ! is_array($install_sqls)) {
    $install_sqls = [];
}

$install_sqls['ALTER_DATABASE_UTF'] = "
ALTER DATABASE `{$dbname}` DEFAULT CHARACTER SET utf8 COLLATE utf8_bin
";


$install_sqls['call'] = "
CREATE TABLE `{$dbprefix}call` (
  `id` int(10) NOT NULL auto_increment PRIMARY KEY,
  `name` varchar(200) collate utf8_bin NOT NULL,
  `text` varchar(350) collate utf8_bin NOT NULL,
  `mail` varchar(350) collate utf8_bin NOT NULL,
  `time` int(11) NOT NULL,
  `ip` varchar(40) collate utf8_bin NOT NULL
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_bin;
";

$install_sqls['reports'] = "
CREATE TABLE `{$dbprefix}reports` (
  `id` int(10) NOT NULL auto_increment PRIMARY KEY,
  `name` varchar(350) collate utf8_bin NOT NULL,
  `mail` varchar(350) collate utf8_bin NOT NULL,
  `url` varchar(250) collate utf8_bin NOT NULL,
  `text` varchar(400) collate utf8_bin NOT NULL,
  `time` int(11) NOT NULL,
  `ip` varchar(40) collate utf8_bin NOT NULL
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_bin;
";


$install_sqls['stats'] = "
CREATE TABLE `{$dbprefix}stats` (
  `files` int(11) unsigned NOT NULL DEFAULT '0',
  `imgs` int(11) unsigned NOT NULL DEFAULT '0',
  `users` int(11) unsigned NOT NULL DEFAULT '0',
  `sizes` bigint(20) NOT NULL DEFAULT '0',
  `last_file` varchar(350) collate utf8_bin NOT NULL,
  `last_f_del` int(10) NOT NULL,
  `today` int(4) NOT NULL,
  `counter_today` int(12) NOT NULL,
  `counter_all` int(12) NOT NULL,
  `counter_yesterday` int(12) NOT NULL,
  `ban` text collate utf8_bin NOT NULL,
  `last_google` int(11) unsigned NOT NULL,
  `google_num` int(11) unsigned NOT NULL,
  `last_bing` int(11) unsigned NOT NULL,
  `bing_num` int(11) unsigned NOT NULL,
  `rules` text collate utf8_bin NOT NULL,
  `ex_header` text collate utf8_bin NOT NULL,
  `ex_footer` text collate utf8_bin NOT NULL,
  `lastuser` varchar(300) collate utf8_bin NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;
";


$install_sqls['users'] = "
CREATE TABLE `{$dbprefix}users` (
  `id` int(10) NOT NULL auto_increment PRIMARY KEY,
  `name` varchar(300) collate utf8_bin NOT NULL,
  `group_id` int(11) unsigned NOT NULL DEFAULT '3',
  `password` varchar(200) collate utf8_bin NOT NULL,
  `password_salt` varchar(250) collate utf8_bin NOT NULL,
  `mail` varchar(350) collate utf8_bin NOT NULL,
  `founder` tinyint(1) NOT NULL default '0',
  `session_id` char(32) COLLATE utf8_bin NOT NULL DEFAULT '',
  `clean_name` varchar(300) collate utf8_bin NOT NULL,
  `last_visit` INT(11)  NOT NULL DEFAULT '0',
  `register_time` int(11) unsigned NOT NULL DEFAULT '0',
  `show_my_filecp` tinyint(1) unsigned NOT NULL default '1',
  `new_password` varchar(200) COLLATE utf8_bin NOT NULL DEFAULT '',
  `hash_key` varchar(200) COLLATE utf8_bin NOT NULL DEFAULT '',
  KEY `clean_name` (`clean_name`(300)),
  KEY `group_id` (`group_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_bin;
";

$install_sqls['files'] = "
CREATE TABLE `{$dbprefix}files` (
  `id` int(11) unsigned NOT NULL auto_increment PRIMARY KEY,
  `last_down` int(11) unsigned NOT NULL DEFAULT '0',
  `name` varchar(300) collate utf8_bin NOT NULL DEFAULT '',
  `real_filename` VARCHAR( 350 ) collate utf8_bin NOT NULL DEFAULT '',
  `about` LONGTEXT collate utf8_bin,
  `size` bigint(20) unsigned NOT NULL DEFAULT '0',
  `uploads` int(11) unsigned NOT NULL DEFAULT '0',
  `time` int(11) unsigned NOT NULL DEFAULT '0',
  `type` varchar(20) collate utf8_bin NOT NULL,
  `folder` varchar(100) collate utf8_bin NOT NULL,
  `report` int(11) unsigned  NOT NULL DEFAULT '0',
  `user` int(11)  NOT NULL default '-1',
  `code_del` varchar(150) collate utf8_bin NOT NULL DEFAULT '',
  `user_ip` VARCHAR( 250 ) NOT NULL DEFAULT '',
  `id_form` VARCHAR( 100 ) NOT NULL DEFAULT 'id',
  KEY `name` (`name`(300)),
  KEY `user` (`user`),
  KEY `code_del` (`code_del`(150)),
  KEY `time` (`time`),
  KEY `last_down` (`last_down`),
  KEY `type` (`type`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_bin;
";



$install_sqls['config'] = "
CREATE TABLE `{$dbprefix}config` (
  `name` varchar(255) collate utf8_bin NOT NULL PRIMARY KEY,
  `value` varchar(255) collate utf8_bin NOT NULL DEFAULT '',
  `option` mediumtext collate utf8_bin  NOT NULL,
  `display_order` int(10)  NOT NULL DEFAULT '1',
  `type` varchar(20) NULL DEFAULT 'other',
  `plg_id` int(11) NOT NULL DEFAULT '0',
  `dynamic` tinyint(1) NOT NULL DEFAULT '0',
  KEY `type` (`type`),
  KEY `plg_id` (`plg_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;
";


$install_sqls['plugins'] = "
CREATE TABLE `{$dbprefix}plugins` (
  `plg_id` int(11) unsigned NOT NULL auto_increment PRIMARY KEY,
  `plg_name` varchar(255) collate utf8_bin NOT NULL DEFAULT '',
  `plg_ver` varchar(255) collate utf8_bin NOT NULL,
  `plg_author` varchar(255) collate utf8_bin NOT NULL DEFAULT '',
  `plg_dsc` mediumtext COLLATE utf8_bin NOT NULL,
  `plg_icon` blob NOT NULL,
  `plg_uninstall` mediumtext COLLATE utf8_bin NOT NULL,
  `plg_disabled` tinyint(1) unsigned NOT NULL default '0',
  `plg_instructions` mediumtext COLLATE utf8_bin NOT NULL,
  `plg_store` longtext COLLATE utf8_bin NOT NULL,
  `plg_files` text COLLATE utf8_bin NOT NULL,
  KEY `plg_name` (`plg_name`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_bin ;
";

$install_sqls['lang'] = "
CREATE TABLE `{$dbprefix}lang` (
  `word` varchar(255) collate utf8_bin NOT NULL ,
  `trans` varchar(255) collate utf8_bin NOT NULL DEFAULT '',
  `lang_id` varchar(100) COLLATE utf8_bin NOT NULL DEFAULT 'en',
  `plg_id` int(11) unsigned NOT NULL DEFAULT '0',
  KEY `lang_id` (`lang_id`),
  KEY `plg_id` (`plg_id`),
  KEY `word` (`word`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;
";

$install_sqls['groups'] = "
CREATE TABLE `{$dbprefix}groups` (
  `group_id` int(11) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `group_name` varchar(255) COLLATE utf8_bin NOT NULL,
  `group_is_default` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `group_is_essential` tinyint(1) unsigned NOT NULL DEFAULT '0'
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_bin;
";

$install_sqls['groups_data'] = "
CREATE TABLE `{$dbprefix}groups_data` (
  `group_id` int(11) unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8_bin NOT NULL,
  `value` varchar(255) COLLATE utf8_bin NOT NULL DEFAULT '',
  KEY `group_id` (`group_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;
";

$install_sqls['groups_acl'] = "
CREATE TABLE `{$dbprefix}groups_acl` (
  `acl_name` varchar(255) COLLATE utf8_bin NOT NULL,
  `group_id` int(11) unsigned NOT NULL,
  `acl_can` tinyint(1) unsigned NOT NULL DEFAULT '0',
  KEY `group_id` (`group_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;
";

$install_sqls['groups_exts'] = "
CREATE TABLE `{$dbprefix}groups_exts` (
  `ext_id` int(11) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `ext` varchar(20) COLLATE utf8_bin NOT NULL,
  `group_id` int(11) unsigned NOT NULL DEFAULT '0',
  `size` bigint(11) unsigned NOT NULL DEFAULT '0',
  KEY `group_id` (`group_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin AUTO_INCREMENT=1;
";

$install_sqls['filters'] = "
CREATE TABLE `{$dbprefix}filters` (
  `filter_id` int(11) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `filter_uid` varchar(30) COLLATE utf8_bin  NOT NULL DEFAULT '',
  `filter_type` varchar(20) COLLATE utf8_bin NOT NULL,
  `filter_value` varchar(255) COLLATE utf8_bin NOT NULL,
  `filter_time` int(11) unsigned  NOT NULL DEFAULT '0',
  `filter_user` int(11) unsigned NOT NULL DEFAULT '0',
  `filter_status` varchar(50) COLLATE utf8_bin NOT NULL DEFAULT '',
  KEY `filter_user` (`filter_user`),
  KEY `filter_uid` (`filter_uid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin AUTO_INCREMENT=1 ;
";

$install_sqls['stats_insert']   = "INSERT INTO `{$dbprefix}stats`  VALUES (0,0,1,0,0," . time() . ",0,0,0,0,'',0,0,0,0,'','','','')";
$install_sqls['users_insert']   = "INSERT INTO `{$dbprefix}users` (`id`,`name`,`group_id`,`password`,`password_salt`,`mail`,`founder`,`clean_name`) VALUES (1,'" . $user_name . "', 1, '" . $user_pass . "','" . $user_salt . "', '" . $user_mail . "', 1,'" . $clean_name . "')";
$install_sqls['TeamMsg_insert'] = "INSERT INTO `{$dbprefix}call` (`name`,`text`,`mail`,`time`,`ip`) VALUES ('" . $SQL->escape($lang['KLEEJA_TEAM_MSG_NAME']) . "', '" . $SQL->real_escape(nl2br($lang['KLEEJA_TEAM_MSG_TEXT'])) . "','info@kleeja.net', " . time() . ", '127.0.0.1')";
$install_sqls['groups_insert']  = "INSERT INTO `{$dbprefix}groups` (`group_id`, `group_name`, `group_is_default`, `group_is_essential`) VALUES
(1, '{lang.ADMINS}', 0, 1),
(2, '{lang.GUESTS}', 0, 1),
(3, '{lang.USERS}', 1, 1);";
