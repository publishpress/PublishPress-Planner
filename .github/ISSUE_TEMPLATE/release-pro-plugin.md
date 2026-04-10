---
name: Release the Pro version (team only)
about: Describes default checklist for releasing the Pro plugin;
title: Release Pro v[VERSION]
labels: release
assignees: ''
type: task
---

To release the Pro plugin please make sure to check all the checkboxes below.

### Pre-release Checklist

**Branch Setup**
- [ ] Create release branch `release-<version>` from development branch
- [ ] Merge hotfixes/features into release branch (direct merge or PR)

**Dependencies**
- [ ] Run `composer update --no-dev --dry-run` to check for updates
- [ ] If updating dependencies: `composer update the/lib:version-constraint`
- [ ] Lock versions if needed (use exact version numbers)
- [ ] Document dependency changes in changelog
- [ ] Review Dependabot warnings/PRs, fix real issues
- [ ] Update the reference for the `publishpress/publishpress-planner` package in the `lib/composer.json` file to use the recently released version tag (e.g., `4.7.0`) instead of a branch reference. This ensures the Pro plugin uses the stable release of the Free plugin.

**Code Quality**
- [ ] Build JS files: `composer build:js` (if applicable)
- [ ] Run `composer check` to run check the code and make sure no warnings or errors.
- [ ] Run `composer test Unit` to run the Unit tests and verify all tests pass successfully.
- [ ] Run `composer test Integration` to run Integration tests and verify all tests pass successfully.

**Localization**
- [ ] Run `composer translate` to regenerate AI-assisted translations.
- [ ] Make sure to commit all i18n/translation updates together.
- [ ] Open a GitHub issue titled `Translation Update for Release v<version>`, and assign it to `@wocmultimedia` (lead translator for ES, FR, IT).
- [ ] Pause the release and wait for `@wocmultimedia` to review and confirm or close the translation issue.
- [ ] After approval, run `composer translate:download` to fetch updated translations from the
translation management service.
- [ ] Run `composer translate:compile` to generate all language files (MO, JSON, PHP)
- [ ] Add a summary of these changes in `CHANGELOG.md`.

**Version & Documentation**
- [ ] Update CHANGELOG.md with user-friendly descriptions
- [ ] Verify release date in CHANGELOG.md
- [ ] Update version in main plugin file and `readme.txt`
- [ ] Commit all changes to release branch

**Build & Test**
- [ ] Build package: `composer build` (creates `./dist` package)
- [ ] Send package to team for testing

### Release

- [ ] PR and merge `release-<version>` → `master`
- [ ] Merge `master` → `development`
- [ ] Create GitHub release (tag from `master` branch)

### Post-release

- [ ] Monitor [GitHub Actions](https://github.com/publishpress/publishpress-planner-pro/actions)
- [ ] Monitor the Slack channel #package-server
- [ ] Test update on staging site
