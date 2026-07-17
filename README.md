# ToDo & Co Application

[![Symfony 3.4 CI Pipeline](https://github.com/Mike031289/ToDo-Co/actions/workflows/ci.yml/badge.svg?branch=develop)](https://github.com/Mike031289/ToDo-Co/actions/workflows/ci.yml)

Base du projet #8 : Améliorez un projet existant
https://openclassrooms.com/projects/ameliorer-un-projet-existant-1

---

## 📝 About The Project

This repository is dedicated to the modernization of the **ToDo & Co** web application.

The main objective of this project is to take over an existing legacy codebase (**Symfony 3.1 / PHP 5.5.9**), perform a thorough code quality audit, fix critical technical debt, and implement new technical requirements—including advanced user management, security policies, and an exhaustive automated testing suite (PHPUnit).

### 🚀 Modernization Goals Achieved

- **Framework Upgrade:** Migrated the legacy application to **Symfony 3.4** and **PHP 7.4** to ensure stability and long-term dependency support.
- **Security Overhaul:** Implemented multi-level roles, isolated task ownership, and resolved security vulnerabilities using Symfony **Voters**.
- **Quality Assurance:** Built a comprehensive automated testing suite with **PHPUnit**, covering controllers, forms, and business logic.
- **CI/CD Pipeline:** Integrated **GitHub Actions** for continuous integration, including automated tests, static analysis, and code style validation on every push.

---

## 🛠️ Tech Stack

| Category                      | Technology                    |
| :---------------------------- | :---------------------------- |
| **Framework**                 | Symfony 3.4                   |
| **Language**                  | PHP 7.4                       |
| **Database**                  | MySQL / MariaDB               |
| **Testing**                   | PHPUnit                       |
| **CI/CD**                     | GitHub Actions                |
| **Static Analysis & Quality** | Codacy, PHPStan, PHP CS Fixer |

---

## 📖 Project Documentation

To make onboarding and maintenance as smooth as possible, the documentation is split into dedicated guides:

### 📘 Installation & Contribution Guide (`CONTRIBUTING.md`)

- Step-by-step local setup instructions.
- Database configuration and fixtures.
- Git workflow (branch naming and Conventional Commits).
- Commands to run the automated test suite.

### 🔐 Technical Security & Authentication (`doc/security.md`)

- Authentication architecture overview.
- Role hierarchy (`ROLE_USER` / `ROLE_ADMIN`).
- Route-level authorization and `TaskVoter` rules.
- Demo accounts for testing.

---

## 🚦 Quick Start (Local Setup)

For complete installation instructions, refer to **CONTRIBUTING.md**.

### 1. Install dependencies

```bash
composer install
```

### 2. Configure the database

During installation, provide your local database credentials when `parameters.yml` is generated.

### 3. Create the database and load fixtures

```bash
php bin/console doctrine:database:create
php bin/console doctrine:schema:create
php bin/console doctrine:fixtures:load --no-interaction
```

### 4. Start the local server

```bash
php bin/console server:start
```

The application will be available at:

```text
http://127.0.0.1:8000
```

---

## 🧪 Running Tests

Run the complete automated test suite:

```bash
php bin/phpunit
```

Run a specific test file (example: Task controller security tests):

```bash
php bin/phpunit tests/AppBundle/Controller/TaskControllerTest.php
```

---

## 📊 Quality Audit

Code quality is continuously monitored to keep the project maintainable and compliant with Symfony best practices.

### ✅ Coding Standards

- PSR-12 compatibility enforced with **PHP CS Fixer**.

### 🔍 Static Analysis

- **PHPStan** is used to detect type inconsistencies and potential runtime issues before deployment.

### 🔄 Continuous Integration

- **GitHub Actions** automatically executes tests, code style checks, and static analysis.
- **Codacy** reviews every Pull Request to monitor technical debt and maintain code quality.
