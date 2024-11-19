<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Upgrade hook.
 *
 * @package     profilefield_autocomplete
 * @author      Dmitrii Metelkin <dnmetelk@gmail.com>
 * @copyright   Dmitrii Metelkin <dnmetelk@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Upgrade the plugin.
 *
 * @param int $oldversion The old version of the plugin
 * @return bool
 */
function xmldb_profilefield_autocomplete_upgrade($oldversion): bool {
    global $DB, $CFG;

    $dbman = $DB->get_manager();

    if ($oldversion < 2022071901) {
        require_once($CFG->dirroot . '/user/profile/lib.php');
        require_once($CFG->dirroot . '/user/profile/field/autocomplete/field.class.php');

        // We would like to update only autocomplete fields with multiple values.
        $select = "datatype = 'autocomplete' AND param2 = '1'";
        $fields = $DB->get_records_select('user_info_field', $select);

        if ($fields) {
            list($fieldssql, $params) = $DB->get_in_or_equal(array_keys($fields), SQL_PARAMS_NAMED);
            $params['search'] = $search = ', '; // Old broken delimiter.
            $params['replace'] = profile_field_autocomplete::DELIMITER; // New delimiter.
            $params['searchparam'] = '%'.$DB->sql_like_escape($search).'%';

            $searchsql = $DB->sql_like('data', ':searchparam');

            $sql = "UPDATE {user_info_data}
                       SET data = REPLACE(data, :search, :replace)
                     WHERE $searchsql AND fieldid $fieldssql";

            $DB->execute($sql, $params);
        }

        upgrade_plugin_savepoint(true, 2022071901, 'profilefield', 'autocomplete');
    }

    return true;
}
