<?php
/**
 *
 * @package Kleeja
 * @copyright (c) 2007 Kleeja.com
 * @license ./docs/license.txt
 *
 */


//no direct access
if (! defined('IN_COMMON'))
{
    exit;
}

class kleeja_style
{
    protected $vars; //Reference to $GLOBALS
    protected $loop = [];
    protected $reg  = ['var' => '/([{]{1,2})+([A-Z0-9_\.]+)[}]{1,2}/i'];
    public $caching = true; //save templates as caches to not compiled a lot of times

    /**
     * Function to load a template file.
     * @param $template_name
     * @param null|mixed $style_path
     */
    protected function _load_template($template_name, $style_path = null)
    {
        global $config, $THIS_STYLE_PATH_ABS, $STYLE_PATH_ADMIN_ABS, $DEFAULT_PATH_ADMIN_ABS;


        if (! ($template_path = $this->template_exists($template_name, $style_path)))
        {
            big_error('No Template !', 'Requested <b>"' . $template_name . '"</b> template doesnt exist!');
        }

        $html = file_get_contents($template_path);
        $html = $this->_parse($html, $template_name);

        //use 'b' to force binary mode
        if ($filename = @fopen(PATH . 'cache/tpl_' . $this->re_name_tpl($template_name, $style_path) . '.php', 'wb'))
        {
            is_array($plugin_run_result = Plugins::getInstance()->run('style_load_template_func', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook


            @flock($filename, LOCK_EX);
            @fwrite($filename, $html);
            @flock($filename, LOCK_UN);
            @fclose($filename);
            // Read and write for owner, read for everybody else
            @chmod(PATH . 'cache/tpl_' . $this->re_name_tpl($template_name, $style_path) . '.php', 0644);
        }
    }

    /**
     * check if a template exists or not
     * @param $template_name
     * @param null $style_path
     */
    public function template_exists($template_name, $style_path  = null)
    {
        global $config, $STYLE_PATH_ADMIN_ABS, $THIS_STYLE_PATH_ABS, $DEFAULT_PATH_ADMIN_ABS;


        $is_admin_template = false;

        //admin template always begin with admin_
        if (substr($template_name, 0, 6) == 'admin_')
        {
            $current_style_path = ! empty($style_path) ? $style_path : $STYLE_PATH_ADMIN_ABS;
            $is_admin_template  = true;
        }
        else
        {
            $current_style_path = ! empty($style_path) ? $style_path : $THIS_STYLE_PATH_ABS;
        }


        $template_path = rtrim($current_style_path, '/') . '/' . $template_name . '.html';


        //if template not found and default style is there and not admin tpl
        $is_tpl_exist = file_exists($template_path);


        if (! $is_tpl_exist)
        {
            if (trim($config['style_depend_on']) != '')
            {
                $template_path_alternative = str_replace('/' . $config['style'] . '/', '/' . $config['style_depend_on'] . '/', $template_path);

                if (file_exists($template_path_alternative))
                {
                    $template_path = $template_path_alternative;
                    $is_tpl_exist  = true;
                }
            }
            elseif ($is_admin_template)
            {
                $template_path = $DEFAULT_PATH_ADMIN_ABS . $template_name . '.html';
                $is_tpl_exist  = true;
            }
            elseif ($config['style'] != 'default' && ! $is_admin_template)
            {
                $template_path_alternative = str_replace('/' . $config['style'] . '/', '/default/', $template_path);

                if (file_exists($template_path_alternative))
                {
                    $template_path = $template_path_alternative;
                    $is_tpl_exist  = true;
                }
            }
        }

        return $is_tpl_exist ? $template_path : false;
    }

    /**
     * Function to parse the Template Tags
     * @param mixed $html
     * @param mixed $template_name
     */
    protected function _parse($html, $template_name = '')
    {
        is_array($plugin_run_result = Plugins::getInstance()->run('style_parse_func', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook

        $html = preg_replace(['#<([\?%])=?.*?\1>#s', '#<script\s+language\s*=\s*(["\']?)php\1\s*>.*?</script\s*>#s', '#<\?php(?:\r\n?|[ \n\t]).*?\?>#s'], '', $html);
        $html = preg_replace_callback('/\(([{A-Z0-9_\.}\s!=<>]+)\?(.*):(.*)\)/iU', ['kleeja_style', '_iif_callback'], $html);
        $html = preg_replace_callback('/<(IF|ELSEIF|UNLESS) (.+)>/iU', ['kleeja_style', '_if_callback'], $html);
        $html = preg_replace_callback('/<LOOP\s+NAME\s*=\s*(\"|)+([a-z0-9_\.]{1,})+(\"|)\s*>/i', ['kleeja_style', '_loop_callback'], $html);
        $html = preg_replace_callback(kleeja_style::reg('var'), ['kleeja_style', '_vars_callback'], $html);

        $rep =
        [
            '/<\/(LOOP|IF|END|IS_BROWSER|UNLESS)>/i'                     => '<?php } ?>',
            '/<INCLUDE(\s+NAME|)\s*=*\s*"(.+)"\s*>/iU'                   => '<?php echo $this->display("\\2"); ?>',
            '/<IS_BROWSER\s*=\s*"([a-z0-9,]+)"\s*>/iU'                   => '<?php if(is_browser("\\1")){ ?>',
            '/<IS_BROWSER\s*\!=\s*"([a-z0-9,]+)"\s*>/iU'                 => '<?php if(!is_browser("\\1")){ ?>',
            '/(<ELSE>|<ELSE\s?\/>)/i'                                    => '<?php }else{ ?>',
            '/<ODD\s*=\s*"([a-zA-Z0-9_\-\+\.\/]+)"\s*>(.*?)<\/ODD\>/is'  => "<?php if(intval(\$value['\\1'])%2){?> \\2 <?php } ?>",
            '/<EVEN\s*=\s*"([a-zA-Z0-9_\-\+\.\/]+)"\s*>(.*?)<\/EVEN>/is' => "<?php if(intval(\$value['\\1'])% 2 == 0){?> \\2 <?php } ?>",
            '/<RAND\s*=\s*"(.*?)\"\s*,\s*"(.*?)"\s*>/is'                 => "<?php \$KLEEJA_tpl_rand_is=(!isset(\$KLEEJA_tpl_rand_is) || \$KLEEJA_tpl_rand_is==0)?1:0; print((\$KLEEJA_tpl_rand_is==1) ?'\\1':'\\2'); ?>",
            '/\{%(key|value)%\}/i'                                       => '<?php echo $\\1; ?>',
        ];

        return preg_replace(array_keys($rep), array_values($rep), $html);
    }


    /**
     * loop tag
     * @param $matches
     * @return string
     */
    protected function _loop_callback($matches)
    {
        $var = strpos($matches[2], '.') !== false ? str_replace('.', '"]["', $matches[2]) : $matches[2];
        return '<?php foreach($this->vars["' . $var . '"] as $key=>$value){ ?>';
    }


    /**
     * if tag
     * @param $matches
     * @return string
     */
    protected function _if_callback($matches)
    {
        $atts      = call_user_func(['kleeja_style', '_get_attributes'], $matches[0]);
        $condition = '';

        foreach (['NAME' => '', 'LOOP' => '', 'AND' => ' && ', 'OR' => ' || '] as $attribute=>$separator)
        {
            if (! empty($atts[$attribute]))
            {
                $condition .= $separator . $this->parse_condition($atts[$attribute], ! empty($atts['LOOP']));
            }
        }

        return strtoupper($matches[1]) == 'IF'
              ? '<?php if(' . $condition . '){ ?>'
              : (strtoupper($matches[1]) == 'UNLESS' ? '<?php if(!(' . $condition . ')){ ?>' : '<?php }elseif(' . $condition . '){ ?>');
    }


    /**
     * iif tag, if else /if
     * @param $matches
     * @return string
     */
    protected function _iif_callback($matches)
    {
        return '<IF NAME="' . $matches[1] . '">' . $matches[2] . '<ELSE>' . $matches[3] . '</IF>';
    }

    protected function parse_condition($condition, $is_loop)
    {
        $char = [' eq ', ' lt ', ' gt ', ' lte ', ' gte ', ' neq ', '==', '!=', '>=', '<=', '<', '>'];
        $reps = ['==', '<', '>', '<=', '>=', '!=', '==', '!=', '>=', '<=', '<', '>'];

        $con = str_replace('$this->vars', '[----this-vars----]', $condition);

        if (preg_match('/(.*)(' . implode('|', $char) . ')(.*)/i', $con, $arr))
        {
            $arr[1] = trim($arr[1]);
            $var1   = $arr[1][0] != '$' ? call_user_func(['kleeja_style', '_var_callback'], (! $is_loop ? '{' . $arr[1] . '}' : '{{' . $arr[1] . '}}')) : $arr[1];
            $opr    = str_replace($char, $reps, $arr[2]);
            $var2   = trim($arr[3]);

            //check for type
            if ($var2[0] != '$' && ! preg_match('/[0-9]/', $var2))
            {
                $var2 = '"' . str_replace('"', '\"', $var2) . '"';
            }

            $con = "$var1 $opr $var2";
        }
        elseif ($con[0] !== '$' && strpos($con, '(') === false)
        {
            $con = call_user_func(['kleeja_style', '_var_callback'], (! $is_loop ? '{' . $con . '}' : '{{' . $con . '}}'));
        }

        return str_replace('[----this-vars----]', '$this->vars', $con);
    }


    /**
     * make variable printable
     * @param $matches
     * @return string
     */
    protected function _vars_callback($matches)
    {
        $variable = call_user_func(['kleeja_style', '_var_callback'], $matches);

        if (strpos($matches[0], '{lang')  !== false || strpos($matches[0], '{olang') !== false)
        {
            return '<?=isset(' . $variable . ') ? ' . $variable . ' : \'' . $matches[0] . '\'?>';
        }

        return '<?=' . $variable . '?>';
    }


    /**
     * variable replace
     * @param $matches
     * @return string
     */
    protected function _var_callback($matches)
    {
        if (! is_array($matches))
        {
            preg_match(kleeja_style::reg('var'), $matches, $matches);
        }

        $var = ! empty($matches[2]) ? str_replace('.', '\'][\'', $matches[2]) : '';
        return ! empty($matches[1]) && trim($matches[1]) == '{{' ? '$value[\'' . $var . '\']' : '$this->vars[\'' . $var . '\']';
    }

    /**
     * att variable replace
     * @param $matches
     * @return string
     */
    protected function _var_callback_att($matches)
    {
        return trim($matches[1]) == '{' ? $this->_var_callback($matches) : '{' . $this->_var_callback($matches) . '}';
    }


    /**
     * get reg var
     * @param $var
     * @return mixed
     */
    protected function reg($var)
    {
        $vars = get_class_vars(__CLASS__);
        return ($vars['reg'][$var]);
    }


    /**
     * get tag  attributes
     * @param $tag
     * @return array
     */
    protected function _get_attributes($tag)
    {
        preg_match_all('/([a-z]+)="(.+)"/iU', $tag, $attribute);

        $attributes = [];

        for ($i = 0; $i < count($attribute[1]); $i++)
        {
            $att = strtoupper($attribute[1][$i]);

            if (preg_match('/NAME|LOOP/', $att))
            {
                $attributes[$att] = preg_replace_callback(kleeja_style::reg('var'), ['kleeja_style', '_var_callback'], $attribute[2][$i]);
            }
            else
            {
                $attributes[$att] = preg_replace_callback(kleeja_style::reg('var'), ['kleeja_style', '_var_callback_att'], $attribute[2][$i]);
            }
        }
        return $attributes;
    }

    /**
     * Assign Variables
     * @param $var
     * @param $to
     */
    public function assign($var, $to)
    {
        $GLOBALS[$var] = $to;
    }


    /**
     * load parser and return page content
     * @param $template_name
     * @param  null         $style_path optional, good for plugins
     * @return mixed|string
     */
    public function display($template_name, $style_path = null)
    {
        global $config;

        $this->vars = &$GLOBALS;

        //is there ?
        if (! file_exists(PATH . 'cache/tpl_' . $this->re_name_tpl($template_name, $style_path) . '.php') || ! $this->caching)
        {
            $this->_load_template($template_name, $style_path);
        }

        ob_start();
        include PATH . 'cache/tpl_' . $this->re_name_tpl($template_name, $style_path) . '.php';
        $page = ob_get_contents();
        ob_end_clean();

        return $page;
    }

    /**
     * generate admin option block
     * @param $html
     * @return string
     */
    public function admindisplayoption($html)
    {
        $this->vars = &$GLOBALS;

        $eval_on = false;
        eval('$eval_on = true;');

        $parsed_html = trim($this->_parse($html));

        ob_start();

        if ($eval_on)
        {
            eval(' ?' . '>' . $parsed_html . '<' . '?php ');
        }
        else
        {
            $path  = PATH . 'cache/tpl_' . md5($parsed_html) . '.php';
            file_put_contents($path, $parsed_html);
            include_once $path;
        }

        $page = ob_get_contents();
        ob_end_clean();

        return $page;
    }

    /**
     * change name of template to be valid
     * @param $name
     * @param  null|mixed $style_path
     * @return mixed
     */
    protected function re_name_tpl($name, $style_path = null)
    {
        return preg_replace('/[^a-z0-9-_]/', '-', strtolower($name)) .
            (! empty($style_path) ? md5($style_path) : '');
    }
}
