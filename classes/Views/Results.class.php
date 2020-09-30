<?php
/**
 * Class to represent the resultset for a poll.
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
namespace Polls\Views;
use Polls\Poll;
use Polls\Answer;
use Polls\Config;
use Polls\Models\Modes;
use Polls\MO;


/**
 * Class for a single poll.
 * @package polls
 */
class Results
{
    private $cmt_order = 'DESC';
    private $cmt_mode = '';
    private $displaytype = 0;
    private $pid = '';
    private $showComments = 1;
    private $Poll = NULL;
    private $isAdmin = false;


    /**
     * Set the poll ID if supplied, and the comment mode to the default.
     *
     * @param   string  $pid    Optionall Poll ID
     */
    public function __construct($pid='')
    {
        global $_CONF;

        if (!empty($pid)) {
            $this->withPoll($pid);
        }
        $this->withCommentMode($_CONF['comment_mode']);
    }


    /**
     * Set the ID of the poll to show, if not set in the constructor.
     *
     * @param   string|object  $pid    Poll ID or object
     * @return  object  $this
     */
    public function withPoll($pid)
    {
        if (is_string($pid)) {
            $this->pid = $pid;
            $this->Poll = Poll::getInstance($pid);
        } elseif (is_object($pid) && $pid instanceof Poll) {
            $this->pid = $pid->getID();
            $this->Poll = $pid;
        }
        return $this;
    }


    /**
     * Set the comment order, ASC or DESC.
     *
     * @param   string  $order  Comment display order
     * @return  object  $this
     */
    public function withCommentOrder($order)
    {
        if ($order == 'DESC') {
            $this->cmt_order = $order;
        } else {
            $order = 'ASC';
        }
        return $this;
    }


    /**
     * Set the display type. Normal (0), Autotag or Print.
     *
     * @param   integer $type   Display type flag.
     * @return  object  $this
     */
    public function withDisplayType($type)
    {
        $this->displaytype = (int)$type;
        return $this;
    }


    /**
     * Set the comment mode, e.g. "nested".
     *
     * @param   string  $mode   Comment display mode
     * @return  object  $this
     */
    public function withCommentMode($mode)
    {
        $this->cmt_mode = $mode;
        return $this;
    }


    /**
     * Set the flag to show comments, or not.
     *
     * @param   boolean $flag   True to show comments, False to suppress
     * @return  object  $this
     */
    public function withComments($flag)
    {
        $this->showComments = $flag ? 1 : 0;
        return $this;
    }


    /**
     * Set the Admin flag to indicate if this view is called from the admin area.
     *
     * @param   boolean $flag   True if this is an admin view, False if not
     * @return  object  $this
     */
    public function withAdmin($flag)
    {
        $this->isAdmin = $flag ? 1 : 0;
        return $this;
    }


    /**
     * Shows the results of a poll.
     * Shows the poll results for a given poll topic.
     *
     * @return     string   HTML Formated Poll Results
     */
    public function Render()
    {
        global $_CONF, $_TABLES, $_USER, $_IMAGE_TYPE,
           $LANG01, $_COM_VERBOSE, $LANG25;

        $retval = '';
        $filter = new \sanitizer();
        $filter->setPostmode('text');

        if ($this->Poll->isNew() || !$this->Poll->canViewResults()) {
            // Invalid poll or no access
            return '';
        }

        if (
            !$this->Poll->hideResults() ||
            !$this->Poll->isOpen() ||
            (isset($_USER['uid']) && $_USER['uid'] == $this->Poll->getOwnerID()) ||
            Poll::hasRights('edit')
        ) {
            // The poll owner or admin is checking early results.
        } else {
            if ($this->displaytype == Modes::AUTOTAG) {
                $retval = '<div class="poll-autotag-message">' .
                    MO::_('Poll results will be available only after the Poll has closed.') .
                    '</div>';
            } else if ($this->displaytype == 1 ) {
                $retval = '';
            } else {
                $msg = '';
                if ($this->Poll->alreadyVoted()) {
                    $msg .= MO::_('You have already voted') . '<br />';
                }
                $msg .= MO::_('Poll results will be available only after the Poll has closed.');
                $retval = COM_showMessageText($msg,'', true,'error');
                $retval .= Poll::listPolls();
            }
            return $retval;
        }

        $poll = new \Template(__DIR__ . '/../../templates/');
        $poll->set_file(array(
            'result' => 'pollresult.thtml',
            'question' => 'pollquestion.thtml',
            'comments' => 'pollcomments.thtml',
            'votes_bar' => 'pollvotes_bar.thtml',
            'votes_num' => 'pollvotes_num.thtml',
        ) );

        $poll->set_var(array(
            //'layout_url'    => $_CONF['layout_url'],
            'poll_topic'    => $filter->filterData($this->Poll->getTopic()),
            'poll_id'   => $this->pid,
            'num_votes' => COM_numberFormat($this->Poll->numVotes()),
            'lang_votes' => MO::_('Votes'),
            'admin_url' => Config::get('admin_url') . '/index.php',
            'polls_url' => $this->isAdmin ? '' : Config::get('url') . '/index.php',
            'isOpen' => $this->Poll->isOpen(),
            'lang_results_open' => MO::_('Early results, poll is open'),
            'lang_back_to_list' => MO::_('Back to List'),
        ) );

        if (Poll::hasRights('edit')) {
            $editlink = COM_createLink(
                $LANG25[27],
                Config::get('admin_url') . '/index.php?edit=x&amp;pid=' . $this->pid);
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
        $questions = $this->Poll->getQuestions();
        $nquestions = count($questions);
        for ($j = 0; $j < $nquestions; $j++) {
            if ($nquestions >= 1) {
                $counter = ($j + 1) . "/$nquestions: " ;
            }
            $Q = $questions[$j];
            $poll->set_var('poll_question', $counter . $filter->filterData($Q->getQuestion()));
            $Answers = Answer::getByQuestion($Q->getQid(), $this->pid);
            $nanswers = count($Answers);
            $q_totalvotes = 0;
            $max_votes = -1;

            // If the poll has closed, get the winning scores.
            foreach ($Answers as $idx=>$A) {
                $q_totalvotes += $A->getVotes();
                if ($A->getVotes() > $max_votes) {
                    $max_votes = $A->getVotes();
                }
            }
            // For open polls, the winner is not highlighted.
            if ($this->Poll->isOpen()) {
                $max_votes = -1;
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
                    'winner' => $A->getVotes() == $max_votes,
                ) );
                $width = (int) ($percent * 100 );
                $poll->set_var('bar_width', $width);
                $poll->parse('poll_votes', 'votes_bar', true);
            }
            $poll->parse('poll_questions', 'question', true);
            $poll->clear_var('poll_votes');
        }

        if ($this->Poll->getCommentcode() >= 0 ) {
            USES_lib_comments();
            $num_comments = CMT_getCount(Config::PI_NAME, $this->pid);
            $poll->set_var('num_comments',COM_numberFormat($num_comments));
            $poll->set_var('lang_comments', $LANG01[3]);
            $comment_link = CMT_getCommentLinkWithCount(
                Config::PI_NAME,
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

        $poll->set_var('lang_polltopics', MO::_('Other polls'));
        if ($this->displaytype !== Modes::PRINT) {
            $retval .= '<a class="uk-button uk-button-success" target="_blank" href="' .
                Config::get('admin_url') . '/index.php?presults=x&pid=' .
                urlencode($this->pid) . '">Print</a>' . LB;
        }
        $retval .= $poll->finish($poll->parse('output', 'result' ));

        if (
            $this->showComments && $this->Poll->getCommentcode() >= 0 && $this->displaytype != Modes::AUTOTAG) {
            $delete_option = Poll::hasRights('edit') ? true : false;
            USES_lib_comment();

            $page = isset($_GET['page']) ? COM_applyFilter($_GET['page'],true) : 0;
            if (isset($_POST['order'])) {
                $this->cmt_order  =  $_POST['order'] == 'ASC' ? 'ASC' : 'DESC';
            } elseif (isset($_GET['order']) ) {
                $this->cmt_order =  $_GET['order'] == 'ASC' ? 'ASC' : 'DESC';
            } else {
                $this->cmt_order = 'DESC';
            }
            if (isset($_POST['mode'])) {
                $this->withCommentMode(COM_applyFilter($_POST['mode']));
            } elseif (isset($_GET['mode'])) {
                $this->withCommentMode(COM_applyFilter($_GET['mode']));
            }
            $retval .= CMT_userComments(
                $this->pid, $filter->filterData($this->Poll->getTopic()), Config::PI_NAME,
                $this->cmt_order, $this->cmt_mode, 0, $page, false,
                $delete_option, $this->Poll->getCommentcode(), $this->Poll->getOwnerID()
            );
        }
        return $retval;
    }


    /**
     * Create a printable results page.
     *
     * @return  string      HTML for printable page.
     */
    public function Print()
    {
        $retval = '';
        $retval .= '<html><head>' . LB;
        $retval .= '<link rel="stylesheet" type="text/css" href="' . _css_out() . '">' . LB;
        $retval .= '</head><body>' . LB;
        $retval .= $this->withDisplayType(Modes::PRINT)->withComments(false)->Render();
        $retval .= '</body></html>' . LB;
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
        global $_CONF, $_TABLES, $_USER;

        $Poll = self::getInstance($pid);
        if (
            !$Poll->isNew() &&
            ($force || Poll::hasRights('edit'))
        ) {
            $pid = DB_escapeString($pid);
            DB_delete($_TABLES['polltopics'], 'pid', $pid);
            DB_delete($_TABLES['pollanswers'], 'pid', $pid);
            DB_delete($_TABLES['pollquestions'], 'pid', $pid);
            DB_delete($_TABLES['pollvoters'], 'pid', $pid);
            DB_delete($_TABLES['comments'], array('sid', 'type'), array($pid,  Config::PI_NAME));
            PLG_itemDeleted($pid, Config::PI_NAME);
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
        global $_CONF, $_TABLES, $_IMAGE_TYPE, $LANG_ADMIN, $LANG25, $LANG_ACCESS;

        $retval = '';
        $header_arr = array(
            array(
                'text' => MO::_('User Name'),
                'field' => 'username',
                'sort' => true,
            ),
            array(
                'text' => MO::_('IP Address'),
                'field' => 'ipaddress',
                'sort' => true,
            ),
            array(
                'text' => MO::_('Date Voted'),
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

        $sql = "SELECT * FROM {$_TABLES['pollvoters']} AS voters
            LEFT JOIN {$_TABLES['users']} AS users ON voters.uid=users.uid
            WHERE voters.pid='" . DB_escapeString($this->pid) . "'";

        $query_arr = array(
            'table' => 'pollvoters',
            'sql' => $sql,
            'query_fields' => array('uid'),
            'default_filter' => '',
        );
        $token = SEC_createToken();
        $retval .= ADMIN_list (
            Config::PI_NAME, array(__CLASS__, 'getListField'), $header_arr,
            $text_arr, $query_arr, $defsort_arr, '', $token
        );
        $retval .= COM_endBlock(COM_getBlockTemplate('_admin_block', 'footer'));
        return $retval;
    }

}

?>
