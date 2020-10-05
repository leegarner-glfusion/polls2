<?php
/**
 * Language file for the new Polls plugin for glFusion.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @author      Mike Lynn <mike@mlynn.com>
 * @copyright   Copyright (c) 2020 Lee Garner <lee@leegarner.com>
 * @package     polls
 * @version     v2.3.1
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */
use Polls\MO;
use Polls\Config;

// Localization of the Admin Configuration UI
$LANG_configsections[Config::PI_NAME] = array(
    'label' => Config::get('pi_display_name'),
    'title' => MO::_('Polls Configuration'),
);

$LANG_confignames[Config::PI_NAME] = array(
    'hidepollsmenu' => MO::_('Hide Polls Menu Entry'),
    'maxquestions' => MO::_('Max. Questions per Poll'),
    'maxanswers' => MO::_('Max. Options per Question'),
    'answerorder' => MO::_('Sort Results'),
    'pollcookietime' => MO::_('Voter Cookie Valid Duration'),
    'polladdresstime' => MO::_('Voter IP Address Valid Duration'),
    'delete_polls' => MO::_('Delete Polls with Owner'),
    'aftersave' => MO::_('After Saving Poll'),
    'default_permissions' => MO::_('Poll Default Permissions'),
    'displayblocks' => MO::_('Display glFusion Blocks'),
    'def_voting_gid' => MO::_('Default group allowed to vote'),
    'def_results_gid' => MO::_('Default group allowed to view results'),
);

$LANG_configsubgroups[Config::PI_NAME] = array(
    'sg_main' => MO::_('Main Settings'),
);

$LANG_fs[Config::PI_NAME] = array(
    'fs_main' => MO::_('General Polls Settings'),
    'fs_permissions' => MO::_('Default Permissions'),
);

$LANG_configSelect[Config::PI_NAME] = array(
    0 => array(
        1 => MO::_('True'),
        0 => MO::_('False'),
    ),
    1 => array(
        true => MO::_('True'),
        false => MO::_('False'),
    ),
    2 => array(
        'submitorder' => MO::_('As Submitted'),
        'voteorder' => MO::_('By Votes'),
    ),
    9 => array(
        'item' => MO::_('Forward to Poll'),
        'list' => MO::_('Display Admin List'),
        'plugin' => MO::_('Display Public List'),
        'home' => MO::_('Display Home'),
        'admin' => MO::_('Display Admin'),
    ),
    12 => array(
        0 => MO::_('No access'),
        2 => MO::_('Read-Only'),
        3 => MO::_('Read-Write'),
    ),
    13 => array(
        0 => MO::_('Left Blocks'),
        1 => MO::_('Right Blocks'),
        2 => MO::_('Left & Right Blocks'),
        3 => MO::_('None'),
    ),
);

?>
