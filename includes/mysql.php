<?php
/**
*
* @package Kleeja
* @copyright (c) 2007 Kleeja.com
* @license ./docs/license.txt
*
*/


//no for directly open
if (!defined('IN_COMMON'))
{
	exit();
}  

	
if(!defined("SQL_LAYER")):

define("SQL_LAYER","mysql4");

class SSQL 
{

	var $connect_id		= null;		
	var $result;
	var $query_num		= 0;
	var $in_transaction = 0;
	var $debugr			= false;
	var $show_errors	= true;


	/*
	 * initiate the class
	 * wirth basic data
	 */
	function __construct($host, $db_username, $db_password, $db_name, $new_link=false)
	{
		global $script_encoding;
	
		$this->host= $host;
		$this->db_username	= $db_username;
		$this->db_name     = $db_name;
		$this->db_password = 'hidden';
	
		//no error
		if(defined('MYSQL_NO_ERRORS'))
		{
			$this->show_errors = false;
		}

		$this->connect_id = @mysql_connect($this->host, $this->db_username, $db_password, $new_link) or die($this->error_msg("we can not connect to the server ..."));

		if($this->connect_id)
		{
			#loggin -> connecting 
			kleeja_log('[Connected] : ' . kleeja_get_page());

			if(!empty($db_name))
			{
				$dbselect = @mysql_select_db($this->db_name) or die($this->error_msg("we can not select database"));
			
				if ($dbselect)
				{
					#login -> selecting database 
					kleeja_log('[Selected Database] :' . $this->connect_id);

					if ((!preg_match('/utf/i', strtolower($script_encoding)) && !defined('IN_LOGINPAGE') && !defined('IN_ADMIN_LOGIN') && !defined('DISABLE_INTR')) || ((empty($script_encoding) || preg_match('/utf/i', strtolower($script_encoding)) || defined('DISABLE_INTR'))))
					{
						if(mysql_query("SET NAMES 'utf8'"))
						{
							#loggin -> set utf8 
							kleeja_log('[Set to UTF8] :' . $this->connect_id);
						}
					}
				}
				else if(!$dbselect)
				{
					#loggin -> no database -> close connection
					$this->close($this->connect_id);
					$this->connect_id = $dbselect;
				}
			}

			return $this->connect_id;
		}
		else
		{
			return false;
		}
	}

	/*
	 * close the connection
	 */
	function close()
	{		
		if( $this->connect_id )
		{
			// Commit any remaining transactions
			if( $this->in_transaction )
			{
				mysql_query("COMMIT", $this->connect_id);
			}

			#loggin -> close connection
			kleeja_log('[Closing connection] :' . kleeja_get_page());

			return @mysql_close($this->connect_id);
		}
		else
		{
			return false;
		}
	}

	/*
	 * encoding functions
	 */
	function set_utf8()
	{
		return $this->set_names('utf8');
	}
	
	function set_names($charset)
	{
		@mysql_query("SET NAMES '" . $charset . "'", $this->connect_id);
	}
	
	function client_encoding()
	{
		return mysql_client_encoding($this->connect_id);
	}

	function mysql_version()
	{
		//version of mysql
		$vr = $this->query('SELECT VERSION() AS v');
		$vs = $this->fetch_array($vr);
		$vs = $vs['v'];
		return preg_replace('/^([^-]+).*$/', '\\1', $vs);
	}

	/*
	the query func . its so important to do 
	the quries and give results
	*/
	function query($query, $transaction = FALSE)
	{
		//no connection
		if(!$this->connect_id)
		{
			return false;
		}
	
		//
		// Remove any pre-existing queries
		//
		unset($this->result);

		if(!empty($query))
		{
			//debug .. //////////////
			$srartum_sql	=	get_microtime();
			////////////////

			if( $transaction == 1 && !$this->in_transaction )
			{
				$result = mysql_query("BEGIN", $this->connect_id);
				if(!$result)
				{
					return false;
				}
				
				$this->in_transaction = TRUE;
			}

			$this->result = mysql_query($query, $this->connect_id);

			//debug .. //////////////
			$this->debugr[$this->query_num+1] = array($query, sprintf('%.5f', get_microtime() - $srartum_sql));
			////////////////

			if(!$this->result)
			{
				$this->error_msg('Error In query');
			}
			else
			{
				//let's debug it
				kleeja_log('[Query] : --> ' . $query);
			}
		}
		else
		{
			if( $transaction == 2 && $this->in_transaction )
			{
				$this->result = mysql_query("COMMIT", $this->connect_id);
			}
		}

		//is there any result
		if($this->result)
		{
			if($transaction == 2 && $this->in_transaction)
			{
				$this->in_transaction = FALSE;

				if (!mysql_query("COMMIT", $this->connect_id))
				{
					mysql_query("ROLLBACK", $this->connect_id);
					return false;
				}
			}

			$this->query_num++;

			return $this->result;
		}
		else
		{
			if( $this->in_transaction )
			{
				mysql_query("ROLLBACK", $this->connect_id);
				$this->in_transaction = FALSE;
			}
			return false;
		}
	}

	/*
	 * query build 
	 */
	function build($query)
	{
		$sql = '';

		if (isset($query['SELECT']))
		{
			$sql = 'SELECT '.$query['SELECT'].' FROM '.$query['FROM'];

			if (isset($query['JOINS']))
			{
				foreach ($query['JOINS'] as $cur_join)
					$sql .= ' '.key($cur_join).' '. @current($cur_join).' ON '.$cur_join['ON'];
			}

			if (!empty($query['WHERE']))
				$sql .= ' WHERE '.$query['WHERE'];
			if (!empty($query['GROUP BY']))
				$sql .= ' GROUP BY '.$query['GROUP BY'];
			if (!empty($query['HAVING']))
				$sql .= ' HAVING '.$query['HAVING'];
			if (!empty($query['ORDER BY']))
				$sql .= ' ORDER BY '.$query['ORDER BY'];
			if (!empty($query['LIMIT']))
				$sql .= ' LIMIT '.$query['LIMIT'];
		}
		else if (isset($query['INSERT']))
		{
			$sql = 'INSERT INTO '.$query['INTO'];

			if (!empty($query['INSERT']))
				$sql .= ' ('.$query['INSERT'].')';

			$sql .= ' VALUES('.$query['VALUES'].')';
		}
		else if (isset($query['UPDATE']))
		{
			$query['UPDATE'] = $query['UPDATE'];

			if (isset($query['PARAMS']['LOW_PRIORITY']))
				$query['UPDATE'] = 'LOW_PRIORITY '.$query['UPDATE'];

			$sql = 'UPDATE '.$query['UPDATE'].' SET '.$query['SET'];

			if (!empty($query['WHERE']))
				$sql .= ' WHERE '.$query['WHERE'];
		}
		else if (isset($query['DELETE']))
		{
			$sql = 'DELETE FROM '.$query['DELETE'];

			if (!empty($query['WHERE']))
				$sql .= ' WHERE '.$query['WHERE'];
		}
		else if (isset($query['REPLACE']))
		{
			$sql = 'REPLACE INTO '.$query['INTO'];

			if (!empty($query['REPLACE']))
				$sql .= ' ('.$query['REPLACE'].')';

			$sql .= ' VALUES('.$query['VALUES'].')';
		}

		return $this->query($sql);
	}

	/*
	 * free the memmory from the last results
	 */
	function free($query_id = 0)
	{
		return $this->freeresult($query_id);
	}

	function freeresult($query_id = 0)
	{
		if(!$query_id)
		{
			$query_id = $this->result;
		}

		if ($query_id)
		{
			mysql_free_result($query_id);
			return true;
		}
		else
		{
			return false;
		}
	}

	/*
	 * if the result is an arry ,
	 * this func is so important to order them as a array
	*/
	function fetch($query_id = 0)
	{
		return $this->fetch_array($query_id);
	}

	function fetch_array($query_id = 0)
	{
		if( !$query_id )
		{
			$query_id = $this->result;
		}

		return $query_id ?  mysql_fetch_array($query_id, MYSQL_ASSOC) : false;	
	}

	/*
	 * if we have a result and we have to know 
	 * the number of it , this is a func ..
	 */
	function num_rows($query_id = 0)
	{
		if( !$query_id )
		{
			$query_id = $this->result;
		}

		return $query_id ? mysql_num_rows($query_id) : false;
	}

	
	/*
	 * last id inserted in sql
	 */
	function insert_id()
	{
		return ($this->connect_id) ? mysql_insert_id($this->connect_id) : false;
	}

	/*
	 * clean the qurery before insert it
	*/
	function escape($msg)
	{
		$msg = htmlspecialchars($msg , ENT_QUOTES);
		#$msg = (!get_magic_quotes_gpc()) ? addslashes ($msg) : $msg;
		$msg = $this->real_escape($msg);
		return $msg;
	}

	/*
	 * real escape .. 
	 */
	function real_escape($msg)
	{
		if (is_array($msg))
		{
			return '';
		}
		else if (function_exists('mysql_real_escape_string'))
		{
			if(!$this-connect_id)
			{
				return 0;
			}
			return mysql_real_escape_string($msg, $this->connect_id);
		}
		else
		{
			// because mysql_escape_string doesnt escape % & _[php.net/mysql_escape_string]
			//return addcslashes(mysql_escape_string($msg),'%_');
			return mysql_escape_string($msg);
		}
	}

	/*
	 * get affected records
	 */
	function affected()
	{
		return ( $this->connect_id ) ? mysql_affected_rows($this->connect_id) : false;
	}

	/*
	 * get the information of mysql server
	 */
	function server_info()
	{
		return 'MySQL ' . $this->mysql_version;
	}

	/*
	error message func
	*/
	function error_msg($msg)
	{
		global $dbprefix;
		
		if(!$this->show_errors)
		{
			return false;
		}

		$error_no  = mysql_errno();
		$error_msg = mysql_error();
		$error_sql = @current($this->debugr[$this->query_num+1]);
		
		//some ppl want hide their table names, not in develoment stage
		if(!defined('DEV_STAGE'))
		{
			$error_sql = preg_replace("#\s{1,3}`*{$dbprefix}([a-z0-9]+)`*\s{1,3}#e", "' <span style=\"color:blue\">' . substr('$1', 0, 1) . '</span> '", $error_sql);
			$error_msg = preg_replace("#{$this->db_name}.{$dbprefix}([a-z0-9]+)#e", "' <span style=\"color:blue\">' . substr('$1', 0, 1) . '</span> '", $error_msg);
			$error_sql = preg_replace("#\s{1,3}(from|update|into)\s{1,3}([a-z0-9]+)\s{1,3}#ie", "' $1 <span style=\"color:blue\">' . substr('$2', 0, 1) . '</span> '", $error_sql);
			$error_msg = preg_replace("#\s{1,3}(from|update|into)\s{1,3}([a-z0-9]+)\s{1,3}#ie", "' $1 <span style=\"color:blue\">' . substr('$2', 0, 1) . '</span> '", $error_msg);
			$error_msg = preg_replace("#\s'([^']+)'@'([^']+)'#ie", "' <span style=\"color:blue\">hidden</span>@$2 '", $error_msg);
			$error_sql = preg_replace("#password\s*=\s*'[^']+'#i", "password='<span style=\"color:blue\">hidden</span>'", $error_sql);
		}

		#is this error related to updating?
		$updating_related = false;
		if(strpos($error_msg, 'Unknown column') !== false)
		{
			$updating_related = true;
		}

		echo "<html><head><title>ERROR IM MYSQL</title>";
		echo "<style>BODY{FONT-FAMILY:tahoma;FONT-SIZE:12px;}.error {}</style></head><body>";
		echo '<br />';
		echo '<div class="error">';
		echo " <a href='#' onclick='window.location.reload( false );'>click to Refresh this page ...</a><br />";
		echo "<h2>Sorry , There is an error in mysql " . ($msg !='' ? ", error : $msg" : "") ."</h2>";
		if($error_sql != '')
		{
			echo "<br />--[query]-------------------------- <br />$error_sql<br />---------------------------------<br /><br />";
		}
		echo "[$error_no : $error_msg] <br />";
		if($updating_related)
		{
			global $config;
			echo "<br /><strong>Your Kleeja database seems to be old, try to update it now from: " . $config['siteurl'] . "install/</strong>";
		}
		echo "<br /><br /><strong>Script: Kleeja <br /><a href='http://www.kleeja.com'>Kleeja Website</a></strong>";
		echo '</b></div>';
		echo '</body></html>';
		
		#loggin -> error
		kleeja_log('[SQL ERROR] : "' . $error_no . ' : ' . $error_msg  . '" ' . $this->connect_id);
		
		@$this->close();
		exit();
	}

	/*
	 * return last error
	 */
	function get_error()
	{
		return array(mysql_errno(), mysql_error()); 
	}

}#end of class

endif;
