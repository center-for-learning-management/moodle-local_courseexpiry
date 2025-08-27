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

// File: common.php | Version: 2025082700 | Author: pro-web.at

// common namespace of current plugin
namespace local_courseexpiry\common;

class db {
    /**
     * @param string $table
     * @param array|object $data
     * @param array|null $where
     * @return bool
     * @throws \moodle_exception
     */
    public static function update_record(string $table, array|object $data, array $where = null): bool {
        global $DB;

        if ($where === null) {
            return $DB->update_record($table, $data);
        }

        $where = (array)$where;
        $data = (array)$data;

        if ($dbItem = $DB->get_record($table, $where)) {
            if ($data) {
                $data['id'] = $dbItem->id;
                $DB->update_record($table, (object)$data);
            }

            return true;
            // return (object)($data + (array)$dbItem);
        }

        return false;
    }

    /**
     * @param string $table
     * @param array|object $data
     * @param array|object|null $where
     * @return object
     * @throws \moodle_exception
     */
    public static function insert_or_update_record(string $table, array|object $data, array|object $where = null): object {
        global $DB;

        $original_data = $data;
        $where = $where === null ? $where : (array)$where;

        $get_data = function(bool $exists) use ($original_data): array {
            // also allow to pass a callback for $data
            $data = is_callable($original_data) ? $original_data($exists) : $original_data;

            if (!is_object($data) && !is_array($data)) {
                throw new \moodle_exception('unsupported type for $data: ' . gettype($data));
            }

            return (array)$data;
        };

        $check_where = $where;
        if (!$check_where) {
            if (is_callable($data)) {
                throw new \moodle_exception('if $data is a callback, a $where has to be provided');
            }
            $check_where = (array)$data;
        }
        if (!$check_where) {
            throw new \moodle_exception('$where is empty');
        }
        if ($dbItem = $DB->get_record($table, $check_where)) {
            $data = $get_data(true);
            if (!$data) {
                throw new \moodle_exception('$data is empty');
            }

            $data['id'] = $dbItem->id;
            $DB->update_record($table, (object)$data);

            return (object)($data + (array)$dbItem);
        } else {
            $data = $get_data(false);
            unset($data['id']);
            if ($where) {
                $data = $data + $where; // first the values of $data, then of $where, but don't override $data
            }
            $id = $DB->insert_record($table, (object)$data);
            $data['id'] = $id;

            return (object)$data;
        }
    }

    public static function get_column_names(string $table): array {
        global $DB;

        return array_keys($DB->get_columns($table));
    }

    public static function get_column_names_prefixed(string $table, string $prefix): array {
        $prefix = trim($prefix, '.');

        $columns = static::get_column_names($table);
        $columns = array_map(function($column) use ($prefix) {
            return $prefix . '.' . $column;
        }, $columns);

        return join(', ', $columns);
    }

    public static function find_in_set($needle, $haystack) {
        global $DB;

        // Determine the database family (mysql, pgsql, etc.)
        $dbfamily = $DB->get_dbfamily();

        // MySQL version
        if ($dbfamily === 'mysql') {
            return "FIND_IN_SET($needle, $haystack)";
        } // PostgreSQL version
        elseif ($dbfamily === 'postgres') {
            // PostgreSQL doesn't have FIND_IN_SET, use string matching.
            return "$needle = ANY (string_to_array($haystack, ','))";
        }

        // Default case (just in case we want to extend it to other databases)
        throw new \coding_exception('Unsupported database type for find_in_set');
    }
}

abstract class event extends \core\event\base {

    protected static function prepareData(array &$data): void {
        if (!isset($data['contextid'])) {
            if (!empty($data['courseid'])) {
                $data['contextid'] = \context_course::instance($data['courseid'])->id;
            } else {
                $data['contextid'] = \context_system::instance()->id;
            }
        }
    }

    static function log(array $data): void {
        static::prepareData($data);

        static::create($data)->trigger();
    }
}

class lang {
    /**
     * get a language string from current plugin or else from global language strings
     *
     * @param string $identifier The identifier of the string to search for
     * @param string $component The module the string is associated with
     * @param string|object|array $a An object, string or number that can be used
     *      within translation strings
     * @param string $lang moodle translation language, null means use current
     * @return string The String !
     */
    static function get_string($identifier, $component = null, $a = null) {
        $manager = get_string_manager();

        if ($component === null) {
            $component = helper::plugin_name();
        }

        if ($manager->string_exists($identifier, $component)) {
            return $manager->get_string($identifier, $component, $a);
        }

        return $manager->get_string($identifier, '', $a);
    }

    /**
     * translator function
     */
    static function trans($string_or_strings, $arg_or_args = null) {

        $origArgs = $args = func_get_args();

        $identifier = '';
        $a = null;

        if (empty($args)) {
            throw new \moodle_exception('no args');
        }

        $arg = array_shift($args);
        if (is_string($arg) && !static::_check_identifier($arg)) {
            $identifier = $arg;
            $arg = array_shift($args);
        }

        if ($arg === null) {
            // just id submitted
            $languagestrings = [];
        } else if (is_array($arg)) {
            $languagestrings = $arg;
        } else if (is_string($arg) && $matches = static::_check_identifier($arg)) {
            $languagestrings = [$matches[1] => $matches[2]];
        } else {
            throw new \moodle_exception('wrong args: ' . print_r($origArgs, true));
        }

        if ($args) {
            $a = array_shift($args);
        }

        if ($args) {
            throw new \moodle_exception('too many arguments: ' . print_r($origArgs, true));
        }

        // parse $languagestrings
        foreach ($languagestrings as $key => $string) {
            if (is_number($key)) {
                if ($matches = static::_check_identifier($string)) {
                    $languagestrings[$matches[1]] = $matches[2];
                    unset($languagestrings[$key]);
                } else {
                    throw new \moodle_exception('wrong language string: ' . $origArgs);
                }
            }
        }

        $lang = current_language();

        $manager = get_string_manager();
        $component = helper::plugin_name();

        // try with $identifier from args
        if ($identifier && $manager->string_exists($identifier, $component)) {
            return $manager->get_string($identifier, $component, $a);
        }

        // try submitted language strings
        if (isset($languagestrings[$lang])) {
            return static::_parse_string($languagestrings[$lang], $a);
        }

        // try language string
        $identifier = reset($languagestrings);
        $identifier = key($languagestrings) . ':' . $identifier;
        if ($manager->string_exists($identifier, $component)) {
            return $manager->get_string($identifier, $component, $a);
        }

        if ($languagestrings) {
            return static::_parse_string(reset($languagestrings), $a);
        } else {
            throw new \moodle_exception("language string '{$origArgs[0]}' not found, did you forget to prefix a language? 'en:{$origArgs[0]}'");
        }
    }

    static protected function _check_identifier($string) {
        if (preg_match('!^([^:]+):(.*)$!s', $string, $matches)) {
            return $matches;
        } else {
            return null;
        }
    }

    static protected function _parse_string($string, $a) {
        // copy from moodle/lib/classes/string_manager_standard.php
        // Process array's and objects (except lang_strings).
        if (is_array($a) or (is_object($a) && !($a instanceof \lang_string))) {
            $a = (array)$a;
            $search = array();
            $replace = array();
            foreach ($a as $key => $value) {
                if (is_int($key)) {
                    // We do not support numeric keys - sorry!
                    continue;
                }
                if (is_array($value) or (is_object($value) && !($value instanceof \lang_string))) {
                    // We support just string or lang_string as value.
                    continue;
                }
                $search[] = '{$a->' . $key . '}';
                $replace[] = (string)$value;
            }
            if ($search) {
                $string = str_replace($search, $replace, $string);
            }
        } else {
            $string = str_replace('{$a}', (string)$a, $string);
        }

        return $string;
    }
}

class moodle_exception extends \moodle_exception {
    function __construct($errorcode, $module = '', $link = '', $a = null, $debuginfo = null) {

        // try to get local error message (use namespace as $component)
        if (empty($module)) {
            if (get_string_manager()->string_exists($errorcode, helper::plugin_name())) {
                $module = helper::plugin_name();
            }
        }

        return parent::__construct($errorcode, $module, $link, $a, $debuginfo);
    }
}

class param {
    public static function clean_object($values, $definition) {
        if (!is_object($values) && !is_array($values)) {
            return null;
        }

        // some value => type
        $ret = new \stdClass;
        $values = (object)$values;
        $definition = (array)$definition;

        foreach ($definition as $key => $valueType) {
            $value = isset($values->$key) ? $values->$key : null;

            $ret->$key = static::_clean($value, $valueType);
        }

        return $ret;
    }

    public static function clean_array($values, $definition) {
        $definition = (array)$definition;

        if (is_object($values)) {
            $values = (array)$values;
        } else if (!is_array($values)) {
            return array();
        }

        $keyType = key($definition);
        $valueType = reset($definition);

        // allow clean_array(PARAM_TEXT): which means PARAM_INT=>PARAM_TEXT
        if ($keyType === 0) {
            $keyType = PARAM_SEQUENCE;
        }

        if ($keyType !== PARAM_INT && $keyType !== PARAM_TEXT && $keyType !== PARAM_SEQUENCE) {
            throw new moodle_exception('wrong key type: ' . $keyType);
        }

        $ret = array();
        foreach ($values as $key => $value) {
            $value = static::_clean($value, $valueType);
            if ($value === null) {
                continue;
            }

            if ($keyType == PARAM_SEQUENCE) {
                $ret[] = $value;
            } else {
                $ret[clean_param($key, $keyType)] = $value;
            }
        }

        return $ret;
    }

    protected static function _clean($value, $definition) {
        if (is_object($definition)) {
            return static::clean_object($value, $definition);
        } else if (is_array($definition)) {
            return static::clean_array($value, $definition);
        } else {
            return clean_param($value, $definition);
        }
    }

    public static function get_param($parname, $definition = null) {
        // POST has precedence.
        if (isset($_POST[$parname])) {
            if (is_array($_POST[$parname])) {
                return static::clean_array($_POST[$parname], $definition);
            } else {
                return clean_param($_POST[$parname], PARAM_TEXT);
            }
        } else if (isset($_GET[$parname])) {
            if (is_array($_GET[$parname])) {
                return static::clean_array($_GET[$parname], $definition);
            } else {
                return clean_param($_GET[$parname], PARAM_TEXT);
            }
        } else {
            return null;
        }
    }

    public static function get_required_param($parname) {
        $param = static::get_param($parname);

        if ($param === null) {
            throw new moodle_exception('param not found: ' . $parname);
        }

        return $param;
    }

    public static function optional_array($parname, $definition) {
        $param = static::get_param($parname, $definition);
        if ($param === null) {
            return array();
        } else {
            return $param;
        }
    }

    public static function required_array($parname, $definition) {
        $param = static::get_required_param($parname);

        if (!is_array($param)) {
            throw new moodle_exception("required parameter '$parname' is not an array");
        }

        return static::clean_array($param, $definition);
    }

    public static function optional_object($parname, $definition) {
        $param = static::get_param($parname);

        if ($param === null) {
            return null;
        } else {
            return static::clean_object($param, $definition);
        }
    }

    public static function required_object($parname, $definition) {
        $param = static::get_required_param($parname);

        if (!is_array($param)) {
            throw new moodle_exception("required parameter '$parname' is not an array an can not converted to object");
        }

        return static::clean_object($param, $definition);
    }

    public static function required_json($parname, $definition = null) {
        $data = required_param($parname, PARAM_RAW);

        $data = json_decode($data, true);
        if ($data === null) {
            throw new moodle_exception('missingparam', '', '', $parname);
        }

        if ($definition === null) {
            return $data;
        } else {
            return static::_clean($data, $definition);
        }
    }
}

class SimpleXMLElement extends \SimpleXMLElement {
    /**
     * Adds a child with $value inside CDATA
     *
     * @param string $name
     * @param mixed $value
     * @return SimpleXMLElement
     */
    public function addChildWithCDATA($name, $value = null) {
        $new_child = $this->addChild($name);

        if ($new_child !== null) {
            $node = dom_import_simplexml($new_child);
            $no = $node->ownerDocument;
            $node->appendChild($no->createCDATASection($value));
        }

        return $new_child;
    }

    public static function create($rootElement) {
        return new static('<?xml version="1.0" encoding="UTF-8"?><' . $rootElement . ' />');
    }

    public function addChildWithCDATAIfValue($name, $value = null) {
        if ($value) {
            return $this->addChildWithCDATA($name, $value);
        } else {
            return $this->addChild($name, $value);
        }
    }

    /**
     * Info: don't add parameter types in the overridden addChild-Method, this ensures compatibility with php7.4!
     * @param \SimpleXMLElement|string $name
     * @param string|null $value
     * @param string|null $namespace
     * @return \SimpleXMLElement|null
     */
    public function addChild($name, $value = null, $namespace = null): ?\SimpleXMLElement {
        if ($name instanceof \SimpleXMLElement) {
            $newNode = $name;
            $node = dom_import_simplexml($this);
            $newNode = $node->ownerDocument->importNode(dom_import_simplexml($newNode), true);
            $node->appendChild($newNode);

            // return last child, this is the added child!
            $children = $this->children();

            return $children[$children->count() - 1];
        } else {
            return parent::addChild($name, $value, $namespace);
        }
    }

    public function asPrettyXML() {
        $dom = dom_import_simplexml($this)->ownerDocument;
        $dom->formatOutput = true;

        return $dom->saveXML();
    }
}

class url extends \moodle_url {
    public static function create($url, array $params = null, $anchor = null) {
        return new static($url, $params, $anchor);
    }

    /**
     *
     * @param array $overrideparams new attributes for object
     * @return self
     */
    public function copy(array $overrideparams = null) {
        $object = new static($this);
        if ($overrideparams) {
            $object->params($overrideparams);
        }

        return $object;
    }

    protected function merge_overrideparams(array $overrideparams = null) {
        $params = parent::merge_overrideparams($overrideparams);

        $overrideparams = (array)$overrideparams;
        foreach ($overrideparams as $key => $value) {
            if ($value === null) {
                unset($params[$key]);
            }
        }

        return $params;
    }

    public function params(array $params = null) {
        parent::params($params);

        $params = (array)$params;
        foreach ($params as $key => $value) {
            if ($value === null) {
                unset($this->params[$key]);
            }
        }

        return $this->params;
    }

    static function request_uri() {
        global $CFG;

        return new static(preg_replace('!^' . preg_quote(parse_url($CFG->wwwroot)['path'], '!') . '!', '', $_SERVER['REQUEST_URI']));
    }
}

class helper {
    static function plugin_name() {
        return preg_replace('!\\\\.*$!', '', __NAMESPACE__); // The \\\\ syntax matches a \ (backslash)!
    }
}

class cachelib {
    private static $caches = [];
    private static ?bool $cacheDisabled = null;

    private static function area(string $area) {
        if (!empty(static::$caches[$area])) {
            // already created?
            return static::$caches[$area];
        }

        $cache = \cache::make(helper::plugin_name(), $area);
        static::$caches[$area] = $cache;

        return $cache;
    }

    /**
     * Getter for caches.
     * @param string $area either application or session.
     * @param mixed $key used to get a certain key from the cache.
     * @param ?int $ttl null forttl, > 0 for a specific ttl in seconds
     * @return mixed the cached value, or null if no cache! (this differs to moodle, which returns false on cache on no cache
     */
    public static function get(string $area, mixed $key, ?int $ttl = null) {
        if (!($ttl === null || $ttl > 0)) {
            throw new \moodle_exception('wrong ttl');
        }

        $data = static::getInternal($area, $key);

        if (!$data) {
            return null;
        } elseif ($ttl && $data->timestamp <= time() - $ttl) {
            // check ttl
            return null;
        } else {
            return $data->value;
        }
    }

    private static function getInternal(string $area, mixed $key) {
        if (static::$cacheDisabled === null) {
            static::$cacheDisabled = $_SESSION['eduportalcachedisabled'] ?? false;
        }
        if (static::$cacheDisabled) {
            return null;
        }

        $key = static::stringKey($key);
        $ret = static::area($area)->get($key);

        if (!$ret) {
            return null;
        } else {
            return $ret;
        }
    }

    /**
     * Method used as setter for caches.
     * @param string $area either application or session.
     * @param mixed $key used to get a certain key from the cache.
     * @param mixed $value used to set a value for a certain key in the cache.
     */
    public static function set(string $area, mixed $key, mixed $value) {
        $key = static::stringKey($key);

        static::area($area)->set($key, (object)[
            'timestamp' => time(),
            'value' => $value,
        ]);
    }

    /**
     * This disables or enables the caching.
     * Used by local_bipidp to disable cache while syncing. This prevents errors,
     * as new items may not be cached and hence are created repeatedly.
     * @param bool $enabled by default true.
     */
    public static function set_enabled(bool $enabled = true) {
        static::$cacheDisabled = !$enabled;
    }

    /**
     * Delete cache entry
     * @param string $area either application or session.
     * @param mixed $key used to get a certain key from the cache.
     */
    public static function delete(string $area, mixed $key) {
        $key = static::stringKey($key);
        static::area($area)->delete($key);
    }

    private static function stringKey($key): string {
        if (!is_scalar($key)) {
            return json_encode($key);
            // return join('-', $key);
        } else {
            return $key;
        }
    }

    public static function purge(string $area) {
        static::area($area)->purge();
    }

    public static function callbackWithBypassCache(string $area, mixed $key, bool $bypasscache, callable $callback, ?int $ttl = null) {
        if (!$bypasscache) {
            $ret = static::get($area, $key, $ttl);
            if ($ret !== null) {
                return $ret;
            }
        }

        $ret = $callback();

        if ($ret !== null) {
            static::set($area, $key, $ret);
        }

        return $ret;
    }

    public static function callback(string $area, mixed $key, callable $callback, ?int $ttl = null) {
        return static::callbackWithBypassCache($area, $key, false, $callback, $ttl);
    }
}

class permissions {
    /**
     * require_login(), but additionally no guests are allowed
     * @return void
     */
    public static function require_login(int|null $courseorid = null, bool $allow_guests = false): void {
        global $SESSION;

        require_login($courseorid);

        if (!$allow_guests && isguestuser()) {
            // don't allow guest login
            $SESSION->wantsurl = qualified_me();
            redirect(get_login_url());
        }
    }

    static function isloggedin() {
        return isloggedin() && !isguestuser();
    }

    static function require_all(array $checks): void {
        foreach ($checks as $key => $check) {
            if (!$check) {
                throw new \moodle_exception('access_denied', debuginfo: 'check: ' . $key);
            }
        }
    }

    static function require_any(array $checks): void {
        if (!static::has_any($checks)) {
            $key = '((' . implode(' || ', array_keys($checks)) . ') = false)';
            throw new \moodle_exception('access_denied', debuginfo: 'check: ' . $key);
        }
    }

    static function has_all(array $checks): bool {
        foreach ($checks as $check) {
            if (!$check) {
                return false;
            }
        }

        return true;
    }

    static function has_any(array $checks): bool {
        foreach ($checks as $check) {
            if ($check) {
                return true;
            }
        }

        return false;
    }
}
