<?php

namespace Enqueue\RdKafka\Tests;

use Enqueue\Null\NullMessage;
use Enqueue\Null\NullQueue;
use Enqueue\RdKafka\RdKafkaMessage;
use Enqueue\RdKafka\RdKafkaProducer;
use Enqueue\RdKafka\RdKafkaTopic;
use Enqueue\RdKafka\Serializer;
use Interop\Queue\InvalidDestinationException;
use Interop\Queue\InvalidMessageException;
use PHPUnit\Framework\TestCase;
use RdKafka\Producer;
use RdKafka\ProducerTopic;
use RdKafka\TopicConf;

class RdKafkaProducerTest extends TestCase
{
    public function testCouldBeConstructedWithKafkaProducerAndSerializerAsArguments()
    {
        new RdKafkaProducer($this->createKafkaProducerMock(), $this->createSerializerMock());
    }

    public function testThrowIfDestinationInvalid()
    {
        $producer = new RdKafkaProducer($this->createKafkaProducerMock(), $this->createSerializerMock());

        $this->expectException(InvalidDestinationException::class);
        $this->expectExceptionMessage('The destination must be an instance of Enqueue\RdKafka\RdKafkaTopic but got Enqueue\Null\NullQueue.');
        $producer->send(new NullQueue('aQueue'), new RdKafkaMessage());
    }

    public function testThrowIfMessageInvalid()
    {
        $producer = new RdKafkaProducer($this->createKafkaProducerMock(), $this->createSerializerMock());

        $this->expectException(InvalidMessageException::class);
        $this->expectExceptionMessage('The message must be an instance of Enqueue\RdKafka\RdKafkaMessage but it is Enqueue\Null\NullMessage.');
        $producer->send(new RdKafkaTopic('aQueue'), new NullMessage());
    }

    public function testShouldUseSerializerToEncodeMessageAndPutToExpectedTube()
    {
        $message = new RdKafkaMessage('theBody', ['foo' => 'fooVal'], ['bar' => 'barVal']);
        $message->setKey('key');

        $kafkaTopic = $this->createKafkaTopicMock();
        $kafkaTopic
            ->expects($this->once())
            ->method('produce')
            ->with(
                RD_KAFKA_PARTITION_UA,
                0,
                'theSerializedMessage',
                'key'
            )
        ;

        $kafkaProducer = $this->createKafkaProducerMock();
        $kafkaProducer
            ->expects($this->once())
            ->method('newTopic')
            ->with('theQueueName', $this->isInstanceOf(TopicConf::class))
            ->willReturn($kafkaTopic)
        ;

        $serializer = $this->createSerializerMock();
        $serializer
            ->expects($this->once())
            ->method('toString')
            ->with($this->identicalTo($message))
            ->willReturn('theSerializedMessage')
        ;

        $producer = new RdKafkaProducer($kafkaProducer, $serializer);

        $producer->send(new RdKafkaTopic('theQueueName'), $message);
    }

    public function testShouldAllowGetPreviouslySetSerializer()
    {
        $producer = new RdKafkaProducer($this->createKafkaProducerMock(), $this->createSerializerMock());

        $expectedSerializer = $this->createSerializerMock();

        //guard
        $this->assertNotSame($producer->getSerializer(), $expectedSerializer);

        $producer->setSerializer($expectedSerializer);

        $this->assertSame($expectedSerializer, $producer->getSerializer());
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ProducerTopic
     */
    private function createKafkaTopicMock()
    {
        return $this->createMock(ProducerTopic::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Producer
     */
    private function createKafkaProducerMock()
    {
        return $this->createMock(Producer::class);
    }

    /**
     * @return Serializer|\PHPUnit_Framework_MockObject_MockObject|Serializer
     */
    private function createSerializerMock()
    {
        return $this->createMock(Serializer::class);
    }
}
