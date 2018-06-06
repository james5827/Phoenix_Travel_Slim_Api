<?php
/**
 * Created by PhpStorm.
 * User: James O'Neill
 * Date: 22/05/2018
 * Time: 00:11
 */

namespace Tests\Functional;


class UsersTest extends CurlBaseTestCase
{
    public function testAuthMiddleware()
    {
        $response = parent::get('/');
        curl_close($response['curl']);
    }

    public function testRegister()
    {
        $json = [
            'first_name' => 'Test',
            'middle_initial' => 'T',
            'last_name' => 'Test',
            'street_no' => 12,
            'street_name' => 'Test_St',
            'suburb' => 'Test',
            'postcode' => 1234,
            'email' => 'test@test1.com',
            'password' => 'password',
            'phone' => 1234567890
        ];
       $response =  parent::post('/register', json_encode($json));

       $curl = $response['curl'];
       $this->assertEquals(200, curl_getinfo($curl, CURLINFO_HTTP_CODE), "Insert Failed");
       curl_close($curl);
    }

    public function testLogin()
    {
        $json = [
            'email' => 'fred.Smith@hit.com',
            'password' => 'password'
        ];

        $expected = md5($json['password'] . $json['email']);

        $response = parent::post('login', json_encode($json));

        $curl = $response['curl'];
        curl_close($curl);

        $this->assertEquals($expected, json_decode($response['response']));
    }

    public function testGetSingleCustomer()
    {
        $expected= (object)[
            'Customer_Id' => '1',
            'First_Name' => 'Fred',
            'Middle_Initial' => null,
            'Last_Name' => 'Smith',
            'Street_No' => '500',
            'Street_Name' => 'Waverly Road',
            'Suburb' => 'Chadstone',
            'Postcode' => '3555',
            'Email' => 'fred.Smith@hit.com',
            'Password' => md5('password' . 'fred.Smith@hit.com'),
            'Phone' => null,
            'AuthCustomer' => '1',
        ];

        $response = parent::get('/user/1');
        curl_close($response['curl']);

        $json = json_decode($response['response']);

        $this->assertEquals($expected, $json, 'Wrong Customer Record');
    }

    public function testUpdateUser()
    {
        $json = [
            'customer_id' => '2',
            'first_name' => 'Test',
            'middle_initial' => 'T',
            'last_name' => 'Test',
            'street_no' => 12,
            'street_name' => 'Test_St',
            'suburb' => 'Test',
            'postcode' => 1234,
            'email' => 'test@test2.com',
            'password' => 'password',
            'phone' => 1234567890
        ];

        $response =  parent::put('/update_account', json_encode($json));

        $curl = $response['curl'];
        $this->assertEquals(200, curl_getinfo($curl, CURLINFO_HTTP_CODE), "Update Failed");
        curl_close($curl);
    }

    public function testTestsComplete(){}
}