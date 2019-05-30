<?php

//fill these variables with your data
$dbtype        = 'sqlite';
$dbserver          = 'localhost'; //database server
$dbuser            = 'root'; // database user
$dbpass            = '112233'; // database password
// $dbname            = 'kleeja'; // database name
$dbname            = 'kleeja.db'; // database name
$dbprefix          = 'klj_'; // if you use prefix for tables , fill it


define('DEV_STAGE', true);
define('STOP_TPL_CACHE', true);
define('STOP_CAPTCHA', true);
