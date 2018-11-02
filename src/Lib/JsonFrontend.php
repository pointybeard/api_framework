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

    // JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
    const DEFAULT_ENCODING_OPTIONS = 207;

    protected $encodingOptions = self::DEFAULT_ENCODING_OPTIONS;

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

        // When the parent construtor is called, the GenericExceptionHandler is
        // turned off, which is fine normally unless you want exceptions to
        // bubble up in a JSON api. Lets turn it back on.
        \GenericExceptionHandler::$enabled = true;

        $this->_env = [];
    }

    /**
     * Code duplication from core Frontend class, however it returns an
     * instance of JsonFrontendPage rather than FrontendPage.
     */
    public function display($page)
    {
        $oPage = (new \FrontendPage)->resolvePage($page);

        // GET Requests on pages that are of type 'cacheable' can be cached.
        $isCacheable = ($_SERVER['REQUEST_METHOD'] == 'GET' && in_array('cacheable', $oPage['type']));

        self::$_page = $isCacheable
            ? new CacheableJsonFrontendPage($oPage)
            : new JsonFrontendPage($oPage)
        ;

        self::$_page->addHeaderToPage(
            'X-API-Framework-Page-Renderer',
            array_pop(explode('\\', get_class(self::$_page)))
        );

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

    public function getEncodingOptions()
    {
        return $this->encodingOptions;
    }

    public function setEncodingOptions($options)
    {
        $this->encodingOptions = $options;
    }
}
