---
name: Release the Free Version (Team Only)
about: Default checklist for the plugin's release process.
title: Release PublishPress Planner v[VERSION]
labels: release
assignees: ''
---

To release the Free plugin, ensure you complete all the tasks below.

### Pre-release Checklist
- [ ] Create a release branch named `release-<version>` from the development branch.
- [ ] Review and merge all relevant Pull Requests into the release branch.
- [ ] Start a dev-workspace session.
- [ ] Execute `composer update` to update the root and lib vendors.
- [ ] Review the updated packages. Mention any production library updates in the changelog.
- [ ] Inspect GitHub's Dependabot warnings or Pull Requests for relevant issues. Resolve any false positives first, then fix and commit the remaining issues.
- [ ] If necessary, build JS files for production using `composer build:js` and commit the changes.
- [ ] Run a WP VIP scan with `composer check:phpcs` to ensure no warnings or errors greater than 5 exist.
- [ ] Update the `.pot` file executing `composer gen:pot` and include a note in the changelog.
- [ ] Especially for minor and patch releases, maintain backward compatibility for changes like renamed or moved classes, namespaces, functions, etc. Include deprecation comments and mention this in the changelog. Major releases may remove deprecated code, but always note this in the changelog.
- [ ] Revise the changelog to include all changes with user-friendly descriptions and ensure the release date is accurate.
- [ ] Update the version number in the main plugin file and `readme.txt`, adhering to specifications from our [tech documentation](https://rambleventures.slab.com/posts/version-numbers-58nmrk4b), and commit to the release branch.
- [ ] Confirm there are no uncommitted changes.
- [ ] Build the zip package with `composer build`, creating a new package in the `./dist` directory.
- [ ] Distribute the new package to the team for testing.

### Release Checklist
- [ ] Create and merge a Pull Request for the release branch into the `main` branch.
- [ ] Merge the `main` branch into the `development` branch.
- [ ] Establish the GitHub release on the `main` branch with the correct tag.

#### WP SVN Deployment
- [ ] Navigate to the local copy of the SVN repo for the plugin.
- [ ] Update your working copy using `svn update`.
- [ ] Clear the `trunk` directory with `rm -rf trunk/*`.
- [ ] Unzip the built package and transfer files to the `trunk` folder.
- [ ] Remove any extraneous files (if found, create an issue to amend the `.rsync-filter-post-build` file). Keep only files really used on production.
- [ ] Find new files with `svn status | grep \?` and add them using `svn add <each_file_path>`.
- [ ] Identify removed files with `svn status | grep !` and delete them using `svn rm <each_file_path>`.
- [ ] Create the new tag using `svn cp trunk tags/<version>`.
- [ ] Commit the changes with `svn ci -m 'Releasing <version>'`.
- [ ] Await WordPress's version number update and perform a final test by updating the plugin on a staging site.
