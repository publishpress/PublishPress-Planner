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
        $('.publishpress-filter-checkbox-list select').multipleSelect({
            filter: true
        });

        // Form validation
        $('form#post').on('submit', function (event) {
            var selected,
                sections = ['event', 'event_content'];

            /**
             * Set the validation status to the given section.
             *
             * @param section
             * @param status
             */
            function set_validation_status(section, status) {
                var selector = '#psppno-workflow-metabox-section-' + section + ' .psppno_workflow_metabox_section_header';

                if (status) {
                    $(selector).removeClass('invalid');
                } else {
                    $(selector).addClass('invalid');
                }
            }

            function set_tooltip(section) {
                var selector = '#psppno-workflow-metabox-section-' + section + ' .psppno_workflow_metabox_section_header';

                $(selector).tooltip();
            }

            // Check the Event and Event Content sections
            $.each(sections, function (index, section) {
                // Check if the "When" and "Which content" filter has at least one selected option.
                selected = $('[name="publishpress_notif[' + section + '][]"]:checked').length;

                if (selected === 0) {
                    set_validation_status(section, false);
                } else {
                    set_validation_status(section, true);
                }
            });

            // Check the Receivers section
            if ($('#psppno-workflow-metabox-section-receiver input[type="checkbox"][name^="publishpress_notif"]:checked').length === 0) {
                set_validation_status('receiver', false);
            } else {
                set_validation_status('receiver', true);
            }

            // Check the Content section
            if ($('input[name="publishpress_notif[content_main][subject]"]').val().trim() === '') {
                set_validation_status('content', false);
            } else {
                set_validation_status('content', true);
            }

            if (tinymce.activeEditor.getContent().trim() === '') {
                set_validation_status('content', false);
            } else {
                set_validation_status('content', true);
            }

            return $('form#post .invalid').length === 0;
        });
    });
})(jQuery);
