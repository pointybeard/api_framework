<?php

use Symphony\ApiFramework\Lib;

function renderer_json($mode)
{
    if (strtolower($mode) == 'administration') {
        throw new Lib\Exceptions\InvalidModeException('JSON Renderer launcher is only available on the frontend');
    }

    // Check if we should enable exception debug information
    $exceptionDebugEnabled = Symphony::isLoggedIn();

    // Use the JSON exception and error handlers instead of the Symphony one.
    Lib\ExceptionHandler::initialise($exceptionDebugEnabled);
    Lib\ErrorHandler::initialise($exceptionDebugEnabled);

    // #1808
    if (isset($_SERVER['HTTP_MOD_REWRITE'])) {
        throw new Exception("mod_rewrite is required, however is not enabled.");
    }

    $output = Lib\JsonFrontend::instance()->display(getCurrentPage());

    /**
     * This is just prior to the page headers being re-rendered
     * @delegate JsonFrontendPreRenderHeaders
     * @param string $context
     * '/json_frontend/'
     */
    Symphony::ExtensionManager()->notifyMembers(
        'JsonFrontendPreRenderHeaders',
        '/json_frontend/',
        []
    );

    // This will render new headers.
    Lib\JsonFrontend::Page()->renderHeaders();

    echo $output;
    return $renderer;
}
