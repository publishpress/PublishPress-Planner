<?php
/**
 * @package PublishPress
 * @author  PublishPress
 *
 * Copyright (c) 2018 PublishPress
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

if (!class_exists('PP_Roles_List_Table')) {
    /**
     * Roles uses WordPress' List Table API for generating the Role management table
     *
     * @since 0.7
     */
    class PP_Roles_List_Table extends WP_List_Table
    {
        protected $twig;

        public $callback_args;

        public function __construct($twig)
        {
            parent::__construct(
                [
                    'plural'   => 'roles',
                    'singular' => 'role',
                    'ajax'     => true,
                ]
            );

            $this->twig = $twig;
        }

        /**
         * @todo  Paginate if we have a lot of roles
         *
         * @since 0.7
         */
        public function prepare_items()
        {
            $columns  = $this->get_columns();
            $hidden   = [];
            $sortable = [];

            $this->_column_headers = [$columns, $hidden, $sortable];

            $roles = get_editable_roles();

            $this->items = [];

            foreach ($roles as $role => $data) {
                $total = count(
                    get_users(
                        [
                            'role' => $role,
                        ]
                    )
                );

                $this->items[] = (object)[
                    'name'         => $role,
                    'display_name' => $data['name'],
                    'capabilities' => $data['capabilities'],
                    'users_count'  => $total,
                ];
            }

            $this->set_pagination_args(
                [
                    'total_items' => count($this->items),
                    'per_page'    => count($this->items),
                ]
            );
        }

        /**
         * Message to be displayed when there are no roles
         *
         * @since 0.7
         */
        public function no_items()
        {
            _e('No roles found.', 'publishpress');
        }

        /**
         * Columns in our Roles table
         *
         * @since 0.7
         */
        public function get_columns()
        {
            $columns = [
                'display_name' => __('Display Name', 'publishpress'),
                'users'        => __('Users in this role', 'publishpress'),
            ];

            return $columns;
        }

        /**
         * Process the Role column value for all methods that aren't registered
         *
         * @since 0.7
         */
        public function column_default($role, $column_name)
        {
        }

        /**
         * Handle the 'description' column for the table of Roles
         * Don't need to unencode this because we already did when the role was loaded
         *
         * @since 0.7
         */
        public function column_display_name($role)
        {
            global $publishpress;

            $actions                   = [];
            $actions['edit edit-role'] = sprintf(
                '<a href="%1$s">' . __('Edit', 'publishpress') . '</a>',
                esc_url($publishpress->roles->getLink(['action' => 'edit-role', 'role-id' => $role->name]))
            );

            if ('administrator' !== $role->name) {
                $actions['delete delete-role'] = sprintf(
                    '<a href="%1$s">' . __('Delete', 'publishpress') . '</a>',
                    esc_url($publishpress->roles->getLink(['action' => 'delete-role', 'role-id' => $role->name]))
                );
            }

            return $this->twig->render(
                'roles-list-table-column-name.twig.html',
                [
                    'actions' => $this->row_actions($actions, false),
                    'role'    => $role,
                    'link'    => $publishpress->roles->getLink(['action' => 'edit-role', 'role-id' => $role->name]),
                ]
            );
        }

        /**
         * Show the "Total Users" in a given role
         *
         * @since 0.7
         */
        public function column_users($role)
        {
            return $this->twig->render(
                'roles-list-table-column-users.twig.html',
                [
                    'role' => $role,
                    'link' => '/wp-admin/users.php?role=' . $role->name,
                ]
            );
        }

        /**
         * Prepare a single row of information about a role
         *
         * @since 0.7
         */
        public function single_row($role)
        {
            static $row_class = '';
            $row_class = ($row_class == '' ? ' class="alternate"' : '');

            echo '<tr id="role-' . esc_attr($role->name) . '"' . $row_class . '>';
            echo $this->single_row_columns($role);
            echo '</tr>';
        }
    }
}
