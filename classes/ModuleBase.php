<?php

/**
 * ModuleBase
 *
 * This is the parent class for all module types (Events, Blog, Gallery, etc.)
 * It handles:
 *  - Loading settings
 *  - Saving settings
 *  - Default settings (child classes override)
 *  - Creating module objects
 *  - Deleting module objects
 *  - CRUD method stubs for child modules
 *  - Validation + sanitization helpers
 */

class ModuleBase
{
    protected $db;
    protected $object_id;
    protected $module_type;
    protected $settings = [];

    public function __construct($db, $module_type, $object_id = null)
    {
        $this->db          = $db;
        $this->module_type = $module_type;
        $this->object_id   = $object_id;

        if ($object_id) {
            $this->loadSettings();
        } else {
            $this->settings = $this->getDefaultSettings();
        }
    }

    /* -----------------------------------------------------------
     * SETTINGS
     * ----------------------------------------------------------- */

    // Child modules override this
    public function getDefaultSettings()
    {
        return [];
    }

    protected function loadSettings()
    {
        $stmt = $this->db->prepare("
            SELECT setting_key, setting_value
            FROM zentra_object_settings
            WHERE object_id = ?
        ");
        $stmt->execute([$this->object_id]);

        $settings = $this->getDefaultSettings();

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }

        $this->settings = $settings;
    }

    public function getSetting($key, $default = null)
    {
        return $this->settings[$key] ?? $default;
    }

    public function saveSetting($key, $value)
    {
        $stmt = $this->db->prepare("
            INSERT INTO zentra_object_settings (object_id, setting_key, setting_value)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
        ");

        $stmt->execute([$this->object_id, $key, $value]);

        $this->settings[$key] = $value;
    }

    public function saveSettings($settings)
    {
        foreach ($settings as $key => $value) {
            $this->saveSetting($key, $value);
        }
    }

    /* -----------------------------------------------------------
     * OBJECT MANAGEMENT
     * ----------------------------------------------------------- */

    public function createObject($site_id, $title)
    {
        $stmt = $this->db->prepare("
            INSERT INTO zentra_objects (site_id, module_type, title, created_at)
            VALUES (?, ?, ?, NOW())
        ");

        $stmt->execute([$site_id, $this->module_type, $title]);

        $this->object_id = $this->db->lastInsertId();

        // Save default settings
        $this->saveSettings($this->getDefaultSettings());

        return $this->object_id;
    }

    public function deleteObject()
    {
        if (! $this->object_id) {
            return;
        }

        $this->db->prepare("DELETE FROM zentra_object_settings WHERE object_id = ?")
            ->execute([$this->object_id]);

        $this->db->prepare("DELETE FROM zentra_objects WHERE id = ?")
            ->execute([$this->object_id]);
    }

    /* -----------------------------------------------------------
     * CRUD — Child modules override these
     * ----------------------------------------------------------- */

    public function create($data)
    {
        throw new Exception("create() not implemented for {$this->module_type}");
    }

    public function update($id, $data)
    {
        throw new Exception("update() not implemented for {$this->module_type}");
    }

    public function delete($id)
    {
        throw new Exception("delete() not implemented for {$this->module_type}");
    }

    public function get($id)
    {
        throw new Exception("get() not implemented for {$this->module_type}");
    }

    public function list($filters = [])
    {
        throw new Exception("list() not implemented for {$this->module_type}");
    }

    /* -----------------------------------------------------------
     * HELPERS
     * ----------------------------------------------------------- */

    protected function sanitize($value)
    {
        return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
    }

    protected function validateRequired($data, $fields)
    {
        foreach ($fields as $field) {
            if (! isset($data[$field]) || $data[$field] === '') {
                throw new Exception("Missing required field: $field");
            }
        }
    }
    protected function toUTC($datetime, $userTz)
    {
        if (! $datetime) {
            return null;
        }

        $dt = new DateTime($datetime, new DateTimeZone($userTz));
        $dt->setTimezone(new DateTimeZone('UTC'));
        return $dt->format('Y-m-d H:i:s');
    }

    protected function fromUTC($datetime, $userTz)
    {
        if (! $datetime) {
            return null;
        }

        $dt = new DateTime($datetime, new DateTimeZone('UTC'));
        $dt->setTimezone(new DateTimeZone($userTz));
        return $dt->format('Y-m-d H:i:s');
    }
    protected $logger;

    public function setLogger($logger)
    {
        $this->logger = $logger;
    }

}
