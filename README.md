# Neo4j Movies Example Application

This is the php implementation of the neo4j movies example application.

## Endpoints:

|METHOD|Endpoint          | Description                                                 |
|------|------------------|-------------------------------------------------------------|
|GET   | /movie/{title}   | Fetches the information of the movie with the given title   |
|GET   | /search?q=matrix | Searches for a movie which contains the q query param       |
|GET   | /graph           | Fetches the full graph of all movie information             |

## Setup

Start via docker compose
```bash
docker-compose up
```

Migrate the database
```bash
docker-compose exec neo4j cypher-shell -u neo4j -p abcde -f /opt/project/movies.cypher
```

Access the page on the url localhost:8080.

You can obviously roll your own setup, but you will need to install a php server & neo4j server locally. The php application can be configured through environment variables.

## Configuration

The application environment can be changed with the following variables:

| VARIABLE          | DEFAULT   |
|----------------   |---------- |
| NEO4J_USER        | neo4j     |
| NEO4J_PASSWORD    | abcde     |
| NEO4J_DATABASE    | movies    |

## Where to look

The php implementation was designed to be a straight forward as possible. Everything can be found in the public/index.php file.

You can use the client yourself by using installing the client through composer: 

```bash 
composer require laudis/neo4j-php-client
``` 

Further information can be found in [github](https://github.com/laudis-technologies/neo4j-php-client).

