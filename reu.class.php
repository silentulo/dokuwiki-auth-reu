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

}