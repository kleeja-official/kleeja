# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/)
and this project adheres to [Semantic Versioning](http://semver.org/spec/v2.0.0.html).


## 2.3
- fix bug where 'empty files' error when url uplaoding is on.
- fix bug where limiting download size is not working.
- fix bugs where some images in ACP are not shown/missing.
- you can use {username} in folder name now.
- only show/validate captcha for login/admin login if GD is installed.
- fix progress bar (with a style that supports that).

## 2.2
- fix a bug where a user can not upload files with uppercase extensions like PNG.
- fix a bug where a long filename might ruin the download.html page.
- fix a bug where the 'site is closed' not shown.
- new functions add_to_htaccess, remove_from_htaccess to make it easier for plugins to add rules.
- fix typo in download.html link target attribute.


## 2.1
- fix bug where installation shows white page if mysqli is not installed.
- fix bug where some links in admin/users page doesn't work
- prevent non-founder admins from installing/uploading/enabling plugins or styles.
- fix bug where uploading doesn't work because of default php uploading error system.
- fix a problem where a plugin cache still exists after enabling/disabling it.
- fix a bug where file inputs don't increase (reported by  ali iraq)
- fix a bug where an admin can not login when Kleeja is closed.


## 2.0
- New file-based-plugin system. No more 'eval' function.
- Removed the ability to edit a template or restore backup template.
- IP addresses now respect CloudFlare integration
- wrong time at show last visit files/images (reported by: bader_vip)
- fix bug:1104, where kleeja_date doesn't respect user time zone  (reported by: bader_vip)
- fix bug:1124, fix permission for fileuser (reported by: sadiq6210)
- fix bug:1134, where you can download files without waiting using imgf links (reported by: 2mka)
- fix bug:1121, where kleeja web site links are wrong (reported by: yasorno)
- fix bug:1120, where editing ACLs doesn't work .. (reported by: sadiq6210)
- Captcha in admin login to prevent automated bots.
- screenshots+info of styles in control panel
- fix bug when searching for selected images’s by ip, or username.
- fix bug 1102, where mobile devices users can not copy urls from inputs (style)
- fix bug where calls+reports deleting queue doesn't work!  (un-reported)
- fix bug:1117, deleting files from user folder other than page 1 won't work (reported by: bader_vip)
- when enabling htaccess url, rename file from htaccess.tx to .htaccess
- remove KLIVE codes
- fix Default style to be responsive (works with mobile phones + tables)
- fix acp notifications
- alert the user if the file is not allowed [ext + size] before uploading.
- use ig, ip, g, p
- new style based on Bootstrap 4
- new Admin style based on Bootstrap 4
- kljuploader class refactored to an interface, so we could add other uploading methods
- fix image quality problem if watermark is on
- new way of detecting and securing kleeja during uploading.
- open ip search in new window
- fix htaccess rule where fileuser page not accessible by others
- Images has their own links form now.
- Captcha in login page to prevent password guessing.
- you can now use parameters in uploading folder input like {year} {month} {week} {day}.
- show captcha for admin login if first attempt was wrong.
- only support php 7+.


## 1.7
- Kleeja 1.7 is compatible with PHP7 now.
- some other fixes.


## 1.6
- Fix XSS bug at uploading files [ thanks to Ebram Atef @geekpero ]; bug#1253
- Add useful https header to improve security.
- fix compatibility issue with php 5.5; bug#1252, bug#1240, bug#1239, bug#1241
- fix an error with thumbs.php if no GD installed
- fix bug where admin can not change the ACL [permissions] of users. bug##1229
- fix bug where user can’t see his folder if ACL to see other’s folders is off to him. bug#1228.
- remove gzip feature because user who doesn’t know how to use it keep using it. bug#1226
- no more mysql driver, all now transformed to mysqli. bug #1224
- fix bug if number of files that user can upload is 0, he can still upload! bug#1223.
- remove backup feature, no need for it.


## 1.5.4 
- When the database old, tell the user to update.
- fix bug in ACP where secondary menu not appear
- add link to user folder at do.php?id=.. page


## 1.5.3 
- add turkish language.
- disable ajax by default at ACP ( you can add AJAX_ACP in config.php to turn ajax on )
- some style improvments at ACP
- hide un-important items from ACP menu and add a button to show them.
- fix problems with login + captcha that's appeared at 1.5.2


## 1.5.2
- Add Persian language (thanx for dverbame)
- Fix bug with thumbs size (reported by : Tony Broomfield)
- fix bug where Last visit in ACP doesnt use Kleeja zone (reborted by: Bader_vip)
- fix bug where total files number excludes images (reborted by: Bader_vip)
- fix bug where guests can not access user folders, ucp.php?go=fileuser&id=[userid] (reported by: ibragate)
- fix bug where kleeja doesnt respect decoding type when it set to nothing (reported by: althani)
- fix security issue ..
- Username at registering now should be between 4 to 25 characters (reported by ibragate)
- fix bug where user can not open other page while uploading files (reported by gulfup.com)
- faster home page with new improved javascript tabs
- Juqery.js library is included offically with Kleeja now
- fix bug when there is no file and request thumbf=..., it shows txt error instead of image error.
- good look of images at userfile while loading instead of just white spaces.
- return back to "mysql_real_escape_string, (reported by twitter/Abdullah_says)

## 1.5.1
- Fix bug with uploading from URL.
- Fix bug in SQL when reparing tables.
- FIx bug where function is miswritten [helper_thumb_imagick]
- Fix unclear halt at install.php file


## 1.0 (was in 2007)
- ...