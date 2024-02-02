<?php
namespace Core\Handler;

use Bitverse\Identicon\Color\Color;
use Bitverse\Identicon\Generator\BaseGenerator;
use Bitverse\Identicon\Generator\PixelsGenerator;
use Bitverse\Identicon\Generator\RingsGenerator;
use Bitverse\Identicon\Identicon;
use Bitverse\Identicon\Preprocessor\MD5Preprocessor;

/**
 * Класс - обертка для генерации аватаров
 *
 * @package Core\Handler
 */
class AvatarGenerator
{

    /**
     * Генерация круговой аватарки
     *
     * @param $str - строка для генерации
     * @param $bg  - цвет фона
     * @return string
     */
    public static function getRing($str, $bg = '#EEEEEE') {
        return self::generate(new RingsGenerator(), $str, $bg);
    }

    /**
     * Генерация пиксельной аватарки
     *
     * @param $str - строка для генерации
     * @param $bg  - цвет фона
     * @return string
     */
    public static function getPixel($str, $bg = '#EEEEEE') {
        return self::generate(new PixelsGenerator(), $str, $bg);
    }

    /**
     * @param BaseGenerator $generator
     * @param $str - строка для генерации
     * @param $bg - цвет фона
     * @return string
     */
    private static function generate(BaseGenerator $generator, $str, $bg) {
        $generator->setBackgroundColor(Color::parseHex($bg));

        return (new Identicon(new MD5Preprocessor(), $generator))->getIcon($str);
    }
}