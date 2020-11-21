<?php

declare(strict_types=1);

namespace pointybeard\Symphony\Extensions\Api_Framework;

use Symfony\Component\HttpFoundation;

/**
 * This extends the JsonFrontendPage class, adding caching features.
 */
class CacheableJsonFrontendPage extends JsonFrontendPage
{

    public function isCacheHit(HttpFoundation\Request $request)
    {
        return Models\PageCache::loadCurrentFromPageAndQueryString(
            rtrim($request->getPathInfo(), "/"),
            $this->normaliseQueryString($request)
        );
    }

    public function normaliseQueryString(HttpFoundation\Request $request): string
    {
        // Grab any query string values. We'll store this with the
        // cache to improve hit accuracy.
        $query = $request->query->all();
        asort($query, SORT_NATURAL | SORT_FLAG_CASE);

        // symphony-page is always set, otherwise we wouldn't be here. Get rid
        // of it to make cache entry slightly less verbose.
        unset($query['symphony-page']);

        $normalised = '';
        foreach ($query as $key => $value) {
            $normalised .= "&{$key}={$value}";
        }
        $normalised = trim($normalised, '&');

        return $normalised;
    }

    public function render(HttpFoundation\Request $request, HttpFoundation\Response $response): HttpFoundation\Response
    {
        // Logic for checking if there is cached page data
        $cache = $this->isCacheHit($request);

        if (false == ($cache instanceof Models\PageCache)) {
            throw new \Exception("No cache record, so why you calling ->render(). This should NEVER happen!");
        }

        $response->headers->set('X-API-Framework-Cache', "hit");

        return $cache->render($response);
    }

    public function saveToCache(HttpFoundation\Request $request, HttpFoundation\Response $response): HttpFoundation\Response
    {

        foreach($response->headers->all() as $name => $value) {
            $headers[$name] = $value[0];
        }

        // Update the cache
        (new Models\PageCache())
            ->headers(json_encode($headers, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES))
            ->contents($response->getContent())
            ->queryString($this->normaliseQueryString($request))
            ->dateCreatedAt('now')
            ->dateExpiresAt(\DateTimeObj::format(
                \Extension_API_Framework::calculateNextCacheExpiryTime(),
                DATE_RFC2822
            ))
            ->page($request->getPathInfo())
            ->save()
        ;
        
        $response->headers->set('X-API-Framework-Cache', "miss");

        return $response;
    }
}
