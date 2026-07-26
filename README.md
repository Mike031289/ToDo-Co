# ToDo & Co Application

[![Symfony 5.4 CI Pipeline](https://github.com/Mike031289/ToDo-Co/actions/workflows/ci.yml/badge.svg?branch=develop)](https://github.com/Mike031289/ToDo-Co/actions/workflows/ci.yml)

Base du projet **OpenClassrooms #8** : _Améliorez un projet existant_
https://openclassrooms.com/projects/ameliorer-un-projet-existant-1

---

# 📝 About the Project

This repository is dedicated to the modernization of the **ToDo & Co** web application.

The main objective of this project is to take over an existing legacy codebase (**Symfony 3.1 / PHP 5.5.9**), perform a thorough code quality audit, eliminate technical debt, and implement new technical requirements, including advanced user management, security policies, and a comprehensive automated testing suite using **PHPUnit**.

## 🚀 Modernization Goals Achieved

- **Framework Upgrade:** Migrated the application from **Symfony 3.1** to **Symfony 5.4 LTS** and from **PHP 5.5.9** to **PHP 8.1**.
- **Security Overhaul:** Introduced multi-level roles, secured task ownership, and protected sensitive actions using Symfony **Voters**.
- **Quality Assurance:** Implemented an extensive automated test suite with **PHPUnit 9.6**, covering controllers, entities, forms, and business logic.
- **CI/CD Pipeline:** Configured **GitHub Actions** to automatically run tests, static analysis, and coding standards validation on every push and pull request.

---

# 🛠️ Tech Stack

| Category                      | Technology                    |
| ----------------------------- | ----------------------------- |
| **Framework**                 | Symfony 5.4                   |
| **Language**                  | PHP 8.1                       |
| **Database**                  | MySQL / MariaDB               |
| **Testing**                   | PHPUnit 9.6                   |
| **CI/CD**                     | GitHub Actions                |
| **Static Analysis & Quality** | Codacy, PHPStan, PHP CS Fixer |
| **Migration Tool**            | Rector (PHP 8.1 compatible)   |
| **Debugging & Profiling**     | Xdebug 3.x                    |

---

# 📖 Project Documentation

To simplify onboarding and long-term maintenance, the documentation is organized into dedicated guides.

## 📘 Installation & Contribution Guide (`CONTRIBUTING.md`)

This guide includes:

- Local installation instructions
- Database configuration
- Fixture loading
- Git workflow (branch naming and Conventional Commits)
- Commands for running the automated test suite

## 🔐 Technical Security & Authentication (`doc/TECHNICAL_SECURITY.md`)

This document describes:

- Authentication architecture
- Role hierarchy (`ROLE_USER` / `ROLE_ADMIN`)
- Route-level authorization
- Business authorization using `TaskVoter`
- Demo accounts for testing

---

# 🚦 Quick Start

For complete installation instructions, see **CONTRIBUTING.md**.

## 1. Install dependencies

```bash
composer install
```

## 2. Configure the database

Update your database connection settings in your `.env` file (or your local environment configuration).

Example:

```dotenv
DATABASE_URL="mysql://user:password@127.0.0.1:3306/todo"
```

## 3. Create the database and load fixtures

```bash
php bin/console doctrine:database:create
php bin/console doctrine:schema:create
php bin/console doctrine:fixtures:load --no-interaction
```

## 4. Start the local server

```bash
php bin/console server:start
```

The application will be available at:

```text
http://127.0.0.1:8000
```

---

# 🧪 Running Tests & Code Coverage

## Run the complete test suite

```bash
vendor/bin/phpunit
```

## Run a specific test class

Task entity tests:

```bash
vendor/bin/phpunit tests/Entity/TaskTest.php
```

Task controller tests:

```bash
vendor/bin/phpunit tests/Controller/TaskControllerTest.php
```

## Generate a code coverage report

```bash
php -d xdebug.mode=coverage vendor/bin/phpunit --coverage-text
```

---

# 📊 Quality Audit

Code quality is continuously monitored to ensure maintainability and compliance with Symfony best practices.

## ✅ Coding Standards

Coding style follows **PSR-12** and is automatically enforced using **PHP CS Fixer**.

## 🔍 Static Analysis

**PHPStan** is used to detect:

- Type inconsistencies
- Potential runtime errors
- Dead code
- Common programming mistakes

before deployment.

## 🔄 Continuous Integration

Every push and pull request automatically triggers the GitHub Actions pipeline, which performs:

- PHPUnit test execution
- PHP CS Fixer validation
- PHPStan static analysis

Additionally, **Codacy** continuously reviews pull requests to monitor technical debt and maintain overall code quality.
