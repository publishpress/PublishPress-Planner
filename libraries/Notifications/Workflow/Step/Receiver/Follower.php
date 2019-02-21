<?php
/**
 * @package     PublishPress\Notifications
 * @author      PublishPress <help@publishpress.com>
 * @copyright   Copyright (c) 2018 PublishPress. All rights reserved.
 * @license     GPLv2 or later
 * @since       1.0.0
 */

namespace PublishPress\Notifications\Workflow\Step\Receiver;

use PublishPress\Notifications\Traits\Dependency_Injector;

class Follower extends Simple_Checkbox implements Receiver_Interface
{
    use Dependency_Injector;

    const META_KEY = '_psppno_tofollower';

    const META_VALUE = 'follower';

    protected $option = 'receiver_follower';

    /**
     * The constructor
     */
    public function __construct()
    {
        $this->name        = 'follower';
        $this->label       = __('Users who selected "Notify me" for the content', 'publishpress');
        $this->option_name = 'receiver_follower';

        parent::__construct();
    }

    /**
     * Filters the list of receivers for the workflow. Returns the list of IDs.
     *
     * @param array   $receivers
     * @param WP_Post $workflow
     * @param array   $args
     *
     * @return array
     */
    public function filter_workflow_receivers($receivers, $workflow, $args)
    {
        global $publishpress;

        // If checked, add the authors to the list of receivers
        if ($this->is_selected($workflow->ID)) {
            $post_id = $args['post']->ID;

            if (empty($post_id)) {
                return $receivers;
            }

            $followers = [];

            // Check if we are saving the post and use that data instead of the stored taxonomies/metadata.
            if ('POST' === $_SERVER['REQUEST_METHOD']
                && (isset($_POST['action']) && 'editpost' === $_POST['action'])
            ) {
                $toNotify = (array)$_POST['to_notify'];


                $roles  = [];
                $users  = [];
                $emails = [];

                if ( ! empty($toNotify)) {
                    foreach ($toNotify as $item) {
                        if (is_numeric($item)) {
                            $users[] = $item;
                        } else {
                            if (strpos($item, '@') > 0) {
                                $emails[] = $item;
                            } else {
                                $roles[] = $item;
                            }
                        }
                    }
                }
                $this->get_service('debug')->write($toNotify, 'Follower::filter_workflow_receivers $toNotify:' . __LINE__);
            } else {
                // Get following users and roles
                $roles  = $publishpress->notifications->get_roles_to_notify($post_id, 'slugs');
                $users  = $publishpress->notifications->get_users_to_notify($post_id, 'id');
                $emails = $publishpress->notifications->get_emails_to_notify($post_id);
            }

            $this->get_service('debug')->write($emails, 'Follower::filter_workflow_receivers $emails:' . __LINE__);

            // Extract users from roles
            if ( ! empty($roles)) {
                foreach ($roles as $role) {
                    $roleUsers = get_users(
                        [
                            'role' => $role,
                        ]
                    );

                    if ( ! empty($roleUsers)) {
                        foreach ($roleUsers as $user) {
                            if (is_user_member_of_blog($user->ID)) {
                                $followers[] = $user->ID;
                            }
                        }
                    }
                }
            }

            // Merge roles' users and users
            $followers         = array_merge($followers, $users);
            $notifyCurrentUser = apply_filters('publishpress_notify_current_user', false);

            // Process the recipients for this email to be sent
            if ( ! empty($followers)) {
                foreach ($followers as $key => $user) {
                    // Make sure we have only user objects in the list
                    if (is_numeric($user)) {
                        $user = get_user_by('ID', $user);
                    }

                    // Don't send the email to the current user unless we've explicitly indicated they should receive it
                    if (false === $notifyCurrentUser && wp_get_current_user()->user_email == $user->user_email) {
                        unset($followers[$key]);
                    }
                }
            }

            // Merge the emails.
            if ( ! empty($emails)) {
                foreach ($emails as $email) {
                    // Do we have a name?
                    $separatorPost = strpos($email, '/');
                    if ($separatorPost > 0) {
                        $emailAddr = substr($email, strpos($email, '/') + 1, strlen($email));
                    } else {
                        $emailAddr = $email;
                    }

                    // Don't send the email to the current user unless we've explicitly indicated they should receive it
                    if (false === $notifyCurrentUser && wp_get_current_user()->user_email == $emailAddr) {
                        continue;
                    }

                    $followers[] = 'email:' . $email;
                }
            }

            /**
             * Filters the list of followers.
             *
             * @param array   $followers
             * @param WP_Post $workflow
             * @param array   $args
             */
            $followers = apply_filters('publishpress_notif_workflow_receiver_post_followers', $followers, $workflow,
                $args);
            $this->get_service('debug')->write($followers, 'Follower::filter_workflow_receivers $followers:' . __LINE__);

            // Add the user ids for the receivers list
            if ( ! empty($followers)) {
                foreach ($followers as $user) {
                    if (is_object($user)) {
                        $receivers[] = $user->ID;
                    } else {
                        $receivers[] = $user;
                    }
                }
            }
        }

        return $receivers;
    }

    /**
     * Add the respective value to the column in the workflow list
     *
     * @param array $values
     * @param int   $post_id
     *
     * @return array
     */
    public function filter_receivers_column_value($values, $post_id)
    {
        if ($this->is_selected($post_id)) {
            $values[] = __('"Notify me"', 'publishpress');
        }

        return $values;
    }
}
