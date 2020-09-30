<?php
/**
 * Class to manage locale settings.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2020 Lee Garner <lee@leegarner.com>
 * @package     polls
 * @version     v0.0.2
 * @since       v0.0.2
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */
namespace Polls;


/**
 * Manage locale settings for the Library plugin.
 * @package polls
 */
class MO
{
    /** Language domain, e.g. plugin name.
     * @var string */
    private static $domain = NULL;

    /** Variable to save the original locale if changed by init().
     * @var string */
    private static $old_locale = NULL;

    /** Supported language name=>locale mapping.
     * @var array */
    private static $lang2locale = array(
        'dutch' => 'nl',
        'finnish' => 'fi',
        'german' => 'de_DE',
        'polish' => 'pl_PL',
        'czech' => 'cs_CZ',
        'english' => 'en_US',
        'french_canada' => 'fr_CA',
        'spanish_colombia' => 'es_CO',
    );


    /**
     * Initialize a language.
     * Sets the language domain and checks the requested language
     *
     * @access  public  so that notifications may set the language as needed.
     * @param   string  $lang   Language name, default is set by lib-common.php
     */
    public static function init($lang = NULL)
    {
        global $_CONF, $LANG_LOCALE;

        // Set the language domain to separate strings from the global
        // namespace.
        self::$domain = Config::PI_NAME;

        // Set the requested language, falling back to the default if not
        // specified.
        if (empty($lang)) {
            $lang = $_CONF['language'];
        }

        // Validate and use the appropriate locale code.
        // Defaults to 'en_US' if a supportated locale wasn't requested.
        $parts = explode('_', $lang);
        if (
            count($parts) > 2 &&
            isset(self::$lang2local[$parts[0] . '_' . $parts[1]])
        ) {
            // 2-part language, e.g. "french_canada"
            // Ignore any other parts like 'utf-8'
            $locale = self::$lang2locale[$parts[0] . '_' . $parts[1]];
        } elseif (isset(self::$lang2locale[$parts[0]])) {
            // single-part language, e.g. "english"
            $locale = self::$lang2locale[$parts[0]];
        } elseif (isset($LANG_LOCALE) && !empty($LANG_LOCALE)) {
            // Not found, try the global variable
            $locale = $LANG_LOCALE;
        } else {
            // global not set, fall back to US english
            $locale = 'en_US';
        }

        self::$old_locale = setlocale(LC_MESSAGES, "0");
        $results = setlocale(LC_MESSAGES, $locale);
        if ($results) {
            $dom =bind_textdomain_codeset(self::$domain, 'UTF-8');
            $dom = bindtextdomain(self::$domain, __DIR__ . "/../locale");
        }
    }


    /**
     * Reset the locale back to the previously-defined value.
     * Called after processes that change the locale for a specific user,
     * such as system-generated notifications.
     */
    public static function reset()
    {
        if (self::$old_locale !== NULL) {
            setlocale(LC_MESSAGES, self::$old_locale);
        }
    }


    /**
     * Initialize the locale for a specific user ID.
     *
     * @uses    self::init()
     * @param   integer $uid    User ID
     */
    public static function initUser($uid=0)
    {
        global $_USER;

        if ($uid == 0) {
            $uid = $_USER['uid'];
        }
        $lang = DB_getItem($_TABLES['users'], 'language', 'uid = ' . (int)$uid);
        self::init($lang);
    }


    /**
     * Get a singular or plural language string as needed.
     *
     * @param   string  $single     Singular language string
     * @param   string  $plural     Plural language string
     * @return  string      Appropriate language string
     */
    public static function dngettext($single, $plural, $number)
    {
        if (!self::$domain) {
            self::init();
        }
        return \dngettext(self::$domain, $single, $plural, $number);
    }
    public static function _n($single, $plural, $number)
    {
        return self::dngettext($single, $plural, $number);
    }


    /**
     * Get a normal, singular language string.
     *
     * @param   string  $txt        Text string
     * @return  string      Translated text
     */
    public static function dgettext($txt)
    {
        if (!self::$domain) {
            self::init();
        }
        return \dgettext(self::$domain, $txt);
    }
    public static function _($txt)
    {
        return self::dgettext($txt);
    }

}


/**
 * Get a single or plural text string as needed.
 *
 * @param   string  $single     Text when $number is singular
 * @param   string  $plural     Text when $number is plural
 * @param   float   $number     Number used in the string
 * @return  string      Appropriate text string
 */
function _n($single, $plural, $number)
{
    return MO::dngettext($single, $plural, $number);
}


/**
 * Get a single text string, automatically applying the domain.
 *
 * @param   string  $txt    Text to be translated
 * @return  string      Translated string
 */
function _($txt)
{
    return MO::dgettext($txt);
}

?>