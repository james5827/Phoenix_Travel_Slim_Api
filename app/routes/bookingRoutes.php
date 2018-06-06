<?php

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

/**
 * Get All Bookings For A Customer
 */
$app->get('/customer_bookings/{customer_id}', function(Request $request, Response $response, array $args){
    try{
        $sql = 'SELECT tb.`Trip_Booking_No`, tb.`Trip_Id`, tb.`Primary_Customer`, tb.`Booking_Date`, tb.`Deposit_Amount`, tr.`Departure_Date`, t.Duration, t.Tour_Name, true as Owner
                FROM `trip_bookings` as tb , `trips` as tr, `tours` as t
                WHERE tb.Trip_Id = tr.Trip_Id
                AND tr.Tour_No = t.Tour_no
                AND Primary_Customer = :customer_id

                UNION ALL

                SELECT tb.`Trip_Booking_No`, tb.`Trip_Id`, tb.`Primary_Customer`,tb.`Booking_Date`,tb.`Deposit_Amount`,tr.`Departure_Date`, tu.Duration, tu.Tour_Name, false as Owner
                FROM `customer_bookings` as cb, `trip_bookings` as tb, `trips` as tr, `tours` as tu
                WHERE cb.Customer_Id = :customer_id
                AND cb.Trip_Booking_No = tb.Trip_Booking_No
                AND tb.Trip_Id = tr.Trip_Id
                AND tr.Tour_No = tu.Tour_no
                AND cb.Accepted_Invite = true';


        //TODO :look at this later booking need to list bookings where you are invited

        $dbh = getConnection();

        $stmt = $dbh->prepare($sql);
        $stmt->bindParam('customer_id', $args['customer_id']);
        $stmt->execute();

        $row = $stmt->fetchAll(PDO::FETCH_OBJ);

        $dbh = null;

        $response->getBody()->write(json_encode($row));
    }catch (PDOException $e){
        $e->getMessage();
    }

    return $response;
});

/**
 * Get Additional Passengers For A Booking
 */
$app->get('/additional_customers_bookings/{trip_booking_no}', function(Request $request, Response $response, array $args){
    try{
        $sql = 'SELECT c.First_Name, c.Middle_Initial, c.Last_Name, cb.Accepted_Invite
            FROM `customer_bookings` as cb, `customers` as c
            WHERE cb.Trip_Booking_No = :trip_booking_no
            AND cb.Customer_Id = c.Customer_Id';

        $dbh = getConnection();

        $stmt = $dbh->prepare($sql);
        $stmt->bindParam('trip_booking_no', $args['trip_booking_no']);
        $stmt->execute();

        $row = $stmt->fetchAll(PDO::FETCH_OBJ);

        $dbh = null;

        $response->getBody()->write(json_encode($row));
    }catch (PDOException $e){
        $e->getMessage();
    }

    return $response;
});

/**
 * Get Customers Invites
 */
$app->get('/customer_invites/{customer_id}', function(Request $request, Response $response, array $args){
    try{
        $sql = 'SELECT cb.Trip_Booking_No, tr.Departure_Date, c.First_Name, c.Middle_Initial, c.Last_Name, tu.Tour_Name
                FROM `customer_bookings` as cb, `trip_bookings` as tb, `trips` as tr, `tours` as tu, customers as c
                WHERE cb.Customer_Id = :customer_id
                AND cb.Trip_Booking_No = tb.Trip_Booking_No
                AND tb.Trip_Id = tr.Trip_Id
                AND tr.Tour_No = tu.Tour_no
                AND tb.Primary_Customer = c.Customer_Id
                AND cb.Accepted_Invite = FALSE ';

        $dbh = getConnection();

        $stmt = $dbh->prepare($sql);
        $stmt->bindParam('customer_id', $args['customer_id']);
        $stmt->execute();

        $row = $stmt->fetchAll(PDO::FETCH_OBJ);

        $dbh = null;
        $stmt = null;

        $response->getBody()->write(json_encode($row));
    }
    catch(PDOException $e){

    }
});

/**
 * Book Trip
 */
$app->post('/book_trip', function(Request $request, Response $response, array $args){
    $parsedBody = $request->getParsedBody();
    $trip_id = $parsedBody['trip_id'];
    $primary_customer = $parsedBody['primary_customer'];
    $booking_date = date("Y-m-d");
    $deposit_amount = $parsedBody['deposit_amount'];

    try{
        $sql = "INSERT INTO trip_bookings VALUES(NULL, :trip_id, :primary_customer, :booking_date, :deposit_amount)";

        $dbh = getConnection();

        $stmt = $dbh->prepare($sql);
        $stmt->bindParam('trip_id', $trip_id);
        $stmt->bindParam('primary_customer', $primary_customer);
        $stmt->bindParam('booking_date', $booking_date);
        $stmt->bindParam('deposit_amount', $deposit_amount);

        $test = $stmt->execute();

        if($test){
            $return = [
                'result' => 'true',
                'trip_booking_no' => $dbh->lastInsertId()
            ];

            $response->getBody()->write(json_encode($return));
        }
    }catch(PDOException $e){
        return $response->withStatus(404, $e->getMessage());
    }

    return $response;
});

/**
 * Insert Additional Passengers
 */
$app->post('/invite_passenger', function(Request $request, Response $response, array $args){
    $parsedBody = $request->getParsedBody();
    $tripNo_emailArray = $parsedBody['tripNo_emailArray'];

    $emailString = '';
    for($i = 1; $i<count($tripNo_emailArray); ++$i)
        $emailString .= "?,";
    $emailString = trim($emailString, ',');

    try{
        $sql = 'INSERT INTO customer_bookings (Trip_Booking_No, Customer_Id, Accepted_Invite)
                SELECT ?, Customer_Id, 0
                FROM customers
                WHERE Email IN ('. $emailString .');';

        $dbh = getConnection();

        $stmt = $dbh->prepare($sql);
        if($stmt->execute($tripNo_emailArray))
            $response->getBody()->write(json_encode(true));

        $dbh = null;
        $stmt = null;
    }catch(PDOException $e){
        return $response->withStatus(404, "Insert Error For Additional Passengers");
    }

    return $response;
});

/**
 * Delete A Booking
 */
$app->delete("/delete_booking/{trip_booking_no}", function(Request $request, Response $response, array $args){
    try{
        $sql = "DELETE FROM trip_bookings WHERE Trip_Booking_No = :trip_booking_no";

        $dbh = getConnection();

        $stmt = $dbh->prepare($sql);
        $stmt->bindParam("trip_booking_no", $args["trip_booking_no"]);

        if($stmt->execute())
            $response->getBody()->write(json_encode(true));
        $dbh = null;
        $stmt = null;
    }catch(PDOException $e){
       return $response->withStatus(404, $e->getMessage());
    }

    return $response;
});

/**
 * Delete an additional customer from a booking
 * RefuseInvite
 */
$app->delete("/refuse_invite/{trip_booking_no}/{customer_id}", function(Request $request, Response $response, array $args){
    try{
        $sql = "DELETE FROM customer_bookings WHERE Trip_Booking_No = :trip_booking_no AND Customer_Id = :customer_id";

        $dbh = getConnection();

        $stmt = $dbh->prepare($sql);
        $stmt->bindParam("trip_booking_no", $args["trip_booking_no"]);
        $stmt->bindParam("customer_id", $args["customer_id"]);
        if($stmt->execute())
            $response->getBody()->write(json_encode(true));

        $dbh = null;
        $stmt = null;
    }catch(PDOException $e){
        echo $e->getMessage();
    }
});

$app->put('/accept_invite/{trip_booking_no}/{customer_id}', function(Request $request, Response $response, array $args){
    try{
        $sql = "UPDATE customer_bookings SET Accepted_Invite = 1 WHERE Customer_Id = :customer_id AND Trip_Booking_No = :trip_booking_no";

        $dbh = getConnection();

        $stmt = $dbh->prepare($sql);
        $stmt->bindParam('customer_id', $args['customer_id']);
        $stmt->bindParam('trip_booking_no', $args['trip_booking_no']);

        if($stmt->execute())
            $response->getBody()->write(json_encode(true));

        $dbh = null;
        $stmt = null;
    }catch(PDOException $e){
        return $response->withStatus(404, $e->getMessage());
    }

    return $response;
});