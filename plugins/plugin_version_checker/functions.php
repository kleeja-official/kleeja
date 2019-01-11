<?php

if ( ! function_exists("get_hosted_plugin")) {

    function get_hosted_plugin($plugin_dir = "./plugins/"){

        $direction = scandir($plugin_dir);

    if (count($direction) > 2) {

        foreach ($direction as  $files) {

            if ($files !== "." && $files !== ".." && $files !== "index.html") {

                $return_file[] = $files;

            }

        }

    }

    return $return_file;

   }

}




if ( ! function_exists("get_plugin_init_file")) {

    function get_plugin_init_file($locatedPlugin = array() , $pluginDir = "./plugins" ){

        foreach ($locatedPlugin as $pluginName) {

            if (file_exists( $pluginDir ."/" . $pluginName ."/" . "init.php")) {

                $return_init_dir[] = array( "pluginName" => $pluginName ,"init" => $pluginName ."/" . "init.php");

            }else {

                $return_init_dir[] = array( "pluginName" => $pluginName ,"init" => "Not Found");

            }

        }

        return $return_init_dir;
    }

}

if ( ! function_exists("plugin_init_github_link")) {

    function plugin_init_github_link( $hosted_plugin = array()){
        
        $github_link = "https://raw.githubusercontent.com/awssat/kleeja/master/";

        foreach ($hosted_plugin as $plugin_info) {

            if ( isset($plugin_info["init"])) {
                
                if ( $plugin_info["init"] !== "Not Found") {

                    $plugin_info["github"] = $github_link . "plugins/" . $plugin_info["init"];

                }

            }

            $return[] = $plugin_info;

        }

        return $return;
    }
}


if (!function_exists("check_plugin_version")) {

    function check_plugin_version( $plugins_info = array()){

        global $olang;
        
        foreach ($plugins_info as $plugin) {
            
            if ($plugin["init"] !== "Not Found") {

                $local_content = file_get_contents("../" . KLEEJA_PLUGINS_FOLDER . "/" . $plugin["init"]);

                preg_match("/'plugin_check_version_link' => {1,4}\'([^\']+)\'\,/" , $local_content , $update_link);
                
                if (count($update_link) > 1) {

                    $online_content = fetch_remote_file($update_link[1] , false , 30);

                }
                else {
                    $online_content = fetch_remote_file($plugin["github"] , false , 30);
                }
                
                
                if ($online_content) {

                    preg_match("/'plugin_version' => {1,4}\'([^\']+)\'\,/" , $online_content , $online_version);
                    preg_match("/'plugin_version' => {1,4}\'([^\']+)\'\,/" , $local_content , $local_version);
        
                    $online_version = trim(htmlspecialchars($online_version[1]));
                    $local_version = trim(htmlspecialchars($local_version[1]));
        
                    if (version_compare(strtolower($local_version), strtolower($online_version), '<'))
                    {
                        $report	= $olang['U_PLG_OLD'];
                        $error = "warning";
                        $plugin["local_version"] = $local_version ;
                        $plugin["online_version"] = $online_version ;
                    }
                    else if (version_compare(strtolower($local_version), strtolower($online_version), '='))
                    {
                        $report	= $olang['U_PLG_OK'];
                        $error = "success";
                        $plugin["local_version"] = $local_version ;
                        $plugin["online_version"] = $online_version ;
                    }
                    else if (version_compare(strtolower($local_version), strtolower($github_version), '>'))
                    {
                        $report	= $olang['U_PLG_NEW'];
                        $error = "warning";
                        $plugin["local_version"] = $local_version ;
                        $plugin["online_version"] = $online_version ;
                    }
        
                    
    
                }else {
                    $report	= $olang['PLG_GITHUB_ERR'];
                    $error = "danger";
                }
    
            }else {
                $report = $olang['NO_INIT_FILE'];
                $error = "danger";
            }

            $plugin["report"] = $report;
            $plugin["error"] = $error;

            $return[] = $plugin;
        }
        
        return $return;
    }
}






