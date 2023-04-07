<?php

/**
 * @maintainer Timur Shagiakhmetov <timur.shagiakhmetov@corp.badoo.com>
 */

namespace unit\MarfaTech\LiveProfiler;

class LiveProfilerTest extends \unit\MarfaTech\BaseTestCase
{
    public function providerGetAutoLabel()
    {
        return [
            [
                'request_uri' => '',
                'script_name' => 'index.php',
                'expected' => 'index.php',
            ],
            [
                'request_uri' => '/test/test?param=value',
                'script_name' => '',
                'expected' => '/test/test',
            ]
        ];
    }

    /**
     * @dataProvider providerGetAutoLabel
     * @param $request_uri
     * @param $script_name
     * @param $expected
     * @throws \ReflectionException
     */
    public function testGetAutoLabel($request_uri, $script_name, $expected)
    {
        $_SERVER['REQUEST_URI'] = $request_uri;
        $_SERVER['SCRIPT_NAME'] = $script_name;
        $Profiler = new \MarfaTech\LiveProfiler\LiveProfiler('sqlite:///:memory:');

        $label = $this->invokeMethod($Profiler, 'getAutoLabel', []);
        self::assertEquals($expected, $label);
    }

    public function testStart()
    {
        $ProfilerMock = $this->getMockBuilder('\MarfaTech\LiveProfiler\LiveProfiler')
            ->disableOriginalConstructor()
            ->setMethods(['end'])
            ->getMock();
        $ProfilerMock->method('end')->willReturn(true);

        /** @var \MarfaTech\LiveProfiler\LiveProfiler $ProfilerMock */
        $ProfilerMock
            ->setDivider(1)
            ->setStartCallback(function () {
        });

        $result = $ProfilerMock->start();
        self::assertTrue($result);
    }

    /**
     * @throws \ReflectionException
     */
    public function testAlreadyStarted()
    {
        $Profiler = new \MarfaTech\LiveProfiler\LiveProfiler();

        $this->setProtectedProperty($Profiler, 'is_enabled', true);

        $result = $Profiler->start();
        self::assertTrue($result);
    }

    public function testStartWithoutCallback()
    {
        $ProfilerMock = $this->getMockBuilder('\MarfaTech\LiveProfiler\LiveProfiler')
            ->disableOriginalConstructor()
            ->setMethods(['end'])
            ->getMock();
        $ProfilerMock->method('end')->willReturn(true);

        /** @var \MarfaTech\LiveProfiler\LiveProfiler $ProfilerMock */
        $result = $ProfilerMock->start();

        self::assertTrue($result);
    }

    public function testStartTotal()
    {
        $ProfilerMock = $this->getMockBuilder('\MarfaTech\LiveProfiler\LiveProfiler')
            ->disableOriginalConstructor()
            ->setMethods(['end'])
            ->getMock();
        $ProfilerMock->method('end')->willReturn(true);

        /** @var \MarfaTech\LiveProfiler\LiveProfiler $ProfilerMock */
        $ProfilerMock
            ->setTotalDivider(1)
            ->setStartCallback(function () {
        });

        $result = $ProfilerMock->start();
        self::assertTrue($result);
    }

    /**
     * @depends testStart
     * @throws \ReflectionException
     */
    public function testEnd()
    {
        $DataPacker = new \MarfaTech\LiveProfiler\DataPacker();

        $ProfilerMock = $this->getMockBuilder('\MarfaTech\LiveProfiler\LiveProfiler')
            ->disableOriginalConstructor()
            ->setMethods(['save'])
            ->getMock();
        $ProfilerMock->method('save')->willReturn(true);

        $this->setProtectedProperty($ProfilerMock, 'is_enabled', true);

        /** @var \MarfaTech\LiveProfiler\LiveProfiler $ProfilerMock */
        $ProfilerMock
            ->setDataPacker($DataPacker)
            ->setEndCallback(function () {
            return ['end result'];
        });

        $result = $ProfilerMock->end();
        self::assertTrue($result);
    }

    /**
     * @throws \ReflectionException
     */
    public function testReset()
    {
        $Profiler = new \MarfaTech\LiveProfiler\LiveProfiler();
        $Profiler->setEndCallback(function () {});

        $this->setProtectedProperty($Profiler, 'is_enabled', true);

        $result = $Profiler->reset();
        self::assertTrue($result);

        $is_enabled = $this->getProtectedProperty($Profiler, 'is_enabled');
        self::assertFalse($is_enabled);
    }

    /**
     * @throws \ReflectionException
     */
    public function testEndWithoutCallback()
    {
        $Profiler = new \MarfaTech\LiveProfiler\LiveProfiler();

        $this->setProtectedProperty($Profiler, 'is_enabled', true);
        $this->setProtectedProperty($Profiler, 'end_callback', null);

        $result = $Profiler->end();
        self::assertTrue($result);
    }

    /**
     * @depends testStart
     */
    public function testEndErrorInProfilerData()
    {
        $LoggerMock = $this->getMockBuilder('\MarfaTech\LiveProfiler\Logger')
            ->disableOriginalConstructor()
            ->setMethods(['warning'])
            ->getMock();
        $LoggerMock->method('warning')->willReturn(true);
        /** @var \Psr\Log\LoggerInterface $LoggerMock */

        $Profiler = new \MarfaTech\LiveProfiler\LiveProfiler('sqlite:///:memory:');
        $Profiler->setLogger($LoggerMock);
        $Profiler->setDivider(1);
        $Profiler->setStartCallback(function () {
            return true;
        });
        $Profiler->setEndCallback(function () {
            return null;
        });
        $Profiler->start();

        $result = $Profiler->end();
        self::assertFalse($result);
    }

    /**
     * @depends testStart
     */
    public function testEndSaveFalse()
    {
        $LoggerMock = $this->getMockBuilder('\MarfaTech\LiveProfiler\Logger')
            ->disableOriginalConstructor()
            ->setMethods(['warning'])
            ->getMock();
        $LoggerMock->method('warning')->willReturn(true);
        /** @var \Psr\LOg\LoggerInterface $LoggerMock */

        $ProfilerMock = $this->getMockBuilder('\MarfaTech\LiveProfiler\LiveProfiler')
            ->setConstructorArgs(['sqlite:///:memory:'])
            ->setMethods(['save'])
            ->getMock();
        $ProfilerMock->method('save')->willReturn(false);

        /** @var \MarfaTech\LiveProfiler\LiveProfiler $ProfilerMock */
        $ProfilerMock->setLogger($LoggerMock);
        $ProfilerMock->setDivider(1);
        $ProfilerMock->setStartCallback(function () {
            return true;
        });
        $ProfilerMock->setEndCallback(function () {
            return ['end result'];
        });
        $ProfilerMock->start();

        $result = $ProfilerMock->end();
        self::assertFalse($result);
    }

    /**
     * @depends testStart
     */
    public function testEndErrorInSaving()
    {
        $LoggerMock = $this->getMockBuilder('\MarfaTech\LiveProfiler\Logger')
            ->disableOriginalConstructor()
            ->setMethods(['warning', 'error'])
            ->getMock();
        $LoggerMock->method('warning')->willReturn(true);
        $LoggerMock->method('error')->willReturn(true);
        /** @var \Psr\LOg\LoggerInterface $LoggerMock */

        $ProfilerMock = $this->getMockBuilder('\MarfaTech\LiveProfiler\LiveProfiler')
            ->setConstructorArgs(['sqlite:///:memory:'])
            ->setMethods(['save'])
            ->getMock();
        $ProfilerMock->method('save')->willReturn(false);

        /** @var \MarfaTech\LiveProfiler\LiveProfiler $ProfilerMock */
        $ProfilerMock->setLogger($LoggerMock);
        $ProfilerMock->setDivider(1);
        $ProfilerMock->setStartCallback(function () {
            return true;
        });
        $ProfilerMock->setEndCallback(function () {
            return ['end result'];
        });
        $ProfilerMock->start();

        $result = $ProfilerMock->end();
        self::assertFalse($result);
    }

    public function testEndWithoutStartProfiling()
    {
        $Profiler = new \MarfaTech\LiveProfiler\LiveProfiler('sqlite:///:memory:');

        $result = $Profiler->end();
        self::assertTrue($result);
    }

    /**
     * @throws \ReflectionException
     */
    public function testSettersGetters()
    {
        /** @var \Psr\Log\LoggerInterface $LoggerMock */
        $LoggerMock = $this->getMockBuilder('\MarfaTech\LiveProfiler\Logger')
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMock();

        $DataPacker = new \MarfaTech\LiveProfiler\DataPacker();

        /** @var \Doctrine\DBAL\Connection $ConnectionMock */
        $ConnectionMock = $this->getMockBuilder('\Doctrine\DBAL\Connection')
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMock();

        $test_mode = 'test_mode';
        $test_app = 'test_app';
        $test_label = 'test_label';
        $test_datetime = 'test_datetime';
        $test_divider = 1;
        $test_total_divider = 2;
        $test_connection_string = 'test_connection_string';
        $test_path = 'test_path';
        $test_api_key = 'test_api_key';

        $Profiler = new \MarfaTech\LiveProfiler\LiveProfiler('sqlite:///:memory:');

        $Profiler
            ->setMode($test_mode)
            ->setApp($test_app)
            ->setLabel($test_label)
            ->setDateTime($test_datetime)
            ->setDivider($test_divider)
            ->setTotalDivider($test_total_divider)
            ->setLogger($LoggerMock)
            ->setDataPacker($DataPacker)
            ->setConnection($ConnectionMock)
            ->setConnectionString($test_connection_string)
            ->setPath($test_path)
            ->setApiKey($test_api_key);

        $mode = $this->getProtectedProperty($Profiler, 'mode');
        $app = $this->getProtectedProperty($Profiler, 'app');
        $label = $this->getProtectedProperty($Profiler, 'label');
        $datetime = $this->getProtectedProperty($Profiler, 'datetime');
        $divider = $this->getProtectedProperty($Profiler, 'divider');
        $total_divider = $this->getProtectedProperty($Profiler, 'total_divider');
        $Logger = $this->getProtectedProperty($Profiler, 'Logger');
        $DataPackerNew = $this->getProtectedProperty($Profiler, 'DataPacker');
        $Connection = $this->getProtectedProperty($Profiler, 'Conn');
        $connection_string = $this->getProtectedProperty($Profiler, 'connection_string');
        $path = $this->getProtectedProperty($Profiler, 'path');
        $api_key = $this->getProtectedProperty($Profiler, 'api_key');

        self::assertEquals($test_mode, $mode);
        self::assertEquals($test_mode, $Profiler->getMode());
        self::assertEquals($test_app, $app);
        self::assertEquals($test_app, $Profiler->getApp());
        self::assertEquals($test_label, $label);
        self::assertEquals($test_label, $Profiler->getLabel());
        self::assertEquals($test_datetime, $datetime);
        self::assertEquals($test_datetime, $Profiler->getDateTime());
        self::assertEquals($test_divider, $divider);
        self::assertEquals($test_total_divider, $total_divider);
        self::assertSame($LoggerMock, $Logger);
        self::assertSame($DataPacker, $DataPackerNew);
        self::assertSame($ConnectionMock, $Connection);
        self::assertSame([], $Profiler->getLastProfileData());
        self::assertSame($test_connection_string, $connection_string);
        self::assertSame($test_path, $path);
        self::assertSame($test_path, $Profiler->getPath());
        self::assertSame($test_api_key, $api_key);
        self::assertSame($test_api_key, $Profiler->getApiKey());
    }

    public function testGetInstance()
    {
        $Profiler1 = \MarfaTech\LiveProfiler\LiveProfiler::getInstance();
        $Profiler2 = \MarfaTech\LiveProfiler\LiveProfiler::getInstance();

        static::assertSame($Profiler1, $Profiler2);
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     */
    public function testCreateTable()
    {
        $Profiler = new \MarfaTech\LiveProfiler\LiveProfiler('sqlite:///:memory:');

        $result = $Profiler->createTable();

        static::assertTrue($result);
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     */
    public function testCreateTableError()
    {
        $LoggerMock = $this->getMockBuilder('\MarfaTech\LiveProfiler\Logger')
            ->disableOriginalConstructor()
            ->setMethods(['error'])
            ->getMock();
        $LoggerMock->method('error')->willReturn(true);
        /** @var \Psr\Log\LoggerInterface $LoggerMock */

        $Profiler = new \MarfaTech\LiveProfiler\LiveProfiler('drizzle-pdo-mysql://localhost:4486/foo?charset=UTF-8');
        $Profiler->setLogger($LoggerMock);
        $result = $Profiler->createTable();

        static::assertFalse($result);
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     * @throws \ReflectionException
     */
    public function testSave()
    {
        $Profiler = new \MarfaTech\LiveProfiler\LiveProfiler('sqlite:///:memory:');
        $Profiler->createTable();

        $result = $this->invokeMethod($Profiler, 'save', ['app', 'label', '1970-01-01', ['data']]);
        self::assertTrue($result);
    }

    /**
     * @throws \ReflectionException
     */
    public function testSaveToFile()
    {
        $Profiler = new \MarfaTech\LiveProfiler\LiveProfiler(
            '/tmp',
            \MarfaTech\LiveProfiler\LiveProfiler::MODE_FILES
        );

        $result = $this->invokeMethod($Profiler, 'save', ['app', 'label', '1970-01-01', ['data']]);
        self::assertTrue($result);
    }

    /**
     * @throws \ReflectionException
     */
    public function testSendToAPI()
    {
        $Profiler = new \MarfaTech\LiveProfiler\LiveProfiler(
            '',
            \MarfaTech\LiveProfiler\LiveProfiler::MODE_API
        );
        $Profiler->setApiKey('70366397-97d6-41be-a83c-e9e649c824e1');

        $result = $this->invokeMethod($Profiler, 'save', ['app', 'label', '1970-01-01', ['data']]);
        self::assertTrue($result);
    }

    /**
     * @throws \ReflectionException
     */
    public function testConvertSampleDataToCommonFormat()
    {
        $Profiler = new \MarfaTech\LiveProfiler\LiveProfiler('sqlite:///:memory:');

        define('XHPROF_SAMPLING_BEGIN', 1000);

        $sampling_data = [
            1001 => 'main()==>func',
            1002 => 'main()==>func==>func2',
            1003 => 'main()'
        ];
        $result = $this->invokeMethod($Profiler, 'convertSampleDataToCommonFormat', [$sampling_data]);
        $expected = [
            'main()' => [
                'ct' => 3,
                'wt' => 3000000
            ],
            'main()==>func' => [
                'ct' => 2,
                'wt' => 2000000
            ],
            'func==>func2' => [
                'ct' => 1,
                'wt' => 1000000
            ]
        ];
        self::assertEquals($expected, $result);
    }

    /**
     * @throws \ReflectionException
     */
    public function testUseXhprofSample()
    {
        $ProfilerMock = $this->getMockBuilder('\MarfaTech\LiveProfiler\LiveProfiler')
            ->disableOriginalConstructor()
            ->setMethods(['__construct'])
            ->getMock();

        /** @var \MarfaTech\LiveProfiler\LiveProfiler $ProfilerMock */
        $result = $ProfilerMock->useXhprofSample();

        self::assertInstanceOf('\MarfaTech\LiveProfiler\LiveProfiler', $result);
        $start_callback = $this->getProtectedProperty($ProfilerMock, 'start_callback');
        $end_callback = $this->getProtectedProperty($ProfilerMock, 'end_callback');
        self::assertNotEmpty($start_callback);
        self::assertInternalType('callable', $start_callback);
        self::assertNotEmpty($end_callback);
        self::assertInternalType('callable', $end_callback);
    }

    /**
     * @throws \ReflectionException
     */
    public function testUseXhprofSampleAfterStart()
    {
        $LoggerMock = $this->getMockBuilder('\MarfaTech\LiveProfiler\Logger')
            ->disableOriginalConstructor()
            ->setMethods(['warning'])
            ->getMock();
        $LoggerMock->method('warning')->willReturn(true);
        /** @var \Psr\LOg\LoggerInterface $LoggerMock */

        $ProfilerMock = $this->getMockBuilder('\MarfaTech\LiveProfiler\LiveProfiler')
            ->disableOriginalConstructor()
            ->setMethods(['__construct'])
            ->getMock();

        $this->setProtectedProperty($ProfilerMock, 'is_enabled', true);

        /** @var \MarfaTech\LiveProfiler\LiveProfiler $ProfilerMock */
        $result = $ProfilerMock
            ->setLogger($LoggerMock)
            ->useXhprofSample();

        self::assertInstanceOf('\MarfaTech\LiveProfiler\LiveProfiler', $result);
    }

    /**
     * @throws \ReflectionException
     */
    public function testUseXhprof()
    {
        $ProfilerMock = $this->getMockBuilder('\MarfaTech\LiveProfiler\LiveProfiler')
            ->disableOriginalConstructor()
            ->setMethods(['__construct'])
            ->getMock();

        /** @var \MarfaTech\LiveProfiler\LiveProfiler $ProfilerMock */
        $result = $ProfilerMock->useXhprof();

        self::assertInstanceOf('\MarfaTech\LiveProfiler\LiveProfiler', $result);

        $start_callback = $this->getProtectedProperty($ProfilerMock, 'start_callback');
        $end_callback = $this->getProtectedProperty($ProfilerMock, 'end_callback');
        self::assertNotEmpty($start_callback);
        self::assertInternalType('callable', $start_callback);
        self::assertNotEmpty($end_callback);
        self::assertInternalType('callable', $end_callback);
    }

    /**
     * @throws \ReflectionException
     */
    public function testUseXhprofAfterStart()
    {
        $LoggerMock = $this->getMockBuilder('\MarfaTech\LiveProfiler\Logger')
            ->disableOriginalConstructor()
            ->setMethods(['warning'])
            ->getMock();
        $LoggerMock->method('warning')->willReturn(true);
        /** @var \Psr\LOg\LoggerInterface $LoggerMock */

        $ProfilerMock = $this->getMockBuilder('\MarfaTech\LiveProfiler\LiveProfiler')
            ->disableOriginalConstructor()
            ->setMethods(['__construct'])
            ->getMock();

        $this->setProtectedProperty($ProfilerMock, 'is_enabled', true);

        /** @var \MarfaTech\LiveProfiler\LiveProfiler $ProfilerMock */
        $result = $ProfilerMock
            ->setLogger($LoggerMock)
            ->useXhprof();

        self::assertInstanceOf('\MarfaTech\LiveProfiler\LiveProfiler', $result);
    }

    /**
     * @throws \ReflectionException
     */
    public function testUseUprofiler()
    {
        $ProfilerMock = $this->getMockBuilder('\MarfaTech\LiveProfiler\LiveProfiler')
            ->disableOriginalConstructor()
            ->setMethods(['__construct'])
            ->getMock();

        /** @var \MarfaTech\LiveProfiler\LiveProfiler $ProfilerMock */
        $result = $ProfilerMock->useUprofiler();

        self::assertInstanceOf('\MarfaTech\LiveProfiler\LiveProfiler', $result);
        $start_callback = $this->getProtectedProperty($ProfilerMock, 'start_callback');
        $end_callback = $this->getProtectedProperty($ProfilerMock, 'end_callback');
        self::assertNotEmpty($start_callback);
        self::assertInternalType('callable', $start_callback);
        self::assertNotEmpty($end_callback);
        self::assertInternalType('callable', $end_callback);
    }

    /**
     * @throws \ReflectionException
     */
    public function testUseUprofilerAfterStart()
    {
        $LoggerMock = $this->getMockBuilder('\MarfaTech\LiveProfiler\Logger')
            ->disableOriginalConstructor()
            ->setMethods(['warning'])
            ->getMock();
        $LoggerMock->method('warning')->willReturn(true);
        /** @var \Psr\LOg\LoggerInterface $LoggerMock */

        $ProfilerMock = $this->getMockBuilder('\MarfaTech\LiveProfiler\LiveProfiler')
            ->disableOriginalConstructor()
            ->setMethods(['__construct'])
            ->getMock();

        $this->setProtectedProperty($ProfilerMock, 'is_enabled', true);

        /** @var \MarfaTech\LiveProfiler\LiveProfiler $ProfilerMock */
        $result = $ProfilerMock
            ->setLogger($LoggerMock)
            ->useUprofiler();

        self::assertInstanceOf('\MarfaTech\LiveProfiler\LiveProfiler', $result);
    }

    /**
     * @throws \ReflectionException
     */
    public function testUseTidyWays()
    {
        $ProfilerMock = $this->getMockBuilder('\MarfaTech\LiveProfiler\LiveProfiler')
            ->disableOriginalConstructor()
            ->setMethods(['__construct'])
            ->getMock();

        /** @var \MarfaTech\LiveProfiler\LiveProfiler $ProfilerMock */
        $result = $ProfilerMock->useTidyWays();

        self::assertInstanceOf('\MarfaTech\LiveProfiler\LiveProfiler', $result);
        $start_callback = $this->getProtectedProperty($ProfilerMock, 'start_callback');
        $end_callback = $this->getProtectedProperty($ProfilerMock, 'end_callback');
        self::assertNotEmpty($start_callback);
        self::assertInternalType('callable', $start_callback);
        self::assertNotEmpty($end_callback);
        self::assertInternalType('callable', $end_callback);
    }

    /**
     * @throws \ReflectionException
     */
    public function testUseTidyWaysAfterStart()
    {
        $LoggerMock = $this->getMockBuilder('\MarfaTech\LiveProfiler\Logger')
            ->disableOriginalConstructor()
            ->setMethods(['warning'])
            ->getMock();
        $LoggerMock->method('warning')->willReturn(true);
        /** @var \Psr\LOg\LoggerInterface $LoggerMock */

        $ProfilerMock = $this->getMockBuilder('\MarfaTech\LiveProfiler\LiveProfiler')
            ->disableOriginalConstructor()
            ->setMethods(['__construct'])
            ->getMock();

        $this->setProtectedProperty($ProfilerMock, 'is_enabled', true);

        /** @var \MarfaTech\LiveProfiler\LiveProfiler $ProfilerMock */
        $result = $ProfilerMock
            ->setLogger($LoggerMock)
            ->useTidyWays();

        self::assertInstanceOf('\MarfaTech\LiveProfiler\LiveProfiler', $result);
    }

    public function testDetectProfiler()
    {
        $ProfilerMock = $this->getMockBuilder('\MarfaTech\LiveProfiler\LiveProfiler')
            ->disableOriginalConstructor()
            ->setMethods(['__construct'])
            ->getMock();

        /** @var \MarfaTech\LiveProfiler\LiveProfiler $ProfilerMock */
        $result = $ProfilerMock->detectProfiler();

        self::assertInstanceOf('\MarfaTech\LiveProfiler\LiveProfiler', $result);
    }
}
