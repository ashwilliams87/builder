<?php

namespace App\Http\JsonRpc\Query;

use App\Http\JsonRpc\Result\BookIrbisQueryResult;
use \Exception as Error;
use \Exception as Config_Error;

class IrbisBook extends IrbisQuery
{
    const BOOK_TITLE = 'T';
    const BOOK_PUB_YEAR = 'G';
    const BOOK_ID = 'I';
    const BOOK_KEYWORDS = 'K';
    const BOOK_IRBIS_TYPE = 'HD';

    /**
     * @return IrbisBook
     */
    public static function createBuilder()
    {
        return new self();
    }

    /**
     * @param $value
     * @return $this
     */
    public function bookTitle($value)
    {
        return $this->addQueryPart(self::BOOK_TITLE, $value);
    }

    /**
     * @param $value
     * @return $this
     */
    public function pubYear(string $value)
    {
        return $this->addQueryPart(self::BOOK_PUB_YEAR, $value);
    }

    public function bookKeywords(string $value)
    {
        foreach (explode(',', $value) as $keyword) {

            $this->addQueryPart(self::BOOK_KEYWORDS, trim($keyword));
        }

        return $this;
    }

    public function bookId(string $value)
    {
        return $this->addQueryPart(self::BOOK_ID, $value);
    }

    /**
     * @param $value
     * @return $this
     */
    public function custom(string $value)
    {
        $this->queryCustom = $value;
        return $this;
    }

    /**
     * @param array $publishTypes
     * @return $this
     */
    public function irbisType(array $publishTypes)
    {

        $this->filterOr(self::BOOK_IRBIS_TYPE, $publishTypes);
        return $this;
    }


    /**
     * @param array $arrayOfId
     * @return $this
     */
    public function inBookId(array $arrayOfId)
    {
        $this->filterOr(self::BOOK_ID, $arrayOfId);
        return $this;
    }

    /**
     * @return $this
     * @throws Exception
     * @throws Config_Error
     */
    public function dumpQueryStringToFile()
    {
        // Debuger::dumpToFile($this->getQueryString());
        return $this;
    }

    public function getQueryResult($sourceClass): BookIrbisQueryResult
    {
        return $sourceClass::getService()->getBookResult($this->getQueryString(), $this->limit, $this->offset);
    }
}
