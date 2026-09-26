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

class KleejaDatabase
{
    //MySQL definitions that SQLite does not understand, used to convert CREATE TABLE queries
    private const SQLITE_TYPES = [
        '/AUTO_INCREMENT/i' => '',
        '/VARCHAR\s?(\\([0-9]+\\))?/i' => 'TEXT',
        '/COLLATE\s+([a-z0-9_]+)/i' => '',
        '/(TINY|SMALL|MEDIUM|BIG)?INT\s?(\([0-9]+\))?\s?(UNSIGNED)?/i' => 'INTEGER ',
        //not a column named `text`
        '/\b(TINY|MEDIUM|LONG)?TEXT\b(?!`)/i' => 'TEXT',
        '/KEY\s`?([a-z0-9_]+)`?\s\(`?([a-z0-9_]+)`?(\([0-9]+\))?\)\s?,?/i' => '',
        '/\)(\s{0,4}ENGINE=([a-z0-9_]+))?(\s{0,4}DEFAULT)?(\s{0,4}CHARSET=([a-z0-9_]+))?(\s{0,4}COLLATE=([a-z0-9_]+))?(\s{0,4}AUTOINCREMENT)?(\s{0,4}=\s?1)?(\s{0,4};)?/i' =>
            ')',
        '/,\s+\)/' => ')',
        '/INTEGER\s{0,4}NOT\s{0,4}NULL/i' => 'INTEGER',
    ];

    //mysql or sqlite
    public string $driver;
    public int $query_num = 0;
    public array $debugr = [];
    public bool $show_errors = true;
    private ?PDO $pdo = null;
    private ?PDOStatement $result = null;
    //[code, message] of the last error
    private array $error = [0, ''];
    //set by close(), so the connection can be opened again if it is needed after that
    private bool $closed = false;

    /**
     * connect
     *
     * @param string $host        MySQL server, with an optional :port
     * @param string $db_username MySQL user
     * @param string $db_password MySQL password
     * @param string $db_name     MySQL database name, or path of the SQLite file from Kleeja folder
     * @param string $dbprefix    tables prefix
     * @param string $driver      mysql or sqlite
     */
    public function __construct(
        private string $host,
        private string $db_username,
        #[\SensitiveParameter] private string $db_password,
        private string $db_name,
        public string $dbprefix,
        string $driver = 'mysql',
    ) {
        $this->driver = $driver === 'sqlite' ? 'sqlite' : 'mysql';

        if (!$this->connect()) {
            $this->error_msg('We can not connect to the database');
        }
    }

    private function connect(): bool
    {
        //return values as strings like mysqli did, the code compares them that way
        $options = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_STRINGIFY_FETCHES => true];

        try {
            if (!in_array($this->driver, PDO::getAvailableDrivers(), true)) {
                throw new PDOException("PDO driver of {$this->driver} is not installed in your server!");
            }

            if ($this->driver === 'sqlite') {
                //PDO creates missing files, and a new empty database is never what we want
                if (!is_file(PATH . $this->db_name)) {
                    throw new PDOException('SQLite database file is not found.');
                }

                $dsn = 'sqlite:' . PATH . $this->db_name;
            } else {
                [$host, $port] = explode(':', $this->host, 2) + [1 => ''];
                $dsn = "mysql:host={$host};port=" . ((int) $port ?: 3306) . ";dbname={$this->db_name};charset=utf8";
                //one statement per query, so an SQL injection can not add more statements
                $options[
                    PHP_VERSION_ID >= 80400 ? Pdo\Mysql::ATTR_MULTI_STATEMENTS : PDO::MYSQL_ATTR_MULTI_STATEMENTS
                ] = false;
                //prepared by the server, so the values are sent apart from the query, not quoted into it
                $options[PDO::ATTR_EMULATE_PREPARES] = false;
            }

            $this->pdo = new PDO($dsn, $this->db_username, $this->db_password, $options);
        } catch (PDOException $e) {
            $this->set_error($e);

            return false;
        }

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
        if (!$this->pdo && $this->closed) {
            $this->closed = false;

            if (!$this->connect()) {
                $this->error_msg('We can not connect to the database');
            }
        }

        return $this->is_connected();
    }

    public function is_connected(): bool
    {
        return $this->pdo !== null;
    }

    // close the connection, it will be opened again if a query comes after this
    public function close(): bool
    {
        if ($this->pdo) {
            kleeja_log('[Closing connection] : ' . kleeja_get_page());

            $this->pdo = $this->result = null;
            $this->closed = true;
        }

        return true;
    }

    public function version(): string
    {
        $row = $this->fetch_array(
            $this->query('SELECT ' . ($this->driver === 'sqlite' ? 'sqlite_version()' : 'VERSION()') . ' AS v'),
        );

        return explode('-', $row['v'] ?? '')[0];
    }

    /**
     * execute a query, values of $params are bound to its :name (or ?) placeholders,
     * and an array value is bound as a list, so 'id IN (:ids)' with ['ids' => [1, 2]] becomes 'id IN (:ids_0, :ids_1)',
     * see bind_names() for the rest
     *
     * @param  string            $query
     * @param  array             $params
     * @return PDOStatement|false
     */
    public function query(string $query, array $params = []): PDOStatement|false
    {
        $this->result = null;
        $this->error = [0, ''];

        if ($query === '' || !$this->reopen()) {
            return false;
        }

        if ($this->driver === 'sqlite' && str_contains($query, 'CREATE TABLE')) {
            //todo extract keys and add as CREATE INDEX index_name ON table (column);
            $query = preg_replace(array_keys(self::SQLITE_TYPES), self::SQLITE_TYPES, $query);
        }

        [$query, $params] = $this->bind_names($query, $params);

        $start = get_microtime();

        try {
            $this->result = $this->pdo->prepare($query);

            foreach ($params as $name => $value) {
                //numbers are bound as numbers, so they work where a text does not, like LIMIT
                $this->result->bindValue(
                    is_int($name) ? $name + 1 : $name,
                    $value,
                    match (true) {
                        is_int($value), is_bool($value) => PDO::PARAM_INT,
                        $value === null => PDO::PARAM_NULL,
                        default => PDO::PARAM_STR,
                    },
                );
            }

            $this->result->execute();
        } catch (PDOException $e) {
            $this->result = null;
            $this->set_error($e);
        }

        //for the debug panel, kept only while developing, the values can be private like passwords hashes
        if (defined('DEV_STAGE')) {
            $this->debugr[$this->query_num + 1] = [$query, sprintf('%.5f', get_microtime() - $start), $params];
        }

        if (!$this->result) {
            $this->error_msg('Error In query', $query);

            return false;
        }

        kleeja_log('[Query] : --> ' . $query);
        $this->query_num++;

        return $this->result;
    }

    /**
     * match the :name placeholders of a query with the values of $params, texts between quotes are skipped:
     * - an array value becomes a list of placeholders, and an empty list becomes a subquery without rows,
     *   so 'IN (:ids)' matches nothing and 'NOT IN (:ids)' matches all
     * - a placeholder used again gets a numbered copy, server prepared statements of MySQL can not use a name twice
     * - values of placeholders not in the query are dropped, like when a plugin hook replaces the WHERE
     *
     * @param  string $query
     * @param  array  $params
     * @return array  [the query, values by their placeholders]
     */
    private function bind_names(string $query, array $params): array
    {
        //only the named placeholders, ? placeholders are bound by their order as they are
        if (!array_filter(array_keys($params), 'is_string')) {
            return [$query, $params];
        }

        $bound = $used = [];

        $query = preg_replace_callback(
            //quoted texts and `names` are matched first, so what looks like a placeholder inside them is kept as it is
            '/\'(?:[^\'\\\\]++|\\\\.|\'\')*\'|"(?:[^"\\\\]++|\\\\.|"")*"|`[^`]*`|(?<!:):([a-z_][a-z0-9_]*)/i',
            function (array $match) use ($params, &$bound, &$used): string {
                $name = $match[1] ?? '';

                if ($name === '' || !array_key_exists($name, $params)) {
                    return $match[0];
                }

                $used[$name] = ($used[$name] ?? -1) + 1;
                $base = $used[$name] ? "{$name}__{$used[$name]}" : $name;

                if (!is_array($params[$name])) {
                    $bound[$base] = $params[$name];

                    return ":{$base}";
                }

                if (!$params[$name]) {
                    return 'SELECT NULL WHERE 1 = 0';
                }

                $names = [];

                foreach (array_values($params[$name]) as $i => $item) {
                    $bound[($names[] = "{$base}_{$i}")] = $item;
                }

                return ':' . implode(', :', $names);
            },
            $query,
        );

        return [$query, $bound];
    }

    /**
     * build structured query ['SELECT' => ..., 'FROM' => ..., 'WHERE' => 'id = :id', 'BIND' => ['id' => $id]],
     * BIND has the values of the placeholders, see query()
     *
     * @param  array             $query
     * @return PDOStatement|false
     */
    public function build(array $query): PDOStatement|false
    {
        $sql = '';
        $where = empty($query['WHERE']) ? '' : ' WHERE ' . $query['WHERE'];

        if (isset($query['SELECT'], $query['FROM'])) {
            $sql = "SELECT {$query['SELECT']} FROM {$query['FROM']}";

            foreach ($query['JOINS'] ?? [] as $join) {
                $type = array_key_first($join);
                $sql .= " {$type} {$join[$type]} ON {$join['ON']}";
            }

            $sql .= $where;

            foreach (['GROUP BY', 'HAVING', 'ORDER BY', 'LIMIT'] as $clause) {
                $sql .= empty($query[$clause]) ? '' : " {$clause} {$query[$clause]}";
            }
        } elseif (isset($query['INSERT']) || isset($query['REPLACE'])) {
            $verb = isset($query['INSERT']) ? 'INSERT' : 'REPLACE';
            $sql = "{$verb} INTO {$query['INTO']}" . (empty($query[$verb]) ? '' : " ({$query[$verb]})");
            $sql .= " VALUES({$query['VALUES']})";
        } elseif (isset($query['UPDATE'])) {
            $sql = "UPDATE {$query['UPDATE']} SET {$query['SET']}{$where}";
        } elseif (isset($query['DELETE'])) {
            $sql = "DELETE FROM {$query['DELETE']}{$where}";
        }

        return $this->query($sql, $query['BIND'] ?? []);
    }

    /**
     * free the memmory from the last results
     *
     * @param  PDOStatement|false|null $query_id optional, the last result by default
     * @return bool
     */
    public function freeresult(PDOStatement|false|null $query_id = null): bool
    {
        return ($query_id ?: $this->result)?->closeCursor() ?? false;
    }

    /**
     * fetch results (alias of fetch_array)
     *
     * @param  PDOStatement|false|null $query_id optional, the last result by default
     * @return array|false             the next row, or false when there are no more rows
     */
    public function fetch(PDOStatement|false|null $query_id = null): array|false
    {
        return $this->fetch_array($query_id);
    }

    /**
     * fetch results
     *
     * @param  PDOStatement|false|null $query_id optional, the last result by default
     * @return array|false             the next row, or false when there are no more rows
     */
    public function fetch_array(PDOStatement|false|null $query_id = null): array|false
    {
        $query_id = $query_id ?: $this->result;

        //only reading queries have rows
        return $query_id?->columnCount() ? $query_id->fetch(PDO::FETCH_ASSOC) : false;
    }

    /**
     * return number of rows of result
     *
     * @param  PDOStatement|false|null $query_id optional, the last result by default
     * @return int|false
     */
    public function num_rows(PDOStatement|false|null $query_id = null): int|false
    {
        $query_id = $query_id ?: $this->result;

        if (!$query_id?->columnCount()) {
            return false;
        }

        if ($this->driver === 'mysql') {
            return $query_id->rowCount();
        }

        //SQLite does not give the number of rows, so count them then run the query again to go back to the first row
        $rows = iterator_count($query_id);
        $query_id->execute();

        return $rows;
    }

    /**
     * return the id of latest inserted record
     *
     * @return int|false
     */
    public function insert_id(): int|false
    {
        return $this->pdo ? (int) $this->pdo->lastInsertId() : false;
    }

    /**
     * escape a text for HTML then for SQL
     *
     * @deprecated bind the values with BIND of build() or $params of query(), and use kleeja_html_encode() for HTML,
     *             it is kept for the plugins that still put the values in their queries
     * @param  string|null $msg
     * @return string
     */
    public function escape(?string $msg): string
    {
        return $this->real_escape(htmlspecialchars($msg ?? '', ENT_QUOTES));
    }

    /**
     * escape a text for SQL, to be put between single quotes
     *
     * @deprecated bind the values with BIND of build() or $params of query(),
     *             it is kept for the plugins that still put the values in their queries
     * @param  string $msg
     * @return string
     */
    public function real_escape(string $msg): string
    {
        //null bytes are never valid text, and PDO can not quote them for SQLite
        //quote() escapes by the connection charset and adds quotes around the text, so we remove them
        return $this->reopen() ? substr($this->pdo->quote(str_replace("\0", '', $msg)), 1, -1) : '';
    }

    /**
     * number of affected rows by latest action
     *
     * @return int|false
     */
    public function affected(): int|false
    {
        return $this->result?->rowCount() ?? false;
    }

    /**
     * information
     *
     * @return string
     */
    public function server_info(): string
    {
        return ($this->driver === 'sqlite' ? 'SQLite ' : 'MySQL ') . $this->version();
    }

    /**
     * return last error as [code, message]
     *
     * @return array
     */
    public function get_error(): array
    {
        return $this->error;
    }

    private function set_error(PDOException $e): void
    {
        //errorInfo is [SQLSTATE, driver error code, driver error message], it is empty for our own exceptions
        $this->error = [$e->errorInfo[1] ?? $e->getCode(), $e->errorInfo[2] ?? $e->getMessage()];
    }

    /**
     * show the error in Kleeja error page, or only log it if errors are hidden
     *
     * @param  string $msg
     * @param  string $error_sql the query that failed, if any
     * @return void
     */
    private function error_msg(string $msg, string $error_sql = ''): void
    {
        [$error_no, $error_msg] = $this->error;

        //loggin -> error
        kleeja_log('[SQL ERROR] : ' . $msg . ' "' . $error_no . ' : ' . $error_msg . '" -->');

        if (!$this->show_errors || defined('SQL_NO_ERRORS') || defined('MYSQL_NO_ERRORS')) {
            return;
        }

        //some ppl want hide their table names
        if (!defined('DEV_STAGE')) {
            [$error_sql, $error_msg] = preg_replace(
                [
                    '#\s{1,3}`*' . preg_quote($this->dbprefix, '#') . '([a-z0-9])[a-z0-9]*`*\s{1,3}#',
                    '#' . preg_quote($this->db_name . '.' . $this->dbprefix, '#') . '([a-z0-9])[a-z0-9]*#',
                    '#\s{1,3}(from|update|into)\s{1,3}([a-z0-9])[a-z0-9]*\s{1,3}#i',
                    "#\s'[^']+'@'([^']+)'#",
                    "#password\s*=\s*'[^']+'#i",
                ],
                [' $1*** ', '$1***', ' $1 $2*** ', " '***'@'$1'", "password='***'"],
                [$error_sql, (string) $error_msg],
            );
        }

        global $config;

        $message = implode(
            "\n",
            array_filter([
                "{$msg}: [{$error_no}] {$error_msg}",
                $error_sql === '' ? '' : "Query: {$error_sql}",
                //is this error related to updating?
                str_contains($error_msg, 'Unknown column') || str_contains($error_msg, 'no such')
                    ? 'Your Kleeja database might be old, try to update it now from: ' .
                        rtrim($config['siteurl'] ?? '', '/') .
                        "/install\nIf this error happened after installing a plugin, add define('STOP_PLUGINS', true); to end of config.php file."
                    : '',
            ]),
        );

        //Kleeja error handler shows it in the error page as a text, then stops the script,
        //it is called directly, E_USER_ERROR is deprecated for trigger_error() since PHP 8.4
        if (function_exists('kleeja_show_error')) {
            kleeja_show_error(E_USER_ERROR, $message, __FILE__, __LINE__);
        }

        //no Kleeja error handler, stop the script like the E_USER_ERROR did
        throw new RuntimeException($message);
    }
}
