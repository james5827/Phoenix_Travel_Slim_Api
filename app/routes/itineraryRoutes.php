<?php

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

/**
 * Get Itineraries For A Tour
 */
$app->get('/itineraries/{tour_no}', function(Request $request, Response $response, array $args){
    try{
        $sql = 'SELECT * 
                FROM `itineraries` 
                WHERE Tour_No = :tour_no';

        $dbh = getConnection();

        $stmt = $dbh->prepare($sql);
        $stmt->bindParam('tour_no', $args['tour_no']);
        $stmt->execute();

        $row = $stmt->fetchAll(PDO::FETCH_OBJ);

        $dbh = null;

        $response->getBody()->write(json_encode($row));
    }catch (PDOException $e){
        $e->getMessage();
    }

    return $response;
});