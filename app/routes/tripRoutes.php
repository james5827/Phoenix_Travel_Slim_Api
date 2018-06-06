<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

/**
 * Get All Trips By Tour
 */
$app->get('/tour_trips/{tour_no}', function(Request $request, Response $response, array $args){
    try{
        $sql = 'SELECT trips.Trip_Id, trips.Departure_Date, trips.Max_Passengers, trips.Standard_Amount, trips.Tour_No, tours.Tour_Name
        FROM trips, tours
        WHERE trips.Tour_No = :tour_no
        AND trips.Tour_No = tours.Tour_no';

        $dbh = getConnection();

        $stmt = $dbh->prepare($sql);
        $stmt->bindParam('tour_no', $args["tour_no"]);
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
 * Get A Single Trip
 */
$app->get('/trip/{trip_id}', function(Request $request, Response $response, array $args){
    try{
        $sql = 'SELECT *
        FROM trips
        WHERE Trip_Id = :trip_id';

        $dbh = getConnection();
        $stmt = $dbh->prepare($sql);
        $stmt->bindParam('trip_id', $args["trip_id"]);
        $stmt->execute();
        $row = $stmt->fetchAll(PDO::FETCH_OBJ);
        $dbh = null;

        $response->getBody()->write(json_encode($row));
    }catch (PDOException $e){
        $e->getMessage();
    }
    return $response;
});



