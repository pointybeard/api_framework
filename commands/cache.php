<?php declare(strict_types=1);

namespace Symphony\Console\Commands\Api_Framework;

use SymphonyPDO;
use Extension_API_Framework;

use Symphony\Console as Console;
use Symphony\Console\AbstractInputType as Type;
use Symphony\ApiFramework\ApiFramework\Models\PageCache;
use pointybeard\Helpers\Cli;

class Cache extends Console\AbstractCommand implements Console\Interfaces\AuthenticatedCommandInterface
{
    use Console\Traits\hasCommandRequiresAuthenticateTrait;

    const ACTION_CLEAN = "clean";
    const ACTION_PURGE = "purge";

    public function __construct() {
        parent::__construct(
            "1.0.0",
            "maintentance script for API Framework cache entries",
            "symphony api_framework cache purge"
        );
    }

    public function init() : bool
    {
        parent::init();
        $this
            ->addArgument(
                'action',
                Type::FLAG_REQUIRED,
                "action to run on the cache entries. Can be either 'clean' (removes expired or duplicate entries) or 'purge' (delete all cache entries)",
                function(Type $input, Console\AbstractInput $context) {
                    if(!in_array(
                        $context->getArgument('action'), [
                            self::ACTION_CLEAN,
                            self::ACTION_PURGE
                        ]
                    )) {
                        throw new Console\Exceptions\ConsoleException(
                            "action must be either purge or clean."
                        );
                    }
                    return $context->getArgument('action');
                }
            )
        ;
        return true;
    }

    public function execute(Console\Interfaces\InputInterface $input) : bool
    {

        switch($input->getArgument('action')) {
            case self::ACTION_CLEAN:
                $cacheEntries = PageCache::fetchExpired();
                break;

            case self::ACTION_PURGE:
                $cacheEntries = PageCache::all();
                break;
        }

        (new Cli\Message\Message)
            ->message(sprintf('%d cache entries located.', $cacheEntries->count()))
            ->display()
        ;

        if ($cacheEntries->count() <= 0) {
            (new Cli\Message\Message)
                ->message('Nothing to do. Exiting.')
                ->foreground(Cli\Colour\Colour::FG_YELLOW)
                ->display()
            ;
            return true;
        }

        foreach($cacheEntries as $c) {
            $c->delete();
        }

        return true;
    }

}
