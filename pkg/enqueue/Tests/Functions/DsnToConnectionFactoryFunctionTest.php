<?php

namespace Enqueue\Tests\Functions;

use Enqueue\AmqpExt\AmqpConnectionFactory;
use Enqueue\Dbal\DbalConnectionFactory;
use Enqueue\Fs\FsConnectionFactory;
use Enqueue\Gearman\GearmanConnectionFactory;
use Enqueue\Null\NullConnectionFactory;
use Enqueue\Pheanstalk\PheanstalkConnectionFactory;
use Enqueue\RdKafka\RdKafkaConnectionFactory;
use PHPUnit\Framework\TestCase;

class DsnToConnectionFactoryFunctionTest extends TestCase
{
    public function testThrowIfDsnEmpty()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The scheme could not be parsed from DSN ""');

        \Enqueue\dsn_to_connection_factory('');
    }

    public function testThrowIfDsnMissingScheme()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The scheme could not be parsed from DSN "dsnMissingScheme"');

        \Enqueue\dsn_to_connection_factory('dsnMissingScheme');
    }

    public function testThrowIfDsnNotSupported()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The scheme "http" is not supported. Supported "file", "amqp+ext"');

        \Enqueue\dsn_to_connection_factory('http://schemeNotSupported');
    }

    /**
     * @dataProvider provideDSNs
     *
     * @param mixed $dsn
     * @param mixed $expectedFactoryClass
     */
    public function testReturnsExpectedFactoryInstance($dsn, $expectedFactoryClass)
    {
        $factory = \Enqueue\dsn_to_connection_factory($dsn);

        $this->assertInstanceOf($expectedFactoryClass, $factory);
    }

    public static function provideDSNs()
    {
        yield ['amqp://', AmqpConnectionFactory::class];

        yield ['amqp://user:pass@foo/vhost', AmqpConnectionFactory::class];

        yield ['file://', FsConnectionFactory::class];

        yield ['file:///foo/bar/baz', FsConnectionFactory::class];

        yield ['null://', NullConnectionFactory::class];

        yield ['mysql://', DbalConnectionFactory::class];

        yield ['pgsql://', DbalConnectionFactory::class];

        yield ['beanstalk://', PheanstalkConnectionFactory::class];

        //        yield ['gearman://', GearmanConnectionFactory::class];

        yield ['rdkafka://', RdKafkaConnectionFactory::class];
    }
}
