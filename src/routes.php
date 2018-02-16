<?php

use Slim\Http\Request;
use Slim\Http\Response;
use Faker\Factory;

// Routes

$app->get('/', function (Request $request, Response $response, array $args) {
    // Sample log message
    $this->logger->info("Slim-Skeleton '/' route");

    // Render index view
    return $this->renderer->render($response, 'index.phtml', $args);
});

$app->get('/users',function(Request $request, Response $response, array $args) {
    /** @var PDO $db */
    $cities = ['Ustka','Gniezno','Konin',''];
    $db = $this->db;
    $users = [];
    foreach ($cities as $city) {
        $stmt = $db->prepare('SELECT * FROM `users` where city = :city');
        $stmt->bindParam(':city',$city);
        $stmt->execute();
        $users = array_merge($users,$stmt->fetchAll());
    }
    return $this->renderer->render($response, 'users.phtml', [
        'users' => $users
    ]);
});

$app->get('/createUsers',function(Request $request, Response $response, array $args) {
    $faker = Factory::create('pl_PL');
    /** @var PDOStatement $stmt */
    $stmt = $this->db->prepare('INSERT INTO `users` (`name`,`lastname`,`city`) VALUES (:name,:lastname,:city)');

    for($i=0;$i<=100;$i++) {
        $firstName = $faker->firstName;
        $lastName = $faker->lastName;
        $city = $faker->city;

        $stmt->bindParam(':name', $firstName, PDO::PARAM_STR);
        $stmt->bindParam(':lastname', $lastName, PDO::PARAM_STR);
        $stmt->bindParam(':city', $city, PDO::PARAM_STR);
        $stmt->execute();
    }
});