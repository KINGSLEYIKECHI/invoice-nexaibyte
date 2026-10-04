# Contributing

Use feature branches and pull requests. Keep `main` deployable. Changes to tenant access, money calculations, public links, platform permissions or delivery jobs need regression coverage. Keep integer kobo and server-side recalculation. Never commit secrets or customer exports.

Run backend tests and frontend tests/build locally. GitHub CI adds MySQL and the restricted production-package browser test. Review lockfile/security updates rather than disabling audit checks.

Schema changes must remain compatible with the previous release. See [delivery guide](docs/CI-CD.md) for expand-and-contract migration and rollback requirements. Include user-visible changes, migration effects and validation evidence in your pull request.
