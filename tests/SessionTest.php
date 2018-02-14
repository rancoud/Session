<?php

declare(strict_types=1);

namespace Rancoud\Session\Test;

use PHPUnit\Framework\TestCase;
use Rancoud\Session\Session;

/**
 * Class SessionTest.
 */
class SessionTest extends TestCase
{
    public function testConstruct()
    {
        $conf = ['driver' => 'file', 'name' => 'myapp', 'folder'=>'./tests', 'cookie_domain'=> '/', 'lifetime'=> 50];
        new Session($conf);
        static::assertTrue(true);
    }
}
