<?PHP
class wbfy_si_Autoloader
{
    /**
     * Register autoloader
     */
    public static function register()
    {
        ini_set('unserialize_callback_func', 'spl_autoload_call');
        spl_autoload_register(array(new self, 'autoload'));
    }

    /**
     * Autoload function
     *
     * @param string $class The class being autoloaded
     */
    public static function autoload($class)
    {
        if (0 !== strpos($class, 'wbfy_si_')) {
            return;
        }

        $file = WBFY_SI_PLUGIN_DIR . 'server/php/' . str_replace(array('wbfy_si_', '_'), array('', '/'), $class) . ".class.php";
        if (file_exists($file)) {
            require_once $file;
            return true;
        }
        return false;
    }
}
