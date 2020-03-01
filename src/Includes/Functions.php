<?php

declare(strict_types=1);

namespace pointybeard\Symphony\Extensions\Api_Framework;

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

        // #1808
        if (isset($_SERVER['HTTP_MOD_REWRITE'])) {
            throw new Exception('mod_rewrite is required, however is not enabled.');
        }

        $output = JsonFrontend::instance()->display((string)getCurrentPage());

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

        // This will render new headers.
        JsonFrontend::Page()->renderHeaders();

        echo $output;
    }
}
