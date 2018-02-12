/**
 * @package PublishPress
 * @author PublishPress
 *
 * Copyright (c) 2018 PublishPress
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
(function ($) {
    $(function () {
        function setupFieldFilters(name) {
            // "When the content is moved to a new status"'s filters
            if ($('#publishpress_notif_' + name).length > 0) {
                var $chkb = $('#publishpress_notif_' + name),
                    $filters = $('.publishpress_notif_' + name + '_filters');

                // Add event to the checkbox
                $chkb.on('change', function () {
                    if ($chkb.is(':checked')) {
                        $filters.show();
                    } else {
                        $filters.hide();
                    }
                });
            }
        }

        setupFieldFilters('event_post_save');
        setupFieldFilters('event_content_post_type');
        setupFieldFilters('event_content_category');
        setupFieldFilters('event_content_category');
        setupFieldFilters('user');
        setupFieldFilters('user_group');

        // List search
        // $('.publishpress-filter-checkbox-list ul').listFilterizer();
        $('.publishpress-filter-checkbox-list select').multipleSelect({
            filter: true
        });

        // Form validation

    });
})(jQuery);
