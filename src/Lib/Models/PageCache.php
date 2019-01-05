<?php
namespace Symphony\ApiFramework\Lib\Models;

use SymphonyPDO;
use Symphony\ApiFramework\Lib;
use Symphony\ClassMapper\Lib as ClassMapper;

final class PageCache extends ClassMapper\AbstractClassMapper
{
    use ClassMapper\Traits\hasClassMapperTrait;

    const SECTION = "page-cache";

    protected static function getCustomFieldMapping()
    {
        return [

            'created-at' => [
                'classMemberName' => 'dateCreatedAt'
            ],

            'expires-at' => [
                'classMemberName' => 'dateExpiresAt',
                'flags' => self::FLAG_NULL
            ],

        ];
    }

    protected function getData()
    {
        $data = parent::getData();
        $data['headers'] = self::removeAPIFrameworkHeadersFromJsonString($data['headers']);
        return $data;
    }

    public static function loadCurrentFromPageAndQueryString($page, $queryString)
    {
        self::findSectionFields();

        $queryStringSQL = "%2\$s.value = :query_string";
        if (empty($queryString)) {
            $queryStringSQL = "(%2\$s.value IS NULL OR %2\$s.value = '')";
        }

        $db = SymphonyPDO\Loader::instance();
        $query = $db->prepare(self::fetchSQL(sprintf(
            " %1\$s.value = :page AND {$queryStringSQL} AND %3\$s.date > NOW()",
            self::findJoinTableFieldName('page'),
            self::findJoinTableFieldName('query-string'),
            self::findJoinTableFieldName('expires-at')
        )));

        $query->bindValue(':page', $page, \PDO::PARAM_STR);

        if (!empty($queryString)) {
            $query->bindValue(':query_string', $queryString, \PDO::PARAM_STR);
        }

        $query->execute();

        $result = (new SymphonyPDO\Lib\ResultIterator(__CLASS__, $query));

        if ($result->count() > 1) {
            // Multiple valid cache entries. Delete them all and let a new cache
            // entry be produced.
            foreach ($result as $r) {
                $r->delete();
            }

            return false;
        }

        return $result->current();
    }

    public static function loadFromPage($page)
    {
        self::findSectionFields();
        $db = SymphonyPDO\Loader::instance();
        $query = $db->prepare(self::fetchSQL(self::findJoinTableFieldName('page') . ".value = :page") . " LIMIT 1");
        $query->bindValue(':page', $page, \PDO::PARAM_STR);
        $query->execute();

        return (new SymphonyPDO\Lib\ResultIterator(__CLASS__, $query))->current();
    }

    public static function fetchExpired()
    {
        self::findSectionFields();
        $db = SymphonyPDO\Loader::instance();
        $query = $db->prepare(self::fetchSQL(self::findJoinTableFieldName('expires-at') . ".date < NOW() "));
        $query->execute();

        return (new SymphonyPDO\Lib\ResultIterator(__CLASS__, $query));
    }

    public static function deleteExpired()
    {
        $expired = self::fetchExpired();
        foreach ($expired as $c) {
            $c->delete();
        }
        return $expired->count();
    }

    public static function removeAPIFrameworkHeadersFromJsonString($headers)
    {
        return json_encode(
            self::removeAPIFrameworkHeadersFromArray(json_decode($headers, true)),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
        );
    }

    public static function removeAPIFrameworkHeadersFromArray(array $headers)
    {
        foreach ($headers as $name => $value) {
            if (preg_match("@^X-API-Framework-@i", $name)) {
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
            is_null($this->dateExpiresAt)
                ? strtotime("+1 year")
                : strtotime($this->dateExpiresAt)
        );

        foreach ($headers as $name => $value) {
            Lib\JsonFrontend::Page()->addHeaderToPage(
                $name,
                $value
            );
        }

        Lib\JsonFrontend::Page()->addRenderTimeToHeaders();

        return $this->contents;
    }

    public function expire()
    {
        return $this
            ->dateExpiresAt("now")
            ->save()
        ;
    }

    public function hasExpired()
    {
        return (bool)(strtotime($this->dateExpiresAt) < time());
    }
}
