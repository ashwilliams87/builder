<?php

namespace App\Http\JsonRpc\Query;

use \Exception as Error;

abstract class IrbisQuery
{
    protected $query = [];
    protected $queryCustom = '';
    protected $limit = 10;
    protected $offset = 1;

    /**
     * @param $limit
     * @param $offset
     * @return IrbisQuery
     * @throws Error
     */
    public function limit($limit, $offset = 1)
    {
        if ($limit > 100) {
            throw new Error('Предельное количество записей для запроса 100');
        }

        $this->limit = $limit;
        $this->offset = $offset;
        return $this;
    }

    /**
     * @param $flag
     * @param $filterValues
     */
    protected function filterOr($flag, $filterValues)
    {
        $this->addQueryPart($flag, $filterValues,
            function ($innerFlag, array $values) {
                $string = [];
                foreach ($values as $item) {
                    $string[] = "<.>$innerFlag=$item<.>";
                }

                return (empty($string) ? '' : '(' . implode('+', $string) . ')');
            });
    }

    /**
     * @param $flag
     * @param string $value
     * @param callable|null $callback
     * @return $this
     */
    protected function addQueryPart($flag, $value, callable $callback = null)
    {
        $this->query[] = [
            'flag' => $flag,
            'value' => $value,
            'callback' => $callback,
        ];

        return $this;
    }

    /** Формируем строку поиска на языке ИРБИС запросов
     * @return string
     */
    protected function getQueryString()
    {
        $queryString = [];

        //для всего пула частей запроса, мы либо имплодим
        //либо если передан callback то генерим по коллбэку
        foreach ($this->query as $item) {
            if (is_callable($item['callback'])) {
                $queryString[] = $item['callback']($item['flag'], $item['value']);
                continue;
            }
            $queryString[] = "(<.>{$item['flag']}={$item['value']}$<.>)";
        }

        //если был кастомный запрос
        if (!empty($this->queryCustom)) {
            $queryString[] = $this->queryCustom;
        }

        $queryString = implode('*', $queryString);

        return $queryString;
    }

    abstract function getQueryResult($sourceClass);
}
