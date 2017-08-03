<?php
namespace Symphony\ApiFramework\Lib;

use \Exception;
use \GenericExceptionHandler;

class ExceptionHandler extends GenericExceptionHandler
{
    private static $debug;

    public static function initialise($debug=false)
    {
        self::$enabled = true;
        self::$debug = $debug;
        restore_exception_handler();
        set_exception_handler(array(__CLASS__, 'handler'));
        //register_shutdown_function(array(__CLASS__, 'shutdown'));
    }

    public static function handler($e)
    {
        try {
            if (self::$enabled !== true) {
                return;
            }

            $class = __CLASS__;
            $exception_type = get_class($e);

            $handler = __NAMESPACE__ . "\Exceptions\{$exception_type}Handler";
            if (class_exists($handler) && method_exists($handler, 'render')) {
                $class = $handler;
            }

            echo call_user_func(array($class, 'render'), $e);
            exit;
        } catch (Exception $e) {
            echo 'Looks like the Exception handler crapped out';
            print_r($e);
            exit;
        }
    }

    private static function getCodeTrace($trace){
        $result = [];
        foreach ($trace as $t) {
            $result[] = sprintf(
                '[%s:%d] %s%s%s();',
                (isset($t['file']) ? $t['file'] : null),
                (isset($t['line']) ? $t['line'] : null),
                (isset($t['class']) ? $t['class'] : null),
                (isset($t['type']) ? $t['type'] : null),
                $t['function']
            );
        }
        return $result;
    }

    private static function getDatabaseTrace($queries){
        $result = [];
        foreach ($queries as $query) {
            $result[] = sprintf(
                '%s; [%01.4f]',
                preg_replace('/[\r\n\t ]+/', ' ', $query['query']),
                (isset($query['execution_time']) ? $query['execution_time'] : null)
            );
        }

        return $result;
    }

    public static function render($e)
    {

        header("Content-Type: application/json");

        // Build the JSON
       $output = [
            "status" => 500,
            "error" => $e->getCode(),
            "message" => $e->getMessage()
        ];

        // Check for a custom status code
        if($e instanceof AbstractApiException) {
            $output['status'] = $e->getHttpStatusCode();
        }

        // Let the exception modify the output if it wants to.
        if(in_array("Symphony\ApiFramework\Lib\Interfaces\ModifiesExceptionOutputInterface", class_implements($e))) {
            $output = $e->modifyOutput($output);
        }

        http_response_code($output['status']);

        if(self::$debug){
            if(is_object(\Symphony::Database())){
                $databaseDebug = \Symphony::Database()->debug();
            }

            $output['debug'] = [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'severity' => ($e instanceof Lib\ErrorException ? \GenericErrorHandler::$errorTypeStrings[$e->getSeverity()] : 'Fatal Error'),
                'code_trace' => (count($e->getTrace()) > 0 ? self::getCodeTrace($e->getTrace()) : null),
                'database_trace' => self::getDatabaseTrace($databaseDebug),
            ];
        }

        // output and die
        echo json_encode($output, JSON_PRETTY_PRINT);
        exit(255);
    }
}
