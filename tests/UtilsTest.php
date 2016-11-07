<?php
namespace Slack\Test;

use Slack\Utils;

class UtilsTest extends \PHPUnit_Framework_TestCase
{
    public function testUnfurl()
    {
        $this->assertEquals('www.example.com', Utils::unfurl('<http://www.example.com|www.example.com>'));
    }
}
