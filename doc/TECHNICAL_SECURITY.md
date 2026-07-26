# Technical Security & Authentication Documentation

This document outlines the security architecture, role hierarchy, access control rules, and authorization mechanisms implemented within the application.

---

## 🔐 1. Authentication & Session Management

The application secures access using standard Symfony session-based authentication.

- **Firewalls**: Configured via `config/packages/security.yaml`. All application paths except public assets, login, and registration endpoints require an authenticated session.
- **User Provider**: Users are loaded from the database via Doctrine using their username.
- **Password Hashing**: Passwords are securely hashed using Symfony's native password hasher mechanism configured in the `security.yaml` `password_hashers` section.

---

## 👥 2. Role Hierarchy

The application defines a clear distinction between standard users and system administrators.

```text
┌─────────────────────────────────┐
│           ROLE_ADMIN            │
└────────────────┬────────────────┘
                 │
          inherits from
                 ▼
┌─────────────────────────────────┐
│            ROLE_USER            │
└─────────────────────────────────┘
```

The hierarchy is declared in `security.yaml` under the `role_hierarchy` key.

### ROLE_USER

The baseline role assigned to any newly registered user.

Permissions include:

- Creating tasks
- Viewing tasks
- Editing their own tasks
- Managing their own tasks

### ROLE_ADMIN

Inherits all permissions from `ROLE_USER`.

Additional privileges include:

- Access to user management
- Permission to delete specific anonymous tasks

---

## 🛡️ 3. Access Control & Authorization Rules

Authorization is enforced at two levels:

1. Route-level access control
2. Fine-grained authorization using Symfony Voters

### A. Route-Level Security (`security.yaml`)

The following global access restrictions are enforced:

| Route      | Required Role |
| ---------- | ------------- |
| `/users/*` | `ROLE_ADMIN`  |
| `/tasks/*` | `ROLE_USER`   |

---

### B. Fine-Grained Authorization: `TaskVoter.php`

For resource-specific actions—particularly task deletion—a global role check is insufficient because it would expose the application to **IDOR (Insecure Direct Object Reference)** vulnerabilities.

A dedicated Symfony Voter implements the following authorization rules:

| Action       | Resource | Authorized User | Condition                                                          |
| ------------ | -------- | --------------- | ------------------------------------------------------------------ |
| `deleteTask` | Task     | Task owner      | Any `ROLE_USER` who owns the task                                  |
| `deleteTask` | Task     | Administrator   | Only if the task owner is `null` or is the system user **anonyme** |

> **Note:** Standard users (`ROLE_USER`) cannot delete tasks belonging to other users or anonymous tasks.

---

## 🧪 4. Demo Accounts & Integration Testing

The application ships with preconfigured demo accounts loaded through the fixture:

```text
App\DataFixtures\LoadUserData
```

These accounts simplify manual testing and automated integration tests.

| Username  | Password     | Role         | Purpose                                          |
| --------- | ------------ | ------------ | ------------------------------------------------ |
| `admin`   | `admin`      | `ROLE_ADMIN` | User management and anonymous task cleanup       |
| `user`    | `user`       | `ROLE_USER`  | Standard workflow (task creation and management) |
| `anonyme` | N/A (locked) | `ROLE_USER`  | Legacy placeholder account for orphan tasks      |

### Reload Fixtures

To reset the database and recreate the demo accounts:

```bash
php bin/console doctrine:fixtures:load --no-interaction
```

---

# 🙌 Thank You

Have fun!
