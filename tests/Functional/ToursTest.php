<?php
/**
 * Created by PhpStorm.
 * User: James O'Neill
 * Date: 21/05/2018
 * Time: 11:47
 */

namespace Tests\Functional;


class ToursTest extends CurlBaseTestCase
{
    public function testGetAllTours()
    {
        $response = parent::get('/tours');
        curl_close($response['curl']);

        $json = json_decode($response['response']);
        $this->assertCount(3, $json, "\nIncorrect Number Of Tours");
    }

    public function testTestsComplete(){}
}