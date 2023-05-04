#!/bin/bash

set -e

NODECOUNT="$(echo "MATCH (x) RETURN count(x) AS count" | cypher-shell -u "$DB_USER" -p "$DB_PASSWORD"  | tail -1)"

if [[ $NODECOUNT == 0 ]]; then
  cypher-shell -u "$DB_USER" -p "$DB_PASSWORD" --file /var/lib/neo4j/import/movies.cypher
  exit 1
fi


