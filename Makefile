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
	docker-compose down --remove-orphans
	docker-compose up --build
	sleep 5;
	$(MAKE) cleanAndTest

cleanAndTest: ## Recreate DB, migrate migrations, load fixtures, clean cache of import CSV, start cron service and run unit tests
	docker-compose exec php bash cleanAndTest

clean: ## Recreate DB, migrate migrations, load fixtures, start cron service
	docker-compose exec php bash clean

cron-launch: ## Start the cron service
	docker-compose exec php bash cron-launch

test: ## Run phpunit tests
	docker-compose exec php bash -c 'vendor/bin/phpunit'
