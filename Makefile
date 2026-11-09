.DEFAULT_GOAL := help
DC := docker compose
VERSION := $(shell sed -n 's:.*<version>\(.*\)</version>.*:\1:p' install.xml | head -1)

-include .env

.env:
	cp .env.example .env

help: ## Show available commands
	@grep -hE '^[a-zA-Z_-]+:.*?## ' $(MAKEFILE_LIST) | awk 'BEGIN{FS=":.*?## "}{printf "  \033[36m%-15s\033[0m %s\n", $$1, $$2}'

up: .env ## Build and start the environment
	$(DC) up -d --build
	@echo ""
	@echo "Shop:       http://localhost:$(or $(OC_PORT),8080)"
	@echo "Admin:      http://localhost:$(or $(OC_PORT),8080)/admin"
	@echo "phpMyAdmin: http://localhost:$(or $(PMA_PORT),8081)"
	@echo "Mailpit:    http://localhost:$(or $(MAILPIT_PORT),8025)"

down: ## Stop the environment
	$(DC) down

reset: ## Stop and DELETE all data (fresh OpenCart install on next 'make up')
	$(DC) down -v

logs: ## Follow OpenCart container logs (wait for "setup finished")
	$(DC) logs -f opencart

shell: ## Bash shell in the OpenCart container
	$(DC) exec opencart bash

link: ## Re-link plugin files (after adding/removing files)
	$(DC) exec opencart oc-link

ocmod: ## Refresh OCMOD modifications (Extensions > Modifications > Refresh)
	$(DC) exec opencart oc-ocmod-refresh

install-plugin: ## (Re)install the MyParcel extensions; EXT="module/<code> ..." to choose
	@# Quote each EXT word for the shell, so a stray quote cannot break the command
	$(DC) exec opencart oc-plugin-install $(foreach e,$(EXT),'$(subst ','\'',$(e))')

db: ## MariaDB console
	$(DC) exec db mariadb -u$(or $(DB_USER),opencart) -p$(or $(DB_PASSWORD),opencart) $(or $(DB_NAME),opencart)

zip: ## Build installable dist/myparcelnl-<version>.ocmod.zip
	@mkdir -p dist
	zip -rq -FS dist/myparcelnl-$(VERSION).ocmod.zip upload install.xml -x '*.DS_Store' 'upload/system/library/myparcelnl/logs/*.txt'
	@echo "Built dist/myparcelnl-$(VERSION).ocmod.zip"

.PHONY: help up down reset logs shell link ocmod install-plugin db zip
