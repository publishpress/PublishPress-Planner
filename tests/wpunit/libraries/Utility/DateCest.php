<?php
namespace libraries\Utility;

use Codeception\Example;
use PublishPress\Utility\Date;
use WpunitTester;

class DateCest
{
    public function _before(WpunitTester $I)
    {
    }

    ####################################################################################################################
    # getTimezoneOffset
    ####################################################################################################################
    /**
     * @example {"offset":"0", "expected": "0"}
     * @example {"offset":"+3.5", "expected": "3.5"}
     * @example {"offset":"+10", "expected": "10"}
     * @example {"offset":"+11.5", "expected": "11.5"}
     * @example {"offset":"-2", "expected": "-2"}
     * @example {"offset":"-4.5", "expected": "-4.5"}
     * @example {"offset":"-10", "expected": "-10"}
     * @example {"offset":"-10.5", "expected": "-10.5"}
     * @example {"offset":"", "expected": "0"}
     */
    public function getWpTimezoneOffset(WpunitTester $I, Example $example)
    {
        update_option('gmt_offset', $example['offset']);

        $util = new Date();
        $offset = $util->getTimezoneOffset();

        $I->assertEquals($example['expected'], $offset);
    }

    ####################################################################################################################
    # formatTimezoneOffset
    ####################################################################################################################
    /**
     * @example {"offset":"0", "expected": "+0000"}
     * @example {"offset":"3", "expected": "+0300"}
     * @example {"offset":"+3", "expected": "+0300"}
     * @example {"offset":"10", "expected": "+1000"}
     * @example {"offset":"+10", "expected": "+1000"}
     * @example {"offset":"3.5", "expected": "+0330"}
     * @example {"offset":"+3.5", "expected": "+0330"}
     * @example {"offset":"11.5", "expected": "+1130"}
     * @example {"offset":"+11.5", "expected": "+1130"}
     * @example {"offset":"-3", "expected": "-0300"}
     * @example {"offset":"-3", "expected": "-0300"}
     * @example {"offset":"-10", "expected": "-1000"}
     * @example {"offset":"-10", "expected": "-1000"}
     * @example {"offset":"-3.5", "expected": "-0330"}
     * @example {"offset":"-3.5", "expected": "-0330"}
     * @example {"offset":"-11.5", "expected": "-1130"}
     * @example {"offset":"-11.5", "expected": "-1130"}
     */
    public function formatTimezoneOffset(WpunitTester $I, Example $example)
    {
        $util = new Date();
        $offset = $util->formatTimezoneOffset($example['offset']);

        $I->assertEquals($example['expected'], $offset);
    }

    ####################################################################################################################
    # getTimezoneOffset
    ####################################################################################################################
    /**
     * @example {"timezone_string": "Pacific/Pohnpei", "gmt_offset": "", "expected": "Pacific/Pohnpei"}
     * @example {"timezone_string": "Atlantic/Cape_Verde", "gmt_offset": "", "expected": "Atlantic/Cape_Verde"}
     * @example {"timezone_string": "UTC", "gmt_offset": "", "expected": "UTC"}
     * @example {"timezone_string": "", "gmt_offset": "0", "expected": "+00:00"}
     * @example {"timezone_string": "", "gmt_offset": "1", "expected": "+01:00"}
     * @example {"timezone_string": "", "gmt_offset": "12", "expected": "+12:00"}
     * @example {"timezone_string": "", "gmt_offset": "3.5", "expected": "+03:30"}
     * @example {"timezone_string": "", "gmt_offset": "-3", "expected": "-03:00"}
     * @example {"timezone_string": "", "gmt_offset": "-3.5", "expected": "-03:30"}
     * @example {"timezone_string": "", "gmt_offset": "-10", "expected": "-10:00"}
     * @example {"timezone_string": "", "gmt_offset": "-10.5", "expected": "-10:30"}
     */
    public function getTimezoneString(WpunitTester $I, Example $example)
    {
        update_option('timezone_string', $example['timezone_string']);
        update_option('gmt_offset', $example['gmt_offset']);

        $util = new Date();
        $timezoneString = $util->getTimezoneString();

        $I->assertEquals($example['expected'], $timezoneString);
    }
}
