<?php
namespace Symphony\ApiFramework\Lib;

use \GenericErrorHandler;
use \ErrorException;

class ErrorHandler extends GenericErrorHandler
{
    /**
     * Whether the error handler is enabled or not, defaults to true.
     * Setting to false will prevent any Symphony error handling from occurring
     * @var boolean
     */
    public static $enabled = true;

    /**
     * Initialise will set the error handler to be the `__CLASS__::handler`
     * function.
     */
    public static function initialise()
    {
        restore_error_handler();
        set_error_handler(array(__CLASS__, 'handler'), error_reporting());
        //register_shutdown_function(array(__CLASS__, 'shutdown'));
    }

    /**
     * Determines if the error handler is enabled by checking that error_reporting
     * is set in the php config and that $enabled is true
     *
     * @return boolean
     */
    public static function isEnabled()
    {
        return (bool) error_reporting() && self::$enabled;
    }

    public static function shutdown()
    {
        $last_error = error_get_last();

        if (!is_null($last_error) && $last_error['type'] == E_ERROR) {
            // Make sure we don't get any crud in the output
            ob_clean();

            $last_error['type'] = self::$errorTypeStrings[$last_error['type']];
            print json_encode([
                "error" => 'A severe, unrecoverable, error occurred.',
                "details" => $last_error
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            exit;
        }
    }

    /**
     * The handler function will write the error to the `$Log` if it is not `E_NOTICE`
     * or `E_STRICT` before raising the error as an Exception. This allows all `E_WARNING`
     * to actually be captured by an Exception handler.
     *
     * @param integer $code
     *  The error code, one of the PHP error constants
     * @param string $message
     *  The message of the error, this will be written to the log and
     *  displayed as the exception message
     * @param string $file
     *  The file that holds the logic that caused the error. Defaults to null
     * @param integer $line
     *  The line where the error occurred.
     * @throws ErrorException
     * @return string
     *  Usually a string of HTML that will displayed to a user
     */
    public static function handler($code, $message, $file = null, $line = null)
    {
        if (self::isEnabled()) {
            throw new ErrorException($message, 0, $code, $file, $line);
        }
    }
}
