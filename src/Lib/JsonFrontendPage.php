<?php
namespace Symphony\ApiFramework\Lib;

/**
 * This extends the core FrontendPage class of Symphony to give us a vector to
 * overload various functionality.
 */
class JsonFrontendPage extends \FrontendPage
{
    /**
     * Constructor function sets the `$is_logged_in` variable.
     */
    public function __construct()
    {
        // We need to skip the FrontendPage constructor as it turns off
        // our exception handling by creating a new instance of Frontend.
        // Instead, lets skip over to the parent->parent constructor.
        \XSLTPage::__construct();
        $this->is_logged_in = JsonFrontend::instance()->isLoggedIn();
    }
}
