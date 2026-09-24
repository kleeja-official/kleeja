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

if (!defined('SQL_LAYER')):
    define('SQL_LAYER', 'sqlite');

    class KleejaDatabase
    {
        private ?SQLite3 $connect_id = null;
        private SQLite3Result|bool|null $result = null;
        public string $dbprefix = '';
        private string $dbname = '';
        public int $query_num = 0;
        private bool $in_transaction = false;
        public array $debugr = [];
        private bool $show_errors = true;
        //set by close(), so the connection can be opened again if it is needed after that
        private bool $closed = false;

        /**
         * connect
         *
         * @param string $location    path of sqlite database
         * @param string $db_username not needed
         * @param string $db_password not needed
         * @param string $db_name     not needed
         * @param string $dbprefix    tables prefix
         */
        public function __construct(
            string $location,
            string $db_username,
            string $db_password,
            string $db_name,
            string $dbprefix,
        ) {
            $this->dbprefix = $dbprefix;
            $this->dbname = $db_name;

            if (class_exists('SQLite3')) {
                $this->connect();
            } else {
                $this->error_msg('SQLite3 extension is not installed in your server!');
            }

            //no error
            if (defined('SQL_NO_ERRORS')) {
                $this->show_errors = false;
            }

            if (!$this->connect_id) {
                //loggin -> no database -> close connection
                $this->close();
                $this->error_msg(
                    'We can not connect to the sqlite database, check location or existence of the SQLite dirver ...',
                );
            }
        }

        private function connect(): bool
        {
            try {
                $this->connect_id = new SQLite3(PATH . $this->dbname, SQLITE3_OPEN_READWRITE);
            } catch (Exception $e) {
                $this->connect_id = null;

                return false;
            }

            //connecting
            kleeja_log('[Connected] : ' . kleeja_get_page());

            return true;
        }

        /**
         * open the connection again if it was closed by close(), for code that still needs the database after that,
         * like plugins hooked after the page footer or after a download started
         *
         * @return bool
         */
        private function reopen(): bool
        {
            if ($this->is_connected() || !$this->closed) {
                return $this->is_connected();
            }

            $this->closed = false;

            if (!$this->connect()) {
                $this->error_msg(
                    'We can not connect to the sqlite database, check location or existence of the SQLite dirver ...',
                );

                return false;
            }

            return true;
        }

        public function __destruct()
        {
            $this->close();
        }

        public function is_connected(): bool
        {
            return $this->connect_id !== null;
        }

        // close the connection, it will be opened again if a query comes after this
        public function close(): bool
        {
            if (!$this->is_connected()) {
                return true;
            }

            // Commit any remaining transactions
            if ($this->in_transaction) {
                $this->query('COMMIT;');
                $this->in_transaction = false;
            }

            //loggin -> close connection
            kleeja_log('[Closing connection] : ' . kleeja_get_page());

            $closed = @$this->connect_id->close();
            $this->connect_id = null;
            $this->closed = true;

            return $closed;
        }

        // encoding functions
        public function set_utf8(): void
        {
            //$this->set_names('utf8');
        }

        public function set_names(string $charset): void {}

        public function client_encoding(): ?string
        {
            return null;
        }

        public function version(): string
        {
            return SQLite3::version()['versionString'];
        }

        /**
         * execute a query
         *
         * @param  string  $query
         * @param  boolean $transaction
         * @return SQLite3Result|bool result set for reads, true for writes, false on failure
         */
        public function query(string $query, bool $transaction = false): SQLite3Result|bool
        {
            //no connection
            if (!$this->reopen()) {
                return false;
            }

            //
            // Remove any pre-existing queries
            //
            $this->result = null;

            if (strpos($query, 'CREATE TABLE') !== false || strpos($query, 'ALTER DATABASE') !== false) {
                $sqlite_types = [
                    '/AUTO_INCREMENT/i' => '',
                    '/VARCHAR\s?(\\([0-9]+\\))?/i' => 'TEXT',
                    '/COLLATE\s+([a-z0-9_]+)/i' => '',
                    '/(TINY|SMALL|MEDIUM|BIG)?INT\s?(\([0-9]+\))?\s?(UNSIGNED)?/i' => 'INTEGER ',
                    '/(TINY|MEDIUM|LONG)?TEXT/i' => 'TEXT',
                    '/KEY\s`?([a-z0-9_]+)`?\s\(`?([a-z0-9_]+)`?(\([0-9]+\))?\)\s?,?/i' => '',
                    '/\)(\s{0,4}ENGINE=([a-z0-9_]+))?(\s{0,4}DEFAULT)?(\s{0,4}CHARSET=([a-z0-9_]+))?(\s{0,4}COLLATE=([a-z0-9_]+))?(\s{0,4}AUTOINCREMENT)?(\s{0,4}=\s?1)?(\s{0,4};)?/i' =>
                        ')',
                    '/,\s+\)/' => ')',
                    '/INTEGER\s{0,4}NOT\s{0,4}NULL/i' => 'INTEGER',
                ];

                //todo extract keys and add as CREATE INDEX index_name ON table (column);

                foreach ($sqlite_types as $old_type => $new_type) {
                    $query = preg_replace($old_type, $new_type, $query);
                }
            }

            if (!empty($query)) {
                //debug
                $srartum_sql = get_microtime();

                if ($transaction && !$this->in_transaction) {
                    $this->query('BEGIN;');
                    $this->in_transaction = true;
                }

                $this->result = @$this->connect_id->query($query);

                //debug .. //////////////
                $this->debugr[$this->query_num + 1] = [$query, sprintf('%.5f', get_microtime() - $srartum_sql)];
                ////////////////

                if (!$this->result) {
                    $this->error_msg('Error In query');
                } else {
                    //let's debug it
                    kleeja_log('[Query] : --> ' . $query);
                }
            } else {
                if ($this->in_transaction) {
                    $this->result = $this->connect_id->query('COMMIT;');
                }
            }

            //is there any result
            if ($this->result) {
                if ($this->in_transaction) {
                    $this->in_transaction = false;

                    if (!$this->connect_id->query('COMMIT;')) {
                        $this->connect_id->query('ROLLBACK;');

                        return false;
                    }
                }

                $this->query_num++;

                return $this->result;
            } else {
                if ($this->in_transaction) {
                    $this->connect_id->query('ROLLBACK;');
                    $this->in_transaction = false;
                }

                return false;
            }
        }

        /**
         * build structured query ['SELECT' => ..., 'FROM' => ..., ...]
         *
         * @param  array  $query
         * @return SQLite3Result|bool
         */
        public function build(array $query): SQLite3Result|bool
        {
            $sql = '';

            if (isset($query['SELECT']) && isset($query['FROM'])) {
                $sql = 'SELECT ' . $query['SELECT'] . ' FROM ' . $query['FROM'];

                if (isset($query['JOINS'])) {
                    foreach ($query['JOINS'] as $cur_join) {
                        $sql .= ' ' . key($cur_join) . ' ' . current($cur_join) . ' ON ' . $cur_join['ON'];
                    }
                }

                if (!empty($query['WHERE'])) {
                    $sql .= ' WHERE ' . $query['WHERE'];
                }

                if (!empty($query['GROUP BY'])) {
                    $sql .= ' GROUP BY ' . $query['GROUP BY'];
                }

                if (!empty($query['HAVING'])) {
                    $sql .= ' HAVING ' . $query['HAVING'];
                }

                if (!empty($query['ORDER BY'])) {
                    $sql .= ' ORDER BY ' . $query['ORDER BY'];
                }

                if (!empty($query['LIMIT'])) {
                    $sql .= ' LIMIT ' . $query['LIMIT'];
                }
            } elseif (isset($query['INSERT'])) {
                $sql = 'INSERT INTO ' . $query['INTO'];

                if (!empty($query['INSERT'])) {
                    $sql .= ' (' . $query['INSERT'] . ')';
                }

                $sql .= ' VALUES(' . $query['VALUES'] . ')';
            } elseif (isset($query['UPDATE'])) {
                $sql = 'UPDATE ' . $query['UPDATE'] . ' SET ' . $query['SET'];

                if (!empty($query['WHERE'])) {
                    $sql .= ' WHERE ' . $query['WHERE'];
                }
            } elseif (isset($query['DELETE'])) {
                $sql = 'DELETE FROM ' . $query['DELETE'];

                if (!empty($query['WHERE'])) {
                    $sql .= ' WHERE ' . $query['WHERE'];
                }
            } elseif (isset($query['REPLACE'])) {
                $sql = 'REPLACE INTO ' . $query['INTO'];

                if (!empty($query['REPLACE'])) {
                    $sql .= ' (' . $query['REPLACE'] . ')';
                }

                $sql .= ' VALUES(' . $query['VALUES'] . ')';
            }

            return $this->query($sql);
        }

        /**
         * free the memmory from the last results
         *
         * @param  SQLite3Result|bool|null $query_id optional, the last result by default
         * @return bool
         */
        public function freeresult(SQLite3Result|bool|null $query_id = null): bool
        {
            if (!$query_id) {
                $query_id = $this->result;
            }

            if ($query_id instanceof SQLite3Result) {
                $query_id->finalize();

                return true;
            } else {
                return false;
            }
        }

        /**
         * fetch results (alias of fetch_array)
         *
         * @param  SQLite3Result|bool|null $query_id optional, the last result by default
         * @return array|false the next row, or false when there are no more rows
         */
        public function fetch(SQLite3Result|bool|null $query_id = null): array|false
        {
            return $this->fetch_array($query_id);
        }

        /**
         * fetch results
         *
         * @param  SQLite3Result|bool|null $query_id optional, the last result by default
         * @return array|false the next row, or false when there are no more rows
         */
        public function fetch_array(SQLite3Result|bool|null $query_id = null): array|false
        {
            if (!$query_id) {
                $query_id = $this->result;
            }

            if ($query_id instanceof SQLite3Result && $query_id->numColumns() > 0) {
                return $query_id->fetchArray(SQLITE3_ASSOC);
            }

            return false;
        }

        /**
         * return number of rows of result (not efficient)
         *
         * @param  SQLite3Result|bool|null $query_id optional, the last result by default
         * @return int|false
         */
        public function num_rows(SQLite3Result|bool|null $query_id = null): int|false
        {
            if (!$query_id) {
                $query_id = $this->result;
            }

            //only reading queries have rows, and stepping other results would run their query again
            if (!($query_id instanceof SQLite3Result) || $query_id->numColumns() === 0) {
                return false;
            }

            //SQLite does not give the number of rows, so count them then go back to the first row
            $rows = 0;

            while ($query_id->fetchArray(SQLITE3_NUM) !== false) {
                $rows++;
            }

            $query_id->reset();

            return $rows;
        }

        /**
         * return the id of latest inserted record
         *
         * @return int|false
         */
        public function insert_id(): int|false
        {
            return $this->is_connected() ? $this->connect_id->lastInsertRowID() : false;
        }

        /**
         * extra escape
         *
         * @param  string $msg
         * @return string
         */
        public function escape(?string $msg): string
        {
            if ($msg === null || $msg === '') {
                return '';
            }
            $msg = htmlspecialchars($msg, ENT_QUOTES);
            $msg = $this->real_escape($msg);

            return $msg;
        }

        /**
         * escape
         * @param  string $msg
         * @return string
         */
        public function real_escape(string $msg): string
        {
            return SQLite3::escapeString($msg);
        }

        /**
         * number of affected rows by latest action
         *
         * @return int|false
         */
        public function affected(): int|false
        {
            return $this->is_connected() ? $this->connect_id->changes() : false;
        }

        /**
         * information
         *
         * @return string
         */
        public function server_info(): string
        {
            return 'SQLite3 ' . $this->version();
        }

        /**
         * present error messages
         *
         * @param  string $msg
         * @return void
         */
        private function error_msg(string $msg): void
        {
            if (!$this->show_errors || (defined('SQL_NO_ERRORS') || defined('MYSQL_NO_ERRORS'))) {
                kleeja_log('SQLite3: ' . $msg);

                return;
            }

            [$error_no, $error_msg] = $this->get_error();
            $error_sql = @current($this->debugr[$this->query_num + 1]);

            //some ppl want hide their table names
            if (!defined('DEV_STAGE')) {
                $error_sql = preg_replace_callback(
                    "#\s{1,3}`*{$this->dbprefix}([a-z0-9]+)`*\s{1,3}#",
                    function (array $m): string {
                        return ' <span style="color:blue">' . substr($m[1], 0, 1) . '</span> ';
                    },
                    $error_sql,
                );
                $error_msg = preg_replace_callback(
                    "#{$this->dbname}.{$this->dbprefix}([a-z0-9]+)#",
                    function (array $m): string {
                        return ' <span style="color:blue">' . substr($m[1], 0, 1) . '</span> ';
                    },
                    $error_msg,
                );
                $error_sql = preg_replace_callback(
                    '#\s{1,3}(from|update|into)\s{1,3}([a-z0-9]+)\s{1,3}#i',
                    function (array $m): string {
                        return $m[1] . ' <span style="color:blue">' . substr($m[2], 0, 1) . '</span> ';
                    },
                    $error_sql,
                );
                $error_msg = preg_replace_callback(
                    '#\s{1,3}(from|update|into)\s{1,3}([a-z0-9]+)\s{1,3}#i',
                    function (array $m): string {
                        return $m[1] . ' <span style="color:blue">' . substr($m[2], 0, 1) . '</span> ';
                    },
                    $error_msg,
                );
                $error_msg = preg_replace_callback(
                    "#\s'([^']+)'@'([^']+)'#i",
                    function (array $m): string {
                        return ' <span style="color:blue">hidden</span>@' . $m[2] . ' ';
                    },
                    $error_msg,
                );
                $error_sql = preg_replace(
                    "#password\s*=\s*'[^']+'#i",
                    "password='<span style=\"color:blue\">hidden</span>'",
                    $error_sql,
                );
            }

            //is this error related to updating?
            $updating_related = false;

            if (strpos($error_msg, 'Unknown column') !== false || strpos($error_msg, 'no such table') !== false) {
                $updating_related = true;
            }

            header('HTTP/1.1 500 Internal Server Error');
            $error_message = '<html><head><title>MYSQL ERROR</title>';
            $error_message .= "<style>BODY{font-family:'Tahoma',serif;font-size:12px;}.error {}</style></head><body>";
            $error_message .= '<br />';
            $error_message .= '<div class="error">';
            $error_message .=
                " <a href='#' onclick='window.location.reload( false );'>click to Refresh this page ...</a><br />";
            $error_message .= '<h2>Sorry , We encountered a MySQL error: ' . ($msg != '' ? $msg : '') . '</h2>';

            if ($error_sql != '') {
                $error_message .= "<br />--[query]-------------------------- <br />$error_sql<br />---------------------------------<br /><br />";
            }
            $error_message .= "[$error_no : $error_msg] <br />";

            if ($updating_related) {
                global $config;
                $error_message .=
                    '<br /><strong>Your Kleeja database might be old, try to update it now from: ' .
                    rtrim($config['siteurl'], '/') .
                    '/install</strong>';
                $error_message .=
                    "<br /><br><strong>If this error happened after installing a plugin, add <span style=\"background-color:#ccc; padding:2px\">define('STOP_PLUGINS', true);</span> to end of config.php file.</strong>";
            }
            $error_message .=
                "<br /><br /><strong>Script: Kleeja <br /><a href='https://kleeja.net'>Kleeja Website</a></strong>";
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
        public function get_error(): array
        {
            if ($this->connect_id) {
                return [$this->connect_id->lastErrorCode(), $this->connect_id->lastErrorMsg()];
            } else {
                return [0, 'uknown-error-not-connected'];
            }
        }
    }
endif;
