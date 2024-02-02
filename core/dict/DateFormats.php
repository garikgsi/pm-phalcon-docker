<?php
namespace Core\Dict;

/**
 * Class DataFormats
 * Система форматирования дат в текстовые варианты
 */
final class DateFormats {
    const MONTHS_TYPE1 = [
        'Января',
        'Февраля',
        'Марта',
        'Апреля',
        'Мая',
        'Июня' ,
        'Июля',
        'Августа',
        'Сентября',
        'Октября',
        'Ноября',
        'Декабря'
    ];

    /**
     * Результат работы скрипта: 12 января 2017
     *
     * @param string $date
     * @return string
     */
    static public function dateType1($date) {
        $date = self::explodeDateTime($date);

    return $date[2].' '.mb_strtolower(self::MONTHS_TYPE1[(int)$date[1] - 1]).' '.$date[0];
    }

    /**
     * @param string $dateTime
     * @return array
     */
    static private function explodeDateTime($dateTime) {
        $explode = explode(' ', $dateTime);

        $explodeDate = explode('-', $explode[0]);

        $explodeTime = isset($explode[1]) ? explode(':', $explode[1]) : [];

        return array_merge($explodeDate, $explodeTime);
    }
}