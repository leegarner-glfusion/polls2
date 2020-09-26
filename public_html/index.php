<?php
// +--------------------------------------------------------------------------+
// | Polls Plugin - glFusion CMS                                              |
// +--------------------------------------------------------------------------+
// | index.php                                                                |
// |                                                                          |
// | Display poll results and past polls.                                     |
// +--------------------------------------------------------------------------+
// | Copyright (C) 2009-2018 by the following authors:                        |
// |                                                                          |
// | Mark R. Evans          mark AT glfusion DOT org                          |
// |                                                                          |
// | Copyright (C) 2000-2008 by the following authors:                        |
// |                                                                          |
// | Authors: Tony Bibbs        - tony AT tonybibbs DOT com                   |
// |          Mark Limburg      - mlimburg AT users DOT sourceforge DOT net   |
// |          Jason Whittenburg - jwhitten AT securitygeeks DOT com           |
// |          Dirk Haun         - dirk AT haun-online DOT de                  |
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

require_once '../lib-common.php';
use Polls\Poll;
use Polls\Menu;
use Polls\Config;
use Polls\Views\Results;

if (!in_array(Config::get('pi_name'), $_PLUGINS)) {
    COM_404();
    exit;
}

// MAIN ========================================================================
//
// no pid will load a list of polls
// no aid will let you vote on the select poll
// an aid greater than 0 will save a vote for that answer on the selected poll
// an aid of -1 will display the select poll

$display = '';
$page = '';
$title = $LANG_POLLS['pollstitle'];

$filter = sanitizer::getInstance();
$filter->setPostmode('text');

if (isset($_POST['pid'])) {
    $pid = COM_applyFilter($_POST['pid']);
} elseif (isset($_GET['pid'])) {
    $pid = COM_applyFilter($_GET['pid']);
} else {
    $pid = '';
}

$type = isset($_POST['type']) ? COM_applyFilter($_POST['type']) : '';
if ( $type != '' && $type != 'article' ) {
    if (!in_array($type,$_PLUGINS)) {
        $type = '';
    }
}

$expected = array(
    'reply', 'votebutton', 'results',
);
$action = 'listpolls';
foreach ($expected as $provided) {
    if (isset($_POST[$provided])) {
        $action = $provided;
        $actionval = $_POST[$provided];
    } elseif (isset($_GET[$provided])) {
        $action = $provided;
        $actionval = $_GET[$provided];
    }
}

if ($action == 'reply') {
    // Handle a comment submission
    echo COM_refresh(
        $_CONF['site_url'] . '/comment.php?sid=' . $pid . '&pid=' . $pid . '&type=' . $type
    );
    exit;
}

$aid = 0;
if ($pid != '') {
    if (isset ($_GET['aid'])) {
        $aid = -1; // only for showing results instead of questions
    } else if (isset ($_POST['aid'])) {
        $aid = $_POST['aid'];
    }
}

$order = '';
if (isset ($_REQUEST['order'])) {
    $order = COM_applyFilter ($_REQUEST['order']);
}
$mode = '';
if (isset ($_REQUEST['mode'])) {
    $mode = COM_applyFilter ($_REQUEST['mode']);
}
$msg = 0;
if (isset($_REQUEST['msg'])) {
    $msg = COM_applyFilter($_REQUEST['msg'], true);
}

if ($pid != '') {
    $Poll = Poll::getInstance($pid);
}

switch ($action) {
case 'votebutton':
    // Get the answer array and check that the number is right, and the user hasn't voted
    $aid = (isset($_POST['aid']) && is_array($_POST['aid'])) ? $_POST['aid'] : array();
    if ($Poll->alreadyVoted()) {
        COM_setMsg($LANG_POLL['alreadyvoted'], 'error', true);
        COM_refresh(Config::get('url') . '/index.php');
    } else {
        if (count($aid) == $Poll->numQuestions()) {
            if ($Poll->saveVote($aid)) {
                COM_refresh(Config::get('url') . '/index.php?results=x&pid=' . $Poll->getID());
            } else {
                COM_refresh(Config::get('url') . '/index.php');
            }
        } else {
            $page .= COM_showMessageText($LANG_POLLS['answer_all'], '', true, 'error');
            $page .= $Poll->withSelections($aid)->Render();
        }
    }
    break;

case 'results':
    if ($Poll->canViewResults()) {
        $page .= (new Results($Poll->getID()))
            ->withCommentMode($mode)
            ->withCommentOrder($order)
            ->Render();
    } else {
        $page .= Poll::listPolls();
    }
    break;

default:
    if (isset($Poll) && !$Poll->isNew()) {
        if ($msg > 0) {
            $page .= COM_showMessage($msg, Config::get('pi_name'));
        }
        if (isset($_POST['aid'])) {
            $eMsg = $LANG_POLLS['answer_all'] . ' "' . $filter->filterData($Poll->getTopic()) . '"';
            $page .= COM_showMessageText($eMsg,$LANG_POLLS['not_saved'],true,'error');
        }
        if (!$Poll->isOpen() && $Poll->canViewResults()) {
            $page .= (new Results($Poll->getID()))
                ->withCommentMode($mode)
                ->withCommentOrder($order)
                ->Render();
        } elseif ($Poll->canVote()) {
            $page .= $Poll->Render();
        } else {
            COM_setMsg($LANG_POLLS['deny_msg'], 'error', true);
            COM_refresh(Config::get('url') . '/index.php');
        }
    } else {
        $title = $LANG_POLLS['pollstitle'];
        $page .= Poll::listPolls();
    }
    break;
}

$display = Menu::siteHeader($title);
$display .= $page;
$display .= Menu::siteFooter();

echo $display;

?>
