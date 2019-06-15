<?php

declare(strict_types=1);

namespace pointybeard\Symphony\Extensions\Api_Framework\Models;

use SymphonyPDO;
use pointybeard\Symphony\Extensions\Api_Framework;
use pointybeard\Symphony\Classmapper;

final class PageCache extends Classmapper\AbstractModel
{
    use Classmapper\Traits\hasModelTrait;
    use Classmapper\Traits\HasFilterableModelTrait;

    public function getSectionHandle(): string
    {
        return 'page-cache';
    }

    protected static function getCustomFieldMapping(): array
    {
        return [
            'created-at' => [
                'classMemberName' => 'dateCreatedAt',
                'flags' => self::FLAG_REQUIRED,
            ],

            'contents' => [
                'flags' => self::FLAG_REQUIRED,
            ],

            'page' => [
                'flags' => self::FLAG_REQUIRED,
            ],

            'headers' => [
                'flags' => self::FLAG_REQUIRED,
            ],

            'expires-at' => [
                'classMemberName' => 'dateExpiresAt',
                'flags' => self::FLAG_NULL,
            ],

            'query-string' => [
                'flags' => self::FLAG_STR | self::FLAG_NULL,
            ],

            'meta' => [
                'flags' => self::FLAG_STR | self::FLAG_ARRAY | self::FLAG_NULL,
            ],
        ];
    }

    protected function getData(): array
    {
        $data = parent::getData();
        $data['headers'] = self::removeAPIFrameworkHeadersFromJsonString($data['headers']);

        return $data;
    }

    public static function loadCurrentFromPageAndQueryString(string $page, ?string $queryString): ?self
    {
        self::findSectionFields();

        $pageCache = (new self())
            ->appendFilter(Classmapper\FilterFactory::build('Basic', 'page', $page))
            ->appendFilter(Classmapper\FilterFactory::build(
                'Now',
                'dateExpiresAt',
                Classmapper\Filters\Basic::COMPARISON_OPERATOR_GT
            ))
        ;

        if (empty($queryString)) {
            $pageCache->appendFilter(
                Classmapper\FilterFactory::build('IsNull', 'queryString')
            );
        } else {
            $pageCache->appendFilter(
                Classmapper\FilterFactory::build('Basic', 'queryString', $queryString)
            );
        }

        // Check for multiple valid cache entries
        if ($pageCache->filter()->count() > 1) {
            // Delete them all and let a new cache entry be produced.
            $pageCache->filter()->each(function (self $f) {
                $r->delete();
            });

            return null;
        }

        $result = $pageCache->filter()->current();

        return $result instanceof self ? $result : null;
    }

    public static function loadFromPage(string $page): ?self
    {
        return self::fetch(
            Classmapper\FilterFactory::build('Basic', 'page', $page)
        )->current();
    }

    public static function fetchExpired(): SymphonyPDO\Lib\ResultIterator
    {
        return self::fetch(
            Classmapper\FilterFactory::build(
                'Now',
                'dateExpiresAt',
                Classmapper\Filters\Basic::COMPARISON_OPERATOR_LT
            )
        );
    }

    public static function deleteExpired(): ?int
    {
        $expired = self::fetchExpired();
        foreach ($expired as $c) {
            $c->delete();
        }

        return $expired->count();
    }

    public static function removeAPIFrameworkHeadersFromJsonString($headers): string
    {
        return json_encode(
            self::removeAPIFrameworkHeadersFromArray(json_decode($headers, true)),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
        );
    }

    public static function removeAPIFrameworkHeadersFromArray(array $headers): array
    {
        foreach ($headers as $name => $value) {
            if (preg_match('@^X-API-Framework-@i', $name)) {
                unset($headers[$name]);
            }
        }

        return $headers;
    }

    public function render()
    {
        // Headers
        $headers = json_decode($this->headers, true);
        $headers['Last-Modified'] = date(DATE_RFC2822, strtotime($this->dateCreatedAt));
        $headers['Expires'] = date(
            DATE_RFC2822,
            null === $this->dateExpiresAt
                ? strtotime('+1 year')
                : strtotime($this->dateExpiresAt)
        );

        foreach ($headers as $name => $value) {
            ApiFramework\JsonFrontend::Page()->addHeaderToPage(
                $name,
                $value
            );
        }

        ApiFramework\JsonFrontend::Page()->addRenderTimeToHeaders();

        return $this->contents;
    }

    public function expire(): self
    {
        return $this
            ->dateExpiresAt('now')
            ->save()
        ;
    }

    public function hasExpired(): bool
    {
        return (bool) (strtotime($this->dateExpiresAt) < time());
    }
}
