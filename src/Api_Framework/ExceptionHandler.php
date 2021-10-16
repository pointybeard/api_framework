<?php

declare(strict_types=1);

/*
 * This file is part of the "RESTful API Framework Extension for Symphony CMS" repository.
 *
 * Copyright 2017-2021 Alannah Kearney <hi@alannahkearney.com>
 *
 * For the full copyright and license information, please view the LICENCE
 * file that was distributed with this source code.
 */

namespace pointybeard\Symphony\Extensions\Api_Framework;

use ErrorException;

use Exception;
use GenericErrorHandler;
use GenericExceptionHandler;
use Symfony\Component\HttpFoundation;
use Symphony;
use SymphonyErrorPage;

class ExceptionHandler extends GenericExceptionHandler
{
    private static $debug;

    public static function initialise($Log = null)
    {
        self::$enabled = true;
        self::$debug = true == Symphony::isLoggedIn();
        restore_exception_handler();
        set_exception_handler([__CLASS__, 'handler']);
    }

    public static function enableDebugOutput()
    {
        self::$debug = true;
    }

    public static function disableDebugOutput()
    {
        self::$debug = false;
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

            echo call_user_func([$class, 'render'], $ex);
            exit;
        } catch (Exception $ex) {
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
        // Build the JSON
        $output = [
            'error' => [
                'timestamp' => date('c'),
                'type' => '/errors/internal-server-error',
                'title' => $ex->getMessage(),
                'status' => HttpFoundation\Response::HTTP_INTERNAL_SERVER_ERROR,
                'detail' => 'The server encountered an internal error or misconfiguration and was unable to the request.',
                'instance' => $_SERVER['REQUEST_URI'],
                'code' => $ex->getCode(),
            ],
        ];

        // Let the exception modify the output if it wants to.
        if (in_array("pointybeard\Symphony\Extensions\Api_Framework\Interfaces\ModifiesExceptionOutputInterface", class_implements($ex))) {
            $output = $ex->modifyOutput($output);
        }

        if (self::$debug) {
            if (is_object(Symphony::Database())) {
                $databaseDebug = Symphony::Database()->debug();
            }

            $output['error']['debug'] = [
                'thrown' => get_class($ex),
                'file' => $ex->getFile(),
                'line' => $ex->getLine(),
                'severity' => (
                    $e instanceof ErrorException
                        ? GenericErrorHandler::$errorTypeStrings[$ex->getSeverity()]
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
                $e instanceof SymphonyErrorPage &&
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
                    $output['error']['debug']['context'] = [];
                    foreach ($xmlLines as $n => $l) {
                        $output['error']['debug']['context'][sprintf('%04d', $n + 1)] = $l;
                    }
                }
            }
        }

        // Send to logs only if it is not an ApiFrameworkException or the status code is a 5XX
        if (false == ($ex instanceof Exceptions\ApiFrameworkException) || $ex->getHttpStatusCode() >= HttpFoundation\Response::HTTP_INTERNAL_SERVER_ERROR) {
            $request = new HttpFoundation\JsonResponse();
            Symphony::Log()->pushExceptionToLog($ex, true, false, false, ['output' => $output, 'request' => [
                'headers' => $request->headers->all(),
                'query' => $request->query ? $request->query->all() : null,
                'request' => $request->request ? $request->request->all() : null,
                'raw' => (string) $request,
            ]]);
        }

        // output and die
        header('Content-Type: application/problem+json');
        http_response_code($output['error']['status']);
        echo json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        exit(255);
    }
}
