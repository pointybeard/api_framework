<?php
namespace Symphony\ApiFramework\Lib;

/**
 * This extends the JsonFrontendPage class, adding caching features
 */
class CacheableJsonFrontendPage extends JsonFrontendPage
{
    public function generate($page = null)
    {

        // Grab any query string values. We'll store this with the
        // cache to improve hit accuracy.
        $query = [];
        parse_str($_SERVER['QUERY_STRING'], $query);
        asort($query, SORT_NATURAL | SORT_FLAG_CASE);

        // symphony-page is always set, otherwise we wouldn't be here. Get rid
        // of it to make cache entry slightly less verbose.
        unset($query['symphony-page']);

        $orderedQueryString = "";
        foreach ($query as $key => $value) {
            $orderedQueryString .= "&{$key}={$value}";
        }
        $orderedQueryString = trim($orderedQueryString, '&');

        // Logic for checking if there is cached page data
        $cache = Models\PageCache::loadCurrentFromPageAndQueryString($page, $orderedQueryString);

        if ($cache instanceof Models\PageCache) {
            JsonFrontend::Page()->addHeaderToPage(
                'X-API-Framework-Cache',
                'hit'
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
            ->queryString($orderedQueryString)
            ->dateCreatedAt('now')
            ->dateExpiresAt(date(DATE_RFC2822, strtotime("+1 hour")))
            ->page($page)
            ->save()
        ;

        JsonFrontend::Page()->addHeaderToPage(
            'X-API-Framework-Cache',
            'miss'
        );

        return $output;
    }
}
