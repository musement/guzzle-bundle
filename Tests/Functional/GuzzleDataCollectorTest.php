<?php

/*
 * This file is part of the MisdGuzzleBundle for Symfony2.
 *
 * (c) University of Cambridge
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Misd\GuzzleBundle\Tests\Functional;

use Guzzle\Http\Message\Request as GuzzleRequest;
use Guzzle\Http\Message\Response as GuzzleResponse;
use Guzzle\Log\ArrayLogAdapter;
use Misd\GuzzleBundle\DataCollector\GuzzleDataCollector;
use Symfony\Component\HttpFoundation\Request as SfRequest;
use Symfony\Component\HttpFoundation\Response as SfResponse;

class GuzzleDataCollectorTest extends TestCase
{
    public function testNoDuplicateLogs()
    {
        $adapter = $this->getMockBuilder(ArrayLogAdapter::class)->getMock();
        $adapter->expects($this->any())
                ->method('getLogs')
                ->will($this->returnValue(array(
                    $this->newGuzzleLog(),
                )));

        $collector = new GuzzleDataCollector($adapter);

        $collector->collect(new SfRequest(), new SfResponse(), null);
        $collector->collect(new SfRequest(), new SfResponse(), null);

        $this->assertCount(1, $collector->getRequests());
    }

    public function testEmptyArrayWhenNoRequests()
    {
        $adapter = $this->getMockBuilder(ArrayLogAdapter::class)->getMock();
        $collector = new GuzzleDataCollector($adapter);

        $this->assertEquals(array(), $collector->getRequests());
    }

    private function newGuzzleLog()
    {
        return array(
            'message' => '',
            'extras' => array(
                'response' => new GuzzleResponse(200),
                'request' => new GuzzleRequest('GET', '/'),
            )
        );
    }
}
