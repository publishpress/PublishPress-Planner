/**
 * @package PublishPress
 * @author PublishPress
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

jQuery(document).ready(function ($) {
  function getOptions (self, custom_options) {
    var default_options = {
      dateFormat: objectL10ndate.date_format,
      firstDay: pp_week_first_day
    };

    var options = $.extend({}, default_options, custom_options);
    var altFieldName = self.attr('data-alt-field');

    if ((!altFieldName) || typeof altFieldName == 'undefined' || altFieldName.length == 0) {
      return options;
    }

    return $.extend({}, options, {
      altField: 'input[name="'+ altFieldName +'"]',
      altFormat: self.attr('data-alt-format'),
    });
  }

  $('.date-time-pick').each(function () {
    var self = $(this);
    var options = getOptions(self, {
      alwaysSetTime: false,
      controlType: 'select',
      altFieldTimeOnly: false
    });
    self.datetimepicker(options);
  });

  $('.date-pick').each(function () {
    var self = $(this);
    var options = getOptions(self, {});
    self.datepicker(options);
  });
});
