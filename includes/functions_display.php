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

/**
 * print Kleeja header
 * @param string $title
 * @param string $extra append html code to head tag
 */
function Saaheader(string $title = '', string $extra = ''): void
{
    global $tpl, $usrcp, $lang, $olang, $user_is, $username, $config;
    global $extras, $script_encoding, $errorpage, $userinfo, $charset;
    global $STYLE_PATH;

    //is user ? and username
    $user_is = $usrcp->name() ? true : false;
    $username = $usrcp->name() ? $usrcp->name() : $lang['GUST'];

    //our default charset
    $charset = 'utf-8';

    $side_menu = [
        1 => [
            'name' => 'profile',
            'title' => $lang['PROFILE'],
            'url' => $config['siteurl'] . ($config['mod_writer'] ? 'profile.html' : 'ucp.php?go=profile'),
            'show' => $user_is,
        ],
        2 => [
            'name' => 'fileuser',
            'title' => $lang['YOUR_FILEUSER'],
            'url' => $config['siteurl'] . ($config['mod_writer'] ? 'fileuser.html' : 'ucp.php?go=fileuser'),
            'show' => $config['enable_userfile'] && user_can('access_fileuser'),
        ],
        3 => $user_is
            ? [
                'name' => 'logout',
                'title' => $lang['LOGOUT'],
                'url' => $config['siteurl'] . ($config['mod_writer'] ? 'logout.html' : 'ucp.php?go=logout'),
                'show' => true,
            ]
            : [
                'name' => 'login',
                'title' => $lang['LOGIN'],
                'url' => $config['siteurl'] . ($config['mod_writer'] ? 'login.html' : 'ucp.php?go=login'),
                'show' => true,
            ],
        4 => [
            'name' => 'register',
            'title' => $lang['REGISTER'],
            'url' => $config['siteurl'] . ($config['mod_writer'] ? 'register.html' : 'ucp.php?go=register'),
            'show' => !$user_is && $config['register'],
        ],
    ];

    $top_menu = [
        1 => ['name' => 'index', 'title' => $lang['INDEX'], 'url' => $config['siteurl'], 'show' => true],
        2 => [
            'name' => 'rules',
            'title' => $lang['RULES'],
            'url' => $config['siteurl'] . ($config['mod_writer'] ? 'rules.html' : 'go.php?go=rules'),
            'show' => true,
        ],
        3 => [
            'name' => 'guide',
            'title' => $lang['GUIDE'],
            'url' => $config['siteurl'] . ($config['mod_writer'] ? 'guide.html' : 'go.php?go=guide'),
            'show' => true,
        ],
        4 => [
            'name' => 'stats',
            'title' => $lang['STATS'],
            'url' => $config['siteurl'] . ($config['mod_writer'] ? 'stats.html' : 'go.php?go=stats'),
            'show' => $config['allow_stat_pg'] && user_can('access_stats'),
        ],
        5 => [
            'name' => 'report',
            'title' => $lang['REPORT'],
            'url' => $config['siteurl'] . ($config['mod_writer'] ? 'report.html' : 'go.php?go=report'),
            'show' => user_can('access_report'),
        ],
        6 => [
            'name' => 'call',
            'title' => $lang['CALL'],
            'url' => $config['siteurl'] . ($config['mod_writer'] ? 'call.html' : 'go.php?go=call'),
            'show' => user_can('access_call'),
        ],
    ];

    //check for extra header
    $extras['header'] = empty($extras['header']) ? false : $extras['header'];

    is_array($plugin_run_result = Plugins::getInstance()->run('Saaheader_links_func', get_defined_vars()))
        ? extract($plugin_run_result)
        : null; //run hook

    //assign some variables
    $tpl->assign('dir', $lang['DIR']);
    $tpl->assign('title', $title);
    $tpl->assign('side_menu', $side_menu);
    $tpl->assign('top_menu', $top_menu);
    $tpl->assign('go_current', g('go', default: 'index'));
    $tpl->assign('go_back_browser', $lang['GO_BACK_BROWSER']);
    $tpl->assign('H_FORM_KEYS_LOGIN', kleeja_add_form_key('login'));
    $tpl->assign(
        'action_login',
        $config['siteurl'] . 'ucp.php?go=login' . (ig('return') ? '&amp;return=' . g('return') : ''),
    );
    $tpl->assign('EXTRA_CODE_META', $extra);
    $default_avatar = $STYLE_PATH . 'images/user_avater.png';

    if ($user_is) {
        $tpl->assign(
            'user_avatar',
            'https://www.gravatar.com/avatar/' .
                md5(strtolower(trim($userinfo['mail']))) .
                '?s=100&amp;d=' .
                urlencode($default_avatar),
        );
    } else {
        $tpl->assign('user_avatar', $default_avatar);
    }

    $tpl->assign('is_embedded', ig('embedded'));

    $header = $tpl->display('header');

    if ($config['siteclose'] == '1' && user_can('enter_acp') && !defined('IN_ADMIN')) {
        //add notification bar
        $header = preg_replace(
            '/<body([^\>]*)>/i',
            "<body\\1>\n<!-- site is closed -->\n<p style=\"z-index:999;width: 100%; text-align:center; background:#FFFFA6; color:black; border:thin;top:0;left:0; position:absolute; clear:both;\">" .
                $lang['NOTICECLOSED'] .
                "</p>\n<!-- #site is closed -->",
            $header,
        );
    }

    is_array($plugin_run_result = Plugins::getInstance()->run('Saaheader_func', get_defined_vars()))
        ? extract($plugin_run_result)
        : null; //run hook

    header('Content-type: text/html; charset=UTF-8');
    header('Cache-Control: private, no-cache="set-cookie"');
    header('Pragma: no-cache');
    header('x-frame-options: SAMEORIGIN');
    header('x-xss-protection: 1; mode=block');
    header('X-Content-Type-Options: nosniff');

    echo $header;
    flush();
}

/**
 * print kleeja footer
 */
function Saafooter(): void
{
    global $tpl, $SQL, $starttm, $config, $usrcp, $lang, $olang;
    global $do_gzip_compress, $script_encoding, $errorpage, $extras, $userinfo;

    //show stats ..
    $page_stats = '';

    if ($config['statfooter'] != 0 || defined('DEV_STAGE')) {
        $hksys = !defined('STOP_PLUGINS') ? 'Enabled' : 'Disabled';
        $endtime = get_microtime();
        $loadtime = number_format($endtime - $starttm, 4);
        $queries_num = $SQL->query_num;
        $time_sql = round($SQL->query_num / $loadtime);
        $page_url = preg_replace(['/([\&\?]+)debug/i', '/&amp;/i'], ['', '&'], kleeja_get_page());
        $link_dbg =
            user_can('enter_acp') && defined('DEV_STAGE')
                ? '[ <a href="' .
                    str_replace('&', '&amp;', $page_url) .
                    (strpos($page_url, '?') === false ? '?' : '&amp;') .
                    'debug">Debug Info ... </a> ]'
                : '';
        $page_stats =
            "<strong>[</strong> Generation Time: $loadtime Sec  - Queries: $queries_num - Hook System:  $hksys <strong>]</strong>  " .
            $link_dbg;
    }

    $tpl->assign('page_stats', $page_stats);

    //if admin, show admin in the bottom of all page
    $tpl->assign(
        'admin_page',
        user_can('enter_acp')
            ? '<a href="' . ADMIN_PATH . '" class="admin_cp_link"><span>' . $lang['ADMINCP'] . '</span></a>'
            : '',
    );

    //assign cron
    $tpl->assign(
        'run_queue',
        '<img src="' . $config['siteurl'] . 'go.php?go=queue" width="1" height="1" alt="queue" />',
    );

    // if google analytics, new version
    //http://www.google.com/support/googleanalytics/bin/answer.py?answer=55488&topic=11126
    $googleanalytics = '';

    if (strlen($config['googleanalytics']) > 4) {
        $googleanalytics .= '<!-- Google tag (gtag.js) -->' . "\n";
        $googleanalytics .=
            '<script async src="https://www.googletagmanager.com/gtag/js?id=' .
            $config['googleanalytics'] .
            '"></script>' .
            "\n";
        $googleanalytics .= '<script>' . "\n";
        $googleanalytics .= 'window.dataLayer = window.dataLayer || [];' . "\n";
        $googleanalytics .= 'function gtag(){dataLayer.push(arguments);}' . "\n";
        $googleanalytics .= "gtag('js', new Date());" . "\n";
        $googleanalytics .= "\n";
        $googleanalytics .= "gtag('config', '" . $config['googleanalytics'] . "');" . "\n";
        $googleanalytics .= '</script>' . "\n";
    }

    $tpl->assign('googleanalytics', $googleanalytics);

    $extras['footer'] = empty($extras['footer']) ? false : $extras['footer'];

    is_array($plugin_run_result = Plugins::getInstance()->run('Saafooter_func', get_defined_vars()))
        ? extract($plugin_run_result)
        : null; //run hook

    $footer = $tpl->display('footer');

    is_array($plugin_run_result = Plugins::getInstance()->run('print_Saafooter_func', get_defined_vars()))
        ? extract($plugin_run_result)
        : null; //run hook

    echo $footer;

    //page analysis, its data are collected only in DEV_STAGE
    if (defined('DEV_STAGE') && ig('debug') && user_can('enter_acp')) {
        kleeja_debug();
    }

    //at end, close sql connections
    $SQL->close();
}

/**
 * return file size in a readable format
 * @param  int    $size in bytes
 * @return string
 */
function readable_size(int $size): string
{
    $sizes = [' B', ' KB', ' MB', ' GB', ' TB', 'PB', ' EB'];
    $ext = $sizes[0];

    for ($i = 1; $i < count($sizes) && $size >= 1024; $i++) {
        $size = $size / 1024;
        $ext = $sizes[$i];
    }
    $result = round($size, 2) . $ext;
    is_array($plugin_run_result = Plugins::getInstance()->run('func_readable_size', get_defined_vars()))
        ? extract($plugin_run_result)
        : null; //run hook

    return $result;
}

/**
 * show an error message
 *
 * @param string      $message
 * @param string      $title
 * @param bool        $exit
 * @param bool|string $redirect          a link to redirect after showing the message, or false
 * @param int         $rs                delay in seconds if redirect parameter is set
 * @param string      $extra_code_header to append a code to head tag
 * @param string      $style             is err or info, set by default, no need to fill
 */
function kleeja_err(
    string $message,
    string $title = '',
    bool $exit = true,
    bool|string $redirect = false,
    int $rs = 2,
    string $extra_code_header = '',
    string $style = 'err',
): void {
    global $text, $tpl, $SQL;

    is_array($plugin_run_result = Plugins::getInstance()->run('kleeja_err_func', get_defined_vars()))
        ? extract($plugin_run_result)
        : null; //run hook

    // assign {text} in err template
    $text = $message . ($redirect ? redirect($redirect, header: false, exit: $exit, sec: $rs, return: true) : '');
    //header
    Saaheader($title, $extra_code_header);
    //show tpl
    echo $tpl->display($style);
    //footer
    Saafooter();

    if ($exit) {
        $SQL->close();

        exit();
    }
}

/**
 * show an information message
 *
 * @param string      $message
 * @param string      $title
 * @param bool        $exit
 * @param bool|string $redirect          a link to redirect after showing the message, or false
 * @param int         $rs                delay in seconds if redirect parameter is set
 * @param string      $extra_code_header to append a code to head tag
 */
function kleeja_info(
    string $message,
    string $title = '',
    bool $exit = true,
    bool|string $redirect = false,
    int $rs = 5,
    string $extra_code_header = '',
): void {
    is_array($plugin_run_result = Plugins::getInstance()->run('kleeja_info_func', get_defined_vars()))
        ? extract($plugin_run_result)
        : null; //run hook

    kleeja_err($message, $title, $exit, $redirect, $rs, $extra_code_header, 'info');
}

/**
 * Show debug information of the current page, for admins who add ?debug to the url while DEV_STAGE is defined
 */
function kleeja_debug(): void
{
    global $SQL, $starttm, $config, $STYLE_PATH_ADMIN;

    is_array($plugin_run_result = Plugins::getInstance()->run('kleeja_debug_func', get_defined_vars()))
        ? extract($plugin_run_result)
        : null; //run hook

    $escape = fn(mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    $ms = fn(float $seconds): string => number_format($seconds * 1000, 2) . ' ms';

    $page_time = get_microtime() - $starttm;
    $durations = array_map(fn(array $query): float => (float) $query[1], $SQL->debugr);
    $sql_time = array_sum($durations);
    $slowest = $durations ? max($durations) : 0.0;
    //marking the slowest one is useful only if there is more than one query
    $slowest_key = count($durations) > 1 ? array_search($slowest, $durations, true) : null;
    $plugins_info = Plugins::getInstance()->getDebugInfo();
    $installed_plugins = $plugins_info['installed_plugins'] ?? [];
    $hooks_plugins = $plugins_info['hooks_plugins'] ?? [];
    ksort($hooks_plugins);

    //the same icon of the plugin in the admin panel, file_exists() results are cached by PHP for the repeated ones
    $plugin_label = fn(string $name): string => '<span class="kj-debug-plugin"><img src="' .
        $escape(
            file_exists(PATH . KLEEJA_PLUGINS_FOLDER . '/' . $name . '/icon.png')
                ? $config['siteurl'] . KLEEJA_PLUGINS_FOLDER . '/' . rawurlencode($name) . '/icon.png'
                : $STYLE_PATH_ADMIN . 'images/plugin.png',
        ) .
        '" alt="" width="24" height="24" loading="lazy">' .
        $escape($name) .
        '</span>';

    //[label, value, note]
    $stats = [
        ['Generation time', $ms($page_time), 'until this panel'],
        ['Queries', $SQL->query_num, $slowest > 0 ? 'slowest ' . $ms($slowest) : ''],
        [
            'SQL time',
            $ms($sql_time),
            $page_time > 0 ? round(($sql_time / $page_time) * 100) . '% of generation time' : '',
        ],
        ['Memory', readable_size(memory_get_usage()), 'peak ' . readable_size(memory_get_peak_usage())],
        ['PHP', PHP_VERSION, PHP_OS_FAMILY],
        ['Database', $SQL->driver ?? '-', 'PDO'],
        [
            'Gzip',
            in_array(strtolower((string) ini_get('zlib.output_compression')), ['', '0', 'off'], true)
                ? 'Disabled'
                : 'Enabled',
            'zlib.output_compression',
        ],
        [
            'Hook system',
            defined('STOP_PLUGINS') ? 'Disabled' : 'Enabled',
            count($installed_plugins) . ' plugins, ' . count($hooks_plugins) . ' hooks',
        ],
    ];

    $stats_html = '';

    foreach ($stats as [$label, $value, $note]) {
        $stats_html .=
            '<div class="kj-debug-stat"><dt>' .
            $label .
            '</dt><dd class="kj-debug-stat-value">' .
            $escape($value) .
            '</dd>' .
            ($note !== '' ? '<dd class="kj-debug-stat-note">' . $escape($note) . '</dd>' : '') .
            '</div>';
    }

    $queries_html = '';

    foreach ($SQL->debugr as $key => [$query, , $params]) {
        $values_html = '';

        //values of the placeholders
        foreach ($params as $name => $value) {
            $values_html .=
                '<dt>' .
                $escape(is_int($name) ? '?' . ($name + 1) : ':' . ltrim($name, ':')) .
                '</dt><dd>' .
                $escape(
                    json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE),
                ) .
                '</dd>';
        }

        $queries_html .=
            '<li class="kj-debug-query"><div class="kj-debug-query-head">' .
            '<span class="kj-debug-query-num">#' .
            $key .
            '</span>' .
            '<span class="kj-debug-meter" aria-hidden="true" title="' .
            number_format($durations[$key] * 1000, 3) .
            ' ms"><span style="width: ' .
            ($slowest > 0 ? round(($durations[$key] / $slowest) * 100, 1) : 0) .
            '%"></span></span>' .
            '<span class="kj-debug-query-time">' .
            $ms($durations[$key]) .
            '</span>' .
            ($key === $slowest_key ? '<span class="kj-debug-badge">Slowest</span>' : '') .
            '</div><pre class="kj-debug-code"><code>' .
            $escape(trim($query)) .
            '</code></pre>' .
            ($values_html !== '' ? '<dl class="kj-debug-values">' . $values_html . '</dl>' : '') .
            '</li>';
    }

    $plugins_html = '';

    foreach ($installed_plugins as $name => $version) {
        $plugins_html .= '<tr><td>' . $plugin_label($name) . '</td><td>' . $escape($version) . '</td></tr>';
    }

    $hooks_html = '';

    foreach ($hooks_plugins as $hook => $priorities) {
        $hooks_html .=
            '<tr><td><code>' .
            $escape($hook) .
            '</code></td><td><div class="kj-debug-plugins">' .
            implode('', array_map($plugin_label, array_merge(...array_values($priorities)))) .
            '</div></td><td class="kj-debug-num" data-label="Priorities">' .
            $escape(implode(', ', array_keys($priorities))) .
            '</td></tr>';
    }

    echo '<div class="kj-debug" dir="ltr"><style>' . kleeja_debug_css() . '</style>';
    echo '<div class="kj-debug-head"><picture>' .
        '<source srcset="https://kleeja.net/images/logo-light.svg" media="(prefers-color-scheme: dark)">' .
        '<img src="https://kleeja.net/images/logo.svg" alt="Kleeja" width="32" height="32"></picture>' .
        '<div><h2 class="kj-debug-title">Page analysis</h2>' .
        '<p class="kj-debug-subtitle">Debug information of this request, shown to administrators only.</p></div></div>';
    echo '<dl class="kj-debug-stats">' . $stats_html . '</dl>';

    echo '<details class="kj-debug-section" open><summary>SQL queries <span class="kj-debug-count">' .
        count($SQL->debugr) .
        '</span></summary>' .
        ($queries_html !== ''
            ? '<ol class="kj-debug-queries">' . $queries_html . '</ol>'
            : '<p class="kj-debug-empty">No queries were run.</p>') .
        '</details>';

    echo '<details class="kj-debug-section" open><summary>Plugins <span class="kj-debug-count">' .
        count($installed_plugins) .
        '</span></summary>';

    echo '<h3 class="kj-debug-subhead">Installed plugins</h3>' .
        ($plugins_html !== ''
            ? '<table class="kj-debug-table"><thead><tr><th>Name</th><th>Version</th></tr></thead><tbody>' .
                $plugins_html .
                '</tbody></table>'
            : '<p class="kj-debug-empty">No plugins are installed.</p>');
    echo '<h3 class="kj-debug-subhead">Hooks</h3>' .
        ($hooks_html !== ''
            ? '<table class="kj-debug-table kj-debug-table-stack"><thead><tr><th>Hook</th><th>Plugins (run order)</th>' .
                '<th class="kj-debug-num">Priorities</th></tr></thead><tbody>' .
                $hooks_html .
                '</tbody></table>'
            : '<p class="kj-debug-empty">No hooks are registered.</p>');

    echo '</details></div>';
}

/**
 * Styles of kleeja_debug() panel, scoped to .kj-debug since it is printed inside the page of the current style
 * @return string
 */
function kleeja_debug_css(): string
{
    return <<<'CSS'
    .kj-debug {
        --kjd-bg: #FFFFFF;
        --kjd-surface: #F6F7F8;
        --kjd-border: #D9DCE0;
        --kjd-text: #0B1F3A;
        --kjd-muted: #546275;
        --kjd-accent: #F45B69;
        --kjd-track: #D9DCE0;
        --kjd-badge-bg: #FEEBED;
        --kjd-badge-text: #7F2F37;
        display: block;
        box-sizing: border-box;
        width: calc(100% - 32px);
        max-width: 1100px;
        margin: 32px auto;
        padding: 24px;
        background: var(--kjd-bg);
        color: var(--kjd-text);
        border: 1px solid var(--kjd-border);
        border-top: 4px solid var(--kjd-accent);
        border-radius: 8px;
        box-shadow: 0 8px 24px rgba(11, 31, 58, .12);
        font: 14px/1.5 "Inter", "IBM Plex Sans Arabic", system-ui, -apple-system, "Segoe UI", sans-serif;
        text-align: left;
        direction: ltr;
    }
    @media (prefers-color-scheme: dark) {
        .kj-debug {
            --kjd-bg: #0B1F3A;
            --kjd-surface: #23354E;
            --kjd-border: #3C4C61;
            --kjd-text: #FFFFFF;
            --kjd-muted: #BBC0C8;
            --kjd-track: #3C4C61;
            --kjd-badge-bg: #582126;
            --kjd-badge-text: #FCD4D8;
            box-shadow: none;
        }
    }
    /* the style of the page can set the direction of all elements, like * { direction: rtl } */
    .kj-debug *, .kj-debug *::before, .kj-debug *::after { box-sizing: border-box; direction: ltr; }
    .kj-debug-head { display: flex; align-items: center; gap: 12px; margin: 0 0 24px; }
    .kj-debug-head img { display: block; width: 32px; height: 32px; }
    .kj-debug .kj-debug-title, .kj-debug .kj-debug-subhead {
        margin: 0; padding: 0; border: 0; background: none;
        font-family: inherit; letter-spacing: normal; text-transform: none;
    }
    .kj-debug .kj-debug-title { font-size: 20px; font-weight: 700; line-height: 1.3; color: var(--kjd-text); }
    .kj-debug .kj-debug-subhead { margin: 0 0 8px; font-size: 13px; font-weight: 600; color: var(--kjd-muted); }
    .kj-debug-subtitle { margin: 2px 0 0; color: var(--kjd-muted); font-size: 13px; }
    .kj-debug-stats {
        display: grid; grid-template-columns: repeat(auto-fill, minmax(210px, 1fr)); gap: 12px;
        margin: 0 0 24px; padding: 0;
    }
    .kj-debug-stat {
        min-width: 0; padding: 12px 16px;
        background: var(--kjd-surface); border: 1px solid var(--kjd-border); border-radius: 8px;
    }
    .kj-debug-stat dt { margin: 0; color: var(--kjd-muted); font-size: 12px; font-weight: 500; }
    .kj-debug-stat dd { margin: 0; overflow-wrap: anywhere; }
    .kj-debug-stat-value { font-size: 20px; font-weight: 600; line-height: 1.4; font-variant-numeric: tabular-nums; }
    .kj-debug-stat-note { color: var(--kjd-muted); font-size: 12px; }
    .kj-debug-section { margin: 0; padding: 16px 0 0; border-top: 1px solid var(--kjd-border); }
    .kj-debug-section + .kj-debug-section { margin-top: 16px; }
    .kj-debug-section > summary { margin: 0 0 12px; cursor: pointer; font-size: 16px; font-weight: 600; }
    .kj-debug-section:not([open]) > summary { margin: 0; }
    .kj-debug-count {
        display: inline-block; margin-left: 8px; padding: 0 8px; vertical-align: 1px;
        background: var(--kjd-surface); border: 1px solid var(--kjd-border); border-radius: 999px;
        color: var(--kjd-muted); font-size: 12px; font-weight: 600; font-variant-numeric: tabular-nums;
    }
    .kj-debug-queries { display: grid; gap: 12px; margin: 0; padding: 0; list-style: none; }
    .kj-debug-query {
        margin: 0; padding: 12px 16px;
        background: var(--kjd-surface); border: 1px solid var(--kjd-border); border-radius: 8px;
    }
    .kj-debug-query-head {
        display: flex; flex-wrap: wrap; align-items: center; gap: 8px 12px; margin: 0 0 8px;
        font-variant-numeric: tabular-nums;
    }
    .kj-debug-query-num { min-width: 32px; color: var(--kjd-muted); font-weight: 600; }
    .kj-debug-meter { flex: 0 1 200px; height: 6px; background: var(--kjd-track); }
    .kj-debug-meter > span {
        display: block; height: 100%; min-width: 4px;
        background: var(--kjd-accent); border-radius: 0 3px 3px 0;
    }
    .kj-debug-query-time { font-weight: 600; }
    .kj-debug-badge {
        padding: 2px 10px; border-radius: 999px;
        background: var(--kjd-badge-bg); color: var(--kjd-badge-text); font-size: 12px; font-weight: 600;
    }
    .kj-debug .kj-debug-code {
        max-height: 240px; margin: 0; padding: 12px; overflow: auto;
        background: var(--kjd-bg); color: var(--kjd-text); border: 1px solid var(--kjd-border); border-radius: 4px;
        font: 13px/1.6 "JetBrains Mono", ui-monospace, SFMono-Regular, Menlo, monospace;
        white-space: pre-wrap; overflow-wrap: anywhere;
    }
    .kj-debug code { padding: 0; background: none; color: inherit; font: inherit; }
    .kj-debug-values {
        display: grid; grid-template-columns: max-content minmax(0, 1fr); gap: 4px 12px; margin: 8px 0 0;
        font: 12px/1.5 "JetBrains Mono", ui-monospace, SFMono-Regular, Menlo, monospace;
    }
    .kj-debug-values dt { margin: 0; color: var(--kjd-muted); }
    .kj-debug-values dd { margin: 0; overflow-wrap: anywhere; unicode-bidi: plaintext; }
    .kj-debug .kj-debug-table { width: 100%; margin: 0 0 16px; border-collapse: collapse; background: none; }
    .kj-debug .kj-debug-table th, .kj-debug .kj-debug-table td {
        padding: 8px 12px; background: none; color: var(--kjd-text);
        border: 0; border-bottom: 1px solid var(--kjd-border); text-align: left; vertical-align: top;
    }
    .kj-debug .kj-debug-table th { color: var(--kjd-muted); font-size: 12px; font-weight: 600; }
    .kj-debug .kj-debug-table .kj-debug-num { text-align: right; font-variant-numeric: tabular-nums; }
    .kj-debug-table code { font-family: "JetBrains Mono", ui-monospace, SFMono-Regular, Menlo, monospace; overflow-wrap: anywhere; }
    .kj-debug .kj-debug-plugin { display: inline-flex; align-items: center; gap: 8px; }
    .kj-debug .kj-debug-plugin img {
        flex: none; width: 24px; height: 24px; max-width: none; margin: 0; padding: 1px;
        background: #FFFFFF; border: 1px solid var(--kjd-border); border-radius: 4px; object-fit: contain;
    }
    .kj-debug-plugins { display: flex; flex-wrap: wrap; gap: 4px 16px; }
    .kj-debug-empty { margin: 0 0 12px; color: var(--kjd-muted); }
    @media (max-width: 600px) {
        .kj-debug { padding: 16px; }
        .kj-debug-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .kj-debug-meter { flex-basis: 80px; }
        /* one row under the other, the hook names are too long for columns */
        .kj-debug .kj-debug-table-stack, .kj-debug .kj-debug-table-stack tbody { display: block; }
        .kj-debug .kj-debug-table-stack thead { display: none; }
        .kj-debug .kj-debug-table-stack tr { display: block; padding: 8px 0; border-bottom: 1px solid var(--kjd-border); }
        .kj-debug .kj-debug-table.kj-debug-table-stack td { display: block; padding: 4px 0; border: 0; text-align: left; }
        .kj-debug-table-stack td[data-label]::before {
            content: attr(data-label) " "; color: var(--kjd-muted); font-size: 12px; font-weight: 600;
        }
    }
    CSS;
}

/**
 * Show error of critical problem
 *
 * @param string $error_title title
 * @param string $msg_text    content
 * @param bool   $error       is it an error or an info message
 */
function big_error(string $error_title, string $msg_text, bool $error = true): void
{
    global $SQL;

    $error_title = htmlspecialchars($error_title, ENT_QUOTES, 'UTF-8');
    $error_template = @file_get_contents(__DIR__ . '/error.html');

    if ($error_template === false) {
        echo '<strong>Kleeja ' .
            ($error ? 'error' : 'information message') .
            ': [ ' .
            $error_title .
            ' ]</strong><br />' .
            $msg_text;
    } else {
        //the details blocks are for PHP errors shown by kleeja_show_error() only
        $error_template = preg_replace('/<!-- BEGIN DETAILS -->.*?<!-- END DETAILS -->/s', '', $error_template);

        echo strtr($error_template, [
            '{TITLE}' => $error_title,
            '{BADGE}' => $error ? 'Kleeja Error' : 'Kleeja Information',
            '{TYPE}' => $error ? 'error' : 'info',
            '{MESSAGE}' => $msg_text,
        ]);
    }

    if (isset($SQL)) {
        @$SQL->close();
    }

    exit();
}

/**
 * Redirect to a url
 * @param  string $url
 * @param  bool   $header true for header location redirect or false for html meta
 * @param  bool   $exit   halt after echoing the redirect code
 * @param  int    $sec    delay in seconds
 * @param  bool   $return return the html code only
 * @return string|null the html code when $return is true
 *
 */
function redirect(string $url, bool $header = true, bool $exit = true, int $sec = 0, bool $return = false): ?string
{
    global $SQL;

    is_array($plugin_run_result = Plugins::getInstance()->run('redirect_func', get_defined_vars()))
        ? extract($plugin_run_result)
        : null; //run hook

    if (!headers_sent() && $header && !$return) {
        header('Location: ' . str_replace(['&amp;'], ['&'], $url));
    } else {
        $gre =
            '<script type="text/javascript"> setTimeout("window.location.href = \'' .
            str_replace(['&amp;'], ['&'], $url) .
            '\'", ' .
            $sec * 1000 .
            '); </script>' .
            '<noscript><meta http-equiv="refresh" content="' .
            $sec .
            ';url=' .
            $url .
            '" /></noscript>';

        if ($return) {
            return $gre;
        }

        echo $gre;
    }

    if ($exit) {
        $SQL->close();

        exit();
    }

    return null;
}

/**
 *
 * Prevent CSRF,
 *
 * This will generate security token for GET request
 * @param  string $request_id
 * @return string
 */
function kleeja_add_form_key_get(string $request_id): string
{
    global $config;

    $return = 'formkey=' . substr(sha1($config['h_key'] . date('H-d-m') . $request_id), 0, 20);

    is_array($plugin_run_result = Plugins::getInstance()->run('kleeja_add_form_key_get_func', get_defined_vars()))
        ? extract($plugin_run_result)
        : null; //run hook

    return $return;
}

function kleeja_check_form_key_get(string $request_id): bool
{
    global $config;

    $token = substr(sha1($config['h_key'] . date('H-d-m') . $request_id), 0, 20);

    $return = false;

    if ($token == g('formkey')) {
        $return = true;
    }

    is_array($plugin_run_result = Plugins::getInstance()->run('kleeja_check_form_key_get_func', get_defined_vars()))
        ? extract($plugin_run_result)
        : null; //run hook

    return $return;
}

/**
 * This will generate hidden fields for kleeja forms, csrf input
 * @param  string $form_name
 * @return string
 */
function kleeja_add_form_key(string $form_name): string
{
    global $config;
    $now = time();
    $return =
        '<input type="hidden" name="k_form_key" value="' .
        sha1($config['h_key'] . $form_name . $now) .
        '" /><input type="hidden" name="k_form_time" value="' .
        $now .
        '" />' .
        "\n";

    is_array($plugin_run_result = Plugins::getInstance()->run('kleeja_add_form_key_func', get_defined_vars()))
        ? extract($plugin_run_result)
        : null; //run hook

    return $return;
}

/**
 * This will check csrf hidden fields that came from kleeja forms
 * @param  string $form_name
 * @param  int    $require_time in seconds
 * @return bool
 */
function kleeja_check_form_key(string $form_name, int $require_time = 300): bool
{
    global $config;

    if (defined('IN_ADMIN')) {
        //we increase it for admin to be a double
        $require_time *= 2;
    }

    $return = false;

    if (ip('k_form_key') && ip('k_form_time')) {
        $key_was = trim(p('k_form_key'));
        $time_was = p('k_form_time', 'int');
        $different = time() - $time_was;

        //check time that user spent in the form
        if ($different && (!$require_time || $require_time >= $different)) {
            if (sha1($config['h_key'] . $form_name . $time_was) === $key_was) {
                $return = true;
            }
        }
    }

    is_array($plugin_run_result = Plugins::getInstance()->run('kleeja_check_form_key_func', get_defined_vars()))
        ? extract($plugin_run_result)
        : null; //run hook

    return $return;
}

/**
 * Link generator
 * TODO to be edited
 * Files can be many links styles, so this will generate the current style of link
 * @param  string $pid
 * @param  array  $extra
 * @return string
 */
function kleeja_get_link(string $pid, array $extra = []): string
{
    global $config;

    $links = [];

    //to avoid problems
    $config['id_form'] = empty($config['id_form']) ? 'id' : $config['id_form'];
    $config['id_form_img'] = empty($config['id_form_img']) ? 'id' : $config['id_form_img'];

    //to prevent bug with rewrite
    if ($config['mod_writer'] && !empty($extra['::NAME::'])) {
        if (
            (($pid == 'image' || $pid == 'thumb') && $config['id_form_img'] != 'direct') ||
            ($pid == 'file' && $config['id_form'] != 'direct')
        ) {
            $extra['::NAME::'] = str_replace('.', '-', $extra['::NAME::']);
        }
    }

    $file_link = [
        'id' => $config['mod_writer'] ? 'download::ID::.html' : 'do.php?id=::ID::',
        'filename' => $config['mod_writer'] ? 'downloadf-::NAME::.html' : 'do.php?filename=::NAME::',
        'direct' => '::DIR::/::NAME::',
    ];

    $image_link = [
        'id' => $config['mod_writer'] ? 'image::ID::.html' : 'do.php?img=::ID::',
        'filename' => $config['mod_writer'] ? 'imagef-::NAME::.html' : 'do.php?imgf=::NAME::',
        'direct' => '::DIR::/::NAME::',
    ];

    $thumb_link = [
        'id' => $config['mod_writer'] ? 'thumb::ID::.html' : 'do.php?thmb=::ID::',
        'filename' => $config['mod_writer'] ? 'thumbf-::NAME::.html' : 'do.php?thmbf=::NAME::',
        'direct' => '::DIR::/thumbs/::NAME::',
    ];

    $del_link = $config['mod_writer'] ? 'del::CODE::.html' : 'go.php?go=del&amp;cd=::CODE::';

    $links['file'] = $file_link[$config['id_form']];
    $links['image'] = $image_link[$config['id_form_img']];
    $links['thumb'] = $thumb_link[$config['id_form_img']];
    $links['del'] = $del_link;

    is_array($plugin_run_result = Plugins::getInstance()->run('kleeja_get_link_func', get_defined_vars()))
        ? extract($plugin_run_result)
        : null; //run hook

    $return_link = $config['siteurl'] . str_replace(array_keys($extra), array_values($extra), $links[$pid]);

    is_array($plugin_run_result = Plugins::getInstance()->run('kleeja_get_link_func2', get_defined_vars()))
        ? extract($plugin_run_result)
        : null; //run hook

    return $return_link;
}

/**
 *  Uploading boxes
 *
 * Parse template of boxes and print them
 * @param  string $box_name html block name from up_boxes.html file
 * @param  array  $extra    variables to pass to the html block
 * @return string
 */
function get_up_tpl_box(string $box_name, array $extra = []): string
{
    global $THIS_STYLE_PATH_ABS, $config;
    static $boxes;

    //prevent loads
    //also this must be cached in future
    if (empty($boxes)) {
        $tpl_path = $THIS_STYLE_PATH_ABS . 'up_boxes.html';

        if (!file_exists($tpl_path)) {
            $depend_on = false;

            if (trim($config['style_depend_on']) != '') {
                $depend_on = $config['style_depend_on'];
            } else {
                $depend_on = 'default';
            }

            $tpl_path = str_replace('/' . $config['style'] . '/', '/' . trim($depend_on) . '/', $tpl_path);
        }

        $tpl_code = file_get_contents($tpl_path);
        $tpl_code = preg_replace("/\n[\n\r\s\t]*/", '', $tpl_code); //remove extra spaces
        $matches = preg_match_all('#<!-- BEGIN (.*?) -->(.*?)<!-- END (?:.*?) -->#', $tpl_code, $match);

        $boxes = [];

        for ($i = 0; $i < $matches; $i++) {
            if (empty($match[1][$i])) {
                continue; //it's empty , let's leave it
            }

            $boxes[$match[1][$i]] = $match[2][$i];
        }
    }

    //extra value
    $extra += [
        'siteurl' => $config['siteurl'],
        'sitename' => $config['sitename'],
    ];

    //return compiled value
    $return = $boxes[$box_name];

    foreach ($extra as $var => $val) {
        $return = preg_replace('/{' . $var . '}/', $val, $return);
    }

    /*
     * We add this hook here so you can substitute you own vars
     * and even add your own boxes to this template.
     */
    is_array($plugin_run_result = Plugins::getInstance()->run('get_up_tpl_box_func', get_defined_vars()))
        ? extract($plugin_run_result)
        : null; //run hook

    return $return;
}

/**
 * Extract info of a style
 * @param  string      $style_name
 * @return array|false
 */
function kleeja_style_info(string $style_name): array|false
{
    $inf_path = PATH . 'styles/' . $style_name . '/info.txt';

    //is info.txt exists or not
    if (!file_exists($inf_path)) {
        return false;
    }

    $inf_c = file_get_contents($inf_path);
    //some ppl will edit this file with notepad or even with office word :)
    $inf_c = str_replace(["\r\n", "\r"], ["\n", "\n"], $inf_c);

    //as lines
    $inf_l = @explode("\n", $inf_c);
    $inf_l = array_map('trim', $inf_l);

    $inf_r = [];

    foreach ($inf_l as $m) {
        //comments
        if ((isset($m[0]) && $m[0] == '#') || trim($m) == '') {
            continue;
        }

        $t = array_map('trim', @explode('=', $m, 2));

        // ':' mean something secondary as in sub-array
        if (strpos($t[0], ':') !== false) {
            $subInfo = explode(':', $t[0]);
            $t_t0 = array_map('trim', $subInfo);
            $inf_r[$t_t0[0]][$t_t0[1]] = $t[1];
        } else {
            if (!empty($t[0])) {
                $inf_r[$t[0]] = empty($t[1]) ? '' : $t[1];
            }
        }
    }

    is_array($plugin_run_result = Plugins::getInstance()->run('kleeja_style_info_func', get_defined_vars()))
        ? extract($plugin_run_result)
        : null; //run hook

    return $inf_r;
}

/**
 * Browser detection
 * returns whether or not the visiting browser is the one specified [part of kleeja style system]
 * i.e. is_browser('ie6') -> true or false
 * i.e. is_browser('ie, opera') -> true or false
 * @param  string $b browser name, like mozilla
 * @return bool
 */
function is_browser(string $b): bool
{
    //is there , which mean -OR-
    if (strpos($b, ',') !== false) {
        $e = explode(',', $b);

        foreach ($e as $n) {
            if (is_browser(trim($n))) {
                return true;
            }
        }

        return false;
    }

    //if no agent, let's take the worst case
    $u_agent = !empty($_SERVER['HTTP_USER_AGENT'])
        ? htmlspecialchars((string) $_SERVER['HTTP_USER_AGENT'])
        : (function_exists('getenv')
            ? getenv('HTTP_USER_AGENT')
            : '');
    $t = trim(preg_replace('/[^a-z]/', '', $b));
    $r = trim(preg_replace('/[a-z]/', '', $b));

    $return = false;

    switch ($t) {
        case 'ie':
            $return = strpos(strtolower($u_agent), trim('msie ' . $r)) !== false ? true : false;

            break;

        case 'firefox':
            $return =
                strpos(str_replace('/', ' ', strtolower($u_agent)), trim('firefox ' . $r)) !== false ? true : false;

            break;

        case 'safari':
            $return = strpos(strtolower($u_agent), trim('safari/' . $r)) !== false ? true : false;

            break;

        case 'chrome':
            $return = strpos(strtolower($u_agent), trim('chrome ' . $r)) !== false ? true : false;

            break;

        case 'flock':
            $return = strpos(strtolower($u_agent), trim('flock ' . $r)) !== false ? true : false;

            break;

        case 'opera':
            $return = strpos(strtolower($u_agent), trim('opera ' . $r)) !== false ? true : false;

            break;

        case 'konqueror':
            $return = strpos(strtolower($u_agent), trim('konqueror/' . $r)) !== false ? true : false;

            break;

        case 'mozilla':
            $return = strpos(strtolower($u_agent), trim('gecko/' . $r)) !== false ? true : false;

            break;

        case 'webkit':
            $return = strpos(strtolower($u_agent), trim('applewebkit/' . $r)) !== false ? true : false;

            break;
        /**
         * Mobile Phones are so popular those days, so we have to support them ...
         * This is still in our test lab.
         * @see http://en.wikipedia.org/wiki/List_of_user_agents_for_mobile_phones
         **/
        case 'mobile':
            $mobile_agents = [
                'iPhone;',
                'iPod;',
                'blackberry',
                'Android',
                'HTC',
                'IEMobile',
                'LG/',
                'LG-',
                'LGE-',
                'MOT-',
                'Nokia',
                'SymbianOS',
                'nokia_',
                'PalmSource',
                'webOS',
                'SAMSUNG-',
                'SEC-SGHU',
                'SonyEricsson',
                'BOLT/',
                'Mobile Safari',
                'Fennec/',
                'Opera Mini',
            ];
            $return = false;

            foreach ($mobile_agents as $agent) {
                if (strpos($u_agent, $agent) !== false) {
                    $return = true;

                    break;
                }
            }

            break;
    }

    is_array($plugin_run_result = Plugins::getInstance()->run('is_browser_func', get_defined_vars()))
        ? extract($plugin_run_result)
        : null; //run hook

    return $return;
}

/**
 * Send an answer for ajax request
 * @param int    $code_number
 * @param string $content
 * @param string $menu
 */
function echo_ajax(int $code_number, string $content, string $menu = ''): void
{
    global $SQL;
    $SQL->close();

    exit(json_encode(['code' => $code_number, 'content' => $content, 'menu' => $menu]));
}

/**
 * Send an answer for ajax request [ARRAY]
 * @param array $array
 */
function echo_array_ajax(array $array): void
{
    global $SQL;
    $SQL->close();

    exit(@json_encode($array));
}

/**
 * show date in a human-readable-text
 * @param  int    $time       timestamp
 * @param  bool   $human_time return a readable time, like today, 1 hour ago
 * @param  string $format     date format like d-m-y
 * @return string
 */
function kleeja_date(int $time, bool $human_time = true, string $format = ''): string
{
    global $lang, $config;

    $time = intval($time);

    if (!defined('TIME_FORMAT')) {
        define('TIME_FORMAT', 'd-m-Y h:i a'); // to be moved to configs later
    }

    if (!empty($config['time_zone']) && strpos($config['time_zone'], '/') !== false) {
        if (strpos($config['time_zone'], 'Buraydah') !== false) {
            $config['time_zone'] = 'Asia/Riyadh';
        }

        $timezone_offset = timezone_offset_get(new DateTimeZone($config['time_zone']), new DateTime());
    } else {
        $timezone_offset = intval($config['time_zone']) * 60 * 60;
    }

    if (time() - $time > 86400 * 9 || $format || !$human_time) {
        $format = !$format ? TIME_FORMAT : $format;
        $time = $time + $timezone_offset;

        return str_replace(['am', 'pm'], [$lang['TIME_AM'], $lang['TIME_PM']], gmdate($format, $time));
    }

    $lengths = ['60', '60', '24', '7', '4.35', '12', '10'];

    $timezone_diff = (int) $config['time_zone'] * 60 * 60;
    $now = time() + $timezone_diff;
    $time = $time + $timezone_diff;
    $difference = $now > $time ? $now - $time : $time - $now;
    $tense = $now > $time ? $lang['W_AGO'] : $lang['W_FROM'];

    for ($j = 0; $difference >= $lengths[$j] && $j < sizeof($lengths) - 1; $j++) {
        $difference /= $lengths[$j];
    }

    $difference = round($difference);

    if ($difference != 1) {
        if ($difference == 2) {
            $return = $lang['W_PERIODS_DP_' . $j];
        } else {
            $return = $difference . ' ' . ($difference > 10 ? $lang['W_PERIODS_' . $j] : $lang['W_PERIODS_P_' . $j]);
        }
    } else {
        $return = '1 ' . $lang['W_PERIODS_' . $j];
    }

    $return = $now > $time ? $return . '  ' . $lang['W_AGO'] : $lang['W_FROM'] . ' ' . $return;

    is_array($plugin_run_result = Plugins::getInstance()->run('kleeja_date_func', get_defined_vars()))
        ? extract($plugin_run_result)
        : null; //run hook

    return $return;
}

/*
 * World Time Zones
 * @return array
 */
function time_zones(): array
{
    static $regions = [
        DateTimeZone::AFRICA,
        DateTimeZone::AMERICA,
        DateTimeZone::ASIA,
        DateTimeZone::ATLANTIC,
        DateTimeZone::AUSTRALIA,
        DateTimeZone::EUROPE,
        DateTimeZone::INDIAN,
        DateTimeZone::PACIFIC,
    ];

    $timezones = [];

    foreach ($regions as $region) {
        foreach (timezone_identifiers_list($region) as $tz) {
            $timezones[$tz] = timezone_offset_get(new DateTimeZone($tz), new DateTime()) / 3600;
        }
    }

    // for compatibility with earlier versions.
    $timezones['Asia/Buraydah'] = 3.01;

    asort($timezones);

    return $timezones;
}

/**
 * generate a config html field to insert to add as an acp option
 * @param  string $name           config name
 * @param  string $type           input type (text, yesno, select)
 * @param  array  $select_options in case of select type, provide options array ([[title=>value], [title=>value]]
 * @return string input html
 */
function configField(string $name, string $type = 'text', array $select_options = []): string
{
    switch ($type) {
        default:
        case 'text':
            return '<input type="text" id="kj_meta_seo_home_meta_keywords" name="' .
                $name .
                '"' .
                ' value="{con.' .
                $name .
                '}" size="50" />';

        case 'yesno':
            return '<label>{lang.YES}<input type="radio" id="' .
                $name .
                '" name="' .
                $name .
                '" ' .
                'value="1"  <IF NAME="con.' .
                $name .
                '==1"> checked="checked"</IF> /></label><label>{lang.NO}' .
                '<input type="radio" id="' .
                $name .
                '" name="' .
                $name .
                '" value="0" ' .
                ' <IF NAME="con.' .
                $name .
                '==0"> checked="checked"</IF> /></label>';

        case 'select':
            $return_value = '<select id="' . $name . '" name="' . $name . '">' . "\n";

            foreach ($select_options as $title => $value) {
                $return_value .=
                    '<option <IF NAME="con.' .
                    $name .
                    '==' .
                    $value .
                    '">selected="selected"</IF> value="' .
                    $value .
                    '">' .
                    $title .
                    '</option>' .
                    "\n";
            }

            return $return_value . '</select>' . "\n";
    }
}

/**
 * Shorten A string
 *
 * @param  string $text  The strings to shorten
 * @param  int    $until
 * @return string Short string
 */
function shorten_text(string $text, int $until = 30): string
{
    $until = $until < 4 ? 4 : $until;

    $chars_len = function_exists('mb_strlen') ? mb_strlen($text, 'UTF-8') : strlen($text);

    if ($chars_len >= $until) {
        $return = function_exists('mb_substr')
            ? mb_substr($text, 0, $until - 4, 'UTF-8') . ' ... ' . mb_substr($text, -4, encoding: 'UTF-8')
            : substr($text, 0, $until - 4) . ' ... ' . substr($text, -4);
    } else {
        $return = $text;
    }

    is_array($plugin_run_result = Plugins::getInstance()->run('shorten_text_func', get_defined_vars()))
        ? extract($plugin_run_result)
        : null; //run hook

    return $return;
}
