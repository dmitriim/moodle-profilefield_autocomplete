<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace profilefield_autocomplete;

/**
 * Events observer.
 *
 * @package    profilefield_autocomplete
 * @author     Dmitrii Metelkin <dnmetelk@gmail.com>
 * @copyright  2024 Dmitrii Metelkin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class helper {

    /**
     * Update delimiter for user data.
     *
     * @param int $userid A specific user to update for. If empty will update for all users.W
     */
    public static function update_delimiter_in_user_data(int $userid = 0): void {
        global $DB, $CFG;

        // We would like to update only autocomplete fields with multiple values.
        $select = "datatype = 'autocomplete' AND param2 = '1'";
        $fields = $DB->get_records_select('user_info_field', $select);

        if ($fields) {
            require_once($CFG->dirroot . '/user/profile/lib.php');
            require_once($CFG->dirroot . '/user/profile/field/autocomplete/field.class.php');

            list($fieldssql, $params) = $DB->get_in_or_equal(array_keys($fields), SQL_PARAMS_NAMED);
            $params['search'] = $search = ', '; // Old broken delimiter.
            $params['replace'] = \profile_field_autocomplete::DELIMITER; // New delimiter.
            $params['searchparam'] = '%' . $DB->sql_like_escape($search) . '%';
            $searchsql = $DB->sql_like('data', ':searchparam');

            $usersql = '';
            if (!empty($userid)) {
                $params['userid'] = $userid;
                $usersql = "AND userid = :userid";
            }

            $sql = "UPDATE {user_info_data}
                       SET data = REPLACE(data, :search, :replace)
                     WHERE $searchsql AND fieldid $fieldssql $usersql";

            $DB->execute($sql, $params);
        }
    }
}
