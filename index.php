<?php

declare(strict_types=1);

use Laudis\Neo4j\Authentication\Authenticate;
use Laudis\Neo4j\ClientBuilder;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

require __DIR__ . '/vendor/autoload.php';

$neo4jVersion = getenv('NEO4J_VERSION');
$neo4jVersion = $neo4jVersion === false ? '' : $neo4jVersion;

$database = getenv('NEO4J_DATABASE');
$database = $database === false ? 'movies' : $database;

$uri = getenv('NEO4J_URI');
$uri = $uri === false ? 'neo4j+s://demo.neo4jlabs.com' : $uri;
if (!str_starts_with($neo4jVersion, '3')) {
    $uri = sprintf("%s?database=%s", $uri, $database);
}

$user = getenv('NEO4J_USER');
$user = $user === false ? 'movies' : $user;

$password = getenv('NEO4J_PASSWORD');
$password = $password === false ? 'movies' : $password;

$auth = Authenticate::basic($user, $password);
$client = ClientBuilder::create()
    ->withDriver('default', $uri, $auth)
    ->build();

$app = AppFactory::create();
$app->addRoutingMiddleware();
$errorMiddleware = $app->addErrorMiddleware(true, true, true);

$app->get('/', static function (Request $request, Response $response) {
    $response->getBody()->write(file_get_contents(__DIR__ . '/public/index.html'));
    return $response;
});

$app->get('/movie/{title}', static function (Request $request, Response $response, array $args) use ($client) {
    $result = $client->run(<<<'CYPHER'
MATCH (movie:Movie {title:$title})
OPTIONAL MATCH (movie)<-[r]-(person:Person)
RETURN movie.title AS title,
       COLLECT({name:person.name, job:head(split(toLower(type(r)),'_')), role:r.roles}) AS cast 
LIMIT 1
CYPHER, $args);

    if ($result->count() === 0) {
        $response->getBody()->write(json_encode([
            'message' => 'Could not find movie with title: "' . $args['title'] . '"'
        ], JSON_THROW_ON_ERROR));
        return $response->withStatus(404);
    }

    $response->getBody()->write(json_encode($result->first(), JSON_THROW_ON_ERROR));
    return $response;
});

$app->get('/search', static function (Request $request, Response $response) use ($client) {
    $result = $client->run(<<<'CYPHER'
MATCH (movie:Movie) 
WHERE TOLOWER(movie.title) CONTAINS TOLOWER($title)
RETURN movie {.title, .tagline, .votes, .released}
CYPHER, ['title' => $request->getQueryParams()['q'] ?? '']);

    $response->getBody()->write(json_encode($result->jsonSerialize()['result'], JSON_THROW_ON_ERROR));
    return $response;
});

$app->get('/graph', static function (Request $request, Response $response) use ($client) {
    $result = $client->run(<<<'CYPHER'
MATCH (m:Movie)<-[:ACTED_IN]-(a:Person)
RETURN m.title AS movie, collect(a.name) AS cast
CYPHER
    );

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
                $mappings['Person:' . $person] = $number;
                $tbr['nodes'][] = ['title' => $person, 'label' => 'actor'];
            }
            $tbr['links'][] = ['source' => $number, 'target' => $mappings['Movie:' . $movieTitle]];
        }
    }

    $response->getBody()->write(json_encode($tbr, JSON_THROW_ON_ERROR));

    return $response;
});

$app->run();

