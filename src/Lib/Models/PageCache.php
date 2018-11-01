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
        unset($headers['X-API-Framework-Render-Time']);

        foreach ($headers as $name => $value) {
            Lib\JsonFrontend::Page()->addHeaderToPage(
                $name,
                $value
            );
        }

        Lib\JsonFrontend::Page()->addRenderTimeToHeaders();

        return $this->contents;
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
}
