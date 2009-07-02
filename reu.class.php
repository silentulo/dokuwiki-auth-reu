<?php
/**
 * REU.RU authentication backend
 *
 * @license    GPL 2
 * @author     Artem <silentulo cxe gmail.com>
*/

define('DOKU_AUTH', dirname(__FILE__));
require_once(DOKU_AUTH.'/basic.class.php');

class auth_reu extends auth_basic {

	var $dbcon		= 0;
	var $dbver		= 0;	// database version
	var $dbrev		= 0;	// database revision
	var $dbsub		= 0;	// database subrevision
	var $cnf		  = null;
	var $defaultgroup = "";

	/**************************************
	 *  INITIALIZATION
	 **************************************/

	/**
	 * Constructor
	 *
	 * checks if the mysql interface is available, otherwise it will
	 * set the variable $success of the basis class to false
	 *
	 * @author Matthias Grimm <matthiasgrimm@users.sourceforge.net>
	 */
	function auth_mysql() {
		global $conf;
		$this->cnf = $conf['auth']['reu'];

		if (method_exists($this, 'auth_basic'))
			 parent::auth_basic();

		if(!function_exists('mysql_connect')) {
			$this->msg_debug ("MySQL err: PHP MySQL extension not found.",-1,__LINE__,__FILE__);
			$this->success = false;
			return;
		}

		// default to UTF-8, you rarely want something else
		if(!isset($this->cnf['charset'])) $this->cnf['charset'] = 'utf8';

		$this->defaultgroup = $conf['defaultgroup'];

		// set capabilities based upon config strings set
		if ( empty($this->cnf['server']) || empty($this->cnf['user']) ||
		     empty($this->cnf['password']) || empty($this->cnf['database'])){
			$this->msg_debug("MySQL err: insufficient configuration.",-1,__LINE__,__FILE__);
			$this->success = false;
			return;
		}

		// $this->cando['addUser']	  = 1;
		// $this->cando['delUser']	  = 1;
		// $this->cando['modLogin']	 = 1;
		// $this->cando['modPass']	  = 1;
		// $this->cando['modName']	  = 1;
		// $this->cando['modMail']	  = 1;
		// $this->cando['modGroups']	= 1;
		// $this->cando['getGroups']	= 1;
		// $this->cando['getUsers']	 = 1;
		// $this->cando['getUserCount'] = 1;
	}


	/**************************************
	 *  INTERFACE
	 **************************************/


	/**
	 * Checks if the given user exists and the given plaintext password
	 * is correct. Furtheron it might be checked wether the user is
	 * member of the right group
	 *
	 * @param  $user  user who would like access
	 * @param  $pass  user's clear text password to check
	 * @return bool
	 */
	function checkPass($user,$pass){
		if(!$this->_openDB())
			return false;

		my $res = $this->_queryCheckPass($user, $pass);
		$this->_closeDB();

		return $res;
	}


	/**************************************
	 *  INTERFACE
	 **************************************/

	/**
	 * Prints msg() if config param debug is set
	 **/
	function msg($msg, $smth, $line, $file) {
		if ($this->cnf['debug'])
		msg($message, $smth, $line, $file);
	}

	/**************************************
	 *  DB CONNECTION
	 **************************************/

	/**
	 * Opens a connection to a database and saves the handle for further
	 * usage in the object. The successful call to this functions is
	 * essential for most functions in this object.
	 *
	 * @return bool
	 */
	function _openDB() {

		// Return if connection already open
		if ($this->dbcon)
			return true;

		// Open connection
		$con = @mysql_connect ($this->cnf['server'], $this->cnf['user'], $this->cnf['password']);
		if (!$con) {
			$this->msg("MySQL err: Connection to {$this->cnf['user']}@{$this->cnf['server']} not possible.",
			           -1,__LINE__,__FILE__);
			return false;
		}

		// Open database
		if (!mysql_select_db($this->cnf['database'], $con)) {
			mysql_close ($con);
			$this->msg ("MySQL err: No access to database {$this->cnf['database']}.",-1,__LINE__,__FILE__);
			return false;
		}

		// Get version
		if ((preg_match("/^(\d+)\.(\d+)\.(\d+).*/", mysql_get_server_info ($con), $result)) == 1) {
			$this->dbver = $result[1];
			$this->dbrev = $result[2];
			$this->dbsub = $result[3];
		}

		$this->dbcon = $con;
		mysql_query('SET CHARACTER SET "utf8"', $con);

		return true;   // connection and database successfully opened
	}

	/**
	 * Closes a database connection.
	 */
	function _closeDB() {
		if (!$this->dbcon)
			return;

		mysql_close ($this->dbcon);
		$this->dbcon = 0;
	}

	/**
	 * Sends a SQL query to the database and transforms the result into
	 * an associative array.
	 *
	 * This function is only able to handle queries that returns a
	 * table such as SELECT.
	 *
	 * @param $query  SQL string that contains the query
	 * @return array with the result table
	 */
	function _queryDB($query) {
		$resultarray = array();

		if (!$this->dbcon)
			return false;

		// Run query
		$result = @mysql_query($query,$this->dbcon);
		if (!$result) {
			$this->msg ('MySQL err: '.mysql_error($this->dbcon),-1,__LINE__,__FILE__);
			return false;
		}

		// Fetch results
		while (($t = mysql_fetch_assoc($result)) !== false)
			$resultarray[]=$t;
		mysql_free_result ($result);

		return $resultarray;
	}

	/**
	 * Escape a string for insertion into the database
	 *
	 * @param  string  $string The string to escape
	 * @param  boolean $like   Escape wildcard chars as well?
	 *
	 * @author Andreas Gohr <andi@splitbrain.org>
	 */
	function _escape($string,$like=false){
		if($this->dbcon){
			$string = mysql_real_escape_string($string, $this->dbcon);
		}else{
			$string = addslashes($string);
		}

		if($like){
			$string = addcslashes($string,'%_');
		}

		return $string;
	}


	/**************************************
	 *  DB QUERIES
	 **************************************/

	/**
	 * Verifies user-password pair
	 *
	 * @param $user username
	 * @param $pass clear password
	 */
	function _queryCheckPass ($user, $pass) {
		// Get hash
		$phash =  $this->_cryptPassword($pass)

		// Construct SQL
		$sql = sprintf ('SELECT kodo FROM `membr` as m ' .
		                'WHERE svorto = "%s" and pvorto = "%s"',
		                $this->escape($user), $this->escape($phash));

		// Query
		$result = $this->_queryDB($sql);

		if($result !== false && count($result) == 1) {
			return true;
		}

		return false;
	}
}