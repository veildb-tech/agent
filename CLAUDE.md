# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

VeilDB Agent is a Symfony 6.2 PHP console application (CLI-only, not web) that runs inside Docker. It polls a remote VeilDB Service for scheduled tasks, creates database dumps, applies anonymization/masking rules in a temporary database, and reports results back to the service.

## Commands

```bash
# Install dependencies
composer install

# Clear Symfony cache
php bin/console cache:clear

# Run all tests
./vendor/bin/phpunit

# Run a single test file
./vendor/bin/phpunit bundles/TestBundle/tests/FakeTest.php

# Key console commands
php bin/console app:db:process           # Poll and process a scheduled dump
php bin/console app:db:analyze -u <uid>  # Analyze DB structure and send to service
php bin/console app:server:add           # Register this agent with the service
php bin/console app:db:add --db <name>   # Create a manual DB dump
php bin/console app:cron:install         # Install crontab for scheduled processing
```

## Architecture

### Directory Structure

- `src/` — Main application code (Symfony `App\` namespace)
  - `Command/` — Symfony console command entry points (thin wrappers that call `Service/PublicCommand/`)
  - `Service/PublicCommand/` — Business logic for each CLI command
  - `ServiceApi/` — HTTP client and actions for communicating with the remote VeilDB Service
  - `Service/` — Supporting services (config, logging, locking, dump management)
- `bundles/` — Custom Symfony bundles, each a separate Composer package loaded via path repositories
  - `CoreBundle/` — Interfaces, factories, and abstract base classes shared by all engines
  - `MysqlBundle/`, `MariaDbBundle/`, `PostgresqlBundle/` — Engine-specific DB management and processing
  - `MagentoBundle/` — Platform-specific processor extending MySQL processing for Magento
  - `TestBundle/` — Test infrastructure and PHPUnit base classes

### Engine Registration

Engines are conditionally enabled. `config/bundles.php` checks for the presence of `.env.{engine}` files (e.g., `.env.mysql`, `.env.pgsql`) and only registers the corresponding bundle if the file exists. Engine services are registered in the DI container under the name `db_manager_core.engines.{engine_name}`.

### Core Processing Flow (`DatabaseProcessor`)

1. Acquire a lock (prevents concurrent runs)
2. Poll the Service API for a scheduled dump (`DatabaseDump::getScheduled()`)
3. Fetch anonymization rules from the Service (`GetDatabaseRules`)
4. Create a temporary database (`temp_{timestamp}`)
5. Import the original dump into the temp database
6. Run the engine/platform processor to apply masking rules (`DbProcessorFactory::create()`)
7. Export the anonymized dump
8. Re-analyze schema and send to Service
9. Drop the temp database
10. Report final status (READY / READY_WITH_ERROR / ERROR)

### Key Factories

- `DbProcessorFactory` — Creates an `EngineInterface` processor. If a platform (e.g., `magento`) is specified, it takes priority over the base engine.
- `DBManagementFactory` — Creates a `DBManagementInterface` for create/drop/import/dump shell operations.

### Configuration

- `.env` — Main app config (service URL, server UUID, secret key, paths, Docker settings)
- `.env.mysql` / `.env.pgsql` / `.env.mariadb` — Per-engine DB credentials (presence enables that engine bundle)
- `backups/configs/{db_uuid}/config` — Per-database config files written by `AppConfig::saveDatabaseConfig()`
- `AppConfig` service reads all config from env files and provides typed accessors

### Service API Communication

`AppService` (base class for all service API actions) handles token-based auth (server UUID + secret key) or user/password auth. Tokens are cached for 1 hour. All actions in `src/ServiceApi/Actions/` extend this class.

### Adding a New DB Engine

1. Create a new bundle in `bundles/` following the pattern of `MysqlBundle`
2. Implement `DBManagementInterface` for shell-level operations
3. Implement `EngineInterface` for data processing
4. Register the engine service as `db_manager_core.engines.{engine_name}` in the bundle's `services.yaml`
5. Add the engine to `DatabaseEngineEnum` in `CoreBundle`
6. Add bundle registration to `config/bundles.php` with `.env.{engine}` file check
