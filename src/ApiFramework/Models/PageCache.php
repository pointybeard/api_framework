<?php declare(strict_types=1);
namespace Symphony\ApiFramework\ApiFramework\Models;

use SymphonyPDO;
use Symphony\ApiFramework\ApiFramework;
use Symphony\ClassMapper\ClassMapper as ClassMapper;

final class PageCache extends ClassMapper\AbstractModel
{
    use ClassMapper\Traits\hasModelTrait;

    const SECTION = "page-cache";

    protected static function getCustomFieldMapping() : array
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

    protected function getData() : array
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

    public static function loadFromPage($page) : ?self
    {
        return self::fetch([
            ['page', $page, \PDO::PARAM_STR]
        ])->current();
    }

    public static function fetchExpired() : SymphonyPDO\Lib\ResultIterator
    {
        return (new self)
            ->appendFilter(new ClassMapper\Filters\FilterNow(
                'dateExpiresAt',
                ClassMapper\Filter::OPERATOR_AND,
                ClassMapper\Filter::COMPARISON_OPERATOR_LT
            ))
            ->filter()
        ;
    }

    public static function deleteExpired() : ?int
    {
        $expired = self::fetchExpired();
        foreach ($expired as $c) {
            $c->delete();
        }
        return $expired->count();
    }

    public static function removeAPIFrameworkHeadersFromJsonString($headers) : string
    {
        return json_encode(
            self::removeAPIFrameworkHeadersFromArray(json_decode($headers, true)),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
        );
    }

    public static function removeAPIFrameworkHeadersFromArray(array $headers) : array
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
            ApiFramework\JsonFrontend::Page()->addHeaderToPage(
                $name,
                $value
            );
        }

        ApiFramework\JsonFrontend::Page()->addRenderTimeToHeaders();

        return $this->contents;
    }

    public function expire() : self
    {
        return $this
            ->dateExpiresAt("now")
            ->save()
        ;
    }

    public function hasExpired() : bool
    {
        return (bool)(strtotime($this->dateExpiresAt) < time());
    }
}
