<?php
/**
*
* @package Kleeja
* @copyright (c) 2007 Kleeja.com
* @license http://www.kleeja.com/license
*
*/


/**
 * @ignore
 */
if (!defined('IN_COMMON'))
{
	exit();
}


/**
 * Make changes with files using FTP
 */
class kleeja_ftp
{
	/**
	 * TimeOut before disconnection
	 */
	public $timeout = 30;
	/**
	 * Move to this folder after connection
	 */
	public $root	 = '';
	/**
	 * If enabled, debug mode will be activated
	 */
	public $debug = false;
    /**
     * FTP current connection handler
     */
    private $handler = null;


    private $unique_name = '';

    private $link = '';

    /**
     * Connect to FTP server
     *
     * @param string $host FTP server address
     * @param string $user FTP server username
     * @param string $password FTP server password
     * @param int $port FTP server port
     * @param string $rootPath
     * @param bool $passive
     * @return bool|kleeja_ftp
     * @internal param string $path FTP server path
     */
	public function open($host, $user, $password, $port = 21, $rootPath = '/', $passive = true, $ssl = false, $timeout = 90)
	{
	    $this->timeout = $timeout;

	    $this->debug = defined('DEV_STAGE');


		#connect to the server
        if($ssl)
        {
            $this->handler = @ftp_ssl_connect($host, $port, $this->timeout);
        }
        else
        {
            $this->handler = @ftp_connect($host, $port, $this->timeout);
        }


		if (!$this->handler)
		{
//		    if($this->debug)
//            {
//                echo 'can not connect<br>';
//                var_dump($this->handler);
//
//            }
			return false;
		}

		#pasv mode
		@ftp_pasv($this->handler, $passive);


		#login to the server
		if (!ftp_login($this->handler, $user, $password))
		{
//            if($this->debug)
//            {
//                echo 'can not login<br>';
//                var_dump($this->handler);
//            }
			return false;
		}

		#move to the path


        $rootPath = trim($rootPath);

		if($rootPath == '/')
        {
            $rootPath = '';
        }


        if ($rootPath != '')
        {
            if (substr($rootPath, -1, 1) == '/')
            {
                $rootPath = substr($rootPath, 0, -1);
            }
        }

        $this->root = $rootPath;

		if($this->root != '')
		{
            $this->link = 'http://' . $host . '/' . ltrim($rootPath, '/');

            if (!$this->file_exists($this->root))
            {
                $this->create_folder('');
            }
        }

		return $this;
	}



    /**
     * Go to the given folder
     * @param string $dir
     * @return bool
     */
    public function go_to($dir = '')
    {
        if ($dir && $dir !== '/')
        {
            if (substr($dir, -1, 1) == '/')
            {
                $dir = substr($dir, 0, -1);
            }
        }

        return @ftp_chdir($this->handler, $dir);
    }

	/**
	 * Close current FTP connection
	 */
	public function close()
	{
		if (!$this->handler)
		{
			return;
		}

		ftp_quit($this->handler);
	}

	/**
	 * Get the current folder that we are in now
	 * @return string
	 */
	public function current_folder()
	{
		return ftp_pwd($this->handler);
	}

    /**
     * Change the file or folder permission
     * @param string $file
     * @param int $perm
     * @return bool
     */
	public function chmod($file, $perm = 0644)
	{
		if (function_exists('ftp_chmod'))
		{
			$action = @ftp_chmod($this->handler, $perm, $this->_fixpath($file));
		}
		else
		{
			$chmod_cmd = 'CHMOD ' . base_convert($perm, 10, 8) . ' ' . $this->_fixpath($file);
			$action = ftp_site($this->handler, $chmod_cmd);
		}
		return $action;
	}


    /**
     * is file exists
     * @return bool
     */
    public function file_exists($file)
    {
        return ftp_size($this->handler, $this->_fixpath($file)) > -1;
    }

    /**
     * fix the given path to be compatible with the FTP
     * @param string $path
     * @return string
     */
    private function _fixpath($path)
    {
        return ($this->root != '' ? $this->root . '/' : '') . $path;
    }

    /**
     * Delete given file
     * @param string $file
     * @return bool
     */
	public function delete($file)
	{
		return @ftp_delete($this->handler, $this->_fixpath($file));
	}

    /**
     * Create a file and write the given content to it
     * @param string $filePath
     * @param $content
     * @return bool
     */
	public function write($filePath, $content)
    {
        $cached_file = PATH . 'cache/cached_ftp_' . uniqid(time());

		#make it as a cached file
		$h = @fopen($cached_file, 'wb');
		fwrite($h, $content);
		@fclose($h);


		$r = @ftp_put($this->handler, $this->_fixpath($filePath), $cached_file, FTP_BINARY);


		kleeja_unlink($cached_file, true);

		return $r;
	}

    /**
     * Upload a local file to the FTP server
     * @param string $local_file
     * @param string $server_file
     * @return bool
     */
	public function upload($local_file, $server_file, $deleteLocal = true)
    {
		#Initate the upload
        #TODO if slow, use ftp_put
		$ret = ftp_nb_put($this->handler, $this->_fixpath($server_file), $local_file, FTP_BINARY);
		while ($ret == FTP_MOREDATA)
		{
			#still uploading
			 if($this->debug)
			 {
			 	print ftell($this->handler)."\n";
			 }

            $ret = ftp_nb_continue($this->handler);
		}

		if($deleteLocal)
		{
            kleeja_unlink($local_file);
        }

		#bad uploading
		if ($ret != FTP_FINISHED)
		{
			return false;
		}

		return true;
	}

    /**
     * Rename a file
     * @param string $old_file
     * @param string $new_file
     * @return bool
     */
	public function rename($old_file, $new_file)
	{
		return @ftp_rename($this->handler, $this->_fixpath($old_file), $this->_fixpath($new_file));
	}

    /**
     * Create a folder
     * @param string $dir
     * @param int $perm
     * @return bool
     */
	public function create_folder($dir, $perm = 0755)
	{
//	    if($this->debug)
//        {
//            var_dump($this->_fixpath($dir));
//        }

        if(ftp_mkdir($this->handler, $this->_fixpath($dir)) === false)
        {
            return false;
        }

        $this->chmod($this->_fixpath($dir), $perm);
        return true;
	}

    /**
     * Delete the given folder
     * @param string $dir
     * @return bool
     */
	public function delete_folder($dir)
	{
		return @ftp_rmdir($this->handler, $this->_fixpath($dir));
	}

    /**
     * @param string $unique_name
     */
    public function setUniqueName($unique_name)
    {
        $this->unique_name = $unique_name;
    }

    /**
     * @return string
     */
    public function getUniqueName()
    {
        return $this->unique_name;
    }

    /**
     * @param string $link
     */
    public function setLink($link)
    {
        if(trim($link) == '')
        {
            return;
        }

        if (substr($link, -1, 1) == '/')
        {
            $link = substr($link, 0, -1);
        }

        $this->link = $link;
    }

    /**
     * @return string
     */
    public function getLink($path)
    {
        return $this->link . '/' . $path;
    }
}

