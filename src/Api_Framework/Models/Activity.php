<?php

declare(strict_types=1);

namespace pointybeard\Symphony\Extensions\Api_Framework\Models;

use Symfony\Component\HttpFoundation;

use SymphonyPDO;
use pointybeard\Symphony\Classmapper;
use pointybeard\Symphony\Extensions\Api_Framework;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class Activity extends Classmapper\AbstractModel implements Classmapper\Interfaces\FilterableModelInterface, Classmapper\Interfaces\SortableModelInterface
{
    use Classmapper\Traits\HasModelTrait;
    use Classmapper\Traits\HasFilterableModelTrait;
    use Classmapper\Traits\HasSortableModelTrait;

    public function getSectionHandle(): string
    {
        return 'activity';
    }

    protected static function getCustomFieldMapping(): array
    {
        return [
            
            // 47: Date Requested At (date)
            'date-requested-at' => [
                //'databaseFieldName' => 'value/date',
                'classMemberName' => 'dateRequestedAt',
                'flags' => self::FLAG_REQUIRED | self::FLAG_STR | self::FLAG_SORTBY | self::FLAG_SORTDESC,
            ],
            // 50: Request URI (textbox)
            'request-uri' => [
                //'databaseFieldName' => 'handle/value/value_formatted/word_count',
                'classMemberName' => 'requestUri',
                'flags' => self::FLAG_REQUIRED | self::FLAG_STR,
            ],
            // 51: Request (textbox)
            'request' => [
                //'databaseFieldName' => 'handle/value/value_formatted/word_count',
                'classMemberName' => 'request',
                'flags' => self::FLAG_REQUIRED | self::FLAG_STR,
            ],
            // 48: Request Type (select)
            'request-method' => [
                //'databaseFieldName' => 'handle/value',
                'classMemberName' => 'requestMethod',
                'flags' => self::FLAG_REQUIRED | self::FLAG_STR,
            ],
            // 52: Response (textbox)
            'response' => [
                //'databaseFieldName' => 'handle/value/value_formatted/word_count',
                'classMemberName' => 'response',
                'flags' => self::FLAG_NULL | self::FLAG_STR,
            ],
            // 49: Response Code (textbox)
            'response-code' => [
                //'databaseFieldName' => 'handle/value/value_formatted/word_count',
                'classMemberName' => 'responseCode',
                'flags' => self::FLAG_NULL | self::FLAG_STR,
            ],

        ];
    }
}
