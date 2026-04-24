<?php

/**
 * Base class for all Zentra CMS modules.
 *
 * Each module (Events, Blog, Gallery, etc.) extends this class.
 * This class defines the standard CRUD signatures that all modules
 * must follow, including user-aware and context-aware operations.
 */

class ModuleBase
{
    protected $db;
    protected $object_id;
    protected $module_type;
    protected $settings = [];
    protected $logger; // <-- logger support

    public function __construct($db, string $module_type, ?int $object_id = null)
    {
        $this->db          = $db;
        $this->module_type = $module_type;
        $this->object_id   = $object_id;

        $this->loadSettings();
    }

    /* -----------------------------------------------------------
     * SETTINGS
     * ----------------------------------------------------------- */
    protected function loadSettings()
    {
        if (! $this->object_id) {
            return;
        }

        $stmt = $this->db->prepare("
            SELECT setting_key, setting_value
            FROM zentra_object_settings
            WHERE object_id = ?
        ");
        $stmt->execute([$this->object_id]);

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $this->settings[$row['setting_key']] = $row['setting_value'];
        }
    }

    public function getSetting(string $key, $default = null)
    {
        return $this->settings[$key] ?? $default;
    }

    public function saveSetting(string $key, $value)
    {
        $stmt = $this->db->prepare("
            INSERT INTO zentra_object_settings (object_id, setting_key, setting_value)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
        ");
        return $stmt->execute([$this->object_id, $key, $value]);
    }

    public function setLogger($logger)
    {
        $this->logger = $logger;
    }

    /* -----------------------------------------------------------
     * ABSTRACT-LIKE CRUD SIGNATURES
     * ----------------------------------------------------------- */

    public function create(array $data, int $userId, array $context = [])
    {
        throw new \Exception("Create() not implemented in module: {$this->module_type}");
    }

    public function update(int $id, array $data, int $userId, array $context = [])
    {
        throw new \Exception("Update() not implemented in module: {$this->module_type}");
    }

    public function delete(int $id, int $userId, array $context = [])
    {
        throw new \Exception("Delete() not implemented in module: {$this->module_type}");
    }

    public function setActive(int $id, bool $active, int $userId, array $context = [])
    {
        throw new \Exception("setActive() not implemented in module: {$this->module_type}");
    }

    public function get(int $id, ?string $userTimezone = null)
    {
        throw new \Exception("Get() not implemented in module: {$this->module_type}");
    }

    public function list(array $filters = [], ?string $userTimezone = null)
    {
        throw new \Exception("List() not implemented in module: {$this->module_type}");
    }

    /* -----------------------------------------------------------
     * VALIDATION HELPERS
     * ----------------------------------------------------------- */
    protected function validateRequired(array $data, array $required)
    {
        foreach ($required as $field) {
            if (! isset($data[$field]) || $data[$field] === '') {
                throw new \Exception("Missing required field: {$field}");
            }
        }
    }

    /* -----------------------------------------------------------
     * SANITIZATION
     * ----------------------------------------------------------- */
    protected function sanitize(?string $value): ?string
    {
        return $value === null ? null : trim(strip_tags($value));
    }

    /* -----------------------------------------------------------
     * TIMEZONE HELPERS
     * ----------------------------------------------------------- */
    protected function toUTC(string $datetime, string $timezone): string
    {
        $dt = new DateTime($datetime, new DateTimeZone($timezone));
        $dt->setTimezone(new DateTimeZone('UTC'));
        return $dt->format('Y-m-d H:i:s');
    }

    protected function fromUTC(string $datetime, string $timezone): string
    {
        $dt = new DateTime($datetime, new DateTimeZone('UTC'));
        $dt->setTimezone(new DateTimeZone($timezone));
        return $dt->format('Y-m-d H:i:s');
    }
}
