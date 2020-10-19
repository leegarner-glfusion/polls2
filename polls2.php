<?php
/**
 * Global configuration items for the Polls plugin.
 * These are either static items, such as the plugin name and table
 * definitions, or are items that don't lend themselves well to the
 * glFusion configuration system, such as allowed file types.
 *
 * @copyright   Copyright (c) 2000-2020 The following authors:
 * @author      Mark R. Evans <mark AT glfusion DOT org>
 * @author      Tony Bibbs <tony AT tonybibbs DOT com>
 * @author      Tom Willett <twillett AT users DOT sourceforge DOT net>
 * @author      Blaine Lang <langmail AT sympatico DOT ca>
 * @author      Dirk Haun <dirk AT haun-online DOT de>
 * @author      Lee Garner <lee@leegarner.com>
 * @package     polls
 * @version     v3.0.0
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */

if (!defined ('GVERSION')) {
    die ('This file can not be used on its own.');
}
use Polls\Config;
use Polls\DB;
use Polls\MO;

global $_DB_table_prefix;

Config::set('pi_display_name', MO::_('Polls v2'));
Config::set('pi_version', '2.3.1');
Config::set('gl_version', '1.7.8');
Config::set('pi_url', 'https://www.glfusion.org');

// Add to $_TABLES array the tables your plugin uses
$_TABLES[DB::key('answers')]    = $_DB_table_prefix . DB::key('answers');
$_TABLES[DB::key('questions')]  = $_DB_table_prefix . DB::key('questions');
$_TABLES[DB::key('topics')]     = $_DB_table_prefix . DB::key('topics');
$_TABLES[DB::key('voters')]     = $_DB_table_prefix . DB::key('voters');

?>
