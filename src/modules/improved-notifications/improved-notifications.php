<?php
/**
 * @package PublishPress
 * @author PressShack
 *
 * Copyright (c) 2017 PressShack
 *
 * ------------------------------------------------------------------------------
 * Based on Edit Flow
 * Author: Daniel Bachhuber, Scott Bressler, Mohammad Jangda, Automattic, and
 * others
 * Copyright (c) 2009-2016 Mohammad Jangda, Daniel Bachhuber, et al.
 * ------------------------------------------------------------------------------
 *
 * This file is part of PublishPress
 *
 * PublishPress is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * PublishPress is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with PublishPress.  If not, see <http://www.gnu.org/licenses/>.
 */

use PublishPress\Notifications\Traits\Dependency_Injector;
use PublishPress\Notifications\Traits\PublishPress_Module;
use PublishPress\Notifications\Workflow\Step\Receiver\Site_Admin as Receiver_Site_Admin;
use PublishPress\Notifications\Workflow\Step\Event\Post_Save as Event_Post_Save;
use PublishPress\Notifications\Workflow\Step\Event\Editorial_Comment as Event_Editorial_Comment;
use PublishPress\Notifications\Workflow\Step\Event\Filter\Post_Type as Filter_Post_Type;
use PublishPress\Notifications\Workflow\Step\Event\Filter\Post_Status as Filter_Post_Status;
use PublishPress\Notifications\Workflow\Step\Event\Filter\Category as Filter_Category;
use PublishPress\Notifications\Workflow\Step\Content\Main as Content_Main;

if ( ! class_exists( 'PP_Improved_Notifications' ) ) {
	/**
	 * class Notifications
	 */
	class PP_Improved_Notifications extends PP_Module {

		use Dependency_Injector, PublishPress_Module;

		const SETTINGS_SLUG = 'pp-improved-notifications-settings';

		const META_KEY_IS_DEFAULT_WORKFLOW = '_psppno_is_default_workflow';

		public $module_name = 'improved-notifications';

		/**
		 * Instace for the module
		 *
		 * @var stdClass
		 */
		public $module;

		/**
		 * List of workflows
		 *
		 * @var array
		 */
		protected $workflows;

		/**
		 * Construct the Notifications class
		 */
		public function __construct() {
			global $publishpress;

			$this->twigPath = dirname( dirname( dirname( __FILE__ ) ) ) . '/twig';

			$this->module_url = $this->get_module_url( __FILE__ );

			// Register the module with PublishPress
			$args = array(
				'title'                => __( 'Notifications', 'publishpress-notifications' ),
				'short_description'    => __( 'Improved notifications for PublishPress', 'publishpress-notifications' ),
				'extended_description' => __( 'Improved Notifications for PublishPress', 'publishpress-notifications' ),
				'module_url'           => $this->module_url,
				'icon_class'           => 'dashicons dashicons-feedback',
				'slug'                 => 'improved-notifications',
				'default_options'      => array(
					'enabled'                  => 'on',
					'post_types'               => array( 'post' ),
				),
				'options_page'      => false,
			);

			// Apply a filter to the default options
			$args['default_options'] = apply_filters( 'publishpress_notif_default_options', $args['default_options'] );
			$this->module = $publishpress->register_module(
				PublishPress\Util::sanitize_module_name( $this->module_name ),
				$args
			);

			parent::__construct();

			$this->configure_twig();
		}

		protected function configure_twig() {
			$function = new Twig_SimpleFunction( 'settings_fields', function () {
				return settings_fields( $this->module->options_group_name );
			} );
			$this->twig->addFunction( $function );

			$function = new Twig_SimpleFunction( 'nonce_field', function ( $context ) {
				return wp_nonce_field( $context );
			} );
			$this->twig->addFunction( $function );

			$function = new Twig_SimpleFunction( 'submit_button', function () {
				return submit_button();
			} );
			$this->twig->addFunction( $function );

			$function = new Twig_SimpleFunction( '__', function ( $id ) {
				return __( $id, 'publishpress-notifications' );
			} );
			$this->twig->addFunction( $function );

			$function = new Twig_SimpleFunction( 'do_settings_sections', function ( $section ) {
				return do_settings_sections( $section );
			} );
			$this->twig->addFunction( $function );
		}

		/**
		 * Initialize the module. Conditionally loads if the module is enabled
		 */
		public function init() {
			add_action( 'admin_enqueue_scripts', array( $this, 'add_admin_scripts' ) );

			// Workflow form
			add_filter( 'get_sample_permalink_html', array( $this, 'filter_get_sample_permalink_html_workflow' ), 10, 5 );
			add_filter( 'post_row_actions', array( $this, 'filter_row_actions' ), 10, 2 );
			add_action( 'add_meta_boxes_' . PUBLISHPRESS_NOTIF_POST_TYPE_WORKFLOW, array( $this, 'action_meta_boxes_workflow' ) );
			add_action( 'save_post', [ $this, 'save_meta_boxes' ], 10, 2 );

			// Cancel the PublishPress and PublishPress Slack Notifications
			add_filter( 'publishpress_slack_enable_notifications', [ $this, 'filter_slack_enable_notifications' ] );
			remove_all_actions( 'pp_send_notification_status_update' );
			remove_all_actions( 'pp_send_notification_comment' );

			// Instantiate the controller of workflow's
			$workflow_controller = $this->get_service( 'workflow_controller' );
			$workflow_controller->load_workflow_steps();

			// Add action to intercept transition between post status - post save
			add_action( 'transition_post_status', [ $this, 'action_transition_post_status' ], 999, 3 );
			// Add action to intercep new editorial comments
			add_action( 'pp_post_insert_editorial_comment', [ $this, 'action_editorial_comment' ], 999, 3 );

			// Add fields to the user's profile screen to select notification channels
			add_action( 'show_user_profile', [ $this, 'user_profile_fields' ] );
			add_action( 'edit_user_profile', [ $this, 'user_profile_fields' ] );

			// Add action to save data from the user's profile screen
			add_action( 'personal_options_update', [ $this, 'save_user_profile_fields' ] );
			add_action( 'edit_user_profile_update', [ $this, 'save_user_profile_fields' ] );

			// Load CSS
			add_action( 'admin_print_styles', array( $this, 'add_admin_styles' ) );
		}

		/**
		 * Load default editorial metadata the first time the module is loaded
		 *
		 * @since 0.7
		 */
		public function install() {
			// Check if we any other workflow before create, avoiding duplicated registers
			if ( false === $this->has_default_workflows() ) {
				$this->create_default_workflows();
			}
		}

		/**
		 * Create default notification workflows based on current notification settings
		 */
		protected function create_default_workflows() {
			// Post Save
			$args = [
				'post_title'      => __( 'Notify when posts are saved', 'publishpress-notifications' ),
				'event_meta_key'  => Event_Post_save::META_KEY_SELECTED,
				'content_subject' => 'The post [psppno_post title] was saved',
				'content_body'    => 'Post: [psppno_post id title separator="-"]',
			];
			$this->create_default_workflow( $args );

			// Editorial Comment
			$args = [
				'post_title'      => __( 'Notify on editorial comments', 'publishpress-notifications' ),
				'event_meta_key'  => Event_Editorial_Comment::META_KEY_SELECTED,
				'content_subject' => 'New Editorial Comment on [psppno_post title]',
				'content_body'    => 'Comment: [psppno_edcomment content]',
			];
			$this->create_default_workflow( $args );
		}

		/**
		 * Create default notification workflow for the post_save event
		 *
		 * $args = [
		 *   'post_title' => ...
		 *   'content_subject' => ...
		 *   'content_body' => ...
		 *   'event_meta_key' => ...
		 * ]
		 *
		 * @param array $args
		 */
		protected function create_default_workflow( $args ) {
			$workflow = [
				'post_status' => 'publish',
				'post_title'  => $args['post_title'],
				'post_type'   => 'psppnotif_workflow',
				'meta_input'  => [
					static::META_KEY_IS_DEFAULT_WORKFLOW          => '1',
					$args['event_meta_key']                       => '1',
					Filter_Post_Type::META_KEY_POST_TYPE          => 'all',
					Filter_Post_Status::META_KEY_POST_STATUS_FROM => 'all',
					Filter_Post_Status::META_KEY_POST_STATUS_TO   => 'all',
					Filter_Category::META_KEY_CATEGORY            => 'all',
					Content_Main::META_KEY_SUBJECT                => $args['content_subject'],
					Content_Main::META_KEY_BODY                   => $args['content_body'],
					Receiver_Site_Admin::META_KEY                 => 1,
				],
			];

			wp_insert_post( $workflow );
		}

		/**
		 * Returns true if we found any default workflow
		 *
		 * @return Bool
		 */
		protected function has_default_workflows() {
			$query_args = [
				'post_type'  => 'psppnotif_workflow',
				'meta_query' => [
					[
						'key'   => static::META_KEY_IS_DEFAULT_WORKFLOW,
						'value' => '1',
					],
				],
			];

			$query = new WP_Query( $query_args );

			if ( ! $query->have_posts() ) {
				return false;
			}

			return $query->the_post();
		}

		/**
		 * Upgrade our data in case we need to
		 *
		 * @since 0.7
		 */
		public function upgrade( $previous_version ) {
		}

		/**
		 * Filters the enable_notifications on the Slack add-on to block it.
		 *
		 * @param bool  $enable_notifications
		 */
		public function filter_slack_enable_notifications( $enable_notifications ) {
			return false;
		}

		/**
		 * Action called on transitioning a post. Used to trigger the
		 * controller of workflows to filter and execute them.
		 *
		 * @param string  $new_status
		 * @param string  $old_status
		 * @param WP_Post $post
		 */
		public function action_transition_post_status( $new_status, $old_status, $post ) {

			// Ignore if the post_type is an internal post_type
			if ( PUBLISHPRESS_NOTIF_POST_TYPE_WORKFLOW === $post->post_type ) {
				return;
			}

			// Go ahead and do the action to run workflows
			$args = [
				'action'     => 'transition_post_status',
				'post'       => $post,
				'new_status' => $new_status,
				'old_status' => $old_status,
			];

			do_action( 'publishpress_notif_run_workflows', $args );
		}

		/**
		 * Action called on editorial comments. Used to trigger the
		 * controller of workflows to filter and execute them.
		 *
		 * @param WP_Comment $comment
		 */
		public function action_editorial_comment( $comment ) {
			// Go ahead and do the action to run workflows
			$post = get_post( $comment->comment_post_ID );
			$args = [
				'action'     => 'editorial_comment',
				'post'       => $post,
				'new_status' => $post->post_status,
				'old_status' => $post->post_status,
				'comment'    => $comment,
			];

			do_action( 'publishpress_notif_run_workflows', $args );
		}

		/**
		 * Enqueue scripts and stylesheets for the admin pages.
		 * @TODO uncomment when admin.js is required
		 *
		 * @param string $hook_suffix
		 */
		public function add_admin_scripts( $hook_suffix ) {
			if ( in_array( $hook_suffix, [ 'profile.php', 'user-edit.php'] ) ) {
				wp_enqueue_script( 'psppno-user-profile-notifications', plugin_dir_url( __FILE__ ) . 'assets/js/user_profile.js');
			}
		}

		/**
		 * Filters the permalink output in the form, to disable it for the
		 * workflow form.
		 */
		public function filter_get_sample_permalink_html_workflow( $return, $post_id, $new_title, $new_slug, $post ) {

			if ( PUBLISHPRESS_NOTIF_POST_TYPE_WORKFLOW === $post->post_type ) {

				$return = '';
			}

			return $return;
		}

		public function filter_row_actions( $actions, $post ) {
			if ( PUBLISHPRESS_NOTIF_POST_TYPE_WORKFLOW === $post->post_type ) {

				unset( $actions[ 'view' ] );
			}

			return $actions;
		}

		public function action_meta_boxes_workflow() {
			add_meta_box(
				'publishpress_notif_workflow_div',
				__( 'Workflow Settings', 'publishpress-notifications' ),
				[ $this, 'publishpress_notif_workflow_metabox' ],
				null,
				'advanced',
				'high'
			);
		}

		public function publishpress_notif_workflow_metabox() {
			// Adds the nonce field
			wp_nonce_field( 'publishpress_notif_save_metabox', 'publishpress_notif_metabox_events_nonce' );

			$twig = $this->get_service( 'twig' );

			$main_context = [];

			// Renders the event section
			$context = [
				'id'     => 'event',
				'header' => __( 'When to notify?' ),
				'html'   => apply_filters( 'publishpress_notif_render_metabox_section_event', '' ),
			];
			$main_context['section_event'] = $twig->render( 'workflow_metabox_section.twig', $context );

			// Renders the receiver section
			$context = [
				'id'   => 'receiver',
				'header' => __( 'Who to notify?' ),
				'html' => apply_filters( 'publishpress_notif_render_metabox_section_receiver', '' ),
			];
			$main_context['section_receiver'] = $twig->render( 'workflow_metabox_section.twig', $context );

			// Renders the content section
			$context = [
				'id'   => 'content',
				'header' => __( 'What to say?' ),
				'html' => apply_filters( 'publishpress_notif_render_metabox_section_content', '' ),
			];
			$main_context['section_content'] = $twig->render( 'workflow_metabox_section.twig', $context );

			// Renders the channel section
			$context = [
				'id'   => 'channel',
				'html' => apply_filters( 'publishpress_notif_render_metabox_section_channel', '' ),
			];
			$main_context['section_channel'] = $twig->render( 'workflow_metabox_section.twig', $context );

			echo $twig->render( 'workflow_metabox.twig', $main_context );
		}

		/**
		 * If it detects a notification workflow is being saved, triggers an
		 * action for the workflow steps to be able to save their specific
		 * metadata from the metaboxes.
		 *
		 * @param int      $id    Unique ID for the post being saved
		 * @param WP_Post  $post  Post object
		 */
		public function save_meta_boxes( $id, $post )
		{
			// Check if the saved post is a notification workflow

			if ( PUBLISHPRESS_NOTIF_POST_TYPE_WORKFLOW === $post->post_type ) {
				// Authentication checks. Make sure the data came from the metabox
				if ( ! (
					isset( $_POST['publishpress_notif_metabox_events_nonce'] )
					&& wp_verify_nonce(
						$_POST['publishpress_notif_metabox_events_nonce'],
						'publishpress_notif_save_metabox'
					)
				) ) {
					return $id;
				}

				// Avoids autosave
				if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) ) {
					return $id;
				}

				// Do the action so each workflow step class can save its metabox data
				do_action( 'publishpress_notif_save_workflow_metadata', $id, $post );
			}

		}

		/**
		 * Returns a list of published workflows.
		 *
		 * @return array
		 */
		protected function get_published_workflows() {
			if ( empty( $this->workflows ) ) {
				// Build the query
				$query_args = [
					'nopaging'    => true,
					'post_type'   => PUBLISHPRESS_NOTIF_POST_TYPE_WORKFLOW,
					'post_status' => 'publish',
					'no_found_rows' => true,
					'cache_results' => true,
					'meta_query'  => [],
				];

				$query = new \WP_Query( $query_args );

				$this->workflows = $query->posts;
			}

			return $this->workflows;
		}

		/**
		 * Add extra fields to the user profile to allow them choose where to
		 * receive notifications per workflow.
		 *
		 * @param WP_User $user
		 */
		public function user_profile_fields( $user ) {
			$twig = $this->get_service( 'twig' );

			// Adds the nonce field
			wp_nonce_field( 'psppno_user_profile', 'psppno_user_profile_nonce' );

			/**
			 * Filters the list of notification channels to display in the
			 * user profile.
			 *
			 * [
			 *    'name': string
			 *    'label': string
			 *    'options': [
			 *        'name'
			 *        'html'
			 *    ]
			 * ]
			 *
			 * @param array
			 */
			$default_channels = [
				[
					'name'    => 'mute',
					'label'   => __( 'Muted', 'publishpress-notifications' ),
					'options' => [],
					'icon'    => PUBLISHPRESS_URL . 'modules/improved-notifications/assets/img/icon-mute.png',
				]
			];
			$channels = apply_filters( 'psppno_filter_channels_user_profile', $default_channels );

			$workflow_channels = $this->get_user_workflow_channels( $user );
			$channels_options  = $this->get_user_workflow_channel_options( $user );

			$context = [
				'labels' => [
					'title'       => __( 'Editorial Notifications', 'publishpress-notifications' ),
					'description' => __( 'Choose the channels where each workflow will send notifications to:', 'publishpress-notifications' ),
					'mute'        => __( 'Muted', 'publishpress-notifications' ),
					'workflows'   => __( 'Workflows', 'publishpress-notifications' ),
					'channels'    => __( 'Channels', 'publishpress-notifications' ),
				],
				'workflows'         => $this->get_published_workflows(),
				'channels'          => $channels,
				'workflow_channels' => $workflow_channels,
				'channels_options'  => $channels_options,
			];

			echo $twig->render( 'user_profile_notification_channels.twig', $context );
		}

		/**
		 * Returns the list of channels for the workflows we find in the user's
		 * meta data
		 *
		 * @param WP_User $user
		 *
		 * @return array
		 */
		public function get_user_workflow_channels( $user ) {
			$workflows = $this->get_published_workflows();
			$channels  = [];

			foreach ( $workflows as $workflow ) {
				$channel = get_user_meta( $user->ID, 'psppno_workflow_channel_' . $workflow->ID, true );

				// If no channel is set yet, use the default one
				if ( empty( $channel ) ) {
					/**
					 * Filters the default notification channel.
					 *
					 * @param string $default_channel
					 *
					 * @return string
					 */
					$channel = apply_filters( 'psppno_filter_default_notification_channel', 'email' );
				}

				$channels[ $workflow->ID ] = $channel;
			}

			return $channels;
		}

		/**
		 * Returns the list of options for the channels in the workflows we find
		 * in the user's meta data.
		 *
		 * @param WP_User $user
		 *
		 * @return array
		 */
		public function get_user_workflow_channel_options( $user ) {
			$workflows = $this->get_published_workflows();
			$options   = [];

			foreach ( $workflows as $workflow ) {
				/**
				 * Filters the options for the channel in the workflow
				 *
				 * @param array $options
				 * @param int   $user_id
				 * @param int   $workflow_id
				 *
				 * @return array
				 */
				$channels_options = apply_filters( 'psppno_filter_workflow_channel_options', [], $user->ID, $workflow->ID );
				$options[ $workflow->ID ] = $channels_options;
			}

			return $options;
		}

		/**
		 * Saves the data coming from the user profile
		 *
		 * @param int $user_id
		 */
		public function save_user_profile_fields( $user_id ) {
			if ( ! current_user_can( 'edit_user', $user_id ) ) {
				return false;
			}

			// Check the nonce field
			if ( ! (
				isset( $_POST['psppno_user_profile_nonce'] )
				&& wp_verify_nonce(
					$_POST['psppno_user_profile_nonce'],
					'psppno_user_profile'
				)
			) ) {
				return;
			}

			// Workflow Channels
			if ( isset( $_POST['psppno_workflow_channel'] ) && ! empty( $_POST['psppno_workflow_channel'] ) ) {
				foreach ( $_POST['psppno_workflow_channel'] as $workflow_id => $channel ) {
					update_user_meta( $user_id, 'psppno_workflow_channel_' . $workflow_id, $channel );
				}
			}

			do_action( 'psppno_save_user_profile', $user_id );
		}

		/**
		 * Add any necessary CSS to the WordPress admin
		 *
		 * @uses wp_enqueue_style()
		 */
		public function add_admin_styles() {
			wp_enqueue_style( 'psppno-admin-css', plugin_dir_url( __FILE__ ) . 'assets/css/admin.css');
			wp_enqueue_style( 'psppno-user-profile', plugin_dir_url( __FILE__ ) . 'assets/css/user_profile.css');
		}
	}
}// End if().
