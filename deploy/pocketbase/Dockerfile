FROM ghcr.io/muchobien/pocketbase:latest

COPY pb_migrations /pb_migrations

EXPOSE 8090

CMD ["/pocketbase", "serve", "--http=0.0.0.0:8090", "--dir=/pb_data", "--migrationsDir=/pb_migrations"]
