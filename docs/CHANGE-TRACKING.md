# Tracking changes and commits

Every future functional update should include a dated entry in CHANGELOG.md and a detailed record under docs/changes/YYYY-MM-DD-short-description.md. Use the actual release/implementation date. Clearly separate previous baseline work from new implementation and proposed features.

## Required change-record contents

1. User-visible behavior and reason for the change; authorization/tenant boundaries where relevant.
2. Every changed source/test/deployment file and its purpose. Obtain the list with `git diff --name-only` and `git diff --cached --name-only` and reconcile the documentation before committing.
3. Database migration names, affected tables/columns, data changes, foreign-key effects, backup requirements and rollback limits.
4. Environment/configuration changes, secret handling, cron changes and installation instructions for both original and managed Hostinger layouts when applicable.
5. Exact verification commands, results and dates; distinguish automated fakes, manual browser checks and real provider/production checks. Never mark an unexecuted check as passed.
6. Remaining manual acceptance, limitations and future work. Include generated artifact names and reproducible packaging commands; do not version credentials or customer data.

## Commit convention

Use one coherent commit per completed change when practical. Suggested prefixes: `feat:`, `fix:`, `docs:`, `test:`, `chore:`. Keep the subject short and describe the resulting behavior. The body should explain scope, database/config effects, validation and outstanding live checks. Do not place its own future hash in the commit's files; use `git log` or tag a release separately when explicitly requested.

For a documentation-only clarification, use `docs:` and say that runtime behavior is unchanged; no full application test rerun is needed unless the documentation uncovered a code change. Do not rewrite published history to improve an earlier commit message.

## Before committing

```sh
git status --short
git diff --cached --stat
git diff --cached --check
# Stage explicit intended files; inspect changes and secret exclusions.
git commit -F PATH_TO_REVIEWED_COMMIT_MESSAGE
git log -1 --oneline
git status --short
```

Do not push, deploy or create a release tag unless requested. Use the existing Git author identity. Commit messages and documentation must not contain real SMTP passwords, API tokens, APP_KEY, production database passwords or customer exports.

See [the current change record](changes/2026-10-04-platform-administration.md) for a completed example and [ADMIN-UPDATE.md](ADMIN-UPDATE.md) for the Hostinger deployment/acceptance checklist.
