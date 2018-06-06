<?php
namespace Tests\Functional;

class CurlBaseTestCase extends \PHPUnit_Framework_TestCase
{
    protected $baseUrl = "http://localhost/index.php";

    public function setup()
    {
        parent::setup();
        $testName = str_replace('test', "", $this->getName());
        print_r(" " . $testName . "\n");
    }

    public function get($route)
    {
        $curl = $this->init($route, []);

        $response = $this->executeCurl($curl);

        return ['response' => $response, 'curl' => $curl];
    }

    public function post($route, $json)
    {
        $curl = $this->init($route, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($json),
        ]);

        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $json);

        $response = $this->executeCurl($curl);

        return ['response' => $response, 'curl' => $curl];
    }

    public function put($route, $json)
    {
        $curl = $this->init($route, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($json),
        ]);

        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($curl, CURLOPT_POSTFIELDS, $json);

        $response = $this->executeCurl($curl);

        return ['response' => $response, 'curl' => $curl];
    }

    public function delete()
    {

    }

    /**
     * Instantiates cURL object and
     * sets default options
     *
     * @param $route
     * @param $headers
     * @return resource
     */
    public function init($route, $headers)
    {
        array_push($headers, 'Auth: ' . md5('password' . 'fred.Smith@hit.com'));

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $this->baseUrl . $route);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_HEADERFUNCTION,
            function($curl, $header){
                print_r($header);
                return strlen($header);
            }
        );

        return $curl;
    }

    /**
     * Executes the curl returns the response
     * or fails the test
     *
     * @param $curl
     * @return mixed
     */
    public function executeCurl($curl)
    {
        $response = curl_exec($curl);

        if($response === false){
            $this->fail(curl_error($curl));
        }

        if($response !== ''){
            print_r($response);
            print_r("\n\n");
        }

        return $response;
    }
}