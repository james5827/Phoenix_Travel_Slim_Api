<?php

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

/**
 * Get all reviews for a tour.
 */
$app->get('/tour_reviews/{tour_no}', function(Request $request, Response $response, array $args){
    try{
        $sql = 'SELECT trips.Tour_No, trips.Trip_Id, trips.Departure_Date ,c.Customer_Id, c.Rating, c.General_Feedback, c.Likes, c.Dislikes
                FROM customer_reviews as c
                INNER JOIN trips ON trips.Trip_Id = c.Trip_Id
                WHERE trips.Tour_No = :tour_no;';

        $dbh = getConnection();
        $stmt = $dbh->prepare($sql);
        $stmt->bindParam("tour_no", $args["tour_no"]);
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
 * Get all reviews From A Customers
 */
$app->get('/customer_reviews/{customer_id}', function(Request $request, Response $response, array $args){
    try{
        $sql = "SELECT cr.Trip_Id, cr.Customer_Id, cr.Dislikes, cr.General_Feedback, cr.Likes, cr.Rating, t.Tour_no, t.Tour_Name
                FROM customer_reviews as cr, trips as tr, tours as t
                WHERE cr.Trip_Id = tr.Trip_Id
                AND tr.Tour_No = t.Tour_no
                AND cr.Customer_Id = :customer_id
                ORDER BY tr.Tour_No DESC";

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
 * Insert A Review
 */
$app->post('/insert_review', function(Request $request, Response $response, array $args){
    $parsedBody = $request->getParsedBody();
    $trip_id = $parsedBody["trip_id"];
    $customer_id = $parsedBody["customer_id"];
    $rating = $parsedBody["rating"];
    $general_feedback = $parsedBody["general_feedback"];
    $likes = $parsedBody["likes"];
    $dislikes = $parsedBody["dislikes"];

    try{
        $sql = "INSERT INTO customer_reviews VALUES(:trip_id, :customer_id, :rating, :general_feedback, :likes, :dislikes)";

        $dbh = getConnection();
        $stmt = $dbh->prepare($sql);
        $stmt->bindParam("trip_id", $trip_id);
        $stmt->bindParam("customer_id", $customer_id);
        $stmt->bindParam("rating", $rating);
        $stmt->bindParam("general_feedback", $general_feedback);
        $stmt->bindParam("likes", $likes);
        $stmt->bindParam("dislikes", $dislikes);

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
 * Delete A Review
 */
$app->delete('/delete_review/{trip_id}/{customer_id}', function(Request $request, Response $response, array $args){
    try{
        $sql = "DELETE FROM customer_reviews WHERE Customer_Id = :customer_id AND Trip_Id = :trip_id";
        $dbh = getConnection();

        $stmt = $dbh->prepare($sql);
        $stmt->bindParam('trip_id', $args['trip_id']);
        $stmt->bindParam('customer_id', $args['customer_id']);
        $stmt->execute();

        $dbh=null;
    }catch(PDOException $e){
        echo $e->getMessage();
    }
});
