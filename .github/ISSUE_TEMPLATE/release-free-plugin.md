---
name: Release the Free version (team only)
about: Describes default checklist for the plugin's release process.
title: Release v[VERSION]
labels: release
assignees: ''
type: task
---

To release the Free plugin please make sure to check all the checkboxes below.

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

**Code Quality**
- [ ] Build JS files: `composer build:js` (if applicable)
- [ ] Run PHPCS: `composer check:phpcs`

**Localization**
- [ ] Update `.pot` file
- [ ] Add to CHANGELOG.md

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
  - Triggers automatic SVN deployment

### Post-release

- [ ] Monitor [GitHub Actions](https://github.com/publishpress/publishpress-future/actions)
- [ ] Verify [WordPress.org plugin page](https://wordpress.org/plugins/post-expirator/)
- [ ] Test update on staging site
