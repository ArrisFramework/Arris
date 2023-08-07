<?php

namespace Arris\Helpers;

class StringsHTML
{
    /**
     * function that removes the HTML tags along with their contents
     *
     * @param string $text
     * @param string $tags
     * @param bool $invert
     * @return array|string|string[]|null
     */
    public static function strip_tags_content(string $text, string $tags = '', bool $invert = false) {

        preg_match_all('/<(.+?)[\s]*\/?[\s]*>/si', trim($tags), $tags_list);
        $tags_list = array_unique($tags_list[1]);

        if(!empty($tags_list) AND count($tags_list) > 0) {
            $imploded_tags = implode('|', $tags_list);
            return $invert
                ? preg_replace('@<('. $imploded_tags .')\b.*?>.*?</\1>@si', '', $text)
                : preg_replace('@<(?!(?:'. $imploded_tags .')\b)(\w+)\b.*?>.*?</\1>@si', '', $text);

        } elseif($invert === false) {
            return preg_replace('@<(\w+)\b.*?>.*?</\1>@si', '', $text);
        }
        return $text;
    }

    /**
     * Вызывается в шаблонах, закрывает открытые теги у обрезанной сторочки
     *
     * @param $content
     * @return string
     */
    public static function close_tags($content): string
    {
        preg_match_all('#<(?!meta|em|strong|img|br|hr|input\b)\b([a-z]+)(?: .*)?(?<![/|/ ])>#iU', $content, $result);   $openedtags = $result[1];
        preg_match_all('#</([a-z]+)>#iU', $content, $result);                                                           $closedtags = $result[1];

        $len_opened = count($openedtags);
        if (count($closedtags) == $len_opened) {
            return $content;
        }
        $openedtags = array_reverse($openedtags);
        for ($i = 0; $i < $len_opened; $i++) {
            if (!in_array($openedtags[$i], $closedtags)) {
                $content .= '</' . $openedtags[$i] . '>';
            } else {
                unset($closedtags[ array_search($openedtags[$i], $closedtags) ]);
            }
        }
        return $content;
    }


}