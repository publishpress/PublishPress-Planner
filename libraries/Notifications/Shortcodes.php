<?php
/**
 * @package     PublishPress\Notifications
 * @author      PublishPress <help@publishpress.com>
 * @copyright   Copyright (c) 2018 PublishPress. All rights reserved.
 * @license     GPLv2 or later
 * @since       1.0.0
 */

namespace PublishPress\Notifications;

use PublishPress\Notifications\Traits\Dependency_Injector;
use PublishPress\Notifications\Traits\PublishPress_Module;

class Shortcodes
{
    use Dependency_Injector;
    use PublishPress_Module;

    /**
     * The post of the workflow.
     *
     * @var WP_Post
     */
    protected $workflow_post;

    /**
     * An array with arguments set by the action
     *
     * @var array
     */
    protected $action_args;

    /**
     * @var mixed
     */
    protected $cache_receiver;

    /**
     * Shortcodes constructor.
     */
    public function __construct()
    {
        add_action('publishpress_workflow_do_shortcode_in_content', [$this, 'setReceiverForShortcode'], 10, 2);
    }

    /**
     * Adds the shortcodes to replace text
     *
     * @param WP_Post $workflow_post
     * @param array   $action_args
     */
    public function register($workflow_post, $action_args)
    {
        $this->set_properties($workflow_post, $action_args);

        add_shortcode('psppno_actor', [$this, 'handle_psppno_actor']);
        add_shortcode('psppno_post', [$this, 'handle_psppno_post']);
        add_shortcode('psppno_workflow', [$this, 'handle_psppno_workflow']);
        add_shortcode('psppno_edcomment', [$this, 'handle_psppno_edcomment']);
        add_shortcode('psppno_receiver', [$this, 'handle_psppno_receiver']);
    }

    /**
     * Set the instance properties
     *
     * @param WP_Post $workflow_post
     * @param array   $action_args
     */
    protected function set_properties($workflow_post, $action_args)
    {
        $this->workflow_post = $workflow_post;
        $this->action_args   = $action_args;
    }

    /**
     * Returns the user who triggered the workflow for notification.
     * You can specify which user's property should be printed:
     *
     * [psppno_actor display_name]
     *
     * @param array $attrs
     *
     * @return string
     */
    public function handle_psppno_actor($attrs)
    {
        $user = $this->get_actor();

        return $this->get_user_data($user, $attrs);
    }

    /**
     * Returns data from the receiver, if available.
     * Available attributes:
     *   - name (display_name - default)
     *   - email
     *   - first_name
     *   - last_name
     *   - login
     *   - nickname
     *
     * [psppno_receiver name]
     *
     * @param array $attrs
     *
     * @return string
     */
    public function handle_psppno_receiver($attrs)
    {
        $receiver = $this->cache_receiver;

        // Decide what field to display.
        if (empty($attrs)) {
            $field = 'name';
        } else {
            $whitelist = [
                'name',
                'email',
                'first_name',
                'last_name',
                'login',
                'nickname',
            ];

            if (in_array($attrs[0], $whitelist)) {
                $field = $attrs[0];
            } else {
                $field = 'name';
            }
        }

        if (is_numeric($receiver)) {
            // Do we have an user?
            $user   = get_user_by('id', $receiver);
            $result = '';

            switch ($field) {
                case 'name':
                    $result = $user->display_name;
                    break;

                case 'email':
                    $result = $user->user_email;
                    break;

                case 'first_name':
                    $result = $user->first_name;
                    break;

                case 'last_name':
                    $result = $user->last_name;
                    break;

                case 'login':
                    $result = $user->user_login;
                    break;

                case 'nickname':
                    $result = $user->nickname;
                    break;

                default:
                    $result = $user->user_email;
                    break;
            }

            if (empty($result)) {
                $result = $user->display_name;
            }
        } else {
            $result = $receiver;

            // Do we have an email address?
            if (strpos($receiver, '@') > 0) {
                // Do we have a name?
                $separatorPos = strpos($receiver, '/');
                if ($separatorPos > 0) {

                    if (in_array('name', $attrs)) {
                        $result = substr($receiver, 0, $separatorPos);
                    } else {
                        $result = substr($receiver, $separatorPos + 1, strlen($receiver));
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Returns the current user, the actor of the action
     *
     * @return WP_User
     */
    protected function get_actor()
    {
        return wp_get_current_user();
    }

    /**
     * Returns the user's info. You can specify which user's property should be
     * printed passing that on the $attrs.
     *
     * If more than one attribute is given, we returns all the data
     * separated by comma (default) or specified separator, in the order it was
     * received.
     *
     * If no attribute is provided, we use display_name as default.
     *
     * Accepted attributes:
     *   - id
     *   - login
     *   - url
     *   - display_name
     *   - email
     *   - separator
     *
     * @param WP_User $user
     * @param array   $attrs
     *
     * @return string
     */
    protected function get_user_data($user, $attrs)
    {
        if ( ! is_array($attrs)) {
            if ( ! empty($attrs)) {
                $attrs[] = $attrs;
            } else {
                $attrs = [];
            }
        }

        // No attributes? Set the default one.
        if (empty($attrs)) {
            $attrs[] = 'display_name';
        }

        // Set the separator
        if ( ! isset($attrs['separator'])) {
            $attrs['separator'] = ', ';
        }

        // Get the user's info
        $info = [];

        foreach ($attrs as $index => $item) {
            switch ($item) {
                case 'id':
                    $info[] = $user->ID;
                    break;

                case 'login':
                    $info[] = $user->user_login;
                    break;

                case 'url':
                    $info[] = $user->user_url;
                    break;

                case 'display_name':
                    $info[] = $user->display_name;
                    break;

                case 'email':
                    $info[] = $user->user_email;
                    break;

                default:
                    break;
            }
        }

        return implode($attrs['separator'], $info);
    }

    /**
     * Returns the info from the post related to the notification.
     * You can specify which post's property should be printed:
     *
     * [psppno_post title]
     *
     * If no attribute is provided, we use title as default.
     *
     * Accepted attributes:
     *   - id
     *   - title
     *   - url
     *
     * @param array $attrs
     *
     * @return string
     */
    public function handle_psppno_post($attrs)
    {
        $post = $this->get_post();

        return $this->get_post_data($post, $attrs);
    }

    /**
     * Returns the post related to the notification.
     *
     * @return WP_Post
     */
    protected function get_post()
    {
        return $this->action_args['post'];
    }

    /**
     * Returns the post's info. You can specify which post's property should be
     * printed passing that on the $attrs.
     *
     * If more than one attribute is given, we returns all the data
     * separated by comma (default) or specified separator, in the order it was
     * received.
     *
     * If no attribute is provided, we use title as default.
     *
     * Accepted attributes:
     *   - id
     *   - title
     *   - permalink
     *   - date
     *   - time
     *   - old_status
     *   - new_status
     *   - separator
     *   - edit_link
     *
     * @param WP_Post $post
     * @param array   $attrs
     *
     * @throws \Exception
     *
     * @return string
     */
    protected function get_post_data($post, $attrs)
    {
        $publishpress = $this->get_service('publishpress');

        // No attributes? Set the default one.
        if (empty($attrs)) {
            $attrs = ['title'];
        }

        // Set the separator
        if ( ! isset($attrs['separator'])) {
            $attrs['separator'] = ', ';
        }

        // Get the post's info
        $info = [];

        foreach ($attrs as $index => $item) {
            switch ($item) {
                case 'id':
                    $info[] = $post->ID;
                    break;

                case 'title':
                    $info[] = $post->post_title;
                    break;

                case 'permalink':
                    $info[] = get_permalink($post->ID);
                    break;

                case 'date':
                    $info[] = get_the_date('', $post);
                    break;

                case 'time':
                    $info[] = get_the_time('', $post);
                    break;

                case 'old_status':
                case 'new_status':
                    $status = $publishpress->custom_status->get_custom_status_by(
                        'slug',
                        $this->action_args[$item]
                    );

                    if (empty($status) || 'WP_Error' === get_class($status)) {
                        break;
                    }

                    $info[] = $status->name;
                    break;

                case 'edit_link':
                    $admin_path = 'post.php?post=' . $post->ID . '&action=edit';
                    $info[]     = htmlspecialchars_decode(admin_url($admin_path));
                    break;

                case 'author_display_name':
                case 'author_email':
                case 'author_login':
                    $authordata = get_userdata($post->post_author);

                    $field_map = [
                        'author_display_name' => 'display_name',
                        'author_email'        => 'user_email',
                        'author_login'        => 'user_login',
                    ];

                    $user_field = $field_map[$item];
                    $data       = $authordata->{$user_field};

                    $info[] = apply_filters('pp_get_author_data', $data, $item, $post);
                    break;

                default:
                    if ($custom = apply_filters('publishpress_notif_shortcode_post_data', false, $item, $post, $attrs)) {
                        $info[] = $custom;
                    }

                    break;
            }
        }

        return implode($attrs['separator'], $info);
    }

    /**
     * Returns the info from the post related to the workflow.
     * You can specify which workflow's property should be printed:
     *
     * [psppno_workflow title]
     *
     * If no attribute is provided, we use title as default.
     *
     * Accepted attributes:
     *   - id
     *   - title
     *
     * @param array $attrs
     *
     * @return string
     */
    public function handle_psppno_workflow($attrs)
    {
	    if (is_string( $attrs )) {
		    $attrs = [ $attrs ];
	    }

        $post = $this->workflow_post;

        // No attributes? Set the default one.
        if (empty($attrs)) {
            $attrs[] = 'title';
        }

        // Set the separator
        if ( ! isset($attrs['separator'])) {
            $attrs['separator'] = ', ';
        }

        // Get the post's info
        $info = [];

        foreach ($attrs as $index => $item) {
            switch ($item) {
                case 'id':
                    $info[] = $post->ID;
                    break;

                case 'title':
                    $info[] = $post->post_title;
                    break;

                default:
                    break;
            }
        }

        return implode($attrs['separator'], $info);
    }

    /**
     * Returns the info from the comment related to the workflow.
     * You can specify which comment's property should be printed:
     *
     * [psppno_comment content]
     *
     * If no attribute is provided, we use content as default.
     *
     * Accepted attributes:
     *   - id
     *   - content
     *   - author
     *   - author_email
     *   - author_url
     *   - author_ip
     *   - date
     *
     * @param array $attrs
     *
     * @return string
     */
    public function handle_psppno_edcomment($attrs)
    {
        if ( ! isset($this->action_args['comment'])) {
            return;
        }

        $comment = $this->action_args['comment'];

        // No attributes? Set the default one.
        if (empty($attrs)) {
            $attrs[] = 'content';
        }

        // Set the separator
        if ( ! isset($attrs['separator'])) {
            $attrs['separator'] = ', ';
        }

        // Get the post's info
        $info = [];

        foreach ($attrs as $index => $item) {
            switch ($item) {
                case 'id':
                    $info[] = $comment->comment_ID;
                    break;

                case 'content':
                    $info[] = $comment->comment_content;
                    break;

                case 'author':
                    $info[] = $comment->comment_author;
                    break;

                case 'author_email':
                    $info[] = $comment->comment_author_email;
                    break;

                case 'author_url':
                    $info[] = $comment->comment_author_url;
                    break;

                case 'author_ip':
                    $info[] = $comment->comment_author_ip;
                    break;

                case 'date':
                    $info[] = $comment->comment_date;
                    break;

                default:
                    break;
            }
        }

        return implode($attrs['separator'], $info);
    }

    /**
     * @param string $content
     * @param mixed  $receiver
     */
    public function setReceiverForShortcode($content, $receiver)
    {
        $this->cache_receiver = $receiver;
    }

    /**
     * Removes the shortcodes
     */
    public function unregister()
    {
        remove_shortcode('psppno_actor');
        remove_shortcode('psppno_post');
        remove_shortcode('psppno_workflow');
        remove_shortcode('psppno_edcomment');
        remove_shortcode('psppno_receiver');

        $this->cleanup();
    }

    /**
     * Cleanup the data
     */
    protected function cleanup()
    {
        $this->workflow_post = null;
        $this->action_args   = null;
    }
}
