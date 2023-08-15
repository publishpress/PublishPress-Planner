---
name: Release the Pro Version (Team Only)
about: Default checklist for the plugin's release process.
title: Release PublishPress Planner Pro v[VERSION]
labels: release
assignees: ''
---

To release the Pro plugin, ensure you complete all the tasks below.

### Pre-release Checklist
- [ ] Create a release branch named `release-<version>` from the development branch.
- [ ] Review and merge all relevant Pull Requests into the release branch.
- [ ] Start a dev-workspace session.
- [ ] Verify the correct version of the free plugin is referenced in the `lib/composer.json` file. Prefer stable versions.
- [ ] Execute `composer update` to update the root and lib vendors.
- [ ] Review the updated packages and mention any production library updates in the changelog.
- [ ] Check if all dependencies are synced from Free into the Pro plugin with `composer check:deps`. If required, merge dependencies using `composer fix:deps` and run `composer update` again.
- [ ] Check if the free plugin uses Composer's autoload and copy the autoload definition from the free plugin to the pro plugin refactoring the relative paths, on `/lib/composer.json`. Execute `composer dumpautoload` to update the autoload files. Commit the changes.
- [ ] Inspect GitHub's Dependabot warnings or Pull Requests for relevant issues. Resolve any false positives first, then fix and commit the remaining issues.
- [ ] If necessary, build JS files for production using `composer build:js` and commit the changes.
- [ ] Run a WP VIP scan with `composer check:phpcs` to ensure no warnings or errors greater than 5 exist.
- [ ] Update the `.pot` file executing `composer gen:pot` and include a note in the changelog.
- [ ] Especially for minor and patch releases, maintain backward compatibility for changes like renamed or moved classes, namespaces, functions, etc. Include deprecation comments and mention this in the changelog. Major releases may remove deprecated code, but always note this in the changelog.
- [ ] Revise the changelog to include all changes with user-friendly descriptions and ensure the release date is accurate.
  -- [ ] Update the version number in the main plugin file and `readme.txt`, adhering to specifications from our [tech documentation](https://rambleventures.slab.com/posts/version-numbers-58nmrk4b), and commit to the release branch.
- [ ] Confirm there are no uncommitted changes.
- [ ] Build the zip package with `composer build`, creating a new package in the `./dist` directory.
- [ ] Distribute the new package to the team for testing.

### Release Checklist
- [ ] Create and merge a Pull Request for the release branch into the `main` branch.
- [ ] Merge the `main` branch into the `development` branch.
- [ ] Establish the GitHub release on the `main` branch with the correct tag.

#### PublishPress.com Deployment
- [ ] Update the EDD registry on the Downloads menu, uploading the new package.
- [ ] Perform a final test by updating the plugin on a staging site.
