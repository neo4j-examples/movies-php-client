<?php

declare(strict_types=1);

use Laudis\Neo4j\Authentication\Authenticate;
use Laudis\Neo4j\Basic\Driver;
use Laudis\Neo4j\Types\ArrayList;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

require __DIR__.'/vendor/autoload.php';

$database = getenv('NEO4J_DATABASE');
$database = false === $database ? 'movies' : $database;

$uri = getenv('NEO4J_URI');
$uri = false === $uri ? sprintf('neo4j+s://demo.neo4jlabs.com?database=%s', $database) : $uri;

$user = getenv('NEO4J_USER');
$user = false === $user ? 'movies' : $user;

$password = getenv('NEO4J_PASSWORD');
$password = false === $password ? 'movies' : $password;

$auth = Authenticate::basic($user, $password);
$driver = Driver::create($uri, authenticate: $auth);
$session = $driver->createSession();

$app = AppFactory::create();
$app->addRoutingMiddleware();
$app->addErrorMiddleware(true, true, true);

$app->get('/', static function (Request $request, Response $response) {
    $response->getBody()->write(file_get_contents(__DIR__.'/public/index.html'));

    return $response;
});

$app->get('/movie/{title}', static function (Request $request, Response $response, array $args) use ($session) {
    /** @var array{title: string} $args */
    $result = $session->run(<<<'CYPHER'
MATCH (movie:Movie {title:$title})
OPTIONAL MATCH (movie)<-[r]-(person:Person)
RETURN movie.title AS title,
       COLLECT({name:person.name, job:head(split(toLower(type(r)),'_')), role:r.roles}) AS cast 
LIMIT 1
CYPHER, $args);

    if (0 === $result->count()) {
        $response->getBody()->write(json_encode([
            'message' => 'Could not find movie with title: "'.$args['title'].'"',
        ], JSON_THROW_ON_ERROR));

        return $response->withStatus(404);
    }

    $response->getBody()->write(json_encode($result->first(), JSON_THROW_ON_ERROR));

    return $response;
});

$app->get('/search', static function (Request $request, Response $response) use ($session) {
    $result = $session->run(<<<'CYPHER'
MATCH (movie:Movie) 
WHERE TOLOWER(movie.title) CONTAINS TOLOWER($title)
RETURN movie {.title, .tagline, .votes, .released}
CYPHER, ['title' => $request->getQueryParams()['q'] ?? '']);

    $response->getBody()->write(json_encode($result->getResults(), JSON_THROW_ON_ERROR));

    return $response;
});

$app->post('/movie/vote/{title}', static function (Request $request, Response $response, array $args) use ($session) {
    $result = $session->run(
        'MATCH (m:Movie {title: $title}) SET m.votes = COALESCE(m.votes, 0) + 1',
        ['title' => $args['title']]);

    $updates = $result->getSummary()->getCounters()->propertiesSet();
    $response->getBody()->write(json_encode(['updates' => $updates], JSON_THROW_ON_ERROR));

    return $response;
});

$app->get('/graph', static function (Request $request, Response $response) use ($session) {
    $result = $session->run(<<<'CYPHER'
MATCH (m:Movie)<-[:ACTED_IN]-(a:Person)
RETURN m.title AS movie, collect(a.name) AS cast
CYPHER
    );

    $tbr = ['nodes' => [], 'links' => []];
    $mappings = [];

    foreach ($result as $row) {
        $movieTitle = $row->getAsString('movie');
        $mappings['Movie:'.$movieTitle] = count($mappings);
        $tbr['nodes'][] = ['title' => $movieTitle, 'label' => 'movie'];
        /** @var ArrayList<string> $cast */
        $cast = $row->getAsArrayList('cast');
        foreach ($cast as $person) {
            $number = $mappings['Person:'.$person] ?? null;
            if (null === $number) {
                $number = count($mappings);
                $mappings['Person:'.$person] = $number;
                $tbr['nodes'][] = ['title' => $person, 'label' => 'actor'];
            }
            $tbr['links'][] = ['source' => $number, 'target' => $mappings['Movie:'.$movieTitle]];
        }
    }

    $response->getBody()->write(json_encode($tbr, JSON_THROW_ON_ERROR));

    return $response;
});

$app->run();
