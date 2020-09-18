<?php
/**
 * Class to describe voters.
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
namespace Polls;


/**
 * Class to manage poll voters.
 * @package polls
 */
class Voter
{
    /**
     * Check if the user has already voted.
     * For anonymous, checks the IP address and the poll cookie.
     *
     * @param   string  $pid    Poll ID
     * @param   integer $voting_grp Group with access to vote
     * @return  boolean     True if the user has voted, False if not
     */
    public static function hasVoted($pid, $voting_grp=2)
    {
        global $_USER;

        // If logged in and the user ID is in the voters table,
        // we can trust that this user has voted.
        if (!COM_isAnonUser()) {
            if (DB_count(
                DB::table('voters'),
                 array('uid', 'pid'),
                 array((int)$_USER['uid'], DB_escapeString($pid)) ) > 0
            ) {
                return true;
            }
        }
        if ($voting_grp != 2) {
            // If a login is required, return fals now since there's no need
            // to check for anonymous votes.
            return false;
        }

        // For Anonymous we only have the cookie and IP address.
        if (isset($_COOKIE['poll-' . $pid])) {
            return true;
        }

        $ip = DB_escapeString(self::getIpAddress());
        if (
            $ip != '' &&
            DB_count(
                DB::table('voters'),
                array('ipaddress', 'pid'),
                array($ip, DB_escapeString($pid))
            ) > 0
        ) {
            return true;
        }

        // No vote found
        return false;
    }


    /**
     * Get the current user's actual IP address.
     *
     * @return  string      User's IP address
     */
    public static function getIpAddress()
    {
        return $_SERVER['REAL_ADDR'];
    }


    /**
     * Create a voter record.
     * This only inserts new records, no updates, so `INSERT IGNORE` is used
     * just to avoid SQL errors.
     *
     * @param   string  $pid    Poll ID
     */
    public static function create($pid)
    {
        global $_USER;

        if ( COM_isAnonUser() ) {
            $userid = 1;
        } else {
            $userid = (int)$_USER['uid'];
        }

        Poll::getInstance($pid)->updateVoters(1);

        // This always does an insert so no need to provide key_field and key_value args
        $sql = "INSERT IGNORE INTO " . DB::table('voters') . " SET
            ipaddress = '" . DB_escapeString(Voter::getIpAddress()) . "',
            uid = '$userid',
            date = UNIX_TIMESTAMP(),
            pid = '" . DB_escapeString($pid) . "'";
        return DB_query($sql);
    }


    /**
     * Change the Poll ID for all items if it was saved with a new ID.
     *
     * @param   string  $old_pid    Original Poll ID
     * @param   string  $new_pid    New Poll ID
     */
    public static function changePid($old_pid, $new_pid)
    {
        DB_query("UPDATE " . DB::table('voters') . "
            SET pid = '" . DB_escapeString($new_pid) . "'
            WHERE pid = '" . DB_escapeString($old_pid) . "'"
        );
    }


    /**
     * Delete all the voters for a poll, when the poll is deleted.
     *
     * @param   string  $pid    Poll ID
     */
    public static function deletePoll($pid)
    {
        DB_delete(DB::table('voters'), 'pid', $pid);
    }


    public static function moveUser($origUID, $destUID)
    {
        DB_query("UPDATE " . DB::table('voters') . "
            SET uid = '" . (int)$destUID . "'
            WHERE uid = '" . (int)$origUID . "'"
        );
    }

}

?>
