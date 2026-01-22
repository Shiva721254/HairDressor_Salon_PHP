# Docker template for PHP projects
This repository provides a starting template for PHP application development.

It contains:
* NGINX webserver
* PHP FastCGI Process Manager with PDO MySQL support
* MariaDB (GPL MySQL fork)
* PHPMyAdmin
* Composer
* Composer package [nikic/fast-route](https://github.com/nikic/FastRoute) for routing

## Setup

1. Install Docker Desktop on Windows or Mac, or Docker Engine on Linux.
1. Clone the project

## Usage

In a terminal, from the cloned project folder, run:
```bash
docker compose up
```

### Composer Autoload

This template is configured to use Composer for PSR-4 autoloading:

- Namespace `App\\` is mapped to `app/src/`.

To install dependencies and generate the autoloader, run:

```bash
docker compose run --rm php composer install
```

If you add new classes or change namespaces, regenerate the autoloader:

```bash
docker compose run --rm php composer dump-autoload
```

Example usage is wired in `app/public/index.php` and a sample class exists at `app/src/hello.php`.

### NGINX

NGINX will now serve files in the app/public folder.

Go to [http://localhost/hello.php](http://localhost/hello.php). You should see a hello world message.

### PHPMyAdmin

PHPMyAdmin provides basic database administration. It is accessible at [localhost:8080](localhost:8080).

Credentials are defined in `docker-compose.yml`. They are: developer/secret123


### Stopping the docker container

If you want to stop the containers, press Ctrl+C. 

Or run:
```bash
docker compose down
```

