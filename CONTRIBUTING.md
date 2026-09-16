# Contributing to KOLD

KOLD uses short-lived branches, Pull Requests, required CI checks, and traceable production releases.

## Development workflow

1. Start from the latest `main` and create a focused branch.
2. Keep unrelated changes out of the same Pull Request.
3. Add or update automated tests for changed behaviour and permissions.
4. Run the local quality gates:

```bash
composer validate --strict
vendor/bin/pint --test
php artisan test
npm ci
npm run build
composer audit --locked --no-interaction
npm audit --audit-level=high
```

5. Open a Pull Request and complete the risk, migration, validation, and rollback sections.
6. Merge only after all required CI checks pass and review conversations are resolved.

## Database changes

- Migrations must work on SQLite and PostgreSQL unless the Pull Request documents an approved exception.
- Prefer additive, backward-compatible schema changes.
- Every migration needs a safe `down()` path or a documented restore procedure.
- Never run seed/demo data in production.
- Never delete or merge production records as a deployment shortcut.

## Secrets and production data

- Never commit `.env`, OAuth secrets, API keys, production database files, logs, or exported user data.
- Use aggregate or synthetic data in tests and documentation.
- Production access is only for scoped deployment, diagnostics, and verified operational work.

## Releases

- `main` must remain deployable.
- Production must correspond to a Git commit.
- Deployment requires a backup, migrations, Laravel cache compilation, and smoke checks.
- Significant beta baselines use an annotated version tag and release notes.
- Emergency fixes follow the same Pull Request and CI path unless an active incident requires a documented exception.
