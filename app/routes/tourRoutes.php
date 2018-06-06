<?php

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

/**
 * Return All Tours
 */
$app->get('/tours', function(Request $request, Response $response, array $args){
    try{
        $sql = 'SELECT *
                FROM tours';

        $dbh = getConnection();

        $stmt = $dbh->prepare($sql);

        $stmt->execute();

        $row = $stmt->fetchAll(PDO::FETCH_OBJ);

        $dbh = null;
        $stmt = null;

        $response->getBody()->write(json_encode($row));
    }
    catch (PDOException $e) {
        return $response->withStatus(404, $e->getMessage());
    }

    return $response;
});