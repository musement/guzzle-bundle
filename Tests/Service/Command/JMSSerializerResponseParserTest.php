<?php

/*
 * This file is part of the MisdGuzzleBundle for Symfony2.
 *
 * (c) University of Cambridge
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Misd\GuzzleBundle\Tests\Service\Command;

use Guzzle\Http\Message\Response;
use Guzzle\Service\Command\CommandInterface;
use Guzzle\Service\Command\ResponseParserInterface;
use Guzzle\Service\Description\OperationInterface;
use JMS\Serializer\DeserializationContext;
use JMS\Serializer\SerializerInterface;
use Misd\GuzzleBundle\Service\Command\JMSSerializerResponseParser;

class JMSSerializerResponseParserTest extends \PHPUnit_Framework_TestCase
{
    public function testDeserializeContextConfiguration()
    {
        $expectedContext = DeserializationContext::create();
        $expectedContext->setGroups('group');
        $expectedContext->setVersion(1);
        $expectedContext->enableMaxDepthChecks();

        $operation = $this->getMockBuilder(OperationInterface::class)->getMock();
        $operation
            ->expects($this->any())
            ->method('getResponseType')
            ->will($this->returnValue(OperationInterface::TYPE_CLASS))
        ;
        $operation
            ->expects($this->any())
            ->method('getResponseClass')
            ->will($this->returnValue('ResponseClass'))
        ;
        $operation
            ->expects($this->any())
            ->method('getData')
            ->will($this->returnValueMap(
                array(
                    array('jms_serializer.groups', 'group'),
                    array('jms_serializer.version', 1),
                    array('jms_serializer.max_depth_checks', true)
                )
            ))
        ;

        $command = $this->getMockBuilder(CommandInterface::class)->getMock();
        $command
            ->expects($this->any())
            ->method('getOperation')
            ->will($this->returnValue($operation))
        ;

        $response = new Response(200);
        $response->setBody('body');

        $serializer = $this->getMockBuilder(SerializerInterface::class)->getMock();
        $serializer
            ->expects($this->once())
            ->method('deserialize')
            ->with('body', 'ResponseClass', 'json', $this->equalTo($expectedContext))
        ;

        $parser = new JMSSerializerResponseParser(
            $serializer,
            $this->getMockBuilder(ResponseParserInterface::class)->getMock()
        );

        $ref = new \ReflectionMethod($parser, 'deserialize');
        $ref->setAccessible(true);

        return $ref->invoke($parser, $command, $response, 'json');
    }
}
