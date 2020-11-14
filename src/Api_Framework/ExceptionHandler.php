<?php

declare(strict_types=1);

namespace pointybeard\Symphony\Extensions\Api_Framework;

class ExceptionHandler extends \GenericExceptionHandler
{
    private static $debug;

    public static function initialise($Log = NULL)
    {
        self::$enabled = true;
        self::$debug = true == \Symphony::isLoggedIn();
        restore_exception_handler();
        set_exception_handler(array(__CLASS__, 'handler'));
    }

    public static function handler($ex): void
    {
        try {
            $class = __CLASS__;
            $exception_type = get_class($ex);

            $handler = __NAMESPACE__."\Exceptions\{$exception_type}Handler";
            if (class_exists($handler) && method_exists($handler, 'render')) {
                $class = $handler;
            }

            echo call_user_func(array($class, 'render'), $ex);
            exit;
        } catch (\Exception $ex) {
            echo 'Looks like the Exception handler crapped out';
            print_r($ex);
            exit;
        }
    }

    private static function getCodeTrace(array $trace): array
    {
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

    private static function getDatabaseTrace(array $queries): array
    {
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

    public static function render($ex): void
    {
        header('Content-Type: application/json');

        // Build the JSON
        $output = [
            'status' => 500,
            'error' => $ex->getCode(),
            'message' => $ex->getMessage(),
        ];

        // Check for a custom status code
        if (true == ($ex instanceof AbstractApiException)) {
            $output['status'] = $ex->getHttpStatusCode();
        }

        // Let the exception modify the output if it wants to.
        if (in_array("pointybeard\Symphony\Extensions\Api_Framework\Interfaces\ModifiesExceptionOutputInterface", class_implements($ex))) {
            $output = $ex->modifyOutput($output);
        }

        http_response_code($output['status']);

        if (self::$debug) {
            if (is_object(\Symphony::Database())) {
                $databaseDebug = \Symphony::Database()->debug();
            }

            $output['debug'] = [
                'file' => $ex->getFile(),
                'line' => $ex->getLine(),
                'severity' => (
                    $e instanceof \ErrorException
                        ? \GenericErrorHandler::$errorTypeStrings[$ex->getSeverity()]
                        : 'Fatal Error'
                ),
                'code_trace' => (
                    count($ex->getTrace()) > 0
                        ? self::getCodeTrace($ex->getTrace())
                        : null
                ),
                'database_trace' => self::getDatabaseTrace($databaseDebug),
            ];

            // See of this is an XSLT error. If so, include the XML output
            // since that is what is most likely failing.
            if (
                $e instanceof \SymphonyErrorPage &&
                'xslt' == $ex->getTemplateName() &&
                isset($ex->getAdditional()->proc)
            ) {
                $err = $ex->getAdditional()->proc->getError(false, true);
                if (isset($err['value']) && isset($err['value']['context'])) {
                    $xml = $err['value']['context'];

                    // Convert double quotes into single quotes. This stops
                    // JSON from escaping double quotes
                    $xml = preg_replace('@"@', "'", $xml);

                    // Convert tab characters into 2 spaces. We cannot display
                    // control characters in JSON output.
                    $xml = preg_replace("@\t@", '  ', $xml);

                    // Split the XML into individual lines. This is because
                    // any newline control characters will be escaped
                    $xmlLines = preg_split("@[\r\n]@", $xml);

                    // Add each line of the XML to the output. Use a zero padded
                    // line number for readablity.
                    $output['debug']['context'] = [];
                    foreach ($xmlLines as $n => $l) {
                        $output['debug']['context'][sprintf('%04d', $n + 1)] = $l;
                    }
                }
            }
        }

        // output and die
        echo json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        exit(255);
    }
}
