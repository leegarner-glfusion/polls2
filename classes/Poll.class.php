<?php
/**
 * Class to represent a poll.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2020 Lee Garner <lee@leegarner.com>
 * @package     polls
 * @version     v3.0.0
 * @since       v3.0.0
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */
namespace Polls2;
use Polls2\DB;
use Polls2\Models\Dates;


/**
 * Class for a single poll.
 * @package polls
 */
class Poll
{
    /** Poll ID.
     * @var string */
    private $pid = '';

    /** Old poll ID. Used when editing.
     * @var string */
    private $old_pid = '';

    /** Poll Topic.
     * @var string */
    private $topic = '';

    /** Poll Description.
     * @var string */
    private $dscp = '';

    /** Number of votes cast.
     * @deprecate
     * @var integer */
    private $voters = 0;

    /** Number of questions in the poll.
     * @deprecate
     * @var integer */
    private $questions = 0;

    /** Date the poll was added.
     * @var object */
    private $Date = NULL;

    /** Does the poll appear in the poll block?
     * @var boolean */
    private $inblock = 1;

    /** Is the poll open to submissions?
     * @var boolean */
    private $is_open = 1;

    /** Opening date/time.
     * @var object */
    private $Opens = NULL;

    /** Closing date/time
     * @var object */
    private $Closes = NULL;

    /** Hide results while the poll is open?
     * @var boolean */
    private $hideresults = 1;

    /** Comments enabled/closed/disabled/etc.?
     * @var integer */
    private $commentcode = 0;

    /** Is a login required to submit the poll?
     * @var boolean */
    private $login_required = 0;

    /** Owner ID.
     * @var integer */
    private $owner_id = 0;

    /** Voting Group ID.
     * @var integer */
    private $group_id = 13;

    /** Results Group ID.
     * @var integer */
    private $results_grp = 13;

    /** Owner permission.
     * @var integer */
    private $perm_owner = 3;

    /** Group permission.
     * @var integer */
    private $perm_group = 2;

    /** Logged-In User permission.
     * @var integer */
    private $perm_members = 2;

    /** Anonymous permission.
     * @var integer */
    private $perm_anon = 2;

    /** Is this a new record?
     * @var boolean */
    private $isNew = true;

    /** Questions for this poll.
     * @var array */
    private $_Questions = array();


    /**
     * Constructor.
     * Create a poll object for the specified user ID, or the current
     * user if none specified.
     * If a key is requested, then just build the poll for that key (requires a $uid).
     *
     * @param   string  $pid     Poll ID, empty to create a new record
     */
    function __construct($pid = '')
    {
        $this->setID(COM_makeSid());
        $this->setOpenDate();
        $this->setClosingDate();
        if (is_array($pid)) {
            $this->setVars($pid, true);
        } elseif (!empty($pid)) {
            $pid = COM_sanitizeID($pid);
            $this->setID($pid);
            if ($this->Read()) {
                $this->isNew = false;
            }
        }
        $this->_Questions = Question::getByPoll($this->pid);
    }


    /**
     * Get an instance of a poll object.
     *
     * @param   string  $pid    Poll record ID
     * @return  object      Poll object
     */
    public static function getInstance($pid)
    {
        return new self($pid);
    }


    /**
     * Get all the currently open polls.
     *
     * @param   boolean $inblock    True if the in_block flag must be set
     * @return  array       Array of Poll objects
     */
    public static function getOpen($inblock=false)
    {
        $in_block = $inblock ? ' AND display = 1' : '';
        $sql = "SELECT p.*, count(v.id) as vote_count FROM " . DB::table('topics') . " p
            LEFT JOIN " . DB::table('voters') . " v
            ON v.pid = p.pid
            WHERE is_open = 1 $in_block
            ORDER BY pid ASC";
        $res = DB_query($sql);
        $retval = array();
        while ($A = DB_fetchArray($res, false)) {
            $retval[] = new self($A);
        }
        return $retval;
    }


    public static function countPolls()
    {
        $result = DB_query("SELECT COUNT(*) AS cnt FROM " . DB::table('topics') . COM_getPermSQL());
        $A = DB_fetchArray ($result);
        return (int)$A['cnt'];
    }


    /**
     * Set the poll record ID.
     *
     * @param   string  $id     Record ID for poll
     * @return  object  $this
     */
    private function setID($id)
    {
        $this->pid = COM_sanitizeID($id, false);
        return $this;
    }


    /**
     * Get the poll reord ID.
     *
     * @return  string  Record ID of poll
     */
    public function getID()
    {
        return $this->pid;
    }


    /**
     * Set the poll topic.
     *
     * @param   string  $name   Name of poll
     * @return  object  $this
     */
    private function setTopic($topic)
    {
        $this->topic = $topic;
        return $this;
    }


    /**
     * Get the poll name.
     *
     * @return  string      Name of poll
     */
    public function getName()
    {
        return $this->pollName;
    }


    /**
     * Set the owner ID.
     *
     * @param   integer $uid    User ID of poll owner
     * @return  object  $this
     */
    public function setOwner($uid)
    {
        $this->owner_id = (int)$uid;
        return $this;
    }


    /**
     * Check if this is a new record.
     *
     * @return  integer     1 if new, 0 if not
     */
    public function isNew()
    {
        return $this->isNew ? 1 : 0;
    }


    /**
     * Check if the poll is open to submissions.
     *
     * @return  integer     1 if open, 0 if closed
     */
    public function isOpen()
    {
        global $_CONF;

        if (
            $this->Opens->toMySQL(true) > $_CONF['_now']->toMySQL(true) ||
            $this->Closes->toMySQL(true) < $_CONF['_now']->toMySQL(true)
        ) {
            return 0;
        } elseif ($this->is_open) {
            return 1;
        }
        return 0;
    }


    /**
     * Check if the current user may vote in this poll.
     * Used to collect results from different fields that may be added,
     * such as a closing date.
     *
     * @return  integer     1 if voting allowed, 0 if not
     */
    public function canVote()
    {
        return $this->isOpen();
    }


    /**
     * Get the poll topic name.
     *
     * @return  string      Topic name
     */
    public function getTopic()
    {
        return $this->topic;
    }


    /**
     * Get the number of questions appearing on this poll.
     *
     * @return  integer     Number of questions asked
     */
    public function numQuestions()
    {
        return count($this->_Questions);
    }


    /**
     * Check if the results are allowed to be shown.
     *
     * @return  integer     1 if hidden, 0 if shown
     */
    public function hideResults()
    {
        return $this->hideresults ? 1 : 0;
    }


    public function getOwnerID()
    {
        return (int)$this->owner_id;
    }


    public function getVoters()
    {
        return (int)$this->voters;
    }


    public function getQuestions()
    {
        return $this->_Questions;
    }

    public function getCommentcode()
    {
        return (int)$this->commentcode;
    }


    /**
     * Set the opening date, minimum date by default.
     *
     * @param   string  $dt     Datetime string
     * @return  object  $this
     */
    public function setOpenDate($dt=NULL)
    {
        global $_CONF;

        if ($dt === NULL) {
            $dt = Dates::MIN_DATE . ' ' . Dates::MIN_TIME;
        }
        $this->Opens = new \Date($dt, $_CONF['timezone']);
        return $this;
    }


    /**
     * Set the closing date, minimum date by default.
     *
     * @param   string  $dt     Datetime string
     * @return  object  $this
     */
    public function setClosingDate($dt=NULL)
    {
        global $_CONF;

        if ($dt === NULL) {
            $dt = Dates::MAX_DATE . ' ' . Dates::MAX_TIME;
        }
        $this->Closes = new \Date($dt, $_CONF['timezone']);
        return $this;
    }


    /**
     * Read a single poll record from the database
     *
     * @return  boolean     True on success, False on error
     */
    public function Read()
    {
        // Clear out any existing items, in case we're reusing this instance.
        $this->Answers = array();

        $sql = "SELECT p.*, count(*) as vote_count FROM " . DB::table('topics') . " p
            LEFT JOIN " . DB::table('voters') . " v
            ON v.pid = p.pid
            WHERE p.pid = '" . DB_escapeString($this->pid) . "'";
        //echo $sql;die;
        $res1 = DB_query($sql, 1);
        if (!$res1 || DB_numRows($res1) < 1) {
            return false;
        }
        $A = DB_fetchArray($res1, false);
        $this->setVars($A, true);
        return true;
    }


    /**
     * Set all values for this poll into local variables.
     *
     * @param   array   $A          Array of values to use.
     * @param   boolean $fromdb     Indicate if $A is from the DB or a poll.
     */
    function setVars($A, $fromdb=false)
    {
        global $_CONF;

        if (!is_array($A)) {
            return false;
        }

        $this->setID($A['pid']);
        $this->topic = $A['topic'];
        $this->dscp = $A['description'];
        $this->inblock = isset($A['display']) && $A['display'] ? 1 : 0;
        $this->is_open = isset($A['is_open']) && $A['is_open'] ? 1 : 0;
        $this->login_required = isset($A['login_required']) && $A['login_required'] ? 1 : 0;
        $this->hideresults = isset($A['hideresults']) && $A['hideresults'] ? 1 : 0;
        $this->commentcode = (int)$A['commentcode'];
        $this->owner_id = (int)$A['owner_id'];
        $this->group_id = (int)$A['group_id'];
        if ($fromdb) {
            $this->voters = (int)$A['vote_count'];
            $this->questions = (int)$A['questions'];
            $this->perm_owner = (int)$A['perm_owner'];
            $this->perm_group = (int)$A['perm_group'];
            $this->perm_members = (int)$A['perm_members'];
            $this->perm_anon = (int)$A['perm_anon'];
            if (!isset($A['date']) || $A['date'] === NULL) {
                $this->Date = clone $_CONF['_now'];
            } else {
                $this->Date = new \Date($A['date'], $_CONF['timezone']);
            }
            $this->setOpenDate($A['opens']);
            $this->setClosingDate($A['closes']);
        } else {
            list(
                $this->perm_owner,
                $this->perm_group,
                $this->perm_members,
                $this->perm_anon
            ) = SEC_getPermissionValues(
                $A['perm_owner'],
                $A['perm_group'],
                $A['perm_members'],
                $A['perm_anon']
            );
            if (empty($A['opens_date'])) {
                $A['opens_date'] = Dates::MIN_DATE;
            }
            if (empty($A['opens_time'])) {
                $A['opens_time'] = Dates::MIN_TIME;
            }
            $this->setOpenDate($A['opens_date'] . ' ' . $A['opens_time']);
            if (empty($A['closes_date'])) {
                $A['closes_date'] = Dates::MAX_DATE;
            }
            if (empty($A['closes_time'])) {
                $A['closes_time'] = Dates::MAX_TIME;
            }
            $this->setClosingDate($A['closes_date'] . ' ' . $A['closes_time']);
        }
    }


    /**
     * Create the edit poll for all the pollzer variables.
     * Checks the type of edit being done to select the right template.
     *
     * @param   string  $type   Type of editing- 'edit' or 'registration'
     * @return  string          HTML for edit poll
     */
    public function editPoll($type = 'edit')
    {
        global $_CONF, $_GROUPS, $_USER, $LANG25, $LANG_ACCESS,
           $LANG_ADMIN, $MESSAGE, $LANG_POLLS;

        $retval = '';

        if (!empty($this->pid)) {
            $lang_create_or_edit = $LANG_ADMIN['edit'];
        } else {
            $lang_create_or_edit = $LANG_ADMIN['create_new'];
        }

        // writing the menu on top
        $menu_arr = array (
            array(
                'url' => Config::get('admin_url') . '/index.php',
                'text' => $LANG_ADMIN['list_all'],
            ),
            array(
                'url' => Config::get('admin_url') . '/index.php?edit=x',
                'text' => $lang_create_or_edit,
                'active' => true,
            ),
            array(
                'url' => $_CONF['site_admin_url'],
                'text' => $LANG_ADMIN['admin_home'],
            ),
        );

        $retval .= COM_startBlock(
            $LANG25[5], '',
            COM_getBlockTemplate ('_admin_block', 'header')
        );

        $retval .= ADMIN_createMenu(
            $menu_arr,
            $LANG_POLLS['editinstructions'],
            plugin_geticon_polls2()
        );

        $T = new \Template(__DIR__ . '/../templates/admin/');
        $T->set_file(array(
            'editor' => 'polleditor.thtml',
            'question' => 'pollquestions.thtml',
            'answer' => 'pollansweroption.thtml',
        ) );

        if (!empty($this->pid)) {       // if not a new record
            // Get permissions for poll
            $access = $this->hasAccess();
            $this->old_pid = $this->pid;
            if ($access < 3) {
                // User doesn't have write access...bail
                $retval .= COM_startBlock ($LANG25[21], '',
                               COM_getBlockTemplate ('_msg_block', 'header'));
                $retval .= $LANG25[22];
                $retval .= COM_endBlock (COM_getBlockTemplate ('_msg_block', 'footer'));
                COM_accessLog("User {$_USER['username']} tried to illegally submit or edit poll $pid.");
                return $retval;
            }
            if (!empty($this->owner_id)) {
                $delbutton = '<input type="submit" value="' . $LANG_ADMIN['delete']
                    . '" name="delete"%s>';
                $jsconfirm = ' onclick="return confirm(\'' . $MESSAGE[76] . '\');"';
                $T->set_var(array(
                    'delete_option' => sprintf($delbutton, $jsconfirm),
                    'delete_option_no_confirmation' => sprintf ($delbutton, ''),
                    'delete_button' => true,
                    'lang_delete'   => $LANG_ADMIN['delete'],
                    'lang_delete_confirm' => $MESSAGE[76]
                ) );
            }
        } else {
            $this->owner_id = (int)$_USER['uid'];
            $this->group_id = (int)SEC_getFeatureGroup ('polls.edit');
            $this->commentcode = (int)$_CONF['comment_code'];
            SEC_setDefaultPermissions($A, Config::get('default_permissions'));
            $access = 3;
        }

        $open_date = $this->Opens->format('Y-m-d', true);
        if ($open_date == Dates::MIN_DATE) {
            $open_date = '';
        }
        $open_time= $this->Opens->format('H:i:s', true);
        if ($open_time == Dates::MIN_TIME) {
            $open_time = '';
        }
        $close_date = $this->Closes->format('Y-m-d', true);
        if ($close_date == Dates::MAX_DATE) {
            $close_date = '';
        }
        $close_time= $this->Closes->format('H:i:s', true);
        if ($close_time == Dates::MAX_TIME) {
            $close_time = '';
        }
        $ownername = COM_getDisplayName($this->owner_id);
        $T->set_var(array(
            'action_url' => Config::get('admin_url') . '/index.php',
            'lang_pollid' => $LANG25[6],
            'poll_id' => $this->pid,
            'lang_donotusespaces' => $LANG25[7],
            'lang_topic' => $LANG25[9],
            'poll_topic' => htmlspecialchars ($this->topic),
            'lang_mode' => $LANG25[1],
            'description' => $this->dscp,
            'lang_description' => $LANG_POLLS['description'],
            //'status_options' => COM_optionList($_TABLES['statuscodes'], 'code,name', $T['statuscode']),
            'comment_options' => COM_optionList(DB::table('commentcodes'),'code,name',$this->commentcode),
            'lang_appearsonhomepage' => $LANG25[8],
            'lang_openforvoting' => $LANG25[33],
            'lang_hideresults' => $LANG25[37],
            'lang_login_required' => $LANG25[43],
            'poll_hideresults_explain' => $LANG25[38],
            'poll_topic_info' => $LANG25[39],
            'poll_display' => $this->inblock ? 'checked="checked"' : '',
            'poll_open' => $this->is_open ? 'checked="checked"' : '',
            'login_req_chk' => $this->login_required ? 'checked="checked"' : '',
            'poll_hideresults' => $this->hideresults ? 'checked="checked"' : '',
            'lang_opens' => $LANG_POLLS['opens'],
            'lang_closes' => $LANG_POLLS['closes'],
            'opens_date' => $open_date,
            'opens_time' => $open_time,
            'closes_date' => $close_date,
            'closes_time' => $close_time,
            'min_date' => Dates::MIN_DATE,
            'max_date' => Dates::MAX_DATE,
            'min_time' => Dates::MIN_TIME,
            'max_time' => Dates::MAX_TIME,
            // user access info
            'lang_accessrights' => $LANG_ACCESS['accessrights'],
            'lang_owner' => $LANG_ACCESS['owner'],
            'lang_openforvoting' => $LANG25[33],
            'owner_username' => DB_getItem(DB::table('users'), 'username', "uid = {$this->owner_id}"),
            'owner_name' => $ownername,
            'owner' => $ownername,
            'owner_id' => $this->owner_id,
            'lang_group' => $LANG_ACCESS['group'],
            'group_dropdown' => SEC_getGroupDropdown($this->group_id, $access),
            'lang_permissions' => $LANG_ACCESS['permissions'],
            'lang_permissionskey' => $LANG_ACCESS['permissionskey'],
            'permissions_editor' => SEC_getPermissionsHTML(
                $this->perm_owner, $this->perm_group, $this->perm_members, $this->perm_anon
            ),
            'lang_permissions_msg' => $LANG_ACCESS['permmsg'],
            'lang_answersvotes' => $LANG25[10],
            'lang_save' => $LANG_ADMIN['save'],
            'lang_cancel' => $LANG_ADMIN['cancel'],
            'lang_datepicker' => $LANG_POLLS['datepicker'],
            'lang_timepicker' => $LANG_POLLS['timepicker'],
        ) );

        // repeat for several questions
        if ($this->old_pid != '') {
            $Questions = Question::getByPoll($this->pid);
        } else {
            $Questions = array();
        }

        $T->set_block('editor','questiontab','qt');
        $maxQ = Config::get('maxquestions');
        for ($j = 0; $j < $maxQ; $j++) {
            $display_id = $j+1;
            if ($j > 0) {
                $T->set_var('style', 'style="display:none;"');
            } else {
                $T->set_var('style', '');
            }

            $T->set_var('question_tab', $LANG25[31] . " $display_id");
            $T->set_var('question_id', $j);
            if (isset($Questions[$j])) {
                $T->set_var(array(
                    'question_text' => $Questions[$j]->getQuestion(),
                    'question_id' => $j,
                    'hasdata' => true,
                ) );
                $Answers = $Questions[$j]->getAnswers();
            } else {
                $Answers = array();
                $T->unset_var('hasdata');
                $T->unset_var('question_text');
            }
            $T->set_var('lang_question', $LANG25[31] . " $display_id");
            $T->set_var('lang_saveaddnew', $LANG25[32]);

            $T->parse('qt','questiontab',true);

            for ($i = 0; $i < Config::get('maxanswers'); $i++) {
                if (isset($Answers[$i])) {
                    $T->set_var(array(
                        'answer_text' => htmlspecialchars ($Answers[$i]->getAnswer()),
                        'answer_votes' => $Answers[$i]->getVotes(),
                        'remark_text' => htmlspecialchars($Answers[$i]->getRemark()),
                    ) );
                } else {
                    $T->set_var(array(
                        'answer_text' => '',
                        'answer_votes' => '',
                        'remark_text' => '',
                    ) );
                }
                $T->parse ('answer_option', 'answer', true);
            }
            $T->parse ('question_list', 'question', true);
            $T->clear_var ('answer_option');
        }
        $token = SEC_createToken();
        $T->set_var(array(
            'sectoken_name' => CSRF_TOKEN,
            'gltoken_name' => CSRF_TOKEN,
            'sectoken' => $token,
            'gltoken' => $token,
        ) );
        $T->parse('output','editor');
        $retval .= $T->finish($T->get_var('output'));
        $retval .= COM_endBlock (COM_getBlockTemplate ('_admin_block', 'footer'));
        return $retval;
    }


    /**
     * Save a poll definition.
     * If creating a new poll, or changing the Poll ID of an existing one,
     * then the DB is checked to ensure that the ID is unique.
     *
     * @param   array   $A      Array of values (e.g. $_POST)
     * @return  string      Error message, empty on success
     */
    function Save($A = '')
    {
        global $LANG_POLLS, $_CONF;

        if (is_array($A)) {
            if (isset($A['old_pid'])) {
                $this->old_pid = $A['old_pid'];
            }
            $this->setVars($A, false);
        }
        if ($this->Date === NULL) {
            $this->Date = clone $_CONF['_now'];
        }

        $frm_name = $this->topic;
        if (empty($frm_name)) {
            return $LANG_POLLS['err_name_required'];
        }

        // If saving a new record or changing the ID of an existing one,
        // make sure the new poll ID doesn't already exist.
        $changingID = (!$this->isNew() && $this->pid != $this->old_pid);
        if ($this->isNew || $changingID) {
            $x = DB_count(DB::table('topics'), 'pid', $this->pid);
            if ($x > 0) {
                $this->pid = COM_makeSid();
                $changingID = true;     // tread as a changed ID if we have to create one
            }
        }

        if (!$this->isNew && $this->old_pid != '') {
            $sql1 = "UPDATE " . DB::table('topics') . " SET ";
            $sql3 = " WHERE pid = '{$this->old_pid}'";
        } else {
            $sql1 = "INSERT INTO " . DB::table('topics') . "  SET ";
            $sql3 = '';
        }
        $sql2 = "pid = '" . DB_escapeString($this->pid) . "',
            topic = '" . DB_escapeString($this->topic) . "',
            description = '" . DB_escapeString($this->dscp) . "',
            date = '" . $this->Date->toMySQL(true) . "',
            opens = '" . $this->Opens->toMySQL(true) . "',
            closes = '" . $this->Closes->toMySQL(true) . "',
            voters = '" . (int)$this->voters . "',
            questions = '" . (int)$this->questions . "',
            display = '" . (int)$this->inblock . "',
            is_open = '" . (int)$this->is_open . "',
            login_required = '" . (int)$this->login_required . "',
            hideresults = '" . (int)$this->hideresults . "',
            commentcode = '" . (int)$this->commentcode . "',
            owner_id = '" . (int)$this->owner_id . "',
            group_id = '" . (int)$this->group_id . "',
            perm_owner = '" . (int)$this->perm_owner . "',
            perm_group = '" . (int)$this->perm_group . "',
            perm_members = '" . (int)$this->perm_members . "',
            perm_anon = '" . (int)$this->perm_anon . "'";
        $sql = $sql1 . $sql2 . $sql3;
        //echo $sql;die;
        DB_query($sql, 1);

        if (!DB_error()) {
            $Questions = Question::getByPoll($this->old_pid);
            for ($i = 0; $i < Config::get('maxquestions'); $i++) {
                if (empty($A['question'][$i])) {
                    break;
                }
                if (isset($Questions[$i])) {
                    $Q = $Questions[$i];
                } else {
                    $Q = new Question();
                }
                $Q->setPid($this->pid)
                    ->setQid($i)
                    ->setQuestion($A['question'][$i])
                    ->setAnswers($A)
                    ->Save();
            }

            // Now delete any questions that were removed.
            for (; $i < count($Questions); $i++) {
                $Questions[$i]->Delete();
            }

            if (!$this->isNew && $changingID) {
                // Questions and answers were already saved above,
                // so just delete the old poll IDs.
                Answer::deletePoll($this->old_pid);
                Question::deletePoll($this->old_pid);
                // Still need to update the voter records.
                Voter::changePid($this->old_pid, $this->pid);
            }

            CTL_clearCache();       // so autotags pick up changes
            $msg = '';              // no error message if successful
        } else {
            COM_errorLog("Poll::Save Error: $sql");
            $msg = "An error occurred saving the poll";
        }
        return $msg;
    }


    /**
     * Determine if a specific user has a given access level to the poll.
     *
     * @return  integer     Access level for the current user.
     */
    public function hasAccess()
    {
        return SEC_hasAccess(
            $this->owner_id,
            $this->group_id,
            $this->perm_owner,
            $this->perm_group,
            $this->perm_members,
            $this->perm_anon
        );
    }


    /**
     * Uses lib-admin to list the pollzer definitions and allow updating.
     *
     * @return  string  HTML for the list
     */
    public static function adminList()
    {
        global $_CONF, $_IMAGE_TYPE, $LANG_ADMIN, $LANG25, $LANG_ACCESS, $LANG_POLLS;

        $retval = '';

        // writing the actual list
        $header_arr = array(      # display 'text' and use table field 'field'
            array(
                'text' => $LANG_ADMIN['edit'],
                'field' => 'edit',
                'sort' => false,
                'align' => 'center',
                'width' => '25px',
            ),
            array(
                'text' => $LANG25[9],
                'field' => 'topic',
                'sort' => true,
            ),
            array(
                'text' => $LANG25[20],
                'field' => 'vote_count',
                'sort' => true,
                'align' => 'center',
            ),
            array(
                'text' => $LANG_POLLS['results'],
                'field' => 'results',
                'sort' => false,
                'align' => 'center',
            ),
            array(
                'text' => $LANG25[3],
                'field' => 'unixdate',
                'sort' => true,
                'align' => 'center',
            ),
            array(
                'text' => $LANG_POLLS['opens'],
                'field' => 'opens',
                'sort' => true,
                'align' => 'center',
            ),
            array(
                'text' => $LANG_POLLS['closes'],
                'field' => 'closes',
                'sort' => true,
                'align' => 'center',
            ),
            array(
                'text' => $LANG25[33],
                'field' => 'is_open',
                'sort' => true,
                'align' => 'center',
                'width' => '35px',
            ),
            array(
                'text' => $LANG_ADMIN['delete'],
                'field' => 'delete',
                'sort' => false,
                'align' => 'center',
                'width' => '35px',
            ),
        );
        $defsort_arr = array(
            'field' => 'unixdate',
            'direction' => 'desc',
        );

        $text_arr = array(
            'has_extras'   => true,
            'instructions' => $LANG25[19],
            'form_url'     => Config::get('admin_url') . '/index.php',
        );

        $query_arr = array(
            'table' => 'polltopics',
            'sql' => "SELECT p.*, UNIX_TIMESTAMP(p.date) AS unixdate, count(v.id) as vote_count
                FROM " . DB::table('topics') . " p
                LEFT JOIN " . DB::table('voters') . " v
                ON v.pid = p.pid",
            'query_fields' => array('topic'),
            'default_filter' => COM_getPermSql('AND'),
            'group_by' => 'p.pid',
        );
        $extras = array(
            'token' => SEC_createToken(),
        );

        $retval .= ADMIN_list (
            'polls', array(__CLASS__, 'getListField'),
            $header_arr, $text_arr, $query_arr, $defsort_arr, '', $extras
        );
        $retval .= COM_endBlock(COM_getBlockTemplate('_admin_block', 'footer'));
        return $retval;
    }


    /**
     * Determine what to display in the admin list for each form.
     *
     * @param   string  $fieldname  Name of the field, from database
     * @param   mixed   $fieldvalue Value of the current field
     * @param   array   $A          Array of all name/field pairs
     * @param   array   $icon_arr   Array of system icons
     * @param   array   $extras     Array of verbatim values
     * @return  string              HTML for the field cell
     */
    public static function getListField($fieldname, $fieldvalue, $A, $icon_arr, $extras)
    {
        global $_CONF, $LANG25, $LANG_ACCESS, $LANG_ADMIN, $LANG_POLLS, $_USER;

        $retval = '';

        if (isset($A['owner_id'])) {    // only pertains to poll lists
            $access = SEC_hasAccess(
                $A['owner_id'], $A['group_id'],
                $A['perm_owner'], $A['perm_group'],
                $A['perm_members'], $A['perm_anon']
            );
        } else {
            $access = 2;
        }
        if ($access < 1) {
            return $retval;
        }

        $dt = new \Date('now',$_USER['tzid']);

        switch($fieldname) {
        case 'edit':
            if ($access == 3) {
                $retval = COM_createLink(
                    '<i class="uk-icon-edit"></i>',
                    Config::get('admin_url') . "/index.php?edit=x&amp;pid={$A['pid']}"
                );
            }
            break;
        case 'unixdate':
        case 'date_voted':
        case 'opens':
        case 'closes':
            if ($fieldvalue != Dates::MAX_DATE . ' ' . Dates::MAX_TIME && 
                $fieldvalue != Dates::MIN_DATE . ' ' . Dates::MIN_TIME
            ) {
                $dt->setTimestamp($fieldvalue);
                $retval = $dt->format($_CONF['daytime'], true);
            }
            break;
        case 'topic' :
            $filter = new \sanitizer();
            $filter->setPostmode('text');
            $fieldvalue = $filter->filterData($fieldvalue);

            $retval = COM_createLink(
                $fieldvalue,
                Config::get('url') . "/index.php?pid={$A['pid']}"
            );
            break;
        case 'access':
            if ($access == 3) {
                $access = $LANG_ACCESS['edit'];
            } else {
                $access = $LANG_ACCESS['readonly'];
            }
            $retval = $access;
            break;
        case 'user_action':
            if (
                $A['closes'] < $extras['now'] &&
                $A['is_open'] &&
                !Voter::hasVoted($A['pid'])
            ) {
                $retval = COM_createLink(
                    $LANG_POLLS['vote'],
                    Config::get('url') . "/index.php?pid={$A['pid']}"
                );
            } else {
                $retval = COM_createLink(
                    $LANG_POLLS['results'],
                    Config::get('url') . "/index.php?results=x&pid={$A['pid']}"
                );
            }
            break;
        case 'is_open':
            if ($fieldvalue == '1') {
                $switch = 'checked="checked"';
                $enabled = 1;
            } else {
                $switch = '';
                $enabled = 0;
            }
            $retval .= "<input type=\"checkbox\" $switch value=\"1\" name=\"ena_check\"
                    id=\"togenabled{$A['pid']}\"
                    onclick='" . __NAMESPACE__ . "_toggle(this,\"{$A['pid']}\",\"is_open\",".
                    "\"poll\");' />" . LB;
            break;
        case 'display':
            if ($A['display'] == 1) {
                $retval = $LANG25[25];
            } else {
                $retval = $LANG25[26];
            }
            break;
        case 'voters':
        case 'vote_count':
            // add a link there to the list of voters
            $retval = COM_createLink(
                COM_numberFormat($fieldvalue),
                Config::get('admin_url') . '/index.php?lv=x&amp;pid='.urlencode($A['pid'])
            );
            break;
        case 'results':
            $retval = COM_createLink(
                '<i class="uk-icon-bar-chart"></i>',
                Config::get('admin_url') . '/index.php?results=x&pid=' . urlencode($A['pid'])
            );
            break;
        case 'delete':
            if ($access == 3) {
                $attr['title'] = $LANG_ADMIN['delete'];
                $attr['onclick'] = "return doubleconfirm('" . $LANG25[41] . "','" . $LANG25[42] . "');";
                $retval = COM_createLink(
                    '<i class="uk-icon-remove uk-text-danger"></i>',
                    Config::get('admin_url') . '/index.php'
                        . '?delete=x&amp;pid=' . $A['pid'] . '&amp;' . CSRF_TOKEN . '=' . $extras['token'], $attr);
            } else {
                $retval = $icon_arr['blank'];
            }
            break;
        default:
            $retval = $fieldvalue;
            break;
        }
        return $retval;
    }


    /**
     * Shows a poll form
     *
     * Shows an HTML formatted poll for the given topic ID
     *
     * @param      string      $pid      ID for poll topic
     * @param      boolean     $showall  Show only the first question in the poll or all?
     * @param        int        $displaytype       Possible values 0 = Normal, 1 = In Block, 2 = autotag
     * @see function COM_pollResults
     * @see function COM_showPoll
     * @return       string  HTML Formatted Poll
     */
    public function Render($showall = true, $displaytype = 0)
    {
        global $_CONF, $LANG_POLLS, $LANG01, $_USER, $LANG25, $_IMAGE_TYPE;

        USES_lib_comment();

        $filterS = new \sanitizer();
        $filterS->setPostmode('text');

        $retval = '';

        if (
            $displaytype == 0 &&    // not in a block or autotag
            (
                $this->alreadyVoted() ||
                !$this->is_open
            )
        ) {
            COM_refresh(Config::get('url') . '/index.php?results&pid=' . $this->pid);
        }

        $Questions = Question::getByPoll($this->pid);
        $nquestions = count($Questions);
        if ($nquestions > 0) {
            $poll = new \Template(__DIR__ . '/../templates/');
            $poll->set_file(array(
                'panswer' => 'pollanswer.thtml',
                'block' => 'pollblock.thtml',
                'pquestions' => 'pollquestions.thtml',
                'comments' => 'pollcomments.thtml',
            ) );
            if ($nquestions > 1) {
                $poll->set_var('poll_topic', $LANG25['34'] . " " . $filterS->filterData($this->topic));
                $poll->set_var('lang_question', $LANG25[31].':');
            }
            $poll->set_var(array(
                'poll_id' => $this->pid,
                'num_votes' => COM_numberFormat($this->voters),
                'poll_vote_url' => Config::get('url') . '/index.php',
                'ajax_url' => Config::get('url') . '/ajax_handler.php',
                'polls_url' => Config::get('url') . '/index.php',
            ) );
                                                
            if ($nquestions == 1 || $showall) {
                // Only one question (block) or showing all (main form)
                $poll->set_var('lang_vote', $LANG_POLLS['vote']);
                $poll->set_var('showall',true);
                if ($displaytype == 2) {
                    $poll->set_var('autotag',true);
                } else {
                    $poll->unset_var('autotag');
                }
            } else {
                $poll->set_var('lang_vote', $LANG_POLLS['start_poll']);
                $poll->unset_var('showall');
                $poll->unset_var('autotag');
            }
            $poll->set_var('lang_votes', $LANG_POLLS['votes']);

            $results = '';
            if (
                $this->is_open == 0 ||
                $this->hideresults == 0 ||
                (
                    $this->hideresults == 1 &&
                    (
                        SEC_hasRights('polls.edit') ||
                        (
                            isset($_USER['uid'])
                            && ($_USER['uid'] == $this->owner_id)
                        )
                    )
                )
            ) {
                $results = COM_createLink($LANG_POLLS['results'],
                    Config::get('url') . '/index.php?pid=' . $this->pid
                    . '&amp;aid=-1');
            }
            $poll->set_var('poll_results', $results);

            $access = $this->hasAccess();
            if (($access == 3) && SEC_hasRights('polls.edit')) {
                $editlink = COM_createLink(
                    $LANG25[27],
                    Config::get('admin_url') . '/index.php?edit=x&amp;pid=' . $this->pid
                );
                $poll->set_var('edit_link', $editlink);
                $poll->set_var('edit_icon', $editlink);
                $poll->set_var('edit_url', Config::get('admin_url').'/index.php?edit=x&amp;pid=' . $this->pid);
            }
            if (array_key_exists('aid', $_POST)) {
                $aid = $_POST['aid'];
            } else {
                $aid = array();
            }

            for ($j = 0; $j < $nquestions; $j++) {
                $Q = $Questions[$j];
                $poll->set_var('poll_question', " ".$filterS->filterData($Q->getQuestion()));
                $poll->set_var('question_id', $j);
                $notification = "";
                if ($showall == false) {
                    $nquestions--;
                    $notification = $LANG25[35] . " $nquestions " . $LANG25[36];
                    $nquestions = 1;
                } else {
                    $poll->set_var('lang_question_number', " ". ($j+1).":");
                }
                $answers = $Q->getAnswers();
                $nanswers = count($answers);
                for ($i = 0; $i < $nanswers; $i++) {
                    $Answer = $answers[$i];
                    if ($j < count($aid) && (int)$aid[$j] == $Answer->getAid()) {
                        $poll->set_var('selected', ' checked="checked"');
                    }
                    $poll->set_var('answer_id', $Answer->getAid());
                    $poll->set_var('answer_text', $filterS->filterData($Answer->getAnswer()));
                    $poll->parse('poll_answers', 'panswer', true);
                    $poll->clear_var('selected');
                }
                $poll->parse('poll_questions', 'pquestions', true);
                $poll->clear_var('poll_answers');
            }
            $poll->set_var('lang_polltopics', $LANG_POLLS['polltopics']);
            $poll->set_var('poll_notification', $notification);
            if ($this->commentcode >= 0 ) {
                $num_comments = CMT_getCount('polls',$this->pid);
                $poll->set_var('num_comments',COM_numberFormat($num_comments));
                $poll->set_var('lang_comments', $LANG01[3]);

                $comment_link = CMT_getCommentLinkWithCount(
                    'polls',
                    $this->pid,
                    Config::get('url') . '/index.php?pid=' . $this->pid,
                    $num_comments,
                    0
                );

                $poll->set_var('poll_comments_url', $comment_link['link_with_count']);
                $poll->parse('poll_comments', 'comments', true);
            } else {
                $poll->set_var('poll_comments', '');
                $poll->set_var('poll_comments_url', '');
            }

            $retval = $poll->finish($poll->parse('output', 'block')) . LB;
            if ($showall && ($this->commentcode >= 0 AND $displaytype != 2)) {
                $delete_option = (SEC_hasRights('polls.edit') && $this->hasAccess() == 3) ? true : false;

                USES_lib_comment();

                $page = isset($_GET['page']) ? COM_applyFilter($_GET['page'],true) : 0;
                if ( isset($_POST['order']) ) {
                    $order = $_POST['order'] == 'ASC' ? 'ASC' : 'DESC';
                } elseif (isset($_GET['order']) ) {
                    $order = $_GET['order'] == 'ASC' ? 'ASC' : 'DESC';
                } else {
                    $order = '';
                }
                if ( isset($_POST['mode']) ) {
                    $mode = COM_applyFilter($_POST['mode']);
                } elseif ( isset($_GET['mode']) ) {
                    $mode = COM_applyFilter($_GET['mode']);
                } else {
                    $mode = '';
                }
                $valid_cmt_modes = array('flat','nested','nocomment','threaded','nobar');
                if (!in_array($mode,$valid_cmt_modes)) {
                    $mode = '';
                }
                $retval .= CMT_userComments(
                    $this->pid, $filterS->filterData($this->topic), 'polls',
                    $order, $mode, 0, $page, false,
                    $delete_option, $this->commentcode, $this->owner_id
                );
            }
        } else {
            $retval .= COM_showMessageText("There are no questions for this poll");
        }
        return $retval;
    }


    /**
     * Saves a user's vote.
     * Saves the users vote, if allowed for the poll $pid.
     * NOTE: all data comes from form $_POST.
     *
     * @param    string   $pid   poll id
     * @param    array    $aid   selected answers
     * @return   string   HTML for poll results
     */
    public function saveVote($aid)
    {
        global $_USER, $LANG_POLLS;

        $retval = '';

        if ($this->alreadyVoted()) {
            COM_setMsg($LANG_POLLS['alreadyvoted']);
            return false;
        }
        $db_pid = DB_escapeString($this->pid);

        // This call to DB-change will properly supress the insertion of quotes around $value in the sql
        $answers = count($aid);
        for ($i = 0; $i < $answers; $i++) {
            DB_change(
                DB::table('answers'),
                'votes',
                "votes + 1",
                array('pid', 'qid', 'aid'),
                array($db_pid, $i, COM_applyFilter($aid[$i], true)),
                '',
                true
            );
        }

        // Record that this user has voted
        Voter::create($this->pid);

        $eMsg = $LANG_POLLS['savedvotemsg'] . ' "' . $this->getTopic() . '"';
            //. DB_getItem ($_TABLES['polltopics'], 'topic', "pid = '". $db_pid . "'").'"';
        COM_setMsg($eMsg);
        return true;
    }


    /**
     * Check if the user has already voted.
     * For anonymous, checks the IP address and the poll cookie.
     *
     * @return  boolean     True if the user has voted, False if not
     */
    public function alreadyVoted()
    {
        return Voter::hasVoted($this->pid, $this->group_id);
    }


    /**
     * Shows all polls in system.
     * List all the polls on the system if no $pid is provided.
     *
     * @return   string          HTML for poll listing
     */
    public static function listPolls()
    {
        global $_CONF, $_USER,
           $LANG25, $LANG_LOGIN, $LANG_POLLS;

        $retval = '';

        if (
            COM_isAnonUser() && (
                $_CONF['loginrequired'] == 1 || Config::get('pollsloginrequired') == 1
            )
        ) {
            return SEC_loginRequiredForm();
        }

        USES_lib_admin();

        $header_arr = array(
            array(
                'text' => $LANG25[9],
                'field' => 'topic',
                'sort' => true,
            ),
            array(
                'text' => $LANG25[20],
                'field' => 'vote_count',
                'sort' => true,
                'align' => 'center',
            ),
            array(
                'text' => $LANG25[3],
                'field' => 'unixdate',
                'sort' => true,
                'align' => 'center',
            ),
            array(
                'text' => 'Action',
                'field' => 'user_action',
                'sort' => true,
                'align' => 'center',
            ),
        );

        $defsort_arr = array(
            'field' => 'unixdate',
            'direction' => 'desc',
        );
        $text_arr = array(
            'has_menu' =>  false,
            'title' => $LANG_POLLS['pollstitle'],
            'instructions' => "",
            'icon' => '', 'form_url' => '',
        );
        $query_arr = array(
            'table' => 'polltopics',
            'sql' => "SELECT p.*, UNIX_TIMESTAMP(p.date) AS unixdate,
                (SELECT COUNT(v.id) FROM " . DB::table('voters') . " v WHERE v.pid = p.pid) AS vote_count
                FROM " . DB::table('topics') . " p",
            'query_fields' => array('topic'),
            'default_filter' => "WHERE opens < '" . $_CONF['_now']->toMySQL(true) . "'" . COM_getPermSQL('AND'),
            'query' => '',
            'query_limit' => 0,
        );
        $extras = array(
            'token' => 'dummy',
            'now' => $_CONF['_now']->toMySQL(true),
        );
        //echo $query_arr['sql'] . ' ' . $query_arr['default_filter'];die;

        if (plugin_ismoderator_polls2()) {
            $retval .= '<div class="floatright"><a class="uk-button uk-button-small uk-button-danger" href="' .
                Config::get('admin_url') . '/index.php">Admin</a></div>' . LB;
        }
        $retval .= ADMIN_list(
            'polls_pollList',
            array(__CLASS__, 'getListField'),
            $header_arr, $text_arr, $query_arr, $defsort_arr, '', $extras
        );
        return $retval;
    }


    /**
     * Shows the results of a poll.
     * Shows the poll results for a given poll topic.
     *
     * @param        string      $pid        ID for poll topic to show
     * @param        int         $scale      Size in pixels to scale formatted results to
     * @param        string      $order      'ASC' or 'DESC' for Comment ordering (SQL statment ordering)
     * @param        string      $mode       Comment Mode possible values 'nocomment', 'flat', 'nested', 'threaded'
     * @param        int        $displaytype       Possible values 0 = Normal, 1 = In Block, 2 = autotag
     * @see POLLS_pollVote
     * @see POLLS_showPoll
     * @return     string   HTML Formated Poll Results
     */
    public function showResults($scale=400, $order='', $mode='', $displaytype = 0)
    {
        global $_CONF, $_USER, $_IMAGE_TYPE,
           $LANG01, $LANG_POLLS, $_COM_VERBOSE, $LANG25;

        USES_lib_comments();

        $filter = new \sanitizer();
        $filter->setPostmode('text');
        $retval = '';

        $access = $this->hasAccess();
        if ($this->isNew() || $access == 0) {
            // Invalid poll or no access
            return $retval;
        }
        if (
            $this->hideresults == 1 &&
            (
                !$this->isOpen() ||
                (isset($_USER['uid']) && $_USER['uid'] == $this->owner_id) ||
                ($this->hideresults == 1 && SEC_hasRights('polls.edit'))
            )
        ) {
            // OK to show results
            $retval = '';
        } else {
            if ($displaytype == 2) {
                $retval = '<div class="poll-autotag-message">' . $LANG_POLLS['pollhidden']. "</div>";
            } else if ($displaytype == 1 ) {
                $retval = '';
            } else {
                $retval = COM_showMessageText($LANG_POLLS['pollhidden'],'', true,'error');
                $retval .= self::listPolls();
            }
            return $retval;
        }

        $poll = new \Template(__DIR__ . '/../templates/');
        $poll->set_file(array(
            'result' => 'pollresult.thtml',
            'question' => 'pollquestion.thtml',
            'comments' => 'pollcomments.thtml',
            'votes_bar' => 'pollvotes_bar.thtml',
            'votes_num' => 'pollvotes_num.thtml'
        ) );
        $poll->set_var(array(
            //'layout_url'    => $_CONF['layout_url'],
            'poll_topic'    => $filter->filterData($this->topic),
            'poll_id'   => $this->pid,
            'num_votes' => COM_numberFormat($this->voters),
            'lang_votes' => $LANG_POLLS['votes'],
        ) );
        if ($access == 3) {
            $editlink = COM_createLink(
                $LANG25[27],
                Config::get('admin_url') . '/index.php?edit=x&amp;pid=' . $this->pid );
            $poll->set_var(array(
                'edit_link' => $editlink,
                'edit_url' => Config::get('admin_url') . '/index.php?edit=x&amp;pid=' . $this->pid,
                'edit_icon' => COM_createLink(
                    '<i class="uk-icon-edit tooltip"></i>',
                    Config::get('admin_url') . '/index.php?edit=x&amp;pid=' . $this->pid,
                    array(
                        'title' => $LANG25[27],
                    )
                ),
            ) );
        }
        if (Config::get('answerorder') == 'voteorder'){
            $order = "votes DESC";
        } else {
            $order = "aid";
        }

        $nquestions = count($this->_Questions);
        for ($j = 0; $j < $nquestions; $j++) {
            if ($nquestions >= 1) {
                $counter = ($j + 1) . "/$nquestions: " ;
            }
            $Q = $this->_Questions[$j];
            $poll->set_var('poll_question', $counter . $filter->filterData($Q->getQuestion()));
            $Answers = Answer::getByQuestion($Q->getQid(), $this->pid);
            $nanswers = count($Answers);
            $q_totalvotes = 0;
            foreach ($Answers as $A) {
                $q_totalvotes += $A->getVotes();
            }
            for ($i=1; $i<=$nanswers; $i++) {
                $A = $Answers[$i - 1];
                if ($q_totalvotes == 0) {
                    $percent = 0;
                } else {
                    $percent = $A->getVotes() / $q_totalvotes;
                }
                $poll->set_var(array(
                    'cssida' =>  1,
                    'cssidb' =>  2,
                    'answer_text' => $filter->filterData($A->getAnswer()),
                    'remark_text' => $filter->filterData($A->getRemark()),
                    'answer_counter' => $i,
                    'answer_odd' => (($i - 1) % 2),
                    'answer_num' => COM_numberFormat($A->getVotes()),
                    'answer_percent' => sprintf('%.2f', $percent * 100),
                ) );
                if ($scale < 120) {
                    $poll->parse('poll_votes', 'votes_num', true);
                } else {
                    $width = (int) ($percent * 100 );
                    $poll->set_var('bar_width', $width);
                    $poll->parse('poll_votes', 'votes_bar', true);
                }
            }
            $poll->parse('poll_questions', 'question', true);
            $poll->clear_var('poll_votes');
            if (($scale < 100) && ($j < 1)) {
                $url = Config::get('url') . "/index.php?pid={$this->pid}";
                $poll->set_var('notification', COM_createLink($LANG25[40], $url). "<br>");
                break;
            }
        }
        if ($this->commentcode >= 0 ) {
            $num_comments = CMT_getCount('polls', $this->pid);
            $poll->set_var('num_comments',COM_numberFormat($num_comments));
            $poll->set_var('lang_comments', $LANG01[3]);
            $comment_link = CMT_getCommentLinkWithCount(
                'polls',
                $this->pid,
                Config::get('url') . '/index.php?pid=' . $this->pid,
                $num_comments,
                0
            );
            $poll->set_var('poll_comments_url', $comment_link['link_with_count']);
            $poll->parse('poll_comments', 'comments', true);
        } else {
            $poll->set_var('poll_comments_url', '');
            $poll->set_var('poll_comments', '');
        }

        $poll->set_var('lang_polltopics', $LANG_POLLS['polltopics'] );
        $retval .= $poll->finish($poll->parse('output', 'result' ));

        if ($scale > 399 && $this->commentcode >= 0 && $displaytype != 2) {
            $delete_option = (SEC_hasRights('polls.edit') && $access == 3) ? true : false;
            USES_lib_comment();

            $page = isset($_GET['page']) ? COM_applyFilter($_GET['page'],true) : 0;
            if (isset($_POST['order'])) {
                $order  =  $_POST['order'] == 'ASC' ? 'ASC' : 'DESC';
            } elseif (isset($_GET['order']) ) {
                $order =  $_GET['order'] == 'ASC' ? 'ASC' : 'DESC';
            } else {
                $order = 'DESC';
            }
            if (isset($_POST['mode'])) {
                $mode = COM_applyFilter($_POST['mode']);
            } elseif (isset($_GET['mode'])) {
                $mode = COM_applyFilter($_GET['mode']);
            } else {
                $mode = '';
            }
            $retval .= CMT_userComments(
                $this->pid, $filter->filterData($this->topic), 'polls',
                $order, $mode, 0, $page, false,
                $delete_option, $this->commentcode, $this->owner_id
            );
        }
        return $retval;
    }

    /**
     * Delete a poll.
     *
     * @param   string  $pid    ID of poll to delete
     * @param   boolean $force  True to disregard access, e.g. user is deleted
     * @return  string          HTML redirect
     */
    public static function deletePoll($pid, $force=false)
    {
        global $_CONF, $_USER;

        $Poll = self::getInstance($pid);
        if (
            !$Poll->isNew() &&
            ($force || $Poll->hasAccess()== 3)
        ) {
            $pid = DB_escapeString($pid);
            Question::deletePoll($this->pid);
            Answer::deletePoll($this->pid);
            Voter::deletePoll($this->pid);
            DB_delete(DB::table('topics'), 'pid', $pid);
            DB_delete(DB::table('comments'), array('sid', 'type'), array($pid,  'polls'));
            PLG_itemDeleted($pid, 'polls');
            if (!$force) {
                // Don't redirect if this is done as part of user account deletion
                COM_refresh(Config::get('admin_url') . '/index.php?msg=20');
            }
        } else {
            if (!$force) {
                COM_accessLog ("User {$_USER['username']} tried to illegally delete poll $pid.");
                // apparently not an administrator, return ot the public-facing page
                COM_refresh(Config::get('url') . '/index.php');
            }
        }
    }


    /**
     * Create the list of voting records for this poll.
     *
     * @return  string      HTML for voting list
     */
    public function listVotes()
    {
        global $_CONF, $_IMAGE_TYPE, $LANG_ADMIN, $LANG_POLLS, $LANG25, $LANG_ACCESS;

        $retval = '';
        $menu_arr = array (
            array(
                'url' => Config::get('admin_url') . '/index.php',
                'text' => $LANG_ADMIN['list_all'],
            ),
            array(
                'url' => Config::get('admin_url') . '/index.php?edit=x',
                'text' => $LANG_ADMIN['create_new'],
            ),
            array(
                'url' => $_CONF['site_admin_url'],
                'text' => $LANG_ADMIN['admin_home']),
        );

        $retval .= COM_startBlock(
            'Poll Votes for ' . $this->pid, '',
            COM_getBlockTemplate('_admin_block', 'header')
        );

        $retval .= ADMIN_createMenu(
            $menu_arr,
            $LANG25[19],
            plugin_geticon_polls2()
        );

        $header_arr = array(
            array(
                'text' => $LANG_POLLS['username'],
                'field' => 'username',
                'sort' => true,
            ),
            array(
                'text' => $LANG_POLLS['ipaddress'],
                'field' => 'ipaddress',
                'sort' => true,
            ),
            array(
                'text' => $LANG_POLLS['date_voted'],
                'field' => 'date_voted',
                'sort' => true,
            ),
        );

        $defsort_arr = array(
            'field' => 'date',
            'direction' => 'desc',
        );
        $text_arr = array(
            'has_extras'   => true,
            'instructions' => $LANG25[19],
            'form_url'     => Config::get('admin_url') . '/index.php?lv=x&amp;pid='.urlencode($this->pid),
        );

        $sql = "SELECT * FROM " . DB::table('voters') . " AS voters
            LEFT JOIN " . DB::table('users') . " AS users ON voters.uid=users.uid
            WHERE voters.pid='" . DB_escapeString($this->pid) . "'";

        $query_arr = array(
            'table' => 'pollvoters',
            'sql' => $sql,
            'query_fields' => array('uid'),
            'default_filter' => '',
        );
        $token = SEC_createToken();
        $retval .= ADMIN_list (
            'polls', array(__CLASS__, 'getListField'), $header_arr,
            $text_arr, $query_arr, $defsort_arr, '', $token
        );
        $retval .= COM_endBlock(COM_getBlockTemplate('_admin_block', 'footer'));
        return $retval;
    }

    public static function moveUser($origUID, $destUID)
    {
        DB_query("UPDATE " . DB::table('topics') .
            " SET owner_id = ".(int)$destUID .
            " WHERE owner_id = ".(int)$origUID,1
        );
        Voter::moveUser($origUID, $destUID);
    }


    /**
     * Sets a boolean field to the opposite of the supplied value.
     *
     * @param   integer $oldvalue   Old (current) value
     * @param   string  $varname    Name of DB field to set
     * @param   integer $id         ID of record to modify
     * @return  integer     New value, or old value upon failure
     */
    public static function toggleEnabled($oldvalue, $id)
    {
        $id = DB_escapeString($id);
        // Determing the new value (opposite the old)
        $oldvalue = $oldvalue == 1 ? 1 : 0;
        $newvalue = $oldvalue == 1 ? 0 : 1;

        $sql = "UPDATE " . DB::table('topics') . "
                SET is_open = $newvalue
                WHERE pid = '$id'";
        // Ignore SQL errors since varname is indeterminate
        DB_query($sql, 1);
        if (DB_error()) {
            COM_errorLog("Error toggling poll: $sql");
            return $oldvalue;
        } else {
            return $newvalue;
        }
    }


    /**
     * Update the voter count for this poll.
     *
     * @todo    deprecate
     * @param   integer $num    Number to add, default = 1
     * @return  object  $this
     */
    public function updateVoters($num = 1)
    {
        global $_TABLES;

        $num = (int)$num;
        DB_change(
            DB::table('topics'),
            'voters',
            "voters + $num",
            'pid',
            DB_escapeString($this->pid),
            '',
            true
        );
        return $this;
    }

}

?>
