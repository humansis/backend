help: ## Show this help
	@echo Usage: make [target]
	@echo
	@echo "Targets:"
	@grep -E '^[a-zA-Z_-]+:.*?## [^ :]+[^:] .*$$' $(MAKEFILE_LIST) | LC_ALL=C sort | awk 'BEGIN {FS = ":.*?## "}; {printf "  %-20s %s\n", $$1, $$2}'
	@echo
	@echo "Docker Targets:"
	@grep -E '^[a-zA-Z_-]+:.*?## Docker: .*$$' $(MAKEFILE_LIST) | LC_ALL=C sort | awk 'BEGIN {FS = ":.*?## Docker: "}; {printf "  %-20s %s\n", $$1, $$2}'
	@echo

stop: ## Docker: Stop containers
	docker-compose stop

start: ## Docker: Start containers
	docker-compose start

restart: ## Docker: Restart containers
	docker-compose restart

recreate: ## Docker: Stop and remove all containers and start it again
	docker-compose down -v
	rm -rf /docker/mysqldata
	docker-compose up -d --force-recreate --build

	#wait for initialize database
	sleep 30;

	$(MAKE) cache fixtures

migrate: ## Migrate database
	docker-compose exec php bash -c 'bin/console doctrine:migrations:migrate --no-interaction'

diff: ## Generate diff migration
	docker-compose exec php bash -c 'bin/console doctrine:migrations:diff'

translation: ## generate new translation keys
	docker-compose exec php bash -c 'bin/console cache:clear'
	docker-compose exec php bash -c 'bin/console translation:update --force en'

crowdin-push: ## push translations to crowdin
	docker-compose exec php bash -c 'bin/console crowdin:push'

crowdin-pull: ## get translations from crowdin
	docker-compose exec php bash -c 'bin/console crowdin:pull'

cleanAndTest: ## Recreate DB, migrate migrations, load fixtures, clean cache of import CSV, start cron service and run unit tests
	docker-compose exec php bash cleanAndTest

clean: ## Recreate DB, migrate migrations, load fixtures, start cron service
	docker-compose exec php bash clean

cron-launch: ## Start the cron service
	docker-compose exec php bash cron-launch

test: ## Run phpunit tests
	docker-compose exec php bash -c 'php -d memory_limit=-1 vendor/bin/phpstan analyse -l 1 src/'
	docker-compose exec php bash -c 'php -d memory_limit=-1 vendor/bin/phpunit'

cache: ## Remove cache
	docker-compose exec php bash -c 'rm -rf var/cache'

fixtures: ## Run fixtures
	docker-compose exec php bash -c 'bin/console doctrine:fixtures:load  --no-interaction'
