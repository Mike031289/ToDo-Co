# Contributing to ToDo List

First off, thank you for considering contributing to this project! This guide will help you set up your local development environment and understand our development workflow.

---

## 🛠️ Local Installation & Setup

### Prerequisites

Before starting, ensure you have the following installed on your machine:

- **PHP 7.4** (with standard XML, cURL, and PDO extensions enabled)
- **Composer** (PHP dependency manager)
- **MySQL** or **MariaDB** database server

### Step-by-Step Setup

1. **Clone the repository:**

   ```bash
   git clone https://github.com/Mike031289/ToDo-Co.git
   cd todo-list
   ```

2. **Install PHP dependencies:**

   ```bash
   composer install
   ```

   During installation, you will be prompted to configure your local parameters. Define your database credentials (host, port, name, user, password) for `app/config/parameters.yml`.

3. **Create the database and schema:**

   ```bash
   php bin/console doctrine:database:create
   php bin/console doctrine:schema:create
   ```

4. **Load development fixtures:**

   ```bash
   php bin/console doctrine:fixtures:load --no-interaction
   ```

5. **Start the local web server:**

   ```bash
   php bin/console server:start
   ```

   Your application will be available at:

   ```
   http://127.0.0.1:8000
   ```

---

## 🌿 Git Workflow & Branch Naming

We use a structured branch naming convention to keep our repository clean and organized. Please prefix your branch names based on the nature of your changes:

- `feat/` : New features (e.g., `feat/security-delete-anonymous-tasks`)
- `fix/` : Bug fixes (e.g., `fix/task-validation`)
- `docs/` : Documentation updates (e.g., `docs/contributing-guide`)
- `refactor/` : Code changes that neither fix a bug nor add a feature
- `test/` : Adding or updating tests

---

## ✍️ Commit Message Guidelines

We enforce the **Conventional Commits** specification. Every commit message must be clear, structured, and prefixed with its type:

```text
<type>(<scope>): <short description>
```

### Examples

```text
feat(task): implement deletion restriction rules
fix(user): resolve login form validation error
docs(readme): update installation steps
```

> **Note:** For issues, please reference them in your commit footer using `closes #XX` or `resolves #XX`.

---

## 🧪 Testing & Quality Assurance

Before submitting any Pull Request, you must run the local test suite to ensure no regressions are introduced:

```bash
# Run the entire automated test suite
php bin/phpunit
```

Make sure all tests pass (`OK`) before pushing your code.

---

## 🙌 Thank You

Thank you for your contribution!
