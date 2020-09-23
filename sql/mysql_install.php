<?php
/**
 * Database creation and update statements for the Polls plugin.
 *
 * @author      Tony Bibbs <tony AT tonybibbs DOT com>
 * @author      Mark Limburg <mlimburg AT users DOT sourceforge DOT net>
 * @author      Jason Whittenburg - jwhitten AT securitygeeks DOT com>
 * @author      Dirk Haun         - dirk AT haun-online DOT de>
 * @author      Trinity Bays      - trinity93 AT gmail DOT com>                 |
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2009-2020 The Above Authors
 * @package     polls
 * @version     v3.0.0
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */

if (!defined ('GVERSION')) {
    die ('This file can not be used on its own.');
}
use Polls\DB;

$_SQL[DB::key('answers')] = "CREATE TABLE " . DB::table('answers') . " (
  pid varchar(128) NOT NULL default '',
  qid mediumint(9) NOT NULL default 0,
  aid tinyint(3) unsigned NOT NULL default '0',
  answer varchar(255) default NULL,
  votes mediumint(8) unsigned default NULL,
  remark varchar(255) NULL,
  PRIMARY KEY (pid, qid, aid)
) ENGINE=MyISAM
";

$_SQL[DB::key('questions')] = "CREATE TABLE " . DB::table('questions') . " (
    qid mediumint(9) NOT NULL DEFAULT '0',
    pid varchar(128) NOT NULL,
    question varchar(255) NOT NULL,
    PRIMARY KEY (qid, pid)
) ENGINE=MyISAM
";

$_SQL[DB::key('topics')] = "CREATE TABLE " . DB::table('topics') . " (
  `pid` varchar(128) NOT NULL,
  `topic` varchar(255) DEFAULT NULL,
  `description` text,
  `voters` mediumint(8) unsigned DEFAULT NULL,
  `questions` int(11) NOT NULL DEFAULT '0',
  `date` datetime DEFAULT NULL,
  `opens` datetime NOT NULL DEFAULT '1970-01-01 00:00:00',
  `closes` datetime NOT NULL DEFAULT '9999-12-31 23:59:59',
  `display` tinyint(4) NOT NULL DEFAULT '0',
  `is_open` tinyint(1) NOT NULL DEFAULT '1',
  `hideresults` tinyint(1) NOT NULL DEFAULT '0',
  `commentcode` tinyint(4) NOT NULL DEFAULT '0',
  `statuscode` tinyint(4) NOT NULL DEFAULT '0',
  `owner_id` mediumint(8) unsigned NOT NULL DEFAULT '1',
  `group_id` mediumint(8) unsigned NOT NULL DEFAULT '1',
  `results_gid` mediumint(8) unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`pid`),
  KEY `pollquestions_qid` (`pid`),
  KEY `pollquestions_date` (`date`),
  KEY `pollquestions_display` (`display`),
  KEY `pollquestions_commentcode` (`commentcode`),
  KEY `pollquestions_statuscode` (`statuscode`),
  KEY `idx_enabled` (`is_open`)
) ENGINE=MyISAM
";

$_SQL[DB::key('voters')] = "CREATE TABLE " . DB::table('voters') . " (
  id int(10) unsigned NOT NULL auto_increment,
  pid varchar(128) NOT NULL default '',
  ipaddress varchar(15) NOT NULL default '',
  uid mediumint(8) NOT NULL default 1,
  date int(10) unsigned default NULL,
  PRIMARY KEY  (id),
  INDEX pollid( pid )
) ENGINE=MyISAM
";

?>
