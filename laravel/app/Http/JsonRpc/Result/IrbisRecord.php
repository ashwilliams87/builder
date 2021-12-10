<?php

namespace App\Http\JsonRpc\Result;

use App\Http\Helper\Bonch\jrecord;
use \Exception as Error;

class IrbisRecord
{
    /**@var jrecord $jRecord */
    private static $jRecord;

    /**
     * @param $number
     * @param $subField
     * @param int $index
     * @return mixed
     * @throws Error
     */
    public static function getSubField($number, $subField, $index = 0)
    {
        if (empty(self::$jRecord)) {
            throw new Error('Need to init');
        }

        return self::$jRecord->GetSubField($number, $index + 1, $subField);
    }

    /**
     * @param $number
     * @param int $index
     * @return mixed
     * @throws Error
     */
    public static function getField($number, $index = 0)
    {
        if (empty(self::$jRecord)) {
            throw new Error('Need to init');
        }

        return self::$jRecord->GetField($number, $index + 1);
    }

    /**
     * @param $field
     * @return jrecord
     * @throws Error
     */
    public static function getCountField($field)
    {
        if (empty(self::$jRecord)) {
            throw new Error('Need to init');
        }

        return self::$jRecord->GetFieldOccCount($field);
    }

    public static function init($content)
    {
        if (empty(self::$jRecord)) {
            self::$jRecord = new jrecord();
        }

        self::$jRecord->SetContent($content);
    }

    public static function destroy()
    {
        self::$jRecord = null;
    }

    /**
     * @return string
     * @throws Error
     */
    public static function book_id()
    {
        $id = self::getField(903);

        return empty($id) ? md5(self::getSubField(200, 'A')) : $id;
    }


    public static function book_name()
    {
        $bookName = '';
        $rec = self::$jRecord;

        if (!empty($rec->GetSubField(461, 1, 'C'))) {
            $bookName .= $rec->GetSubField(461, 1, 'C') . ' ' .
                $rec->GetSubField(200, 1, 'A') . ' ' .
                $rec->GetSubField(200, 1, 'V') . ': ' .
                $rec->GetSubField(461, 1, 'E');
        } else {
            $bookName .= $rec->GetSubField(200, 1, 'A') . ' ' . $rec->GetSubField(200, 1, 'V') . ': ' . $rec->GetSubField(200, 1, 'E');
        }

        return $bookName;
    }

    /**
     * @return mixed
     * @throws Error
     */
    public static function multipart_name()
    {
        return self::getSubField(461, 'C');
    }

    /**
     * @return mixed
     * @throws Error
     */
    public static function multipart_part_name()
    {
        $partName = '';
        $rec = self::$jRecord;

        if (!empty($rec->GetSubField(461, 1, 'C'))) {
            $partName .= $rec->GetSubField(200, 1, 'A');
        }

        return $partName;
    }


    /**
     * @return mixed
     * @throws Error
     */
    public static function multipart_title_info()
    {
        return self::getSubField(461, 'E');
    }

    /**
     * @return mixed
     * @throws Error
     */
    public static function multipart_start_year()
    {
        return self::getSubField(461, 'Н');
    }

    /**
     * @return mixed
     * @throws Error
     */
    public static function multipart_isbn()
    {
        return self::getSubField(461, 'I');
    }

    /**
     * @return mixed
     * @throws Error
     */
    public static function multipart_part_type()
    {
        $subField200V = self::getSubField(200, 'V');
        $matches = [];
        //парсим варики
        //вроде первое попавшееся берет часть=т|ч
        preg_match('/(?:ч|Ч|часть|Часть|т|Т|том|Том|к|К|книга|Книга|в|В|Выпуск)/u', $subField200V, $matches);
        //свитчим первый символ к варианту
        switch (mb_substr(mb_strtolower(reset($matches)), 0, 1)) {
            case 'ч':
                return 'часть';
            case 'т':
                return 'том';
            case 'к':
                return 'книга';
            case 'в':
                return 'выпуск';
            default:
                return '';
        }
    }

    /**
     * @return mixed
     * @throws Error
     */
    public static function multipart_part_number()
    {
        $subField200V = self::getSubField(200, 'V');
        $matches = [];
        preg_match('!\d+!', $subField200V, $matches);
        return empty($matches) ? '' : reset($matches);
    }


    /**
     * @return string
     * @throws Error
     */
    public static function authors()
    {
//    0 => "^4220 сост.^AРабкин^BЕ. Л.^YДА"
//    1 => "^4220 сост.^AПолевая^BГ. М.^YДА"
//    2 => "^4220 сост.^AФарфоровская^BЮ. Б.^YДА"
//    3 => "^4340 ред.^AБаскин^BЛ. М.^YДА"
//    4 => "^4675 рец.^AПламеневский^BБ. А."

        //берем первого атвора из поля 700
        $authors = [];
        $firstAuthor = '';
        $firstAuthor .= trim(self::getSubField(700, 'A', 0) . ' ' . self::getSubField(700, 'B', 0));

        if (!empty($firstAuthor)) {
            $authors[] = $firstAuthor;
        }

        //берем авторов из поля 701, если не пустые
        for ($i = 0; $i < self::getCountField(701); $i++) {
            $secondaryAuthor = trim(self::getSubField(701, 'A', $i) . ' ' . self::getSubField(701, 'B', $i));
            if (!empty($secondaryAuthor)) {
                $authors[] = $secondaryAuthor;
            }
        }

        return implode(', ', $authors);
    }

    /**
     * @return string
     * @throws Error
     */
    public static function editors()
    {
        //^4340 ред.^AБаскин^BЛ. М.^YДА
        return self::getOtherAuthors('4340');

    }

    /**
     * @return string
     * @throws Error
     */
    public static function compilers()
    {
        //^4220 сост.^AРабкин^BЕ. Л.^YДА
        return self::getOtherAuthors('4220');
    }

    /**
     * @return string
     * @throws Error
     */
    public static function painters()
    {
        //^4220 сост.^AРабкин^BЕ. Л.^YДА
        return self::getOtherAuthors('4440');
    }

    /**
     * @return string
     * @throws Error
     */
    public static function translators()
    {
        //^4220 сост.^AРабкин^BЕ. Л.^YДА
        return self::getOtherAuthors('4730');
    }

    /**
     * @param $flag
     * @return string
     * @throws Error
     */
    private static function getOtherAuthors($flag)
    {
        $otherAuthors = [];
        $role = '';
        for ($i = 0; $i < self::getCountField(702); $i++) {
            $role = trim(self::getSubField(702, $flag, $i));
            if (!empty($role)) {
                $secondaryAuthor = trim(self::getSubField(702, 'B', $i) . ' ' . self::getSubField(702, 'A', $i));
                $otherAuthors[] = $secondaryAuthor;
            }

        }

        return trim($role) . implode(', ', $otherAuthors);
    }


    /**
     * @return mixed
     * @throws Error
     */
    public static function publish_year()
    {
        return self::getSubField(210, 'D');
    }

    /**
     * @return mixed
     * @throws Error
     */
    public static function publisher_name()
    {
        return self::getSubField(210, 'C');
    }

    /**
     * @return mixed
     * @throws Error
     */
    public static function publisher__fk()
    {
        return self::publisher_name();
    }

    /**
     * @return mixed
     * @throws Error
     */
    public static function isbn()
    {
        $isbn = trim(self::getSubField(10, 'A'));

        if (empty($isbn)) {
            $isbn = trim(self::getSubField(461, 'I'));
        }
        return $isbn;
    }

    /**
     * @return mixed
     * @throws Error
     */
    public static function pages()
    {
        return self::getSubField(215, 'A');
    }

    /**
     * @return mixed
     * @throws Error
     */
    public static function book_desc()
    {
        return self::getField(331);
    }

    /**
     * @return mixed
     * @throws Error
     */
    public static function bbk()
    {
        return self::getField(621);
    }

    /**
     * @return mixed
     * @throws Error
     */
    public static function udk()
    {
        return self::getField(675);
    }

    /**
     * @return mixed
     * @throws Error
     */
    public static function book_title_info()
    {
        return self::getSubField(200, 'E');
    }

    /**
     * @return mixed
     * @throws Error
     */
    public static function edition()
    {
        return self::getSubField(205, 'A');
    }

    /**
     * @return mixed
     * @throws Error
     */
    public static function book_keywords()
    {
        $keywords = [];

        for ($i = 0; $i < self::getCountField(610); $i++) {
            $keyword = trim(self::getField(610, $i));
            if (!empty($keyword)) {
                $keywords[] = $keyword;
            }
        }

        return implode(',', $keywords);
    }

    /**
     * @return mixed
     * @throws Error
     */
    public static function publish_type_irbis()
    {
        $types = [];

        for ($i = 0; $i < 6; $i++) {
            $field = ($i == 0) ? 'C' : $i;
            $type = trim(self::getSubField(900, $field));
            if (!empty($type)) {
                $types[] = $type;
            }
        }
        return implode(',', $types);
    }

    /**
     * @return mixed
     * @throws Error
     */
    public static function pdf_path()
    {
        return self::getSubField(951, 'A');
    }


}
