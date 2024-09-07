.PHONY: up down build install migrate test logs shell-php shell-frontend messenger-consume

up:
	docker-compose up -d

down:
	docker-compose down

build:
	docker-compose build

install:
	docker-compose run --rm php composer install
	docker-compose run --rm frontend npm install

migrate:
	docker-compose run --rm php sh -c "\
		php bin/console doctrine:database:create --if-not-exists && \
		php bin/console doctrine:migrations:migrate --no-interaction && \
		php bin/console doctrine:fixtures:load --no-interaction \
	"

logs:
	docker-compose logs -f

shell-php:
	docker-compose exec php bash

shell-frontend:
	docker-compose exec frontend sh

messenger-start:
	docker-compose exec -d php php /var/www/html/bin/console messenger:consume async

messenger-stop:
	docker-compose exec php php /var/www/html/bin/console messenger:stop-workers

messenger-status:
	@docker-compose exec php php /var/www/html/bin/console messenger:stats

cron-logs:
	docker-compose exec php tail -f /var/log/cron.log

mailcatcher-logs:
	docker-compose logs -f mailcatcher

help:
	@echo "Available commands:"
	@echo "  up                  - Start the Docker containers"
	@echo "  down                - Stop the Docker containers"
	@echo "  build               - Rebuild the Docker containers"
	@echo "  install             - Install/update dependencies"
	@echo "  migrate             - Run database migrations"
	@echo "  logs                - View logs from all containers"
	@echo "  shell-php           - Open a shell in the PHP container"
	@echo "  shell-frontend      - Open a shell in the frontend container"
	@echo "  messenger-start     - Start the Symfony Messenger consumer in the background"
	@echo "  messenger-stop      - Stop the Symfony Messenger consumer"
	@echo "  messenger-status    - Check the status of the Symfony Messenger consumer"
	@echo "  cron-logs           - View cron job logs"
	@echo "  mailcatcher-logs	- View mailcatcher logs"