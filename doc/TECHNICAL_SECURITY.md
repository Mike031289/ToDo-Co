# Technical Security & Authentication Documentation

This document outlines the security architecture, role hierarchy, access control rules, and authorization mechanisms implemented within the application.

---

## 🔐 1. Authentication & Session Management

The application secures access using standard Symfony session-based authentication.

- **Firewalls**: Configured via `app/config/security.yml`. All application paths except public assets, login, and registration endpoints require an authenticated session.
- **User Provider**: Users are loaded from the database via Doctrine using their username. Passwords are encrypted using a secure hashing algorithm (e.g., bcrypt) configured in the `encoders` section.

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

The hierarchy is declared in `security.yml` under the `role_hierarchy` key:

- **`ROLE_USER`**: The baseline role assigned to any newly registered user. Allows creating, viewing, editing tasks, as well as managing their own tasks.
- **`ROLE_ADMIN`**: Inherits all permissions from `ROLE_USER`. Additionally grants administrative access to user management and specific deletion capabilities.

---

## 🛡️ 3. Access Control & Authorization Rules

Authorization is enforced at two levels: route-level access controls and fine-grained programmatic checks (Voters).

### A. Route-Level Security (`security.yml`)

We enforce global boundaries on endpoints based on role requirements:

- `/users/*` paths (User management) are restricted strictly to **`ROLE_ADMIN`**.
- `/tasks/*` paths require a minimum of **`ROLE_USER`**.

### B. Fine-Grained Logic: Voters (`TaskVoter.php`)

For actions on specific resources—specifically deleting tasks—a global role check is not sufficient (to prevent IDOR vulnerabilities). We use a **Symfony Voter** to handle this business logic:

| Action       | Subject | Authorized Actor  | Rules / Scenarios                                                                                 |
| :----------- | :------ | :---------------- | :------------------------------------------------------------------------------------------------ |
| **`delete`** | `Task`  | **Task Owner**    | Any `ROLE_USER` who is the designated author of the task.                                         |
| **`delete`** | `Task`  | **Administrator** | A `ROLE_ADMIN` **only if** the task's author is `null` or belongs to the `"anonyme"` system user. |

> 💡 **Note:** Standard users (`ROLE_USER`) are strictly forbidden from deleting anonymous tasks or tasks belonging to other users.

---

## 🧪 4. Demo Accounts & Integration Testing

The following preconfigured demo accounts are populated by the system fixtures (`AppBundle\DataFixtures\ORM\LoadUserData`) to facilitate quick manual testing and automated integration tests:

| Username      | Password       | Assigned Role | Main Test Purpose                                           |
| :------------ | :------------- | :------------ | :---------------------------------------------------------- |
| **`admin`**   | `admin`        | `ROLE_ADMIN`  | User management and anonymous task cleanup.                 |
| **`user`**    | `user`         | `ROLE_USER`   | Standard workflow: task creation and self-owned management. |
| **`anonyme`** | _N/A (Locked)_ | `ROLE_USER`   | Legacy placeholder user for unassigned tasks.               |

To reload these accounts and reset the database state:

```bash
php bin/console doctrine:fixtures:load --no-interaction
```

## 🙌 Thank You

Have fun !
