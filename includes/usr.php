<?php
/**
 *
 * @package Kleeja
 * @copyright (c) 2007 Kleeja.net
 * @license ./docs/license.txt
 *
 */

//no for directly open
if (!defined('IN_COMMON')) {
    exit();
}

class usrcp
{
    public function data(
        string $name,
        string $pass,
        bool $hashed = false,
        int $expire = 86400,
        bool $loginadm = false,
    ): bool {
        //expire
        $expire = time() + ((int) $expire ? intval($expire) : 86400);
        $name = trim($name);
        $pass = trim($pass);

        $return_now = $login_status = false;

        is_array($plugin_run_result = Plugins::getInstance()->run('data_func_usr_class', get_defined_vars()))
            ? extract($plugin_run_result)
            : null; //run hook

        if ($return_now) {
            return $login_status;
        }

        //normal
        return $this->normal($name, $pass, $expire, $hashed, $loginadm);
    }

    //get username by id
    public function usernamebyid(int $user_id)
    {
        $return_now = $auth_status = false;

        is_array($plugin_run_result = Plugins::getInstance()->run('auth_func_usr_class', get_defined_vars()))
            ? extract($plugin_run_result)
            : null; //run hook

        if ($return_now) {
            return $auth_status;
        }

        //normal system
        $u = $this->get_data('name', $user_id);

        return $u['name'];
    }

    //now our table, normal user system
    public function normal(string $name, string $pass, int $expire, bool $hashed = false, bool $loginadm = false): bool
    {
        global $SQL, $dbprefix, $config, $userinfo;

        $userinfo = [
            'id' => 0,
            'group_id' => 2,
        ];

        $query = [
            'SELECT' => '*',
            'FROM' => "{$dbprefix}users",
            'LIMIT' => '1',
        ];

        if ($hashed) {
            $query['WHERE'] = 'id=' . intval($name) . " and password='" . $SQL->escape($pass) . "'";
        } else {
            $query['WHERE'] = "clean_name='" . $SQL->real_escape($this->cleanusername($name)) . "'";
        }

        is_array($plugin_run_result = Plugins::getInstance()->run('qr_select_usrdata_n_usr_class', get_defined_vars()))
            ? extract($plugin_run_result)
            : null; //run hook
        $result = $SQL->build($query);

        if ($SQL->num_rows($result)) {
            while ($row = $SQL->fetch_array($result)) {
                if (empty($row['password'])) {
                    //more security
                    return false;
                }

                $phppass = $hashed ? $pass : $pass . $row['password_salt'];

                //CHECK IF IT'S MD5 PASSWORD
                if (strlen($row['password']) == '32' && empty($row['password_salt']) && defined('CONVERTED_SCRIPT')) {
                    $passmd5 = md5($pass);

                    ////update old md5 hash to phpass hash
                    if ($row['password'] == $passmd5) {
                        ////new salt
                        $new_salt = substr(base64_encode(pack('H*', sha1(mt_rand()))), 0, 7);
                        ////new password hash
                        $new_password = $this->kleeja_hash_password(trim($pass) . $new_salt);

                        is_array(
                            $plugin_run_result = Plugins::getInstance()->run(
                                'qr_update_usrdata_md5_n_usr_class',
                                get_defined_vars(),
                            ),
                        )
                            ? extract($plugin_run_result)
                            : null; //run hook

                        ////update now !!
                        $update_query = [
                            'UPDATE' => "{$dbprefix}users",
                            'SET' => "password='" . $new_password . "' ,password_salt='" . $new_salt . "'",
                            'WHERE' => 'id=' . intval($row['id']),
                        ];

                        $SQL->build($update_query);
                    } else {
                        //if the password is wrong
                        return false;
                    }
                }

                if (
                    ($phppass != $row['password'] && $hashed) ||
                    ($this->kleeja_hash_password($phppass, $row['password']) != true && $hashed == false)
                ) {
                    return false;
                }

                //Avoid dfining constants again for admin panel login
                if (!$loginadm) {
                    define('USER_ID', $row['id']);
                    define('GROUP_ID', $row['group_id']);
                    define('USER_NAME', $row['name']);
                    define('USER_MAIL', $row['mail']);
                    define('LAST_VISIT', $row['last_visit']);
                }

                //all user fileds info
                $userinfo = $row;

                $user_y = base64_encode(
                    serialize([
                        'id' => $row['id'],
                        'name' => $row['name'],
                        'mail' => $row['mail'],
                        'last_visit' => $row['last_visit'],
                    ]),
                );

                if (!$hashed && !$loginadm) {
                    $hash_key_expire = sha1(md5($config['h_key'] . $row['password']) . $expire);
                    cookie()->set(
                        'ulogu',
                        $this->en_de_crypt(
                            $row['id'] .
                                '|' .
                                $row['password'] .
                                '|' .
                                $expire .
                                '|' .
                                $hash_key_expire .
                                '|' .
                                $row['group_id'] .
                                '|' .
                                $user_y,
                        ),
                        $expire,
                    );
                }

                //if last visit > 1 minute then update it
                if (empty($row['last_visit']) || time() - $row['last_visit'] > 60) {
                    $update_last_visit = [
                        'UPDATE' => "{$dbprefix}users",
                        'SET' => 'last_visit=' . time(),
                        'WHERE' => 'id=' . intval($row['id']),
                    ];

                    $SQL->build($update_last_visit);
                }

                is_array(
                    $plugin_run_result = Plugins::getInstance()->run(
                        'qr_while_usrdata_n_usr_class',
                        get_defined_vars(),
                    ),
                )
                    ? extract($plugin_run_result)
                    : null; //run hook
            }
            $SQL->freeresult($result);

            unset($pass);

            return true;
        } else {
            return false;
        }
    }

    /*
        get user data
        new function:1rc5+
    */
    public function get_data(string $type = '*', int $user_id = 0)
    {
        global $dbprefix, $SQL;

        if (!$user_id) {
            $user_id = $this->id();
        }

        //todo :
        //if type != '*' and contains no , and type in 'name, id, email' return $this->id .. etc

        //te get files and update them !!
        $query_name = [
            'SELECT' => $type,
            'FROM' => "{$dbprefix}users",
            'WHERE' => 'id=' . intval($user_id),
        ];

        is_array($plugin_run_result = Plugins::getInstance()->run('qr_select_userdata_in_usrclass', get_defined_vars()))
            ? extract($plugin_run_result)
            : null; //run hook
        $data_user = $SQL->fetch_array($SQL->build($query_name));

        return $data_user;
    }

    // user ids
    public function id()
    {
        is_array($plugin_run_result = Plugins::getInstance()->run('id_func_usr_class', get_defined_vars()))
            ? extract($plugin_run_result)
            : null; //run hook

        return defined('USER_ID') ? USER_ID : false;
    }

    // group ids
    public function group_id()
    {
        is_array($plugin_run_result = Plugins::getInstance()->run('group_id_func_usr_class', get_defined_vars()))
            ? extract($plugin_run_result)
            : null; //run hook

        return defined('GROUP_ID') ? GROUP_ID : false;
    }

    // user name
    public function name()
    {
        is_array($plugin_run_result = Plugins::getInstance()->run('name_func_usr_class', get_defined_vars()))
            ? extract($plugin_run_result)
            : null; //run hook

        return defined('USER_NAME') ? USER_NAME : false;
    }

    // user mail
    public function mail()
    {
        is_array($plugin_run_result = Plugins::getInstance()->run('mail_func_usr_class', get_defined_vars()))
            ? extract($plugin_run_result)
            : null; //run hook

        return defined('USER_MAIL') ? USER_MAIL : false;
    }

    // logout func
    public function logout(): bool
    {
        is_array($plugin_run_result = Plugins::getInstance()->run('logout_func_usr_class', get_defined_vars()))
            ? extract($plugin_run_result)
            : null; //run hook

        //acp
        if (user_can('enter_acp') && !empty($_SESSION['ADMINLOGIN'])) {
            $this->logout_cp();
        }

        //is ther any cookies
        cookie()->set('ulogu', '', time() - 31536000); //31536000 = year

        return true;
    }

    // logut just from acp
    public function logout_cp(): bool
    {
        is_array($plugin_run_result = Plugins::getInstance()->run('logout_cp_func_usr_class', get_defined_vars()))
            ? extract($plugin_run_result)
            : null; //run hook

        if (!empty($_SESSION['ADMINLOGIN'])) {
            unset($_SESSION['ADMINLOGIN'], $_SESSION['USER_SESS'] /*, $_SESSION['LAST_VISIT']*/);
        }

        return true;
    }

    //clean usernames
    public function cleanusername(string $uname): string
    {
        is_array($plugin_run_result = Plugins::getInstance()->run('cleanusername_func_usr_class', get_defined_vars()))
            ? extract($plugin_run_result)
            : null; //run hook

        static $arabic_t = [];
        static $latin_t = [
            [
                'á',
                'à',
                'â',
                'ã',
                'å',
                'Á',
                'À',
                'Â',
                'Ã',
                'Å',
                'é',
                'è',
                'ê',
                'ë',
                'É',
                'È',
                'Ê',
                'í',
                'ì',
                'ï',
                'î',
                'Í',
                'Ì',
                'Î',
                'Ï',
                'ò',
                'ó',
                'ô',
                'õ',
                'º',
                'ø',
                'Ó',
                'Ò',
                'Ô',
                'Õ',
                'Ø',
                'ú',
                'ù',
                'û',
                'Ú',
                'Ù',
                'Û',
                'ç',
                'Ç',
                'Ñ',
                'ñ',
                'ÿ',
                'Ë',
            ],
            [
                'a',
                'a',
                'a',
                'a',
                'a',
                'a',
                'a',
                'a',
                'a',
                'a',
                'e',
                'e',
                'e',
                'e',
                'e',
                'e',
                'e',
                'i',
                'i',
                'i',
                'i',
                'i',
                'i',
                'i',
                'i',
                'o',
                'o',
                'o',
                'o',
                'o',
                'o',
                'o',
                'o',
                'o',
                'o',
                'o',
                'u',
                'u',
                'u',
                'u',
                'u',
                'u',
                'c',
                'c',
                'n',
                'n',
                'y',
                'e',
            ],
        ];

        if (empty($arabic_t)) {
            //Arabic chars must be stay in utf8 format, so we encoded them
            $arabic_t = unserialize(
                base64_decode(
                    'YToyOntpOjA7YToxMjp7aTowO3M6Mjoi2KMiO2k6MTtzOjI6ItilIjtpOjI7czoyOiLYpCI7aTozO3M6Mjoi2YAiO2k6NDtzOjI6Itm' .
                        'LIjtpOjU7czoyOiLZjCI7aTo2O3M6Mjoi2Y8iO2k6NztzOjI6ItmOIjtpOjg7czoyOiLZkCI7aTo5O3M6Mjoi2ZIiO2k6MTA7czoyOiLYoiI7aToxMTtzOjI6ItimIjt9aToxO' .
                        '2E6MTI6e2k6MDtzOjI6ItinIjtpOjE7czoyOiLYpyI7aToyO3M6Mjoi2YgiO2k6MztzOjA6IiI7aTo0O3M6MDoiIjtpOjU7czowOiIiO2k6NjtzOjA6IiI7aTo3O3M6MDoiIjt' .
                        'pOjg7czowOiIiO2k6OTtzOjA6IiI7aToxMDtzOjI6ItinIjtpOjExO3M6Mjoi2YkiO319',
                ),
            );
        }

        $uname = str_replace($latin_t[0], $latin_t[1], $uname); //replace confusable Latin chars
        $uname = str_replace($arabic_t[0], $arabic_t[1], $uname); //replace confusable Arabic chars
        $uname = preg_replace('#(?:[\x00-\x1F\x7F]+|(?:\xC2[\x80-\x9F])+)#', '', $uname); //un-wanted utf8 control chars
        $uname = preg_replace('# {2,}#', ' ', $uname); //2+ spaces with one space

        return strtolower($uname);
    }

    //depand on phpass class
    public function kleeja_hash_password(string $password, string $check_pass = '')
    {
        include_once 'phpass.php';

        is_array(
            $plugin_run_result = Plugins::getInstance()->run('kleeja_hash_password_func_usr_class', get_defined_vars()),
        )
            ? extract($plugin_run_result)
            : null; //run hook

        $hasher = new PasswordHash(8, true);
        $return = $hasher->HashPassword($password);

        //return check or hash
        return $check_pass != false ? $hasher->CheckPassword($password, $check_pass) : $return;
    }

    //kleeja cookie
    public function kleeja_set_cookie(string $name, string $value, int $expire): void
    {
        // for plugins that are still using the old version of kleeja
        cookie()->set($name, $value, $expire);
    }

    //encrypt and decrypt any data with our function
    public function en_de_crypt(string $data, int $type = 1): string
    {
        global $config;
        static $txt = [];

        if (empty($txt)) {
            if (empty($config['h_key'])) {
                $config['h_key'] = sha1(microtime());
            }

            $chars = str_split($config['h_key']);

            foreach (range('a', 'z') as $k => $v) {
                if (!isset($chars[$k])) {
                    break;
                }
                $txt[$v] = $chars[$k] . $k . '-';
            }
        }

        switch ($type) {
            case 1:
                $data = str_replace('=', '_', base64_encode($data));
                $data = strtr($data, $txt);

                break;

            case 2:
                $txtx = array_flip($txt);
                $txtx = array_reverse($txtx, true);
                $data = strtr($data, $txtx);
                $data = base64_decode(str_replace('_', '=', $data));

                break;
        }

        return $data;
    }

    //
    //get cookie
    //
    public function kleeja_get_cookie(string $name)
    {
        // for plugins that are still using old version of kleeja
        return cookie()->get($name);
    }

    //check if user is admin or not
    //return : mean return true or false, but if return is false will show msg
    public function kleeja_check_user(): bool
    {
        global $config, $userinfo;

        is_array(
            $plugin_run_result = Plugins::getInstance()->run('kleeja_check_user_func_usr_class', get_defined_vars()),
        )
            ? extract($plugin_run_result)
            : null; //run hook

        //to make sure
        $userinfo = [
            'id' => -1,
            'group_id' => 2,
        ];

        //if login up
        if (cookie()->get('ulogu')) {
            $user_data = false;

            [$user_id, $hashed_password, $expire_at, $hashed_expire, $group_id, $u_info] = @explode(
                '|',
                $this->en_de_crypt(cookie()->get('ulogu'), 2),
            );

            //if not expire
            if ($hashed_expire == sha1(md5($config['h_key'] . $hashed_password) . $expire_at) && $expire_at > time()) {
                if (user_can('enter_acp', $group_id)) {
                    $user_data = $this->data($user_id, $hashed_password, true, $expire_at);
                } else {
                    if (!empty($u_info)) {
                        $userinfo = unserialize(base64_decode($u_info));
                        $userinfo['group_id'] = $group_id;
                        $userinfo['password'] = $hashed_password;

                        define('USER_ID', $userinfo['id']);
                        define('GROUP_ID', $userinfo['group_id']);
                        define('USER_NAME', $userinfo['name']);
                        define('USER_MAIL', $userinfo['mail']);
                        define('LAST_VISIT', $userinfo['last_visit']);
                        $user_data = true;
                    }
                }
            }

            if ($user_data == false) {
                $this->logout();
            } else {
                return $user_data;
            }
        } else {
            //guest
            define('USER_ID', $userinfo['id']);
            define('GROUP_ID', $userinfo['group_id']);
        }

        return false; //nothing
    }
}
