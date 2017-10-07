<?php
namespace Symphony\ApiFramework\Lib;

use \Symphony;

/**
 * This extends the core Symphony class to give us a vector to
 * overload various functionality. There is a certain amount of code
 * duplication, taken from Frontend, since $_page is a private variable
 * and thus we cannot extend the Frontend class and inherit it's core features.
 */
class JsonFrontend extends Symphony
{
    /**
     * An instance of the ApiFramework\Lib\JsonFrontendPage class
     * @var JsonFrontendPage
     */
    protected static $_page;

    /**
     * Code duplication from core Frontend class.
     */
    protected function __construct()
    {
        parent::__construct();
        $this->_env = [];
    }

    /**
     * Code duplication from core Frontend class, however it returns an
     * instance of JsonFrontendPage rather than FrontendPage.
     */
    public function display($page)
    {
        self::$_page = new JsonFrontendPage;
        Symphony::ExtensionManager()->notifyMembers('FrontendInitialised', '/frontend/');
        $output = self::$_page->generate($page);

        return $output;
    }

    /**
     * Code duplication from core Frontend class, however it returns an
     * instance of self rather than hard coding the class name.
     */
    public static function instance()
    {
        if (!(self::$_instance instanceof self)) {
            self::$_instance = new self;
        }

        return self::$_instance;
    }

    /**
     * Code duplication from core Frontend class.
     */
    public static function Page()
    {
        return self::$_page;
    }

    /**
     * Code duplication from core Frontend class.
     */
    public static function isLoggedIn()
    {
        if (isset($_REQUEST['auth-token']) && $_REQUEST['auth-token'] && strlen($_REQUEST['auth-token']) == 8) {
            return self::loginFromToken($_REQUEST['auth-token']);
        }

        return Symphony::isLoggedIn();
    }
}
