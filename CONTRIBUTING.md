# Contributing

Use feature branches and pull requests. Keep `main` deployable. Changes to tenant access, money calculations, public links, platform permissions or delivery jobs need regression coverage. Keep integer kobo and server-side recalculation. Never commit secrets or customer exports.

Run backend tests and frontend tests/build locally. GitHub CI adds MySQL and the restricted production-package browser test. Review lockfile/security updates rather than disabling audit checks.

Schema changes must remain compatible with the previous release. See [delivery guide](docs/CI-CD.md) for expand-and-contract migration and rollback requirements. Include user-visible changes, migration effects and validation evidence in your pull request.

Every functional commit must update [CHANGELOG.md](CHANGELOG.md) and follow [the change-tracking process](docs/CHANGE-TRACKING.md), including a file-level record, schema/config effects and evidence of actual validation.

Every update installation guide must directly include scoped maintenance-mode entry (`artisan down`) and exit (`artisan up`) commands, cron pause/resume guidance and failure recovery instructions; do not leave these only in a linked guide.

Owner instruction (2026-10-11): after each completed and validated code change, commit the intended source/documentation and push to the configured GitHub branch without asking again. Report the commit and push result. Do not include unrelated work or secrets. Explicit server deployment and release tags still require separate authorization; pushes may trigger configured CI/CD. If push fails, preserve the local commit and report the failure.
