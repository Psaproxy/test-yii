# Передача аргументов команды через параметр "a". Например:
#  make logs a=nginx
#  make logs a=nginx,-n,5

# Запуск нескольких команд одновременно
# выполняется перечислением команд через пробел. Например:
#  make down up

# "-" в начале команды включает игнорирование ошибки этой команды.
# "@" в начале команды заглушает вывод кода этой команды.

# Документация
# https://www.gnu.org/software/make/manual/make.html
# https://makefiletutorial.com
# https://www.dmosk.ru/faq.php?object=unix-operators#str

# ****************************************

.PHONY: build config help logs ps
.DEFAULT_GOAL := help

# ****************************************
# Константы
# ****************************************

ACTION = $(MAKECMDGOALS)

ARGS_SEPARATOR = ,
ARGS = $(subst $(ARGS_SEPARATOR), ,$(a))

MAKE_FILE_MAIN = "Makefile"
MAKE_FILE_OVERRIDE = "Makefile.override"

SEPARATOR = ****************************************

COLOR_OPEN = \033[33m
COLOR_OPEN2 = \033[36m
COLOR_CLOSE = \033[0m

USER_ID = $(shell id -u)
USER_GROUP_ID = $(shell id -g)
export USER_ID
export USER_GROUP_ID

# ****************************************
# Настройки окружения
# ****************************************

include .env

ENV = $(APP_ENV)
ENV_IS_EMPTY = $(shell if [ "$(ENV)" ]; then echo 1; fi)
$(if $(ENV_IS_EMPTY), , $(error Необходимо указать окружение))

-include .env.$(ENV)

# ****************************************
# Команды оформления
# ****************************************

separator = printf "$(COLOR_OPEN)%s$(COLOR_CLOSE)\n" '$(SEPARATOR)'
title = printf "$(COLOR_OPEN)%s$(COLOR_CLOSE)\n"

# ****************************************
# Вывод данных сборки
# ****************************************

.co = $(shell echo "$(COLOR_OPEN)")
.cc = $(shell echo "$(COLOR_CLOSE)")

${info ${shell $(separator)} $(.co)}
${info action($(ACTION)), args($(ARGS)) $(.cc)}

# ****************************************
# Общие команды сервисов
# ****************************************

# Docker
ifeq ($(wildcard .env.$(ENV)), .env.$(ENV))
  COMPOSE_ENV = --env-file .env --env-file .env.$(ENV)
else
  COMPOSE_ENV = --env-file .env
endif
#compose = docker compose $(COMPOSE_ENV)
compose = docker compose
exec = $(compose) exec -it -u $(USER_ID)
logs = $(compose) logs

# PHP CLI
cli = $(exec) cli
php = $(cli) php
composer = $(cli) composer --working-dir=src
app = $(cli) src/yii
phpunit = $(cli) bin/phpunit

# ****************************************
# Контейнеры
# ****************************************

setup-config-create:
	@$(separator)
	[ -f "compose.override.yml" ] && : || cp compose.override.yml.dist compose.override.yml
	[ -f ".env.dev" ] && : || cp .env.dev.dist .env.dev

setup: setup-config-create build up install

build: down
	@$(separator)
	$(compose) build

up:
	@$(separator)
	$(compose) up -d

down:
	@$(separator)
	$(compose) down --remove-orphans

down-volumes:
	@$(separator)
	$(compose) down -v --remove-orphans

restart: down up

# Пример:
#  make restart-one a=caddy
restart-one:
	@$(separator)
	$(compose) restart $(ARGS)

# Пример:
#  make ps
#  make ps a=nginx
ps:
	@$(separator)
	$(compose) ps

# Пример:
#  make logs a=nginx
#  make logs a=nginx,-n,5
#  make logs a=nginx,-f
logs:
	@$(separator)
	$(compose) logs $(ARGS)

# ****************************************
# Приложение
# ****************************************

# Установка приложения
install: composer-install migration-run

# Выполнение shell-команды приложения
cli:
	@$(separator)
	$(cli) $(ARGS)

# Подключение к контейнеру приложения
cli-open:
	@$(separator)
	$(cli) sh

# Выполнение PHP-скрипта приложения
php:
	@$(separator)
	$(php) $(ARGS)

# Выполнение команды приложения
app:
	@$(separator)
	$(app) $(ARGS)

# Выполнение команды приложения
reinstall:
	@$(separator)
	$(compose) down -v --remove-orphans
	$(compose) up -d
	$(composer) install
	$(app) doctrine:migrations:migrate --no-interaction


# ****************************************
# Composer
# ****************************************

# Выполнение команды composer
composer:
	@$(separator)
	$(composer) $(ARGS)

composer-require:
	@$(separator)
	$(composer) require $(ARGS)

# Установка composer-пакетов
composer-install:
	@$(separator)
	$(composer) install

# Обновление версий и установка composer-пакетов
composer-update:
	@$(separator)
	$(composer) update

ci: composer-install
cu: composer-update
cr: composer-require

# ****************************************
# Миграции
# ****************************************

# Статус миграций
migration-status:
	@$(separator)
	$(app) doctrine:migrations:status

# Создание пустой миграции
migration-create:
	@$(separator)
	$(app) doctrine:migrations:create --no-interaction

# Выполнение миграций
migration-run:
	@$(separator)
	$(app) doctrine:migrations:migrate --no-interaction

# Создание миграции
migration-diff:
	@$(separator)
	$(app) doctrine:migrations:diff --no-interaction

migration-fixture:
	@$(separator)
	$(app) doctrine:fixture:load --no-interaction

ms: migration-status
mc: migration-create
mr: migration-run
md: migration-diff

# ****************************************
# Тесты
# ****************************************

phpunit:
	@$(separator)
	$(phpunit)

# ****************************************
# PostgreSQL
# ****************************************

postgres = $(exec) postgres

# Выполнение команды postgres
postgres:
	@$(separator)
	$(posetgres) psql -d $(POSTGRES_DB) -U $(POSTGRES_USER) $(ARGS)

# Подключение к контейнеру postgres
posetgres-open:
	@$(separator)
	$(posetgres) psql -d $(POSTGRES_DB) -U $(POSTGRES_USER)

po: posetgres-open

# ****************************************
# Redis
# ****************************************

redis = $(exec) redis

# Выполнение команды redis
redis:
	@$(separator)
	$(redis) redis $(ARGS)

# Подключение к контейнеру redis
redis-open:
	@$(separator)
	$(redis) redis

# ****************************************
# RabbitMQ
# ****************************************

rabbit = $(exec) rabbitmq

# Выполнение команды rabbit
rabbit:
	@$(separator)
	$(rabbit) rabbitmqctl $(ARGS)

# Подключение к контейнеру rabbit
rabbit-open:
	@$(separator)
	$(rebbit) rabbitmqctl

# ****************************************
# Nginx
# ****************************************

nginx = $(exec) nginx
nginx-logs = $(logs) nginx

.n = $(shell [ -n "$(ARGS)" ] && echo "-n $(ARGS)")

# Пример:
#  make nginx-logs
#  make nginx-logs a=5
nginx-logs:
	@$(separator)
	$(nginx-logs) $(.n)

nginx-ps:
	@$(separator)
	-@docker ps | head -1; docker ps | grep "\-nginx"

nginx-restart:
	@$(separator)
	$(compose) restart nginx

# Подключение к контейнеру nginx
nginx-open:
	@$(separator)
	$(nginx) sh

# ****************************************
# Caddy
# ****************************************

caddy = $(exec) caddy
caddy-logs = $(logs) caddy

.n = $(shell [ -n "$(ARGS)" ] && echo "-n $(ARGS)")

# Пример:
#  make caddy-logs
#  make caddy-logs a=5
caddy-logs:
	@$(separator)
	$(caddy-logs) $(.n)

caddy-ps:
	@$(separator)
	docker ps | head -1; docker ps | grep "\-caddy"

caddy-restart:
	@$(separator)
	$(compose) restart caddy

# Подключение к контейнеру caddy
caddy-open:
	@$(separator)
	$(caddy) sh

# Экспортировать SSL-сертификат CA caddy
caddy-ssl-ca-export:
	@$(separator)
	@[ -f docker/tmp/caddy/ssl-ca.crt ] && rm -f docker/tmp/caddy/ssl-ca.crt || :
	$(compose) cp caddy:/data/caddy/pki/authorities/local/root.crt docker/tmp/caddy/ssl-ca.crt

# Установка в систему и в браузерах SSL-сертификат CA caddy
# может потребоваться: sudo apt install libnss3-tools
caddy-ssl-ca-install: caddy-ssl-ca-export caddy-ssl-ca-install-system caddy-ssl-ca-install-browsers

# Установка в систему SSL-сертификат CA caddy
caddy-ssl-ca-install-system: caddy-ssl-ca-export
	@$(separator)
	chmod +x docker/develop/caddy/ssl-ca-add-to-system.sh
	cd docker/develop/caddy && sh ssl-ca-add-to-system.sh

# Установка в браузеры SSL-сертификат CA caddy
# может потребоваться: sudo apt install libnss3-tools
caddy-ssl-ca-install-browsers: caddy-ssl-ca-export
	@$(separator)
	chmod +x docker/develop/caddy/ssl-ca-add-to-browser.sh
	cd docker/develop/caddy && sh ssl-ca-add-to-browser.sh

# ****************************************
# PHP FPM
# ****************************************

fpm = $(exec) fpm

# Подключение к контейнеру php-fpm
fpm-open:
	@$(separator)
	$(fpm) sh

# ****************************************
# Инфо
# ****************************************

info:
	@$(separator)
	@echo "user id($(USER_ID)), group id($(USER_GROUP_ID))," \
	     "environment($(ENV)), host($(SERVER_NAME))"

# ****************************************
# Сервис
# ****************************************

help:
	@$(separator)

	@$(title) "Команды $(MAKE_FILE_MAIN)"
	@awk '/^[[:alnum:]]+[^[:space:]]+:/ {printf "%s",substr($$1,1,length($$1)-1); if (match($$0,/#/)) {desc=substr($$0,RSTART+1); sub(/^[[:space:]]+/,"",desc); printf "- %s\n",desc} else printf "\n" }' "$(MAKE_FILE_MAIN)"

	@$(title) "Команды $(MAKE_FILE_OVERRIDE)"
	@[ -f "$(MAKE_FILE_OVERRIDE)" ] && awk '/^[[:alnum:]]+[^[:space:]]+:/ {printf "%s",substr($$1,1,length($$1)-1); if (match($$0,/#/)) {desc=substr($$0,RSTART+1); sub(/^[[:space:]]+/,"",desc); printf "- %s\n",desc} else printf "\n" }' "$(MAKE_FILE_OVERRIDE)" || :
