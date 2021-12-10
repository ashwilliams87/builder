<?php

namespace App\Http\JsonRpc\Result;

use \Exception as Error;

class BookIrbisQueryResult
{
    //private $result;
    private $recordsCount;
    private $records;

    public function getRows($fields)
    {
        //парсим контент из респонса в марк-записи ирбис
        $rows = [];

        foreach ($this->records as $item) {
            //Теория регионального развития
            IrbisRecord::init($item['Content']);
            $id = IrbisRecord::book_id();
            foreach ($fields as $field) {
                if (!method_exists(IrbisRecord::class, $field)) {
                    throw new Error('Нет такого поля в Irbis записи ' . $field);
                }
                $rows[$id][$field] = IrbisRecord::$field();
            }
        }

        IrbisRecord::destroy();

        return [
            'recordsCount' => $this->recordsCount,
            'records' => $rows
        ];
    }

    public function getMarcRecords()
    {
        //парсим контент из респонса в марк-записи ирбис
        $rows = [];

        foreach ($this->records as $item) {
            //Теория регионального развития
            IrbisRecord::init($item['Content']);
            $id = IrbisRecord::book_id();
            $rows[$id] = $item['Content'];
        }

        IrbisRecord::destroy();

        return [
            'recordsCount' => $this->recordsCount,
            'records' => $rows
        ];
    }

    public function getRawRecords()
    {
        return $this->records;
    }

    public function getOneRecord($fields): array
    {
        $records = $this->records;

        $rawRecord = reset($records);
        $record = [];

        //Теория регионального развития
        IrbisRecord::init($rawRecord['Content']);
        foreach ($fields as $field) {
            if (!method_exists(IrbisRecord::class, $field)) {
                throw new Error('Нет такого поля в Irbis записи ' . $field);
            }
            $record[$field] = IrbisRecord::$field();
        }

        IrbisRecord::destroy();

        return $record;
    }

    /**
     * @param $recordCounts
     * @param $records
     * @return BookIrbisQueryResult
     */
    public static function createResult($recordCounts, $records)
    {
        return new self($recordCounts, $records);
    }

    private function __construct($recordCounts, $records)
    {
        $this->recordsCount = $recordCounts;
        $this->records = $records;
    }

}
