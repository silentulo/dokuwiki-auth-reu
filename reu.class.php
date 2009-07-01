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

    var $dbcon        = 0;
    var $dbver        = 0;    // database version
    var $dbrev        = 0;    // database revision
    var $dbsub        = 0;    // database subrevision
    var $cnf          = null;
    var $defaultgroup = "";

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
            if ($this->cnf['debug'])
                msg("MySQL err: PHP MySQL extension not found.",-1,__LINE__,__FILE__);
            $this->success = false;
            return;
        }

        // default to UTF-8, you rarely want something else
        if(!isset($this->cnf['charset'])) $this->cnf['charset'] = 'utf8';

        $this->defaultgroup = $conf['defaultgroup'];

        // set capabilities based upon config strings set
        if (empty($this->cnf['server']) || empty($this->cnf['user']) ||
            empty($this->cnf['password']) || empty($this->cnf['database'])){
            if ($this->cnf['debug'])
                msg("MySQL err: insufficient configuration.",-1,__LINE__,__FILE__);
            $this->success = false;
            return;
        }

        /* temporary commented
        $this->cando['addUser']      = $this->_chkcnf(array('getUserInfo',
                                                            'getGroups',
                                                            'addUser',
                                                            'getUserID',
                                                            'getGroupID',
                                                            'addGroup',
                                                            'addUserGroup'),true);
        $this->cando['delUser']      = $this->_chkcnf(array('getUserID',
                                                            'delUser',
                                                            'delUserRefs'),true);
        $this->cando['modLogin']     = $this->_chkcnf(array('getUserID',
                                                            'updateUser',
                                                            'UpdateTarget'),true);
        $this->cando['modPass']      = $this->cando['modLogin'];
        $this->cando['modName']      = $this->cando['modLogin'];
        $this->cando['modMail']      = $this->cando['modLogin'];
        $this->cando['modGroups']    = $this->_chkcnf(array('getUserID',
                                                            'getGroups',
                                                            'getGroupID',
                                                            'addGroup',
                                                            'addUserGroup',
                                                            'delGroup',
                                                            'getGroupID',
                                                            'delUserGroup'),true); */

        /* getGroups is not yet supported
        $this->cando['getGroups']    = $this->_chkcnf(array('getGroups',
                                                            'getGroupID'),false); */

        /* temporary commented
        $this->cando['getUsers']     = $this->_chkcnf(array('getUsers',
                                                            'getUserInfo',
                                                            'getGroups'),false);
        $this->cando['getUserCount'] = $this->_chkcnf(array('getUsers'),false); */
    }
    
    /**
     * Check if the given config strings are set
     *
     * @author  Matthias Grimm <matthiasgrimm@users.sourceforge.net>
     * @return  bool
     */
    function _chkcnf($keys, $wop=false){
        foreach ($keys as $key){
            if (empty($this->cnf[$key])) return false;
        }

        /* write operation and lock array filled with tables names? */
        if ($wop && (!is_array($this->cnf['TablesToLock']) ||
                     !count($this->cnf['TablesToLock']))){
            return false;
        }

        return true;
    }
}