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

$app->get('/users', function (Request $request, Response $response, array $args) {
    /** @var PDO $db */
    $workingInDay = $request->getParam('date',date('Y-m-d'));
    $cities = $request->getParam('cities',[]);
    $db = $this->db;
    $users = [];
    /** @var Get only workers from given cities */
    foreach ($cities as $city) {
        $stmt = $db->prepare('SELECT * FROM `users` where city = :city');
        $stmt->bindParam(':city', $city);
        $stmt->execute();
        $users = array_merge($users, $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    // Get all workers working in given period
    $stmtContracts = $db->prepare("SELECT contracts.*, users.name,users.lastname FROM `contracts`
          LEFT JOIN users ON users.id = contracts.user_id 
          WHERE `from` <= :from AND (`to` >= :from OR `to` is null);");

    $stmtContracts->bindValue(':from', $workingInDay,PDO::PARAM_STR);
    $stmtContracts->execute();
    $contracts = $stmtContracts->fetchAll(PDO::FETCH_ASSOC);

    //and sum their salary
    $salaryTotal = 0;
    foreach ($contracts as $contract) {
        $salaryTotal += $contract['salary'];
    }

    return $this->renderer->render($response, 'users.phtml', [
        'cities'=>$cities,
        'users' => $users,
        'contracts' => $contracts,
        'averageSalary' => $salaryTotal / count($contracts),
        'workingInDay' =>$workingInDay
    ]);
});

$app->get('/fixtures', function (Request $request, Response $response, array $args) {
    /** @var PDO $db */
    $db = $this->db;
    /** @var PDOStatement $stmt */
    $db->exec('
        DROP TABLE IF EXISTS `contracts`;
        CREATE TABLE  `contracts` (
          `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
          `from` date NOT NULL DEFAULT \'0000-00-00\',
          `to` date NOT NULL DEFAULT \'0000-00-00\',
          `dimension` decimal(10,2) NOT NULL DEFAULT \'0.00\',
          `user_id` int(10) unsigned NOT NULL DEFAULT \'0\',
          `salary` decimal(10,2) NOT NULL DEFAULT \'0.00\',
          PRIMARY KEY (`id`),
          KEY `FK_users` (`user_id`),
          CONSTRAINT `FK_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ) ENGINE=InnoDB AUTO_INCREMENT=102 DEFAULT CHARSET=utf8;'
    );
    $this->logger->info('Contract table created');

    $db->exec('
        DROP TABLE IF EXISTS `users`;
        CREATE TABLE  `users` (
          `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
          `name` varchar(45) NOT NULL DEFAULT \'\',
          `lastname` varchar(45) NOT NULL DEFAULT \'\',
          `city` varchar(100) NOT NULL DEFAULT \'\',
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB AUTO_INCREMENT=315 DEFAULT CHARSET=utf8;
    ');
    $this->logger->info('users table created');

    $db->exec('truncate `users`');
    $this->logger->info('Data in table `users` truncated');
    $db->exec('truncate `contracts`');
    $this->logger->info('Data in table `contracts` truncated');

    $faker = Factory::create('pl_PL');
    /** @var PDOStatement $stmt */
    $stmt = $this->db->prepare('INSERT INTO `users` (`name`,`lastname`,`city`) VALUES (:name,:lastname,:city)');

    for ($i = 0; $i <= 100; $i++) {
        $firstName = $faker->firstName;
        $lastName = $faker->lastName;
        $city = $faker->city;

        $stmt->bindParam(':name', $firstName, PDO::PARAM_STR);
        $stmt->bindParam(':lastname', $lastName, PDO::PARAM_STR);
        $stmt->bindParam(':city', $city, PDO::PARAM_STR);
        $stmt->execute();
        $userId = $this->db->lastInsertId();

        $dateFrom = $faker->dateTimeBetween('-1 month','+2 month');
        $dateTo = $faker->dateTimeBetween('+3 month','+12 month');

        /** @var PDOStatement $stmtContract */
        $stmtContract = $this->db->prepare('INSERT INTO `contracts` (`from`,`to`,`salary`,`dimension`,`user_id`) VALUES (:from, :to, :salary, :dimension, :userId)');
        $stmtContract->bindValue(':from',$dateFrom->format('Y-m-d'),PDO::PARAM_STR);
        $stmtContract->bindValue(':to',$dateTo->format('Y-m-d'),PDO::PARAM_STR);
        $stmtContract->bindValue(':salary',rand(100,500),PDO::PARAM_STR);
        $stmtContract->bindValue(':dimension',1,PDO::PARAM_STR);
        $stmtContract->bindValue(':userId',$userId,PDO::PARAM_INT);
        $stmtContract->execute();
    }

    $this->logger->info('Example data loaded');
});