ARG NEO4J_VERSION=5

FROM neo4j:$NEO4J_VERSION

COPY import/ /var/lib/neo4j/import/
COPY healthcheck.bash /healthcheck.bash

RUN chmod +x /healthcheck.bash

HEALTHCHECK --interval=10s \
    --timeout=60s \
    --start-period=5s \
    --retries=10 \
    CMD /healthcheck.bash