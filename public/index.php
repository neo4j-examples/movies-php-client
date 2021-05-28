<?php

declare(strict_types=1);

use Laudis\Neo4j\ClientBuilder;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

$user = getenv('NEO4J_USER');
$user = $user === false ? 'neo4j' : $user;

$password = getenv('NEO4J_PASSWORD');
$password = $password === false ? 'abcde' : $password;

$client = ClientBuilder::create()
    ->withDriver('default', sprintf('bolt://%s:%s@neo4j?database=%s', $user, $password, getenv('NEO4J_DATABASE')))
    ->build();

$app = AppFactory::create();
$app->addRoutingMiddleware();
$errorMiddleware = $app->addErrorMiddleware(true, true, true);

$app->get('/', static function (Request $request, Response $response) {
    $response->getBody()->write(file_get_contents(__DIR__ . '/index.html'));
    return $response;
});

$app->get('/movie/{title}', static function (Request $request, Response $response, array $args) use ($client) {
    $result = $client->run(<<<'CYPHER'
MATCH (movie:Movie {title:$title})
OPTIONAL MATCH (movie)<-[r]-(person:Person)
WITH movie.title AS title,
     collect({name:person.name, job:head(split(toLower(type(r)),'_')), role:r.roles}) AS cast 
LIMIT 1
UNWIND cast AS c 
RETURN c.name AS name, c.job AS job, c.role AS role
CYPHER, $args);

    if ($result->count() === 0) {
        $response->getBody()->write(json_encode([
            'message' => 'Could not find movie with title: "' . $args['title'] . '"'
        ], JSON_THROW_ON_ERROR));
        return $response->withStatus(404);
    }

    $response->getBody()->write(json_encode(['cast' => $result], JSON_THROW_ON_ERROR));
    return $response;
});

$app->get('/search', static function (Request $request, Response $response) use ($client) {
    $result = $client->run(<<<'CYPHER'
MATCH (movie:Movie) 
WHERE toLower(movie.title) contains toLower($title)
RETURN {title: movie.title, released: movie.released, tagline: movie.tagline} as movie
CYPHER, ['title' => $request->getQueryParams()['q'] ?? '']);

    $response->getBody()->write(json_encode($result, JSON_THROW_ON_ERROR));
    return $response;
});

$app->get('/graph', static function (Request $request, Response $response) use ($client) {
    $result = $client->run(<<<'CYPHER'
MATCH (m:Movie)<-[:ACTED_IN]-(a:Person)
RETURN m.title AS movie, collect(a.name) AS cast
CYPHER);

    $tbr = ['nodes' => [], 'links' => []];
    $mappings = [];

    foreach ($result as $row) {
        $movieTitle = $row->get('movie');
        $mappings['Movie:' . $movieTitle] = count($mappings);
        $tbr['nodes'][] = ['title' => $movieTitle, 'label' => 'movie'];
        foreach ($row->get('cast') as $person) {
            $number = $mappings['Person:' . $person] ?? null;
            if ($number === null) {
                $number = count($mappings);
                $mappings['Person:' .  $person] = $number;
                $tbr['nodes'][] = ['title' => $person, 'label' => 'actor'];
            }
            $tbr['links'][] = ['source' => $number, 'target' => $mappings['Movie:' . $movieTitle]];
        }
    }

    $response->getBody()->write(json_encode($tbr, JSON_THROW_ON_ERROR));

    return $response;
});

// Run app
$app->run();

