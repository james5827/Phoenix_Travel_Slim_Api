<?php
/**
 * Created by PhpStorm.
 * User: James O'Neill
 * Date: 23/05/2018
 * Time: 01:11
 */

namespace Tests\Functional;


class BookingsTest extends CurlBaseTestCase
{
    public function testViewUserBookings()
    {
        $response = parent::get('/customer_bookings/2');
        curl_close($response['curl']);

        $json = json_decode($response['response']);
        $this->assertCount(2, $json, "\nIncorrect Number of Bookings For This User");
    }

    public function testTestsComplete(){}
}