<?php
namespace Symphony\ApiFramework\Lib;

use \Symphony;

/**
 * This extends the core Frontend class of Symphony to give us a vector to
 * overload various functionality. Unfortunately it relies on Frontend::$_page
 * being protected instead of private which is not available in the main
 * Symphony CMS 2.7.x release. Once it is, this code will be available in
 * stable releases for the API Framework.
 */
class JsonFrontend extends \Frontend
{
    public function display($page)
    {
        // Note to self: had to modify Frontend to make $_page protected instead
        // of private. Will consider making a patch for the offical Symphony
        // repo at some stage, but for now remember to update the core code
        self::$_page = new JsonFrontendPage;

        // Unfortunately we have to use some code duplication here since there
        // isnt't a way to inject a custom FrontendPage class and still call
        // the parent display() method.
        Symphony::ExtensionManager()->notifyMembers('FrontendInitialised', '/frontend/');
        $output = self::$_page->generate($page);

        return $output;
    }

    public static function instance()
    {
        if (!(self::$_instance instanceof self)) {
            self::$_instance = new self;
        }

        return self::$_instance;
    }
}
