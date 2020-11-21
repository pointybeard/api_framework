<?php

declare(strict_types=1);

namespace pointybeard\Symphony\Extensions\Console\Commands\Api_Framework;

use pointybeard\Symphony\Extensions\Console as Console;
use pointybeard\Helpers\Cli;
use pointybeard\Helpers\Cli\Input;
use pointybeard\Helpers\Cli\Colour\Colour;
use pointybeard\Helpers\Cli\Input\AbstractInputType as Type;
use pointybeard\Helpers\Foundation\BroadcastAndListen;
use pointybeard\Symphony\Extensions\Console\Commands\Console\Symphony;

use pointybeard\Symphony\Extensions\Api_Framework\Router;
use pointybeard\Symphony\Extensions\Api_Framework\Route;
use pointybeard\Symphony\Extensions\Api_Framework\Controllers;

class Routes extends Console\AbstractCommand implements Console\Interfaces\AuthenticatedCommandInterface, BroadcastAndListen\Interfaces\AcceptsListenersInterface
{
    use BroadcastAndListen\Traits\HasListenerTrait;
    use BroadcastAndListen\Traits\HasBroadcasterTrait;
    use Console\Traits\hasCommandRequiresAuthenticateTrait;

    const ACTION_EXPORT = 'export';

    public function __construct()
    {
        parent::__construct();
        $this
            ->description('Helper command for working with page routes')
            ->version('1.0.0')
            ->example(
                'symphony -t 4141e465 api_framework routes export'
            )
            ->support("If you believe you have found a bug, please report it using the GitHub issue tracker at https://github.com/pointybeard/api_framework/issues, or better yet, fork the library and submit a pull request.\r\n\r\nCopyright 2017-2020 Alannah Kearney. See ".realpath(__DIR__.'/../LICENCE')." for software licence information.\r\n")
        ;
    }

    public function usage(): string
    {
        return 'Usage: symphony [OPTIONS]... api_framework routes ACTION';
    }

    public function init(): void
    {
        parent::init();

        $this
            ->addInputToCollection(
                Input\InputTypeFactory::build('Argument')
                    ->name('action')
                    ->flags(Input\AbstractInputType::FLAG_REQUIRED)
                    ->description('The action to perform. Currently only supports the export action.')
                    ->validator(
                        function (Type $input, Input\AbstractInputHandler $context) {
                            if (!in_array(
                                $context->find('action'),
                                [
                                    self::ACTION_EXPORT
                                ]
                            )) {
                                throw new Console\Exceptions\ConsoleException(
                                    'action must be export'
                                );
                            }

                            return $context->find('action');
                        }
                    )
            )
            ->addInputToCollection(
                Input\InputTypeFactory::build('LongOption')
                    ->name('output')
                    ->short('o')
                    ->flags(Input\AbstractInputType::FLAG_OPTIONAL | Input\AbstractInputType::FLAG_VALUE_REQUIRED)
                    ->description('path to save routes file to')
                    ->default(null)
            )
        ;
    }

    public function execute(Input\Interfaces\InputHandlerInterface $input): bool
    {

        $routes = new Router;

        // Load routes
        $loader = include WORKSPACE . "/routes.php";
        $loader($routes);

        \Symphony::ExtensionManager()->notifyMembers(
            'ModifyRoutes',
            '/backend/',
            ['routes' => &$routes]
        );

        // Check to see if we have default routes enabled
        if(false == \Extension_API_Framework::isDefaultRoutesDisabled()) {
            $routes->buildDefaultRoutes();
        }

        if (null === $input->find('output')) {
            echo (string)$routes.PHP_EOL;

        } else {
            file_put_contents($input->find('output'), (string)$routes);
            $this->broadcast(
                Symphony::BROADCAST_MESSAGE,
                E_NOTICE,
                (new Cli\Message\Message())
                    ->message(filesize($input->find('output')).' bytes written to '.$input->find('output'))
                    ->foreground(Colour::FG_GREEN)
            );
        }

        return true;
    }
}
