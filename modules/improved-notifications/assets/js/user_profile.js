/**
 * @package PublishPress
 * @author PublishPress
 *
 * Copyright (c) 2022 PublishPress
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

        show_workflow_channel_options();

        // Add the event listener to the channel list
        $('.psppno_workflow_channel input[type="radio"]').change(function (event) {
            show_workflow_channel_options();
        });

        function hide_all_channel_options() {
            $('.psppno_workflow_channel_options > div').hide();
        }

        function show_workflow_channel_options() {
            hide_all_channel_options();

            // Show the channel options for the selected channels
            $('.psppno_workflow_channel input[name^=psppno_workflow_channel]:checked').each(function () {
                var $elem = $(this),
                    workflow_id = $elem.data('workflow-id'),
                    channel = $elem.val();

                $('.psppno_workflow_' + workflow_id + '  .psppno_workflow_' + channel + '_options').show();
            });
        }
    });
})(jQuery);
