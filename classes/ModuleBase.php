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
    protected PDO $db;
    protected ?int $object_id;
    protected string $module_type;
    protected array $settings         = [];
    protected ?ActivityLogger $logger = null;

    public function __construct(PDO $db, string $module_type, ?int $object_id = null)
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

    public function saveSetting(string $key, mixed $value)
    {
        $stmt = $this->db->prepare("
            INSERT INTO zentra_object_settings (object_id, setting_key, setting_value)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
        ");
        return $stmt->execute([$this->object_id, $key, $value]);
    }

    public function setLogger(ActivityLogger $logger)
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

    public function isLibraryItemInUse(int $libraryId, int $tenantId): bool
    {
        // Events
        $sql = "SELECT COUNT(*)
            FROM zentra_events
            WHERE poster_library_id = :id
              AND tenant_id = :tenant_id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $libraryId, 'tenant_id' => $tenantId]);
        if ($stmt->fetchColumn() > 0) {
            return true;
        }

        // Blogs
        $sql = "SELECT COUNT(*)
            FROM zentra_blogs
            WHERE featured_library_id = :id
              AND tenant_id = :tenant_id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $libraryId, 'tenant_id' => $tenantId]);
        if ($stmt->fetchColumn() > 0) {
            return true;
        }

        // Gallery
        $sql = "SELECT COUNT(*)
            FROM zentra_gallery
            WHERE library_id = :id
              AND tenant_id = :tenant_id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $libraryId, 'tenant_id' => $tenantId]);
        if ($stmt->fetchColumn() > 0) {
            return true;
        }

        return false;

        /*
        // USAGE OF DELETE LIBRARY ITEM  - CHECKS IF THE LIBRARY ITEM IS USED IN ANY OF THE MODULES BEFORE DELETION
        
        $libraryId = (int) $_POST['library_id'];

            if ($library->isLibraryItemInUse($libraryId)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'This image is used in Events, Blogs, or Gallery and cannot be deleted.'
                ]);
                exit;
            }

            $library->deleteLibraryItem($libraryId);

        */
    }

    public function deleteLibraryItem(int $libraryId, int $tenantId, int $userId): bool
    {
        $ip      = cleanIP(getClientIP());
        $browser = getBrowserName($_SESSION['user_agent'] ?? '');
        $device  = getDeviceType($_SESSION['user_agent'] ?? '');
        $geo     = $_SESSION['geo'] ?? [];
        $logger  = new ActivityLogger($this->db, $tenantId);

        // 1. Validate media belongs to tenant
        $sql = "SELECT file_url
            FROM zentra_library
            WHERE library_id = :id AND tenant_id = :tenant_id
            LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'id'        => $libraryId,
            'tenant_id' => $tenantId,
        ]);

        $fileUrl = $stmt->fetchColumn();

        if (! $fileUrl) {

            // Log unauthorized delete attempt
            $logger->log(
                $userId,
                "Failed delete attempt for media ID {$libraryId} — not owned by tenant",
                "Media Delete Failed",
                [
                    'library_id'    => $libraryId,
                    'reason'        => 'Media does not belong to tenant',
                    'user_name'     => $_SESSION['user_name'] ?? null,
                    'user_timezone' => $_SESSION['user_timezone'] ?? 'UTC',
                    'tenant_id'     => (string) $tenantId,
                    'ip'            => $ip,
                    'browser'       => $browser,
                    'device'        => $device,
                    'city'          => $geo['city'] ?? null,
                    'region'        => $geo['region'] ?? null,
                    'country'       => $geo['country'] ?? null,
                    'geo_raw'       => $geo['raw'] ?? null,
                ]
            );

            return false;
        }

        // 2. Check if media is in use
        if ($this->isLibraryItemInUse($libraryId, $tenantId)) {

            $logger->log(
                $userId,
                "Failed delete attempt for media ID {$libraryId} — media is still in use",
                "Media Delete Failed",
                [
                    'library_id'    => $libraryId,
                    'reason'        => 'Media is referenced in Events, Blogs, or Gallery',
                    'user_name'     => $_SESSION['user_name'] ?? null,
                    'user_timezone' => $_SESSION['user_timezone'] ?? 'UTC',
                    'tenant_id'     => (string) $tenantId,
                    'ip'            => $ip,
                    'browser'       => $browser,
                    'device'        => $device,
                    'city'          => $geo['city'] ?? null,
                    'region'        => $geo['region'] ?? null,
                    'country'       => $geo['country'] ?? null,
                    'geo_raw'       => $geo['raw'] ?? null,
                ]
            );

            return false;
        }

        // 3. Attempt delete
        $sql = "UPDATE `zentra_library`
        SETstatus       = 'deleted',
        deleted_on_utc  = UTC_TIMESTAMP(),
        deleted_by      = :user_id
        WHERElibrary_id = :id
        and tenant_id   = :tenant_id";

        $stmt    = $this->db->prepare($sql);
        $success = $stmt->execute([
            'id'        => $libraryId,
            'tenant_id' => $tenantId,
            'user_id'   => $userId,
        ]);

        // 4. Log success or failure
        if ($success) {

            $logger->log(
                $userId,
                "Deleted media ID {$libraryId}",
                "Media Deleted",
                [
                    'library_id'    => $libraryId,
                    'file_url'      => $fileUrl,
                    'user_name'     => $_SESSION['user_name'] ?? null,
                    'user_timezone' => $_SESSION['user_timezone'] ?? 'UTC',
                    'tenant_id'     => (string) $tenantId,
                    'ip'            => $ip,
                    'browser'       => $browser,
                    'device'        => $device,
                    'city'          => $geo['city'] ?? null,
                    'region'        => $geo['region'] ?? null,
                    'country'       => $geo['country'] ?? null,
                    'geo_raw'       => $geo['raw'] ?? null,
                ]
            );

            return true;
        }

        // 5. Log DB/system failure
        $logger->log(
            $userId,
            "Failed delete attempt for media ID {$libraryId} — DB or filesystem error",
            "Media Delete Failed",
            [
                'library_id'    => $libraryId,
                'reason'        => 'Database or filesystem error',
                'user_name'     => $_SESSION['user_name'] ?? null,
                'user_timezone' => $_SESSION['user_timezone'] ?? 'UTC',
                'tenant_id'     => (string) $tenantId,
                'ip'            => $ip,
                'browser'       => $browser,
                'device'        => $device,
                'city'          => $geo['city'] ?? null,
                'region'        => $geo['region'] ?? null,
                'country'       => $geo['country'] ?? null,
                'geo_raw'       => $geo['raw'] ?? null,
            ]
        );
        return false;

        /*  
        --------------------------------------------
        Purge job runs after 90 days:
        --------------------------------------------
        UPDATE zentra_library
        SET status = 'purged',
            purged_on_utc = UTC_TIMESTAMP(),
            purged_on_localtime = :system_localtime,
            purged_by = 0  -- system user
        WHERE library_id = :id;
        
        --------------------------------------------
        Delete purged items after 7 days from library:
        --------------------------------------------
        DELETE FROM zentra_library
        WHERE status = 'purged'
        AND purged_on_utc < (UTC_TIMESTAMP() - INTERVAL 7 DAY);
        
        (You can choose 0–7 days for final hard delete.)
        */

    }

}
