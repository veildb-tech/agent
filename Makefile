SHELL := /usr/bin/env bash

args = `arg="$(filter-out $(firstword $(MAKECMDGOALS)),$(MAKECMDGOALS))" && echo $${arg:-${1}}`

green  = $(shell printf "\e[32;01m$1\e[0m")
yellow = $(shell printf "\e[33;01m$1\e[0m")
red    = $(shell printf "\e[33;31m$1\e[0m")

format = $(shell printf "%-40s %s" "$(call green,$1)" $2)

comma:= ,

.DEFAULT_GOAL:=help

%:
	@:

help:
	@echo ""
	@echo "$(call yellow,Use the following CLI commands:)"
	@echo "$(call red,===============================)"
	@echo "$(call format,cli,'Run the cli commands')"
	@echo "$(call format,setup,'Run the project setup process.')"
	@echo "$(call format,start,'Start all containers.')"
	@echo "$(call format,stop,'Stop server container.')"
	@echo "$(call format,stopall,'Stop all containers.')"
	@echo "$(call format,console,'Run symfony bin/console command, to run commands pass them via the param c=, ex.: c='app:db:add')"

cli:
	@$(eval c ?=)
	@./bin/docker/cli $(c)

composer:
	@$(eval c ?=)
	@./bin/docker/cli composer $(c)

console:
	@$(eval c ?=)
	@./bin/docker/cli bin/console $(c)

cc: c=c:c
cc: console

sh:
	@./bin/docker/cli sh

setup:
	@./bin/setup

start:
	@./bin/docker/start

build:
	@./bin/docker/start -b

stop:
	@./bin/docker/stop

stopall:
	@./bin/docker/stopall

start-db:
	@./bin/docker/start-db $(call args)

stop-db:
	@./bin/docker/stop-db $(call args)

logs:
	@./bin/docker/logs

cron-start:
	@./bin/docker/cron start

cron-stop:
	@./bin/docker/cron stop