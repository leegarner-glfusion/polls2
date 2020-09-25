<?php
/**
 * Define display mode constants
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
namespace Polls\Models;


/**
 * Constants for display modes (block, autotag, normal, etc.)
 * @package polls
 */
class Modes
{
    /** All display types.
     */
    public const ALL = -1;

    /** Displaying normally via the plugin's index.php.
     */
    public const NORMAL = 0;

    /** Displaying in a block.
     */
    public const BLOCK = 1;

    /** Displaying as an autotag.
     */
    public const AUTOTAG = 2;

    /** Creating a printable view without site header.
     */
    public const PRINT = 4;
}
