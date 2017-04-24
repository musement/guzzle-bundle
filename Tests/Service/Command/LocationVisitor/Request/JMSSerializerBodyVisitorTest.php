<?php

/*
 * This file is part of the MisdGuzzleBundle for Symfony2.
 *
 * (c) University of Cambridge
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Misd\GuzzleBundle\Tests\Service\Command\LocationVisitor\Request;

use Guzzle\Http\Message\EntityEnclosingRequest;
use Guzzle\Service\Command\CommandInterface;
use Guzzle\Service\Command\ResponseParserInterface;
use Guzzle\Service\Description\Parameter;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerInterface;
use Misd\GuzzleBundle\Service\Command\LocationVisitor\Request\JMSSerializerBodyVisitor;

class JMSSerializerBodyVisitorTest extends \PHPUnit_Framework_TestCase
{
    public function testSerializeContextConfiguration()
    {
        $expectedContext = SerializationContext::create();
        $expectedContext->setGroups('group');
        $expectedContext->setVersion(1);
        $expectedContext->setSerializeNull(true);
        $expectedContext->enableMaxDepthChecks();

        $parameter = $this->getMockBuilder(Parameter::class)->getMock();
        $parameter->expects($this->once())->method('getSentAs')->will($this->returnValue('json'));
        $parameter->expects($this->any())->method('filter')->will($this->returnValue(array()));
        $parameter->expects($this->any())
            ->method('getData')
            ->will($this->returnValueMap(
                array(
                    array('jms_serializer.groups', 'group'),
                    array('jms_serializer.version', 1),
                    array('jms_serializer.serialize_nulls', true),
                    array('jms_serializer.max_depth_checks', true)
                )
            ));

        $command = $this->getMockBuilder(CommandInterface::class)->getMock();
        $request = $this->getMockBuilder(EntityEnclosingRequest::class)
            ->disableOriginalConstructor()
            ->getMock();

        $serializer = $this->getMockBuilder(SerializerInterface::class)->getMock();
        $serializer
            ->expects($this->once())
            ->method('serialize')
            ->with(array(), 'json', $this->equalTo($expectedContext))
            ->will($this->returnValue('serialized'))
        ;

        $parser = new JMSSerializerBodyVisitor(
            $serializer,
            $this->getMockBuilder(ResponseParserInterface::class)
        );

        $ref = new \ReflectionMethod($parser, 'visit');
        $ref->setAccessible(true);

        return $ref->invoke($parser, $command, $request, $parameter, 'value');
    }
}
