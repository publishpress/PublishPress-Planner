<?php
/**
 * @package     PublishPress\Notifications
 * @author      PublishPress <help@publishpress.com>
 * @copyright   Copyright (c) 2018 PublishPress. All rights reserved.
 * @license     GPLv2 or later
 * @since       1.0.0
 */

namespace PublishPress\Notifications\Workflow\Step\Channel;

use WP_Post;

class Email extends Base implements Channel_Interface
{
    const META_KEY_EMAIL = '_psppno_chnemail';

    /**
     * The constructor
     *
     * @throws \Exception
     */
    public function __construct()
    {
        $this->name  = 'email';
        $this->label = __('Email', 'publishpress');
        $this->icon  = PUBLISHPRESS_URL . 'modules/improved-notifications/assets/img/icon-email.png';

        parent::__construct();
    }

    /**
     * Check if this channel is selected and triggers the notification.
     *
     * @param WP_Post $workflow_post
     * @param array   $action_args
     * @param array   $receivers
     * @param array   $content
     * @param string  $channel
     *
     * @throws \Exception
     */
    public function action_send_notification($workflow_post, $action_args, $receivers, $content, $channel)
    {
        $this->get_service('debug')->write($receivers, 'Email::action_send_notification $receivers');

        if (empty($receivers)) {
            return;
        }

        // Make sure we unserialize the content when it comes from async notifications.
        if (is_string($content)) {
            $content = maybe_unserialize($content);
        }

        $signature  = $this->get_notification_signature($content, $channel . ':' . serialize($receivers));
        $controller = $this->get_service('workflow_controller');

        // Check if the notification was already sent
        if ($controller->is_notification_signature_registered($signature)) {
            return;
        }

        // Send the emails
        $emails = $this->get_receivers_emails($receivers);
        $action = 'transition_post_status' === $action_args['action'] ? 'status-change' : 'comment';

        $this->get_service('debug')->write($emails, 'Email::action_send_notification $emails');

        $subject = html_entity_decode($content['subject']);

        $body = apply_filters('the_content', $content['body']);
        $body = str_replace(']]>', ']]&gt;', $body);

        // Call the legacy notification module
        foreach ($emails as $email) {
            // Split the name and email, if set.
            $separatorPos = strpos($email, '/');
            if ($separatorPos > 0) {
                $email = substr($email, $separatorPos + 1, strlen($email));
            }

            $this->get_service('debug')->write($email, 'Email::action_send_notification $email');

            $this->get_service('publishpress')->notifications->send_email(
                $action,
                $action_args,
                $subject,
                $body,
                '',
                $email
            );
        }

        $controller->register_notification_signature($signature);
    }

    /**
     * Returns a list of the receivers' emails
     *
     * @param array $receivers
     *
     * @return array
     */
    protected function get_receivers_emails($receivers)
    {
        $emails = [];

        if ( ! empty($receivers)) {
            if ( ! is_array($receivers)) {
                $receivers = [$receivers];
            }

            foreach ($receivers as $receiver) {
                // Check if we have the user ID or an email address
                if (is_numeric($receiver)) {
                    $data     = $this->get_user_data($receiver);
                    $emails[] = $data->user_email;
                    continue;
                }

                // Is it a valid email address?
                $emails[] = sanitize_email($receiver);
            }
        }

        // Remove duplicated
        $emails = array_unique($emails);

        return $emails;
    }

    /**
     * Filter the receivers organizing it by channel. Each channel get the list of receivers
     * and return
     *
     * @param array   $receivers
     * @param WP_Post $workflow_post
     * @param array   $action_args
     *
     * @return array
     */
    public function filter_receivers($receivers, $workflow_post, $action_args)
    {
        return $receivers;
    }
}
