<?php

use Slim\Http\Request;
use Slim\Http\Response;

// Routes

$app->get('/', function (Request $request, Response $response) {
    $response->getBody()->write(getenv('DATABASE_URL'));
    $response->getBody()->write("\n");
    $response->getBody()->write("\n\n");

    try{
        $sql = "SELECT * FROM tours;";

        $dbh = getConnection();
        $stmt = $dbh->prepare($sql);
        $stmt->execute();
        $row = $stmt->fetchAll(PDO::FETCH_OBJ);

        $response->write(json_encode($row));

    }catch (PDOException $exception){
        $response->write($exception->getMessage());
    }

    return $response;
});

function getConnection()
{
    $url = parse_url(getenv('DATABASE_URL'));

    $dbhost = $url['host'];
    $dbPort = $url['port'];
    $dbUser = $url['user'];
    $dbPass = $url['pass'];
    $dbName = ltrim($url["path"], "/");

    echo $dbhost;
    echo $dbPort;
    echo $dbName;
    echo $dbUser;
    echo $dbPass;


    $dbh = new PDO("pgsql:host=$dbhost;port=$dbPort;dbname=$dbName", $dbUser, $dbPass);
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    return $dbh;
}

////TODO::REMOVE THIS MIDDLEWARE THAT LETS IT WORK IN CHROME
$app->add(function(Request $request, Response $response, $next){
    $response = $next($request, $response);

    return $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Headers', '*')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
});

$authMiddleware = function (Request $request, Response $response, $next){
    try{
        $sql = "SELECT Password FROM customers WHERE Password = :authKey";

        $dbh = getConnection();
        $stmt = $dbh->prepare($sql);
        $stmt->bindParam('authKey', $request->getHeader("HTTP_AUTH")[0]);
        $stmt->execute();

        if($stmt->rowCount() !== 1){
            throw new PDOException("Auth Key Not Found");
        }

        $stmt = null;
    }catch (PDOException $e){
        return $response->withStatus(404, $e->getMessage());
    }

    $response = $next($request->withAttribute("connection", $dbh), $response);
    return $response;
};

/**
 * Insert user
 */
$app->post('/register', function(Request $request, Response $response, array $args){
    $parsedBody = $request->getParsedBody();
    $first_name = $parsedBody["first_name"];
    $middle_initial = $parsedBody['middle_initial'];
    $last_name = $parsedBody['last_name'];
    $street_no = $parsedBody['street_no'];
    $street_name = $parsedBody['street_name'];
    $suburb = $parsedBody['suburb'];
    $postcode = $parsedBody['postcode'];
    $email = $parsedBody['email'];
    $password = $parsedBody['password'];
    $phone = $parsedBody['phone'];

    try{
        $sql = "INSERT INTO customers VALUES(NULL,
                :first_name, :middle_initial, :last_name,
                :street_no, :street_name, :suburb, :postcode,
                :email, :password, :phone, 0)";

        $dbh = getConnection();

        $stmt = $dbh->prepare($sql);
        $stmt->bindParam('first_name', $first_name);
        $stmt->bindParam('middle_initial', $middle_initial);
        $stmt->bindParam('last_name', $last_name);
        $stmt->bindParam('street_no', $street_no);
        $stmt->bindParam('street_name', $street_name);
        $stmt->bindParam('suburb', $suburb);
        $stmt->bindParam('postcode', $postcode);
        $stmt->bindParam('email', $email);
        $stmt->bindParam('password', md5($password . $email));
        $stmt->bindParam('phone', $phone);
        $stmt->execute();

        $count = $stmt->rowCount();

        $dbh = null;
        $stmt = null;
    }catch(PDOException $e){
        return $response->withStatus(404, $e->getMessage());
    }
    return $response->getBody()->write(json_encode(md5($password . $email)));
});

/**
 * Login User
 */
$app->post('/login', function(Request $request, Response $response, array $args){
    $parsedBody = $request->getParsedBody();
    $email = $parsedBody["email"];
    $password = $parsedBody["password"];

    try{
        $sql = "SELECT * FROM phoenix_travel.customers WHERE email = :email AND password = :pass";

        $dbh = getConnection();

        $stmt = $dbh->prepare($sql);
        $stmt->bindParam('email', $email);
        $stmt->bindParam('pass', md5($password . $email));
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_OBJ);

        if($stmt->rowCount() === 1){
            $response->getBody()->write(json_encode([$row->Customer_Id, $row->Password, $row->Email]));
        }
        else{
            return $response->withStatus(404, "Invalid Login Details");
        }

        $dbh = null;
        $stmt = null;

    }catch(PDOException $e){
        if($e->getCode() === 1062){
            return $response->withStatus(404, $e->getMessage());
        }
        return $response->withStatus(404, $e->getMessage());
    }

    return $response;
});

$app->group('', function() use ($app){
    require __DIR__ . "/../app/routes/tourRoutes.php";
    require __DIR__ . "/../app/routes/tripRoutes.php";
    require __DIR__ . "/../app/routes/reviewRoutes.php";
    require __DIR__ . "/../app/routes/userRoutes.php";
    require __DIR__ . "/../app/routes/bookingRoutes.php";
    require __DIR__ . "/../app/routes/itineraryRoutes.php";
})->add($authMiddleware);