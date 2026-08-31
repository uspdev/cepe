Passos para subir:

    git clone git@github.com:uspdev/cepe.git
    cd cepe
    cp .env.example .env
    docker compose up --build
    docker exec -it cepe php artisan migrate
