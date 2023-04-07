<?php

/**
 * @maintainer Timur Shagiakhmetov <timur.shagiakhmetov@corp.badoo.com>
 */

namespace unit\MarfaTech\LiveProfiler;

class SimpleProfilerTest extends \unit\MarfaTech\BaseTestCase
{
    public function testRunWithoutTags()
    {
        \MarfaTech\LiveProfiler\SimpleProfiler::getInstance()->enable();
        $data = \MarfaTech\LiveProfiler\SimpleProfiler::getInstance()->disable();

        self::assertEquals(['main()'], array_keys($data));
        self::assertEquals(['wt', 'mu', 'ct'], array_keys($data['main()']));
    }

    public function testRunWithTag()
    {
        \MarfaTech\LiveProfiler\SimpleProfiler::getInstance()->enable();

        \MarfaTech\LiveProfiler\SimpleProfiler::getInstance()->startTimer('tag');
        \MarfaTech\LiveProfiler\SimpleProfiler::getInstance()->endTimer('tag');

        $data = \MarfaTech\LiveProfiler\SimpleProfiler::getInstance()->disable();

        self::assertEquals(['main()', 'main()==>tag'], array_keys($data));
    }

    public function testNotClosedTag()
    {
        \MarfaTech\LiveProfiler\SimpleProfiler::getInstance()->enable();

        \MarfaTech\LiveProfiler\SimpleProfiler::getInstance()->startTimer('tag');

        $data = \MarfaTech\LiveProfiler\SimpleProfiler::getInstance()->disable();

        self::assertEquals([], $data);
    }

    public function testInvalidClosedTag()
    {
        \MarfaTech\LiveProfiler\SimpleProfiler::getInstance()->enable();

        \MarfaTech\LiveProfiler\SimpleProfiler::getInstance()->startTimer('tag');
        \MarfaTech\LiveProfiler\SimpleProfiler::getInstance()->endTimer('invalid');

        $data = \MarfaTech\LiveProfiler\SimpleProfiler::getInstance()->disable();

        self::assertEquals([], $data);
    }
}
