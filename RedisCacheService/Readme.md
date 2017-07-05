Redis cache implementation for whole page caching

Configure service as below
snc_redis:
    clients:
        default:
            type: predis
            alias: default
            dsn: %snc_redis_dsn%
        slave:
            type: predis
            alias: slave
            dsn: %snc_redis_dsn_slave%
        session:
            type: predis
            alias: session
            dsn: %snc_redis_dsn%
    session:
        client: session
        ttl: 3600
        use_as_default: true
    doctrine:
        metadata_cache:
            client: default
            entity_manager: default
            document_manager: default
        result_cache:
            client: default
            entity_manager: [default]
            namespace: "dcrc:"
        query_cache:
            client: default
            entity_manager: default
