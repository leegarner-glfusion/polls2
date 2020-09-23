<?php
/**
* glFusion CMS
*
* UTF-8 Language File for Polls Plugin
*
* @license GNU General Public License version 2 or later
*     http://www.opensource.org/licenses/gpl-license.php
*
*  Copyright (C) 2008-2018 by the following authors:
*   Mark R. Evans   mark AT glfusion DOT org
*
*  Based on prior work Copyright (C) 2001-2005 by the following authors:
*   Tony Bibbs - tony AT tonybibbs DOT com
*   Trinity Bays - trinity93 AT gmail DOT com
*
*/

if (!defined ('GVERSION')) {
    die ('This file cannot be used on its own.');
}

use Polls\Config;
global $LANG32;

$LANG_POLLS = array(
    'polls'             => 'Sondages',
    'results'           => 'Résultats',
    'pollresults'       => 'Résultat des sondages',
    'votes'             => 'votes',
    'vote'              => 'Votez',
    'pastpolls'         => 'Sondages anciens',
    'savedvotetitle'    => 'Vote sauvegardé',
    'savedvotemsg'      => 'Votre vote à été enregistré',
    'pollstitle'        => 'Sondages dans le système',
    'polltopics'        => 'Other polls',
    'stats_top10'       => 'Top-10 des sondages',
    'stats_topics'      => 'Sondage Sujet',
    'stats_votes'       => 'Votes',
    'stats_none'        => 'Il appert qu\'il n\'y a aucun sondage actif en ce moment, ou que personne n\'ait voté à ce jour.',
    'stats_summary'     => 'Sondages (réponses) dans le système',
    'open_poll'         => 'Ouvert au vote',
    'answer_all'        => 'Please answer all remaining questions',
    'not_saved'         => 'Result not saved',
    'upgrade1'          => 'You installed a new version of the Polls plugin. Please',
    'upgrade2'          => 'upgrade',
    'editinstructions'  => 'Please fill in the Poll ID, at least one question and two answers for it.',
    'pollclosed'        => 'This poll is closed for voting.',
    'pollhidden'        => 'Poll results will be available only after the Poll has closed.',
    'start_poll'        => 'Start Poll',
    'deny_msg' => 'Access to this poll is denied.  Either the poll has been moved/removed or you do not have sufficient permissions.',
    'login_required'    => '<a href="'.$_CONF['site_url'].'/users.php" rel="nofollow">Login</a> required to vote',
    'username'          => 'Username',
    'ipaddress'         => 'IP Address',
    'date_voted'        => 'Date Voted',
    'description'       => 'Description',
    'general'           => 'General',
    'poll_questions'    => 'Poll Questions',
    'permissions'       => 'Permissions',
'msg_updated' => 'Item(s) have been updated',
'msg_deleted' => 'Item(s) have been deleted',
'msg_nochange' => 'Item(s) are unchanged',
'datepicker' => 'Date Picker',
'timepicker' => 'Time Picker',
'closes' => 'Poll Closes',
'opens' => 'Poll Opens',
'voting_group' => 'Voting Group',
'results_group' => 'Results Group',
'back_to_list' => 'Back to List',
'msg_results_open' => 'Early results, poll is open',
);

###############################################################################
# admin/plugins/polls/index.php

$LANG25 = array(
    1 => 'Mode',
    2 => 'Please enter a topic, at least one question and at least one answer for that question.',
    3 => 'Poll Created',
    4 => "Poll %s saved",
    5 => 'Edit Poll',
    6 => 'Poll ID',
    7 => '(do not use spaces)',
    8 => 'Appears on Pollblock',
    9 => 'Sujet',
    10 => 'Answers / Votes / Remark',
    11 => "There was an error getting poll answer data about the poll %s",
    12 => "There was an error getting poll question data about the poll %s",
    13 => 'Create Poll',
    14 => 'Sauvegarder',
    15 => 'Annuler',
    16 => 'Effacer',
    17 => 'Please enter a Poll ID',
    18 => 'Polls Administration',
    19 => 'To modify or delete a poll, click on the edit icon of the poll.  To create a new poll, click on "Create New" above.',
    20 => 'Voters',
    21 => 'Accès refusé',
    22 => "You are trying to access a poll that you don't have rights to.  This attempt has been logged. Please <a href=\"{$_CONF['site_admin_url']}/poll.php\">go back to the poll administration screen</a>.",
    23 => 'New Poll',
    24 => 'Accueil Admin',
    25 => 'Oui',
    26 => 'Aucun',
    27 => 'Modifier',
    28 => 'Submit',
    29 => 'Search',
    30 => 'Limit Results',
    31 => 'Question',
    32 => 'To remove this question from the poll, remove its question text',
    33 => 'Ouvert au vote',
    34 => 'Poll Topic:',
    35 => 'This poll has',
    36 => 'more questions.',
    37 => 'Hide results while poll is open',
    38 => 'While the poll is open, only the owner &amp; root can see the results',
    39 => 'The topic will be only displayed if there are more than 1 questions.',
    40 => 'See all answers to this poll',
    41 => 'Are you sure you want to delete this Poll?',
    42 => 'Are you absolutely sure you want to delete this Poll?  All questions, answers and comments that are associated with this Poll will also be permanently deleted from the database.',
    43 => 'Login Required to Vote',
);

$LANG_PO_AUTOTAG = array(
    'desc_poll'                 => 'Link: to a Poll on this site.  link_text defaults to the Poll topic.  usage: [poll:<i>poll_id</i> {link_text}]',
    'desc_poll_result'          => 'HTML: renders the results of a Poll on this site.  usage: [poll_result:<i>poll_id</i>]',
    'desc_poll_vote'            => 'HTML: renders a voting block for a Poll on this site.  usage: [poll_vote:<i>poll_id</i>]',
);

$PLG_polls_MESSAGE19 = 'Vos sondages ont été sauvegardés avec succès.';
$PLG_polls_MESSAGE20 = 'Your poll has been successfully deleted.';

// Messages for the plugin upgrade
$PLG_polls_MESSAGE3001 = 'Plugin Mise à niveau non pris en charge.';
$PLG_polls_MESSAGE3002 = $LANG32[9];


// Localization of the Admin Configuration UI
$LANG_configsections[Config::PI_NAME] = array(
    'label' => 'Sondages',
    'title' => 'Polls Configuration'
);

$LANG_confignames[Config::PI_NAME] = array(
    'pollsloginrequired' => 'Polls Login Required',
    'hidepollsmenu' => 'Hide Polls Menu Entry',
    'maxquestions' => 'Max. Questions per Poll',
    'maxanswers' => 'Max. Options per Question',
    'answerorder' => 'Sort Results',
    'pollcookietime' => 'Voter Cookie Valid Duration',
    'polladdresstime' => 'Voter IP Address Valid Duration',
    'delete_polls' => 'Delete Polls with Owner',
    'aftersave' => 'After Saving Poll',
    'default_permissions' => 'Poll Default Permissions',
    'displayblocks' => 'Afficher glFusion Blocs',
);

$LANG_configsubgroups[Config::PI_NAME] = array(
    'sg_main' => 'Paramètres Principaux'
);

$LANG_fs[Config::PI_NAME] = array(
    'fs_main' => 'General Polls Settings',
    'fs_permissions' => 'Autorisations par Défaut'
);

$LANG_configSelect[Config::PI_NAME] = array(
    0 => array(1=>'True', 0=>'False'),
    1 => array(true=>'True', false=>'False'),
    2 => array('submitorder'=>'As Submitted', 'voteorder'=>'By Votes'),
    9 => array('item'=>'Forward to Poll', 'list'=>'Display Admin List', 'plugin'=>'Display Public List', 'home'=>'Display Home', 'admin'=>'Display Admin'),
    12 => array(0=>'No access', 2=>'Lecture-Seule', 3=>'Read-Write'),
    13 => array(0=>'Gauche Blocs', 1=>'Right Blocks', 2=>'Gauche et blocs Droite', 3=>'None')
);

?>
