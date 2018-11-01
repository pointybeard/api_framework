<?php
namespace Symphony\ApiFramework\Lib;

/**
 * This extends the JsonFrontendPage class, adding caching features
 */
class CacheableJsonFrontendPage extends JsonFrontendPage
{
    public function generate($page = null)
    {
        // Logic for checking if there is cached page data
        $cache = Models\PageCache::loadFromPage($page);
        if ($cache instanceof Models\PageCache) {
            JsonFrontend::Page()->addHeaderToPage(
                'X-API-Framework-Cached',
                'yes'
            );
            return $cache->render();
        }

        $output = parent::generate($page);

        $headers = [];
        foreach ($this->headers() as $h) {
            list($name, $value) = explode(':', $h['header'], 2);
            $headers[$name] = $value;
        }

        // Update the cache
        (new Models\PageCache)
            ->headers(json_encode($headers, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES))
            ->contents($output)
            ->dateCreatedAt('now')
            ->dateExpiresAt('+1 hour')
            ->page($page)
            ->save()
        ;

        JsonFrontend::Page()->addHeaderToPage(
            'X-API-Framework-Cached',
            'no'
        );

        return $output;
    }
}
