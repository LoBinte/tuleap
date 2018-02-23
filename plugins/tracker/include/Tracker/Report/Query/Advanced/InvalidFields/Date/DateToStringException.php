<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Tuleap\Tracker\Report\Query\Advanced\InvalidFields\Date;

class DateToStringException extends \Exception
{
    private $submitted_value;

    /**
     * @param string $submitted_value
     */
    public function __construct($submitted_value)
    {
        parent::__construct();

        $this->submitted_value = $submitted_value;
    }

    /**
     * @return string
     */
    public function getSubmittedValue()
    {
        return $this->submitted_value;
    }
}
