---
name: Release Add-on (team only)
about: Describes default checklist for the plugin's add-on release process.
title: Release [ADD-ON] version [VERSION]
labels: release
assignees: ''

---

To release the add-on plugin, please make sure to check all the checkboxes below.

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
- [ ] Update EDD registry and upload the new package
- [ ] Make the final test updating the plugin in a staging site
