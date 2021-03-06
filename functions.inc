<?php
// +--------------------------------------------------------------------------+
// | Polls Plugin - glFusion CMS                                              |
// +--------------------------------------------------------------------------+
// | functions.inc                                                            |
// |                                                                          |
// +--------------------------------------------------------------------------+
// | Copyright (C) 2008-2018 by the following authors:                        |
// |                                                                          |
// | Mark R. Evans          mark AT glfusion DOT org                          |
// |                                                                          |
// | Copyright (C) 2000-2008 by the following authors:                        |
// |                                                                          |
// | Authors: Tony Bibbs       - tony AT tonybibbs DOT com                    |
// |          Tom Willett      - twillett AT users DOT sourceforge DOT net    |
// |          Blaine Lang      - langmail AT sympatico DOT ca                 |
// |          Dirk Haun        - dirk AT haun-online DOT de                   |
// +--------------------------------------------------------------------------+
// |                                                                          |
// | This program is free software; you can redistribute it and/or            |
// | modify it under the terms of the GNU General Public License              |
// | as published by the Free Software Foundation; either version 2           |
// | of the License, or (at your option) any later version.                   |
// |                                                                          |
// | This program is distributed in the hope that it will be useful,          |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of           |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the            |
// | GNU General Public License for more details.                             |
// |                                                                          |
// | You should have received a copy of the GNU General Public License        |
// | along with this program; if not, write to the Free Software Foundation,  |
// | Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.          |
// |                                                                          |
// +--------------------------------------------------------------------------+

if (!defined ('GVERSION')) {
    die('This file can not be used on its own.');
}

/*
* Check and see if we need to load the plugin configuration
*/
/*if (!isset($_PO2_CONF['pollsloginrequired'])) {
    $po_config = config::get_instance();
    $_PO2_CONF = $po_config->get_config('polls2');
}*/

spl_autoload_register(function ($class)
{
    // project-specific namespace prefix
    $prefix = 'Polls\\';

    // does the class use the namespace prefix?
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        // no, move to the next registered autoloader
        return;
    }

    // base directory for the namespace prefix
    $base_dir = __DIR__ . '/classes/';

    // get the relative class name
    $relative_class = substr($class, $len);

    // replace the namespace prefix with the base directory, replace namespace
    // separators with directory separators in the relative class name, append
    // with .php
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.class.php';

    // if the file exists, require it
    if (file_exists($file)) {
        require $file;
    }
});
use Polls\DB;
use Polls\Config;
use Polls\Poll;
use Polls\Voter;

// May later rely on the class autoloader
require_once __DIR__ . '/polls2.php';
Config::set('url', $_CONF['site_url'] . '/' . Config::get('pi_name'));
Config::set('admin_url', $_CONF['site_admin_url'] . '/plugins/' . Config::get('pi_name'));

/*
* load the language file(s), including custom strings if any
*/
$langfile = __DIR__ . '/language/' . $_CONF['language'] . '.php';
$custfile = __DIR__ . '/language/custom/' . $_CONF['language'] . '.php';

if (file_exists($langfile)) {
    include_once $langfile;
    if (file_exists($custfile)) {
        include_once $custfile;
    }
} else {
    $langfile = __DIR__ . '/language/english_utf-8.php';
    $custfile = __DIR__ . '/language/custom/english_utf-8.php';
    include_once $langfile;
    if (file_exists($custfile)) {
        include_once $custfile;
    }
}

// +---------------------------------------------------------------------------+
// | glFusion Plugin API Implementations                                       |
// +---------------------------------------------------------------------------+

/**
* Returns the items for this plugin that should appear on the main menu
*
* NOTE: this MUST return the url/value pairs in the following format
* $<arrayname>[<label>] = <url>
*
* @return   mixed   menu entry, or boolean false if disabled / hidden
*/
function plugin_getmenuitems_polls2()
{
    global $_CONF, $_USER, $LANG_POLLS;

    $anon = (COM_isAnonUser()) ? true : false;
    if (
        Config::get('hidepollsmenu') == 1 || (
            $anon && ($_CONF['loginrequired'] || Config::get('pollsloginrequired'))
        )
    ) {
        return false;
    }
    $menuitems[ucfirst(Config::get('pi_name'))] = Config::get('url') . '/index.php';
    return $menuitems;
}

/**
* glFusion is checking to see if this plugin supports comments, tell it yes.
*
* NOTE: to support comments you must used the same date/time based ID for your
* widget.  In other words, to make primary keys for your plugin you should call
* makesid().  Comments are keyed off of that...it is a limitation on how glFusion
* does comments.
*
*/
function plugin_commentsupport_polls2()
{
    // polls will use comments
    return true;
}

function plugin_getcommenturlid_polls2()
{
    $retval = array();
    $retval[] = Config::get('url') . '/index.php';
    $retval[] = 'pid';
    return $retval;
}

/**
 * Poll saves a comment
 *
 * @param   string  $title  comment title
 * @param   string  $comment comment text
 * @param   string  $id     Item id to which $cid belongs
 * @param   int     $pid    comment parent
 * @param   string  $postmode 'html' or 'text'
 * @return  mixed   false for failure, HTML string (redirect?) for success
 */
function plugin_savecomment_polls2($title, $comment, $id, $pid, $postmode)
{
    global $_CONF, $_TABLES, $LANG03, $_USER;

    $retval = '';

    $commentcode = DB_getItem(
        DB::table('topics'),
        'commentcode',
        "pid = '" . DB_escapeString($id)."'"
    );
    if ($commentcode != 0) {
        return COM_refresh ($_CONF['site_url'] . '/index.php');
    }

    $ret = CMT_saveComment ($title, $comment, $id, $pid, 'polls', $postmode);
    if ($ret > 0) {
        $retval .= CMT_commentForm ($title, $comment, $id, $pid, 'polls',
                                   $LANG03[14], $postmode);
    } else { // success
        $retval = COM_refresh(Config::get('url') . "/index.php?pid=$id");
    }
    $c = glFusion\Cache::getInstance()->deleteItemsByTag('whatsnew');
    return $retval;
}

function plugin_editcomment_polls2($cid,$id)
{
    echo COM_refresh(Config::get('url') . "/index.php?pid=$id");
    exit;
}

/**
 * polls: delete a comment
 *
 * @param   int     $cid    Comment to be deleted
 * @param   string  $id     Item id to which $cid belongs
 * @return  mixed   false for failure, HTML string (redirect?) for success
 */
function plugin_deletecomment_polls2($cid, $id)
{
    global $_CONF, $_USER;

    $retval = '';

    if (Poll::hasRights('edit')) {
        CMT_deleteComment($cid, $id, 'polls');
        $retval .= COM_refresh(Config::get('url') . "/index.php?pid=$id");
    } else {
        COM_errorLog ("User {$_USER['username']} (IP: {$_SERVER['REMOTE_ADDR']}) "
                    . "did not have permissions to delete comment $cid from poll $id");
        $retval .= COM_refresh ($_CONF['site_url'] . '/index.php');
    }
    $c = glFusion\Cache::getInstance()->deleteItemsByTag('whatsnew');
    return $retval;
}


function plugin_getwhatsnewcomment_polls2()
{
    global $_CONF, $_USER;

    $commentrow = array();

    $tbl_topics = DB::table('topics');
    $tbl_comments = DB::table('comments');
    $stwhere = '';
    if( !COM_isAnonUser() ) {
        $stwhere .= "({$tbl_topics}.owner_id IS NOT NULL AND {$tbl_topics}.perm_owner IS NOT NULL) OR ";
        $stwhere .= "({$tbl_topics}.group_id IS NOT NULL AND {$tbl_topics}.perm_group IS NOT NULL) OR ";
        $stwhere .= "({$tbl_topics}.perm_members IS NOT NULL)";
    } else {
        $stwhere .= "({$tbl_topics}.perm_anon IS NOT NULL)";
    }
    $sql = "SELECT DISTINCT COUNT(*) AS dups, {$tbl_topics}.topic AS title,
            {$tbl_topics}.pid AS sid, UNIX_TIMESTAMP(max({$tbl_comments}.date)) AS lastdate
            FROM {$tbl_comments} LEFT JOIN {$tbl_topics} ON (({$tbl_topics}.pid = {$tbl_comments}.sid)"
            . COM_getPermSQL( 'AND', 0, 2, $tbl_topics ) .
            " AND ({$tbl_topics}.commentcode >= 0)" .
            /* COM_getLangSQL( 'pid', 'AND', $_TABLES['polltopics'] ) .*/ ")
            WHERE ({$tbl_comments}.queued = 0 AND {$tbl_comments}.date >= (DATE_SUB('".$_CONF['_now']->toMySQL(true)."', INTERVAL {$_CONF['newcommentsinterval']} SECOND)))
            AND ((({$stwhere}))) GROUP BY {$tbl_comments}.sid,type, {$tbl_topics}.topic,
            {$tbl_topics}.topic, {$tbl_topics}.pid
            ORDER BY 4 DESC LIMIT 15";

    $result = DB_query( $sql );
    $nrows = DB_numRows( $result );
    if ( $nrows > 0 ) {
        $filter = new sanitizer();
        $filter->setPostmode('text');

        for ($x = 0; $x < $nrows; $x++ ) {
            $A = DB_fetchArray($result);
            $cmtReturn['url'] = Config::get('url') . '/index.php?pid=' . $A['sid'] . '#comments';
            $cmtReturn['type'] = 'polls';
            $cmtReturn['dups'] = $A['dups'];
            $cmtReturn['title'] = $filter->filterData($A['title']);
            $cmtReturn['lastdate'] = $A['lastdate'];
            $commentrow[] = $cmtReturn;
        }
    }
    return $commentrow;
}

/**
* glFusion is asking us to provide any items that show up in the type
* drop-down on search.php.  Let's users search for events.
*
* @return   array   (plugin name/entry title) pair for the dropdown
*
*/

function plugin_searchtypes_polls2()
{
    global $LANG_POLLS;

    return array(Config::get('pi_name') => ucfirst(Config::get('pi_name'));

}

/**
 * This searches for polls matching the user query and returns an array of
 * for the header and table rows back to search.php where it will be formatted and
 * printed.
 *
 * @param    string $query     Keywords user is looking for
 * @param    string $dateStart Start date to get results for
 * @param    string $dateEnd   End date to get results for
 * @param    string $topic     The topic they were searching in
 * @param    string $type      Type of items they are searching, or 'all' (deprecated)
 * @param    int    $author    Get all results by this author
 * @param    string $keyType   search key type: 'all', 'phrase', 'any'
 * @param    int    $page      page number of current search (deprecated)
 * @param    int    $perpage   number of results per page (deprecated)
 * @return   SearchCriteria
  */
function plugin_dopluginsearch_polls2($query, $dateStart, $dateEnd, $topic, $type, $author, $keyType, $page, $perpage)
{
    global $_CONF, $_DB_dbms, $LANG09, $LANG_POLLS, $_USER;

    // Make sure the query is SQL safe
    $query = trim(DB_escapeString($query));
    $uid = isset($_USER['uid']) ? $_USER['uid'] : 1;

    $tbl_topics = DB::table('topics');
    $pi_name = Config::get('pi_name');
    // Search polls
    $sql = "SELECT p.pid AS id, p.topic AS title, p.description AS description, UNIX_TIMESTAMP(p.date) AS date, "
        . " p.owner_id AS uid, p.voters AS hits, CONCAT('/$pi_name/index.php?pid=',p.pid) AS url "
        . " FROM {$tbl_topics} AS p "
        . " WHERE (p.date <= '".$_CONF['_now']->toMySQL(true)."') "
        . COM_getPermSQL('AND', 0, 2, 'p');

    if (!Poll::hasRights('admin', $uid)) {
        $sql .= " AND ((p.hideresults = 0) OR (p.owner_id = {$uid})) ";
    }

    if (!empty($author)) {
        $sql .= "AND (p.owner_id = '{$author}') ";
    }

    $search = new SearchCriteria(
        Config::get('pi_name'),
        array(ucfirst(Config::get('pi_name')))
    );

    $columns = array('title' => 'p.topic', 'description' => 'p.description');
    $sql .= $search->getDateRangeSQL('AND', 'p.date', $dateStart, $dateEnd);
    list($sql, $ftsql) = $search->buildSearchSQL($keyType, $query, $columns, $sql);

    $search->setSQL($sql);
    $search->setFTSQL($ftsql);
    $search->setRank(2);

    return $search;
}


/**
* this searches the plugins comments matching the user query and returns an array of
* for the header and table rows back to search.php where it will be formated and
* printed
*
* @query            string          Keywords user is looking for
* @datestart        date/time       Start date to get results for
* @dateend          date/time       End date to get results for
* @topic            string          The topic they were searching in
* @type             string          Type of items they are searching
* @author           string          Get all results by this author
*
*/
function plugin_dopluginsearch_comment_polls2($query, $datestart, $dateend, $topic, $type, $author, $keyType, $page, $perpage)
{
    global $_USER, $_CONF, $LANG09;

    $query = trim(DB_escapeString(htmlspecialchars($query)));
    $pi_name = Config::get('pi_name');

    $sql =  "SELECT c.cid AS id1, a.pid AS id, c.title AS title, c.comment AS description, UNIX_TIMESTAMP(c.date) AS date, c.uid AS uid, '0' AS hits, ";
    $sql .= "CONCAT('/$pi_name/index.php?pid=',a.pid,'#comments') AS url ";
    $sql .= "FROM {$_TABLES['users']} AS u, {$_TABLES['comments']} AS c ";
    $sql .= "LEFT JOIN " . DB::table('topics') . " a ON c.sid=a.pid ";
    $sql .= "WHERE (c.type='polls' AND c.queued = 0) AND (u.uid = c.uid) ";
    $sql .= COM_getPermSQL ('AND');

    if (!empty($author)) {
        $sql .= "AND (c.uid = '$author') ";
    }

    $search_c = new SearchCriteria('comments', 'Polls > '.$LANG09[66]);
    $columns = array('comment','c.title');
    $sql .= $search_c->getDateRangeSQL('AND', 'UNIX_TIMESTAMP(c.date)', $datestart, $dateend);
    list($sql,$ftsql) = $search_c->buildSearchSQL($keyType, $query, $columns, $sql);
    $search_c->setSQL($sql);
    $search_c->setFTSQL($ftsql);
    $search_c->setRank(2);

    return $search_c;
}

/**
* Shows the statistics for the Polls plugin on stats.php.
* If $showsitestats is 1 then we are to only print the overall stats in the
* 'site statistics box' otherwise we show the detailed stats
*
* @param    int     showsitestats   Flag to let us know which stats to get
*/
function plugin_showstats_polls2($showsitestats)
{
    global $_CONF, $LANG_POLLS;

    require_once ($_CONF['path_system'] . 'lib-admin.php');

    $retval = '';

    $result = DB_query ("SELECT pid,topic,voters FROM " . DB::table('topics') . " WHERE (voters > 0)" . COM_getPermSQL ('AND') . " ORDER BY voters DESC LIMIT 10");
    $nrows  = DB_numRows ($result);

    if ($nrows > 0) {
        $header_arr = array(
            array('text'         => $LANG_POLLS['stats_topics'],
                  'field'        => 'pid',
                  'header_class' => 'stats-header-title'
            ),
            array('text'         => $LANG_POLLS['stats_votes'],
                  'field'        => 'voters',
                  'header_class' => 'stats-header-count',
                  'field_class'  => 'stats-list-count'
            ),
        );
        $data_arr = array();
        $text_arr = array('has_menu' => false,
                          'title'    => $LANG_POLLS['stats_top10'],
        );

        $filter = new sanitizer();
        $filter->setPostmode('text');

        for ($i = 0; $i < $nrows; $i++) {
            $A = DB_fetchArray ($result);
            $url = Config::get('url') . '/index.php?pid=' . $A['pid']
                 . '&amp;aid=-1';
            $pid = COM_createLink($filter->filterData($A['topic']), $url);
            $voters = COM_NumberFormat ($A['voters']);
            $data_arr[] = array ('pid' => $pid, 'voters' => $voters);
        }
        $retval .= ADMIN_simpleList ('', $header_arr, $text_arr, $data_arr);
    } else {
        $retval .= COM_startBlock ($LANG_POLLS['stats_top10'],'',COM_getBlockTemplate('_admin_block', 'header'));
        $retval .= $LANG_POLLS['stats_none'];
        $retval .= COM_endBlock (COM_getBlockTemplate('_admin_block', 'footer'));
    }

    return $retval;
}

/**
* New stats plugin API function for proper integration with the site stats
*
* @return   array(item text, item count);
*
*/
function plugin_statssummary_polls2()
{
    global $LANG_POLLS;

    list($total_polls, $total_answers) = POLLS2_countPollsAndVotes ();

    $item_count = COM_numberFormat ($total_polls)
                . ' (' . COM_numberFormat ($total_answers) . ')';

    return array ($LANG_POLLS['stats_summary'], $item_count);
}


/**
* This will put an option for polls in the command and control block on
* moderation.php
*
*/
function plugin_cclabel_polls2()
{
    global $LANG_POLLS;

    if (plugin_ismoderator_polls2()) {
        return array(
            ucfirst(Config::get('pi_name')),
            Config::get('admin_url') . '/index.php',
            plugin_geticon_polls2()
        );
    }
    return false;
}


/**
* returns the administrative option for this plugin
*
*/
function plugin_getadminoption_polls2()
{
    global $_CONF, $LANG_POLLS;

    if (plugin_ismoderator_polls2()) {
        return array(
            ucfirst(Config::get('pi_name')),
            Config::get('admin_url') . '/index.php',
            Poll::countPolls()
        );
    }
}

function plugin_user_move_polls2($origUID, $destUID)
{
    Poll::moveUser($origUID, $destUID);
}


/**
* A user is about to be deleted. Update ownership of any polls owned
* by that user or delete them.
*
* @param   uid   int   User id of deleted user
*
*/
function plugin_user_delete_polls2($uid)
{
    global $_TABLES;    // needed for groups and group_assignments

    // Anonymize pollvoters by uid / ipaddress
    Voter::anonymize($uid);

    if (DB_count(DB::table('topics'), 'owner_id', intval($uid)) == 0) {
        // there are no polls owned by this user
        return;
    }

    if (Config::get('delete_polls') == 1) {
        // delete the polls
        $result = DB_query ("SELECT pid FROM " . DB::table('topics') . " WHERE owner_id=".intval($uid));
        while ($A = DB_fetchArray($result, false)) {
            Poll::deletePoll($pid);
        }
    } else {
        // assign ownership to a user from the Root group
        $rootgroup = DB_getItem(
            $_TABLES['groups'],
            'grp_id',
            "grp_name = 'Root'"
        );
        $result = DB_query(
            "SELECT DISTINCT ug_uid FROM {$_TABLES['group_assignments']}
            WHERE ug_main_grp_id = $rootgroup ORDER BY ug_uid LIMIT 1"
        );
        list($rootuser) = DB_fetchArray($result, false);
        Poll::getInstance($pid)->setOwner($rootuser)->Save();
    }
}


/**
* Return the current version of code.
* Used in the Plugin Editor to show the registered version and code version
*/
function plugin_chkVersion_polls2()
{
    return Config::get('pi_version');
}

/**
* glFusion informs us that we're about to be enabled or disabled
*
* @param    boolean     $enable     true = we're being enabled, false = disabled
* @return   void
*
*/
function plugin_enablestatechange_polls2($enable)
{
    global $_TABLES;        // needed for blocks table

    $is_enabled = $enable ? 1 : 0;

    // toggle Poll block
    DB_query(
        "UPDATE {$_TABLES['blocks']}
        SET is_enabled = $is_enabled
        WHERE (type = 'phpblock')
        AND (phpblockfn = 'phpblock_" . Config::get('pi_name') . "')"
    );
}

/**
* Display the current poll(s) in a side block
*
* @return   string  HTML for the poll(s) to be displayed (or an empty string)
*
*/
function phpblock_polls2()
{
    $retval = '';
    $Polls = Poll::getOpen(true);
    foreach ($Polls as $Poll) {
        $retval .= $Poll->Render(false, 1);
    }
    return $retval;
}


/**
* Returns the URL of the plugin's icon
*
* @return   string      URL of the icon
*
*/
function plugin_geticon_polls2()
{
    return Config::get('url') . '/images/polls.png';
}

/**
* Plugin should display [a] comment[s]
*
* @param   string  $id     Unique idenifier for item comment belongs to
* @param   int     $cid    Comment id to display (possibly including sub-comments)
* @param   string  $title  Page/comment title
* @param   string  $order  'ASC' or 'DESC' or blank
* @param   string  $format 'threaded', 'nested', or 'flat'
* @param   int     $page   Page number of comments to display
* @param   boolean $view   True to view comment (by cid), false to display (by $pid)
* @return  mixed   results of calling the plugin_displaycomment_ function
*/
function plugin_displaycomment_polls2($id, $cid, $title, $order, $format, $page, $view)
{
    global $_TABLES, $LANG_ACCESS;

    $retval = '';

    $pid = $id;
    $aid = 0;

    $result = DB_query("SELECT is_open FROM " . DB::table('topics') . " WHERE pid='".DB_escapeString($pid)."'");
    if ( $result && DB_numRows($result) > 0 ) {
        $row = DB_fetchArray($result);
        $is_open = $row['is_open'];
        if ($is_open != 1) {
            $aid = -1; // poll closed - show result
        }
        $Poll = Poll::getInstance($pid);
        if (!isset ($_COOKIE['poll-'.$pid]) && !$Poll->alreadyVoted() && $aid != -1 ) {
            //$retval .= POLLS_pollVote($pid);
            $retval .= $Poll->Render();
        } else {
            $retval .= $Poll->showResults(400, '', '');
        }
    } else {
        return false;
    }
    return $retval;
}


/**
* Set template variables
*
* @param    string  $templatename   name of template, e.g. 'header'
* @param    ref     $template       reference of actual template
* @return   void
*
* Note: A plugin should use its name as a prefix for the names of its
* template variables, e.g. 'polls_xxx' and 'lang_polls_xxx'.
* 'button_polls' is an exception, as such a variable existed for header.thtml
*
*/
function plugin_templatesetvars_polls2($templatename, &$template)
{
    global $LANG_POLLS;

    if ($templatename == 'header') {
        $template->set_var ('button_polls', ucfirst(Config::get('pi_name')));
    }
}

function plugin_getheadercss_polls2()
{
    global $_CONF;

    $styles = array();
    $file = '/' . Config::get('pi_name') . '/style.css';

    if (@file_exists($_CONF['path_layout'].'plugins' . $file)) {
        $styles[] = $_CONF['path_layout'].'plugins/' . $file;
    } else {
        $styles[] = $_CONF['path_html'] . $file;
    }
    return $styles;
}


/**
* Update the Polls plugin
*
* @return   int     Number of message to display (true = generic success msg)
*
*/
function plugin_upgrade_polls2()
{
    global $_CONF;

    if ( @file_exists(__DIR__ . '/upgrade.php') ) {
        require_once __DIR__ . '/upgrade.php';
        return polls_upgrade();
    } else {
        // upgrades are done by the install script - return a generic error
        COM_errorLog("Polls plugin upgrade function not implemented");
        return 3001;
    }
}

/**
* Poll Autotags
* [poll: pid] - Displays a link to a poll using the Poll Topic as the title.
*               A alternate title may be specified.
* [poll_vote: pid pid class:poll-autotag showall:1] - Class and showall not
*             required. Class specifies the css class and Showall if set to 1, shows all questions
* [poll_result: pid class:poll-autotag] - Class not required. Class specifies
*                                         the css class
*
*/
function plugin_autotags_polls2($op, $content = '', $autotag = '')
{
    global $_CONF, $_TABLES, $LANG_PO_AUTOTAG;

    $skip = 0;

    if ($op == 'tagname' ) {
        return array('poll_vote', 'poll_result', 'poll');
    } elseif ( $op == 'desc' ) {
        switch($content) {
            case 'poll_vote' :
                return $LANG_PO_AUTOTAG['desc_poll_vote'];
                break;
            case 'poll_result' :
                return $LANG_PO_AUTOTAG['desc_poll_result'];
                break;
            case 'poll' :
                return $LANG_PO_AUTOTAG['desc_poll'];
                break;
            default :
                return '';
                break;
        }
    } elseif ($op == 'parse') {
        $tag = str_replace('&nbsp;',' ',$autotag['tagstr']);
        $parms = explode (' ', $tag);
        // Extra test to see if autotag was entered with a space
        // after the module name
        if (substr ($parms[0], -1) == ':') {
            $startpos = strlen ($parms[0]) + strlen ($parms[1]) + 2;
            $label = str_replace (']', '', substr ($tag, $startpos));
            $tagid = $parms[1];
        } else {
            $label = str_replace (']', '',
                     substr ($tag, strlen ($parms[0]) + 1));
            $parms = explode (':', $parms[0]);
            if (count ($parms) > 2) {
                // whoops, there was a ':' in the tag id ...
                array_shift ($parms);
                $tagid = implode (':', $parms);
            } else {
                $tagid = $parms[1];
            }
        }
        $autotag['parm1'] = str_replace(']','',$tagid);
        $autotag['parm2'] = $label;

        $class = 'poll-autotag';
        $showall = false;

        $px = explode (' ', trim ($autotag['parm2']));
        if (is_array ($px)) {
            foreach ($px as $part) {
                if (substr ($part, 0, 6) == 'class:') {
                    $a = explode (':', $part);
                    $class = $a[1];
                    $skip++;
                } elseif (substr ($part, 0, 8) == 'showall:') {
                    $a = explode (':', $part);
                    $val = (int) $a[1];
                    if ( $val == 1 ) {
                        $showall = true;
                    }
                    $skip++;
                } else {
                    break;
                }
            }

            if ($skip != 0) {
                if (count ($px) > $skip) {
                    for ($i = 0; $i < $skip; $i++) {
                        array_shift ($px);
                    }
                    $caption = trim (implode (' ', $px));
                } else {
                    $caption = '';
                }
            }
        } else {
            $caption = trim ($autotag['parm2']);
        }

        $pid = COM_applyFilter($autotag['parm1']);

        switch ($autotag['tag']) {
            case 'poll':
                $filter = new sanitizer();
                $filter->setPostmode('text');

                if (!empty($autotag['parm2'])) {
                    $title = COM_applyFilter($autotag['parm2']);
                } else {
                    $title = DB_getItem(
                        DB::table('topics'),
                        'topic',
                        "pid = '$pid'"
                    );
                }
                if ( $title == '' ) {
                    $retval = '';
                } else {
                    $retval = COM_createLink(
                        $filter->filterData($title),
                        Config::get('url') . '/index.php?pid=' . $pid);
                }
                break;
        case 'poll_vote':
        case 'poll_result':
            if ($autotag['tag'] == 'poll_vote') {
                $retval = POLLS_showPoll(400, $pid, $showall, 2);
            } else {
                $retval = POLLS_pollResults($pid, 0,'', false, 2);
            }

            if ( $retval != '' ) {
                $retval = '<div class="' . $class . '">' . $retval . '</div>';
            }
            break;
        }

        $content = str_replace($autotag['tagstr'], $retval, $content);
    }

    return $content;
}

/**
* Return information for a poll
*
* @param    string  $id         poll ID or '*'
* @param    string  $what       comma-separated list of properties
* @param    int     $uid        user ID or 0 = current user
* @param    array   $options    (reserved for future extensions)
* @return   mixed               string or array of strings with the information
*
*/
function plugin_getiteminfo_polls2($id, $what, $uid = 0, $options = array())
{
    global $_CONF, $_TABLES, $LANG_POLLS;

    $properties = explode(',', $what);
    $fields = array();
    foreach ($properties as $p) {
        switch ($p) {
            case 'date-modified':
            case 'date' :
                $fields[] = 'UNIX_TIMESTAMP(date) AS unixdate';
                break;
            case 'id':
                $fields[] = 'pid';
                break;
            case 'description' :
            case 'searchidx' :
                $fields[] = 'description';
                $fields[] = 'topic';
                break;
            case 'title':
                $fields[] = 'topic';
                break;
            case 'url':
            case 'label':
                $fields[] = 'pid';
                $fields[] = 'hideresults';
                break;
            case 'author' :
            case 'author_name' :
                $fields[] = 'owner_id';
                break;
            case 'hits' :
                $fields[] = 'voters';
                break;
            case 'perms' :
                $fields[] = 'owner_id';
                $fields[] = 'group_id';
                $fields[] = 'perm_owner';
                $fields[] = 'perm_group';
                $fields[] = 'perm_members';
                $fields[] = 'perm_anon';
                break;
            default:
                break;
        }
    }

    $fields = array_unique($fields);

    if (count($fields) == 0) {
        $retval = array();

        return $retval;
    }

    if ($id == '*') {
        $where = '';
        $permOp = 'WHERE';
    } else {
        $where = " WHERE pid = '" . DB_escapeString($id) . "'";
        $permOp = 'AND';
    }
    if ($uid > 0) {
        $permSql = COM_getPermSql($permOp, $uid);
    } else {
        $permSql = COM_getPermSql($permOp);
    }
    $sql = "SELECT " . implode(',', $fields) . " FROM " . DB::table('topics') . " " . $where . $permSql;
    if ($id != '*') {
        $sql .= ' LIMIT 1';
    }

    $result = DB_query($sql);
    $numRows = DB_numRows($result);

    $filter = new sanitizer();
    $filter->setPostmode('text');

    $retval = array();
    for ($i = 0; $i < $numRows; $i++) {
        $A = DB_fetchArray($result);

        $props = array();
        foreach ($properties as $p) {
            switch ($p) {
                case 'date-modified':
                case 'date' :
                    $props[$p] = $A['unixdate'];
                    break;
                case 'id':
                    $props['id'] = $A['pid'];
                    break;
                case 'description' :
                    $props['description'] = $filter->filterData($A['description']);
                    break;
                case 'searchidx' :
                    if ( $A['description'] == '' ) {
                        $props['searchidx'] = $A['topic'];
                    } else {
                        $props['searchidx'] = $A['topic'] . ' ' . $A['description'];
                    }
                    break;
                case 'title':
                    $props['title'] = $filter->filterData($A['topic']);
                    break;
                case 'url':
                    if (empty($A['pid'])) {
                        $props['url'] = Config::get('url') . '/index.php?pid='.$pid.'&amp;aid=-1';
                    } else {
                        // Users who have already voted but cannot see the results cannot view link
                        if (
                            Poll::hasRights('edit') ||
                            (!$A['hideresults'] && Voter::hasVoted($A['pid'], $A['group_id']))
                        ) {
                            $props['url'] = Config::get('url') . '/index.php?pid=' . $A['pid'];
                        }
                    }
                    break;
                case 'label':
                    $props['label'] = ucfirst(Config::get('pi_name'));
                    break;
                case 'status':
                    $props['status'] = 1; // stub - default
                    break;
                case 'author':
                    $props['author'] = $A['owner_id'];
                    break;
                case 'author_name' :
                    $props['author_name'] = COM_getDisplayName($A['owner_id']);
                    break;
                case 'hits' :
                    $props['hits'] = $A['voters'];
                    break;
                case 'perms' :
                    $props['perms'] = array(
                        'owner_id' => $A['owner_id'],
                        'group_id' => $A['group_id'],
                        'perm_owner' => $A['perm_owner'],
                        'perm_group' => $A['perm_group'],
                        'perm_members' => $A['perm_members'],
                        'perm_anon' => $A['perm_anon'],
                    );
                    break;
                default:
                    $props[$p] = '';
                    break;
            }
        }

        $mapped = array();
        foreach ($props as $key => $value) {
            if ($id == '*') {
                if ($value != '') {
                    $mapped[$key] = $value;
                }
            } else {
                $mapped[$key] = $value;
            }
        }

        if ($id == '*') {
            $retval[] = $mapped;
        } else {
            $retval = $mapped;
            break;
        }
    }

    if (($id != '*') && (count($retval) == 1)) {
        $tRet = array_values($retval);
        $retval = $tRet[0];
    }

    return $retval;
}


/**
 * Check if this is a plugin moderator.
 * The Moderator can edit polls.
 *
 * @return  boolean     True for admins, False for regular users
 */
function plugin_ismoderator_polls2()
{
    static $isMod = NULL;
    if ($isMod === NULL) {
        $isMod = Poll::hasRights('admin,edit', 'OR');
    }
    return $isMod;
}


function plugin_getconfigelementhelp_polls2($type, $option, $doclang = 'english' )
{
    global $_CONF;

    $retval = '';

    $baseUrl = $_CONF['site_url'];
    $cfg = 'docs/' . $doclang . '/polls.html';
    if (@file_exists($_CONF['path_html'] . $cfg)) {
        $descUrl = $baseUrl . '/' . $cfg;
    } else {
        $descUrl = $baseUrl . '/docs/english/polls.html';
    }
    $retval = $descUrl;

    return array($retval,0);
}

/**
* Helper function: count number of polls and total number of votes
*
* @return   array(number of polls, number of votes);
*
*/
function POLLS2_countPollsAndVotes()
{
    global $_TABLES;

    $total_polls = 0;
    $total_answers = 0;

    $result = DB_query ("SELECT COUNT(*) AS count FROM " . DB::table('topics') . COM_getPermSQL ());
    $A = DB_fetchArray($result);
    $total_polls = $A['count'];

    $result = DB_query ("SELECT pid FROM " . DB::table('topics') . COM_getPermSQL ());
    $nrows = DB_numRows ($result);
    if ($nrows > 0) {
        $topics = '';
        for ($i = 1; $i <= $nrows; $i++) {
            $A = DB_fetchArray($result);
            if ($i > 1) {
                $topics .= ',';
            }
            $topics .= "'" . $A['pid'] . "'";
        }
        $result = DB_query ("SELECT SUM(votes) FROM " . DB::table('answers') . " WHERE pid IN ({$topics})");
        $A = DB_fetchArray($result, true);
        $total_answers = $A[0];
    }

    return array ($total_polls, $total_answers);
}


?>
