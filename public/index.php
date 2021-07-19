<?php

use Slim\Factory\AppFactory;
use Slim\Middleware\MethodOverrideMiddleware; //for changing method from post to patch
use DI\Container;


require __DIR__ . '/../vendor/autoload.php';


//for phtml files
$container = new Container();
$container->set('renderer', function () {
    return new \Slim\Views\PhpRenderer(__DIR__ . '/../templates');
});

//init App with requires
$app = AppFactory::createFromContainer($container);
$app->addErrorMiddleware(true, true, true);
//for flash messages
session_start();
$container->set('flash', function () {
    return new \Slim\Flash\Messages();
});
//names for routing
$router = $app->getRouteCollector()->getRouteParser();

//bd for data
$repo = new App\SchoolRepository();

//for changing method from post to patch
$app->add(MethodOverrideMiddleware::class);

$app->get("/", function ($request, $response) use ($router) {
    $url = $router->urlFor('schools');
    $response = $response->write("<a href=$url>List of schools</a>");
    return $this->get('renderer')->render($response, "index.phtml");
})->setName("main");

$app->get("/schools", function ($request, $response) use ($repo) {
    $schoolData = $repo->read();
    $flash = $this->get('flash')->getMessages();
    $params = [
        'schoolData' => $schoolData,
        'flash' => $flash
    ];
    return $this->get('renderer')->render($response, "schools/index.phtml", $params);
})->setName("schools");

$app->get("/schools/new", function ($request, $response) {
    $params = [
        'errors' => [],
        'schoolData' => []
    ];
    return $this->get('renderer')->render($response, "schools/new.phtml", $params);
})->setName("school");

$app->post("/schools", function ($request, $response) use ($router, $repo) {
    $schoolData = $request->getParsedBodyParam('school');
    $genetator = \App\Generator::generate(100);
    $schoolId = $genetator[rand(0, 99)];
    $schoolData['id'] = $schoolId;
    $validator = new \App\Validator();
    $errors = $validator->validate($schoolData);
    if (count($errors) === 0) {
        $repo->save($schoolData);
        $this->get('flash')->addMessage('success', 'School has been added');
        $url = $router->urlFor("schools");
        return $response->withRedirect($url);
    }

    $params = [
        'errors' => $errors,
        '$schoolData' => $schoolData
    ];
    $response = $response->withStatus(422);
    return $this->get('renderer')->render($response, "schools/new.phtml", $params);
});

$app->delete("/schools/{id}", function ($request, $response, array $args) use ($router, $repo) {
    $id = $args['id'];
    $repo->destroy($id);
    $this->get('flash')->addMessage('success', 'School was removed');
    return $response->withRedirect($router->urlFor('schools'));
});

$app->run();


