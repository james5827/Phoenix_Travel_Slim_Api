<?php

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

/**
 * Get Single Customer Details
 */
$app->get('/user/{customer_id}', function(Request $request, Response $response, array $args){

    try{
        $sql = "SELECT *
                FROM customers
                WHERE customer_id = :customer_id";

        $dbh = getConnection();
        $stmt = $dbh->prepare($sql);
        $stmt->bindParam("customer_id", $args["customer_id"]);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_OBJ);

        $response->getBody()->write(json_encode($row));

        $dbh = null;
        $stmt = null;
    }catch (PDOException $e){
       echo $e->getMessage();
    }

    return $response;
});



/**
 * Get Customers by email
 */
$app->get('/additional_passenger_email/{email}', function(Request $request, Response $response, array $args){

    try{
        $sql = "SELECT email
                FROM customers
                WHERE email LIKE CONCAT('%',:email, '%');";

        $dbh = getConnection();
        $stmt = $dbh->prepare($sql);
        $stmt->bindParam("email", $args["email"]);
        $stmt->execute();

        $row = $stmt->fetchAll(PDO::FETCH_OBJ);

        $response->getBody()->write(json_encode($row));

        $dbh = null;
        $stmt = null;
    }catch (PDOException $e){
        echo $e->getMessage();
    }

    return $response;
});


/**
 * Update User
 */
$app->put('/update_account', function(Request $request, Response $response, array $args){
    $parsedBody = $request->getParsedBody();
    $customer_id = $parsedBody["customer_id"];
    $first_name = $parsedBody["first_name"];
    $middle_initial = $parsedBody['middle_initial'];
    $last_name = $parsedBody['last_name'];
    $street_no = $parsedBody['street_no'];
    $street_name = $parsedBody['street_name'];
    $suburb = $parsedBody['suburb'];
    $postcode = $parsedBody['postcode'];
    $email = $parsedBody['email'];
    $phone = $parsedBody['phone'];

    try{
        $sql = "UPDATE customers
                SET first_name = :first_name, middle_initial = :middle_initial , last_name = :last_name,
                street_no = :street_no, street_name = :street_name, suburb = :suburb, postcode = :postcode,
                 email = :email, phone = :phone
                 WHERE customer_id = :customer_id";

        $dbh = getConnection();

        $stmt = $dbh->prepare($sql);
        $stmt->bindParam('customer_id', $customer_id);
        $stmt->bindParam('first_name', $first_name);
        $stmt->bindParam('middle_initial', $middle_initial);
        $stmt->bindParam('last_name', $last_name);
        $stmt->bindParam('street_no', $street_no);
        $stmt->bindParam('street_name', $street_name);
        $stmt->bindParam('suburb', $suburb);
        $stmt->bindParam('postcode', $postcode);
        $stmt->bindParam('email', $email);
        $stmt->bindParam('phone', $phone);

        if($stmt->execute()) {
            $response->getBody()->write(json_encode(true));
        }

        $dbh = null;
        $stmt = null;
    }catch(PDOException $e){
        return $response->withStatus(404, $e->getMessage());
    }

    return $response;
});

/**
 * Function to reset a users password
 */
$app->put('/reset_password', function(Request $request, Response $response, array $args){
    $parsedBody = $request->getParsedBody();
    $customer_id  = $parsedBody['customer_id'];
    $password = $parsedBody['password'];
    $email = $parsedBody['email'];

    try{
        $sql = "UPDATE customers SET password = :user_password WHERE customer_id = :customer_id;";
        $dbh = getConnection();

        $stmt = $dbh->prepare($sql);
        $stmt->bindParam('user_password', md5($password . $email));
        $stmt->bindParam('customer_id', $customer_id);
        if($stmt->execute())
            $response->getBody()->write(json_encode([$email, $password, $customer_id]));

        $dbh = null;
        $stmt = null;
    }catch (PDOException $e){
        return $response->withStatus(404, $e->getMessage());
    }
    return $response;
});