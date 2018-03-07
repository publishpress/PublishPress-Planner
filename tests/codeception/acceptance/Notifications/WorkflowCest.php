<?php

namespace Notifications;

use AcceptanceTester;

class WorkflowCest
{
    public function _before(AcceptanceTester $I)
    {
        $I->amOnPage('/wp-login.php');
        $I->wait(1);
        $I->fillField(['name' => 'log'], 'admin');
        $I->fillField(['name' => 'pwd'], 'admin');
        $I->click('#wp-submit');
    }

    public function _after(AcceptanceTester $I)
    {
    }

    // tests
    public function iWantToCreateANotificationWorkflowForUsersAndRole(AcceptanceTester $I)
    {
        //        $I->amOnPage('/wp-admin/edit.php?post_type=psppnotif_workflow');
        //        $I->see('Notification Workflows');
        //        $I->click('.page-title-action');
        //
        //        $I->fillField('#title', 'Testing...');
        //
        //        // Check the trigger for comment is added.
        //        $I->checkOption('#publishpress_notif_event_editorial_comment');
        //
        //        // Select all posts.
        //        $I->click('#publishpress_notif_event_content_post_type');
        //        $I->click('.publishpress_notif_event_content_post_type_filters button.ms-choice');
        //        $I->checkOption('.publishpress_notif_event_content_post_type_filters input[value="post"]');
        //        $I->checkOption('.publishpress_notif_event_content_post_type_filters input[value="page"]');
        //
        //        // Select the admin and author 1 users.
        //        $I->click('#publishpress_notif_user');
        //        $I->click('#publishpress_notif_user_list_filter button.ms-choice');
        //        // Check admin.
        //        $I->checkOption('#publishpress_notif_user_list_filter li input[value="1"]');
        //        // Check author1.
        //        $I->checkOption('#publishpress_notif_user_list_filter li input[value="2"]');
        //
        //        // Select 2 roles
        //        $I->checkOption('#publishpress_notif_role');
        //        $I->click('#publishpress_notif_role_list_filter button.ms-choice');
        //        $I->checkOption('#publishpress_notif_role_list_filter li input[value="administrator"]');
        //        $I->checkOption('#publishpress_notif_role_list_filter li input[value="editor"]');
        //
        //        // Fill the content.
        //        $I->fillField('#publishpress_notification_content_main_subject', 'Hey, watch out!');
        //        $I->executeJS('tinyMCE.activeEditor.setContent(\'Here is the notification body...\');');
        //
        //        // Submit.
        //        $I->click('#publish');
    }
}
