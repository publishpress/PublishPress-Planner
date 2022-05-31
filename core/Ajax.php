<?php
/**
 * @package PublishPress
 * @author  PublishPress
 *
 * Copyright (c) 2022 PublishPress
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

namespace PublishPress\Core;

/**
 * @package PublishPress\Core
 */
class Ajax
{
    const HTTP_STATUS_UNAUTHORIZED = 401;

    const HTTP_STATUS_FORBIDDEN = 403;

    /**
     * @var Ajax
     */
    private static $instance;

    /**
     * @var Error
     */
    private $errorDefinitions;

    /**
     * @var array
     */
    private $statusByErrorCodes;

    public function __construct()
    {
        $this->errorDefinitions = Error::getInstance();
        $this->setStatusByErrorCodes();
    }

    public static function getInstance()
    {
        if (! isset(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * @param string $errorCode
     * @param int $statusCode
     * @param int $options
     * @return void
     */
    public function sendJsonError($errorCode, $statusCode = null, $options = 0)
    {
        if (empty($statusCode) && isset($this->statusByErrorCodes[$errorCode])) {
            $statusCode = $this->statusByErrorCodes[$errorCode];
        }

        wp_send_json_error($this->errorDefinitions->getWpError($errorCode), $statusCode, $options);
    }

    /**
     * @param mixed $response
     * @param int $statusCode
     * @param int $options
     * @return void
     */
    public function sendJson($response, $statusCode = null, $options = 0)
    {
        wp_send_json($response, $statusCode, $options);
    }

    /**
     * @param mixed $response
     * @param int $statusCode
     * @param int $options
     * @return void
     */
    public function sendJsonSuccess($response, $statusCode = null, $options = 0)
    {
        wp_send_json_success($response, $statusCode, $options);
    }

    /**
     * @return void
     */
    private function setStatusByErrorCodes()
    {
        $this->statusByErrorCodes = [
            Error::ERROR_CODE_INVALID_NONCE => 401,
            Error::ERROR_CODE_ACCESS_DENIED => 403,
        ];
    }
}
