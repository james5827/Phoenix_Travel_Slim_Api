<?php
/**
 * Created by PhpStorm.
 * User: James O'Neill
 * Date: 23/05/2018
 * Time: 01:17
 */

namespace Tests\Functional;


class ItinerariesTest extends CurlBaseTestCase
{
    public function testGetTourItineraries()
    {
        $response = parent::get('/itineraries/2');
        curl_close($response['curl']);

        $json = json_decode($response['response']);
        $this->assertCount(1, $json, "\nIncorrect Number of Itineraries for this tour");
    }

    public function testTestsComplete(){}
}