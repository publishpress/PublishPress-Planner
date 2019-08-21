---
name: Release PublishPress (team only)
about: Describes default checklist for the plugin's release process.
title: Release PublishPress version [VERSION]
labels: release
assignees: ''

---

To release the plugin, please make sure to check all the checkboxes below.

### Pre-release Checklist

- [ ] Run `composer update --no-dev` and check if there is any relevant update. Check if you need to lock the current version for any dependency.
- [ ] Commit changes to the `development` branch
- [ ] Update the changelog - make sure all the changes are there with a user-friendly description
- [ ] Update the version number to the next stable version. Use `$ phing set-version`
- [ ] Pull to the `development` branch
- [ ] Build the zip using `$ phing build`
- [ ] Send to the team for testing

### Release Checklist

- [ ] Create a Pull Request and merge it into the `master` branch
- [ ] Create the Github release (make sure it is based on the `master` branch and correct tag)

#### SVN Repo
- [ ] Cleanup the `trunk` directory.
- [ ] Unzip the built package and move files to the `trunk`
- [ ] Remove any eventual file that shouldn't be released in the package (if you find anything, make sure to create an issue to fix the build script)
- [ ] Look for new files `$ svn status | grep \?` and add them using `$ svn add <each_file_path>`
- [ ] Look for removed files `$ svn status | grep !` and remove them `$ svn rm <each_file_path>`
- [ ] Create the new tag `$ svn cp trunk tags/<version>`
- [ ] Commit the changes `$ svn ci 'Releasing <version>'`
- [ ] Wait until WordPress updates the version number and make the final test updating the plugin in a staging site
