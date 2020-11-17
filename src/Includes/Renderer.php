<?php

declare(strict_types=1);

namespace pointybeard\Symphony\Extensions\Api_Framework;

use Symfony\Component\HttpFoundation;

if (!function_exists(__NAMESPACE__.'\renderer_json')) {
    function renderer_json(?string $mode): void
    {
        if (null !== $mode && 0 == strcasecmp('administration', $mode)) {
            throw new Exceptions\InvalidModeException('JSON Renderer launcher is only available on the frontend');
        }

        // Initialise the Frontend object
        JsonFrontend::instance();

        // Use the JSON exception and error handlers instead of the \Symphony one.
        ExceptionHandler::initialise();
        ErrorHandler::initialise();

        // This ensures the composer autoloader for the framework is included
        \Extension_API_Framework::init();

        if(true == \Extension_API_Framework::isExceptionDebugOutputEnabled()) {
            ExceptionHandler::enableDebugOutput();
        }

        // #1808
        if (isset($_SERVER['HTTP_MOD_REWRITE'])) {
            throw new Exception('mod_rewrite is required, however is not enabled.');
        }

        $output = JsonFrontend::instance()->display((string)getCurrentPage());

        if(true == (JsonFrontend::Page() instanceof JsonFrontendPage)) {

            /*
             * This is just prior to the page headers being re-rendered
             * @delegate JsonFrontendPreRenderHeaders
             * @param string $context
             * '/json_frontend/'
             */
            \Symphony::ExtensionManager()->notifyMembers(
                'JsonFrontendPreRenderHeaders',
                '/json_frontend/',
                []
            );

            $profile = (object) array_combine(
                ['message', 'elapsed', 'created', 'type', 'queries', 'memory'],
                \Symphony::Profiler()->retrieveLast()
            );

            JsonFrontend::Page()->addHeaderToPage(
                'X-API-Framework-Render-Time',
                number_format($profile->elapsed, 4)
            );

            if(true == ($output instanceof HttpFoundation\Response)) {

                // Transfer all the page headers over to the Response object
                foreach(JsonFrontend::Page()->headers() as $h) {
                    $output->headers->set(...explode(":", $h['header'], 2));
                }
                $output->send();
                
            } else {
                // This will render new headers.
                JsonFrontend::Page()->renderHeaders();
                echo $output;
            }

        } else {
            echo $output;
        }

        // Make sure nothing happens after calling this method. It shouldn't
        // but just in case.
        exit;
    }
}
