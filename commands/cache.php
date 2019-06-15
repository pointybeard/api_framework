<?php

declare(strict_types=1);

namespace Symphony\Console\Commands\Api_Framework;

use pointybeard\Helpers\Cli\Input;
use pointybeard\Helpers\Cli\Input\AbstractInputType as Type;
use Symphony\Console as Console;
use pointybeard\Symphony\Extensions\Api_Framework\Models\PageCache;
use pointybeard\Helpers\Cli;

class Cache extends Console\AbstractCommand implements Console\Interfaces\AuthenticatedCommandInterface
{
    use Console\Traits\hasCommandRequiresAuthenticateTrait;

    const ACTION_CLEAN = 'clean';
    const ACTION_PURGE = 'purge';

    public function __construct()
    {
        parent::__construct();
        $this
            ->description('maintentance script for API Framework cache entries')
            ->version('1.0.0')
            ->example(
                'symphony -t 4141e465 api_framework cache purge'.PHP_EOL.
                'symphony --user=admin 4141e465 api_framework cache purge'
            )
            ->support("If you believe you have found a bug, please report it using the GitHub issue tracker at https://github.com/pointybeard/api_framework/issues, or better yet, fork the library and submit a pull request.\r\n\r\nCopyright 2017-2019 Alannah Kearney. See ".realpath(__DIR__.'/../LICENCE')." for software licence information.\r\n")
        ;
    }

    public function usage(): string
    {
        return 'Usage: symphony [OPTIONS]... api_framework cache ACTION';
    }

    public function init(): void
    {
        parent::init();

        $this
            ->addInputToCollection(
                Input\InputTypeFactory::build('Argument')
                    ->name('action')
                    ->flags(Input\AbstractInputType::FLAG_REQUIRED)
                    ->description('action to run on the cache entries. Can be either clean (removes expired or duplicate entries) or purge (delete all cache entries)')
                    ->validator(
                        function (Type $input, Input\AbstractInputHandler $context) {
                            if (!in_array(
                                $context->find('action'),
                                [
                                    self::ACTION_CLEAN,
                                    self::ACTION_PURGE,
                                ]
                            )) {
                                throw new Console\Exceptions\ConsoleException(
                                    'action must be either purge or clean.'
                                );
                            }

                            return $context->find('action');
                        }
                    )
            )
        ;
    }

    public function execute(Input\Interfaces\InputHandlerInterface $input): bool
    {
        switch ($input->find('action')) {
            case self::ACTION_CLEAN:
                $cacheEntries = PageCache::fetchExpired();
                break;

            case self::ACTION_PURGE:
                $cacheEntries = PageCache::all();
                break;
        }

        (new Cli\Message\Message())
            ->message(sprintf('%d cache entries located.', $cacheEntries->count()))
            ->display()
        ;

        if ($cacheEntries->count() <= 0) {
            (new Cli\Message\Message())
                ->message('Nothing to do. Exiting.')
                ->foreground(Cli\Colour\Colour::FG_YELLOW)
                ->display()
            ;

            return true;
        }

        foreach ($cacheEntries as $c) {
            $c->delete();
        }

        return true;
    }
}
