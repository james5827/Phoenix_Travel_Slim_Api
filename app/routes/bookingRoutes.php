<?php

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

/**
 * Get All Bookings For A Customer
 */
$app->get('/customer_bookings/{customer_id}', function(Request $request, Response $response, array $args){
    try{
        $sql = 'SELECT tb.`trip_booking_no`, tb.`trip_id`, tb.`primary_customer`, tb.`booking_date`, tb.`deposit_amount`, tr.`departure_date`, t.duration, t.tour_name, true as owner
                FROM `trip_bookings` as tb , `trips` as tr, `tours` as t
                WHERE tb.trip_Id = tr.trip_Id
                AND tr.tour_No = t.tour_no
                AND primary_customer = :customer_id

                UNION ALL

                SELECT tb.`trip_booking_no`, tb.`trip_id`, tb.`primary_customer`,tb.`booking_date`,tb.`deposit_amount`,tr.`departure_date`, tu.duration, tu.tour_name, false as owner
                FROM `customer_bookings` as cb, `trip_bookings` as tb, `trips` as tr, `tours` as tu
                WHERE cb.customer_id = :customer_id
                AND cb.trip_booking_no = tb.trip_booking_no
                AND tb.trip_id = tr.trip_id
                AND tr.tour_no = tu.tour_no
                AND cb.accepted_invite = true';


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
        $sql = 'SELECT c.first_name, c.middle_initial, c.last_name, cb.accepted_invite
            FROM `customer_bookings` as cb, `customers` as c
            WHERE cb.trip_booking_no = :trip_booking_no
            AND cb.customer_id = c.customer_id';

        $dbh = getConnection();

        $stmt = $dbh->prepare($sql);
        $stmt->bindParam('trip_booking_no', $args['trip_booking_no']);
        $stmt->execute();

        $row = $stmt->fetchAll(PDO::FETCH_OBJ);

        $dbh = null;
        $stmt = null;

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
        $sql = 'SELECT cb.trip_booking_no, tr.departure_date, c.first_name, c.middle_initial, c.last_name, tu.tour_name
                FROM `customer_bookings` as cb, `trip_bookings` as tb, `trips` as tr, `tours` as tu, customers as c
                WHERE cb.customer_id = :customer_id
                AND cb.trip_booking_no = tb.trip_booking_no
                AND tb.trip_id = tr.trip_id
                AND tr.tour_no = tu.tour_no
                AND tb.primary_customer = c.customer_id
                AND cb.accepted_invite = FALSE ';

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
        $sql = 'INSERT INTO customer_bookings (trip_booking_no, customer_id, accepted_invite)
                SELECT ?, customer_id, 0
                FROM customers
                WHERE email IN ('. $emailString .');';

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
        $sql = "DELETE FROM trip_bookings WHERE trip_booking_no = :trip_booking_no";

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
        $sql = "DELETE FROM customer_bookings WHERE trip_booking_no = :trip_booking_no AND customer_id = :customer_id";

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
        $sql = "UPDATE customer_bookings SET accepted_invite = 1 WHERE customer_id = :customer_id AND trip_booking_no = :trip_booking_no";

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