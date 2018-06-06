<?php
/**
 * Created by PhpStorm.
 * User: James O'Neill
 * Date: 23/05/2018
 * Time: 01:23
 */

namespace Tests\Functional;


class ReviewsTest extends CurlBaseTestCase
{
    public function testInsertReview()
    {
        $json = [
            'trip_id' => 1,
            'customer_id' => 1,
            'rating' => 5,
            'general_feedback' => 'Test Data',
            'likes' => 'Test Test',
            'dislikes' => 'Test Test'
        ];

        $response =  parent::post('/insert_review', json_encode($json));

        $curl = $response['curl'];
        $this->assertEquals(200, curl_getinfo($curl, CURLINFO_HTTP_CODE), "Insert Failed");
        curl_close($curl);
    }

    public function testTestsComplete(){}
}