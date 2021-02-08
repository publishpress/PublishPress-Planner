<?php namespace modules\editorial_metadata;


use Codeception\Example;
use ErrorException;
use PP_Editorial_Metadata;
use WpunitTester;

class PP_Editorial_MetadataCest
{
    public function _before(WpunitTester $I)
    {
    }

    /**
     * @return PP_Editorial_Metadata;
     */
    private function getEditorialMetadataModule()
    {
        $plugin = PublishPress();
        $plugin->action_init();

        return $plugin->editorial_metadata;
    }

    private function cleanUpRoles($roles)
    {
        foreach ($roles as $role) {
            remove_role($role);
            add_role($role, $role);
        }
    }

    /**
     * @example ["administrator", "editor", "author"]
     */
    public function setDefaultCapabilitiesForViewingMetadata(WpunitTester $I, Example $roles)
    {
        $module = $this->getEditorialMetadataModule();

        $this->cleanUpRoles($roles);

        $module->setDefaultCapabilities();

        foreach ($roles as $role) {
            $role = get_role($role);

            $I->assertTrue($role->has_cap(PP_Editorial_Metadata::CAP_VIEW_METADATA), sprintf('The role %s can edit metadata', $role->name));
        }
    }

    /**
     * @example ["administrator", "editor", "author"]
     */
    public function setDefaultCapabilitiesForEditingMetadata(WpunitTester $I, Example $roles)
    {
        $module = $this->getEditorialMetadataModule();

        $this->cleanUpRoles($roles);

        $module->setDefaultCapabilities();

        foreach ($roles as $role) {
            $role = get_role($role);

            $I->assertTrue($role->has_cap(PP_Editorial_Metadata::CAP_EDIT_METADATA), sprintf('The role %s can edit metadata', $role->name));
        }
    }

    /**
     * @example ["administrator", "editor", "author"]
     */
    public function setDefaultCapabilitiesForEditingMetadataWhenOneUserRoleIsMissedShouldntRaiseAnError(WpunitTester $I, Example $roles)
    {
        $module = $this->getEditorialMetadataModule();

        remove_role('editor');
        remove_role('author');

        $module->setDefaultCapabilities();

        $I->assertTrue(true, 'If we got here we didn\'t see an error');
    }
}
