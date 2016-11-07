<?php
namespace Slack;

final class Utils
{
    /**
     * $param string $text
     * @return string
     */
    public static function unfurl($text)
    {
        $text = preg_replace('/<@\w+>/', '', $text);
        $text = preg_replace('/<(.+)\|(.+)>/', '$2', $text);
        return trim($text);
    }
}
