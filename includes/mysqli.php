<?php
/**
*
* @package Kleeja
* @copyright (c) 2007 Kleeja.net
* @license ./docs/license.txt
*
*/


//no for directly open
if (! defined('IN_COMMON')) {
    exit();
}

if (! defined('SQL_LAYER')):

define('SQL_LAYER', 'mysqli');

class KleejaDatabase
{
    /** @var mysqli */
    private $connect_id               = null;
    /** @var mysqli_result */
    private $result                   = null;
    public $dbprefix                  = '';
    private $dbname                   = '';
    public $query_num                 = 0;
    private $in_transaction           = 0;
    public $debugr                    = false;
    private $show_errors              = true;


    /**
     * connect
     *
     * @param string $host
     * @param string $db_username
     * @param string $db_password
     * @param string $db_name
     * @param string $dbprefix
     */
    public function __construct($host, $db_username, $db_password, $db_name, $dbprefix)
    {
        $port  = 3306;

        if (strpos($host, ':') !== false) {
            $host = substr($host, 0, strpos($host, ':'));
            $port = (int) substr($host, strpos($host, ':')+1);
        }

        $this->dbprefix        = $dbprefix;
        $this->dbname          = $db_name;

        $this->connect_id = @mysqli_connect($host, $db_username, $db_password, $db_name, $port);

        //no error
        if (defined('SQL_NO_ERRORS') || defined('MYSQL_NO_ERRORS')) {
            $this->show_errors = false;
        }


        if (! $this->connect_id) {
            //loggin -> no database -> close connection
            $this->close();
            $this->error_msg('We can not connect to the server ...');
            return false;
        }

        //connecting
        kleeja_log('[Connected] : ' . kleeja_get_page());


        if (! defined('DISABLE_MYSQL_UTF8')) {
            if (mysqli_set_charset($this->connect_id, 'utf8')) {
                kleeja_log('[Set to UTF8] : --> ');
            }
        }

        return $this->connect_id;
    }

    public function __destruct()
    {
        $this->close();
    }

    public function is_connected()
    {
        return ! (is_resource($this->connect_id) || empty($this->connect_id));
    }

    // close the connection
    public function close()
    {
        if (! $this->is_connected()) {
            return true;
        }

        // Commit any remaining transactions
        if ($this->in_transaction) {
            mysqli_commit($this->connect_id);
        }

        //loggin -> close connection
        kleeja_log('[Closing connection] : ' . kleeja_get_page());

        if (! is_resource($this->connect_id)) {
            return true;
        }

        return @mysqli_close($this->connect_id);
    }

    // encoding functions
    public function set_utf8()
    {
        $this->set_names('utf8');
    }

    public function set_names($charset)
    {
        @mysqli_set_charset($this->connect_id, $charset);
    }

    public function client_encoding()
    {
        return mysqli_character_set_name($this->connect_id);
    }

    public function version()
    {
        $vr = $this->query('SELECT VERSION() AS v');
        $vs = $this->fetch_array($vr);
        $vs = $vs['v'];
        return preg_replace('/^([^-]+).*$/', '\\1', $vs);
    }


    /**
     * execute a query
     *
     * @param  string  $query
     * @param  boolean $transaction
     * @return bool
     */
    public function query($query, $transaction = false)
    {
        //no connection
        if (! $this->is_connected()) {
            return false;
        }

        //
        // Remove any pre-existing queries
        //
        unset($this->result);

        if (! empty($query)) {
            //debug .. //////////////
            $srartum_sql = get_microtime();
            ////////////////

            if ($transaction && ! $this->in_transaction) {
                if (! mysqli_autocommit($this->connect_id, false)) {
                    return false;
                }

                $this->in_transaction = true;
            }

            $this->result = mysqli_query($this->connect_id, $query);

            //debug .. //////////////
            $this->debugr[$this->query_num+1] = [$query, sprintf('%.5f', get_microtime() - $srartum_sql)];
            ////////////////

            if (! $this->result) {
                $this->error_msg('Error In query');
            } else {
                //let's debug it
                kleeja_log('[Query] : --> ' . $query);
            }
        } else {
            if ($this->in_transaction) {
                $this->result = mysqli_commit($this->connect_id);
            }
        }

        //is there any result
        if ($this->result) {
            if ($this->in_transaction) {
                $this->in_transaction = false;

                if (! mysqli_commit($this->connect_id)) {
                    mysqli_rollback($this->connect_id);
                    return false;
                }
            }

            $this->query_num++;
            return $this->result;
        } else {
            if ($this->in_transaction) {
                mysqli_rollback($this->connect_id);
                $this->in_transaction = false;
            }
            return false;
        }
    }

    /**
     * build structured query ['SELECT' => ..., 'FROM' => ..., ...]
     *
     * @param  array  $query
     * @return string
     */
    public function build($query)
    {
        $sql = '';

        if (isset($query['SELECT']) && isset($query['FROM'])) {
            $sql = 'SELECT ' . $query['SELECT'] . ' FROM ' . $query['FROM'];

            if (isset($query['JOINS'])) {
                foreach ($query['JOINS'] as $cur_join) {
                    $sql .= ' ' . key($cur_join) . ' ' . @current($cur_join) . ' ON ' . $cur_join['ON'];
                }
            }

            if (! empty($query['WHERE'])) {
                $sql .= ' WHERE ' . $query['WHERE'];
            }

            if (! empty($query['GROUP BY'])) {
                $sql .= ' GROUP BY ' . $query['GROUP BY'];
            }

            if (! empty($query['HAVING'])) {
                $sql .= ' HAVING ' . $query['HAVING'];
            }

            if (! empty($query['ORDER BY'])) {
                $sql .= ' ORDER BY ' . $query['ORDER BY'];
            }

            if (! empty($query['LIMIT'])) {
                $sql .= ' LIMIT ' . $query['LIMIT'];
            }
        } elseif (isset($query['INSERT'])) {
            $sql = 'INSERT INTO ' . $query['INTO'];

            if (! empty($query['INSERT'])) {
                $sql .= ' (' . $query['INSERT'] . ')';
            }

            $sql .= ' VALUES(' . $query['VALUES'] . ')';
        } elseif (isset($query['UPDATE'])) {
            if (isset($query['PARAMS']['LOW_PRIORITY'])) {
                $query['UPDATE'] = 'LOW_PRIORITY ' . $query['UPDATE'];
            }

            $sql = 'UPDATE ' . $query['UPDATE'] . ' SET ' . $query['SET'];

            if (! empty($query['WHERE'])) {
                $sql .= ' WHERE ' . $query['WHERE'];
            }
        } elseif (isset($query['DELETE'])) {
            $sql = 'DELETE FROM ' . $query['DELETE'];

            if (! empty($query['WHERE'])) {
                $sql .= ' WHERE ' . $query['WHERE'];
            }
        } elseif (isset($query['REPLACE'])) {
            $sql = 'REPLACE INTO ' . $query['INTO'];

            if (! empty($query['REPLACE'])) {
                $sql .= ' (' . $query['REPLACE'] . ')';
            }

            $sql .= ' VALUES(' . $query['VALUES'] . ')';
        }

        return $this->query($sql);
    }

    /**
     * free the memmory from the last results
     *
     * @param  integer $query_id optional
     * @return bool
     */
    public function freeresult($query_id = 0)
    {
        if (! $query_id) {
            $query_id = $this->result;
        }

        if ($query_id) {
            mysqli_free_result($query_id);
            return true;
        } else {
            return false;
        }
    }


    /**
     * fetch results (alias of fetch_array)
     *
     * @param  mysqli_result $query_id
     * @return array
     */
    public function fetch($query_id = 0)
    {
        return $this->fetch_array($query_id);
    }

    /**
     * fetch results
     *
     * @param  mysqli_result $query_id
     * @return array
     */
    public function fetch_array($query_id = 0)
    {
        if (! $query_id) {
            $query_id = $this->result;
        }

        return $query_id ? mysqli_fetch_array($query_id, MYSQLI_ASSOC) : false;
    }

    /**
     * return number of rows of result (not efficient)
     *
     * @param  mysqli_result $query_id
     * @return int
     */
    public function num_rows($query_id = 0)
    {
        if (! $query_id) {
            $query_id = $this->result;
        }

        return $query_id ? mysqli_num_rows($query_id) : false;
    }


    /**
     * return the id of latest inserted record
     *
     * @return int
     */
    public function insert_id()
    {
        return $this->is_connected() ? mysqli_insert_id($this->connect_id) : false;
    }

    /**
     * extra escape
     *
     * @param  string $msg
     * @return string
     */
    public function escape($msg)
    {
        $msg = htmlspecialchars($msg, ENT_QUOTES);
        //$msg = (!get_magic_quotes_gpc()) ? addslashes ($msg) : $msg;
        $msg = $this->real_escape($msg);
        return $msg;
    }

    /**
     * escape
     * @param  string     $msg
     * @return int|string
     */
    public function real_escape($msg)
    {
        if (! $this->is_connected()) {
            return false;
        }

        return mysqli_real_escape_string($this->connect_id, $msg);
    }

    /**
     * number of affected rows by latest action
     *
     * @return int
     */
    public function affected()
    {
        return $this->is_connected() ? mysqli_affected_rows($this->connect_id) : false;
    }

    /**
     * information
     *
     * @return string
     */
    public function server_info()
    {
        return 'MySQLi ' . $this->version();
    }

    /**
     * present error messages
     *
     * @param  string $msg
     * @return void
     */
    private function error_msg($msg)
    {
        if (! $this->show_errors || (defined('SQL_NO_ERRORS') || defined('MYSQL_NO_ERRORS'))) {
            kleeja_log('MySQL: ' . $msg);
            return false;
        }

        list($error_no, $error_msg) = $this->get_error();
        $error_sql                  = @current($this->debugr[$this->query_num+1]);

        //some ppl want hide their table names
        if (! defined('DEV_STAGE')) {
            $error_sql = preg_replace_callback("#\s{1,3}`*{$this->dbprefix}([a-z0-9]+)`*\s{1,3}#", function ($m) {
                return ' <span style="color:blue">' . substr($m[1], 0, 1) . '</span> ';
            }, $error_sql);
            $error_msg = preg_replace_callback("#{$this->dbname}.{$this->dbprefix}([a-z0-9]+)#", function ($m) {
                return ' <span style="color:blue">' . substr($m[1], 0, 1) . '</span> ';
            }, $error_msg);
            $error_sql = preg_replace_callback("#\s{1,3}(from|update|into)\s{1,3}([a-z0-9]+)\s{1,3}#i", function ($m) {
                return $m[1] . ' <span style="color:blue">' . substr($m[2], 0, 1) . '</span> ';
            }, $error_sql);
            $error_msg = preg_replace_callback("#\s{1,3}(from|update|into)\s{1,3}([a-z0-9]+)\s{1,3}#i", function ($m) {
                return $m[1] . ' <span style="color:blue">' . substr($m[2], 0, 1) . '</span> ';
            }, $error_msg);
            $error_msg = preg_replace_callback("#\s'([^']+)'@'([^']+)'#i", function ($m) {
                return ' <span style="color:blue">hidden</span>@' . $m[2] . ' ';
            }, $error_msg);
            $error_sql = preg_replace("#password\s*=\s*'[^']+'#i", "password='<span style=\"color:blue\">hidden</span>'", $error_sql);
        }

        //is this error related to updating?
        $updating_related = false;

        if (strpos($error_msg, 'Unknown column') !== false) {
            $updating_related = true;
        }

        header('HTTP/1.1 500 Internal Server Error');
        $error_message = '<html><head><title>MYSQL ERROR</title>';
        $error_message .= "<style>BODY{font-family:'Tahoma',serif;font-size:12px;}.error {}</style></head><body>";
        $error_message .= '<br />';
        $error_message .= '<div class="error">';
        $error_message .= " <a href='#' onclick='window.location.reload( false );'>click to Refresh this page ...</a><br />";
        $error_message .= '<h2>Sorry , We encountered a MySQL error: ' . ($msg !='' ? $msg : '') . '</h2>';

        if ($error_sql != '') {
            $error_message .= "<br />--[query]-------------------------- <br />$error_sql<br />---------------------------------<br /><br />";
        }
        $error_message .= "[$error_no : $error_msg] <br />";

        if ($updating_related) {
            global $config;
            $error_message .= '<br /><strong>Your Kleeja database might be old, try to update it now from: ' . rtrim($config['siteurl'], '/') . '/install</strong>';
            $error_message .= "<br /><br><strong>If this error happened after installing a plugin, add <span style=\"background-color:#ccc; padding:2px\">define('STOP_PLUGINS', true);</span> to end of config.php file.</strong>";
        }
        $error_message .= "<br /><br /><strong>Script: Kleeja <br /><a href='https://kleeja.net'>Kleeja Website</a></strong>";
        $error_message .= '</b></div>';
        $error_message .= '</body></html>';


        print $error_message;


        //loggin -> error
        kleeja_log('[SQL ERROR] : "' . $error_no . ' : ' . $error_msg . '" -->');

        @$this->close();

        exit();
    }

    /**
     * return last error as [code, message]
     *
     * @return array
     */
    public function get_error()
    {
        if ($this->is_connected()) {
            return [@mysqli_errno($this->connect_id), @mysqli_error($this->connect_id)];
        } else {
            return [@mysqli_connect_errno(), @mysqli_connect_error()];
        }
    }

    public function showErrors()
    {
        $this->show_errors = true;
    }

    public function hideErrors()
    {
        $this->show_errors = false;
    }
}

endif;
