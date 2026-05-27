<?php
class LibraryModule extends ModuleBase
{
    protected PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    // --- SOC2-compliant methods ---
    public function isLibraryItemInUse(int $libraryId, int $tenantId): bool
    {
        $sql  = "SELECT COUNT(*) FROM zentra_events WHERE poster_library_id = :id AND tenant_id = :tenant_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $libraryId, 'tenant_id' => $tenantId]);
        if ($stmt->fetchColumn() > 0) {
            return true;
        }

        $sql  = "SELECT COUNT(*) FROM zentra_blogs WHERE featured_library_id = :id AND tenant_id = :tenant_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $libraryId, 'tenant_id' => $tenantId]);
        if ($stmt->fetchColumn() > 0) {
            return true;
        }

        $sql  = "SELECT COUNT(*) FROM zentra_gallery WHERE library_id = :id AND tenant_id = :tenant_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $libraryId, 'tenant_id' => $tenantId]);
        if ($stmt->fetchColumn() > 0) {
            return true;
        }

        return false;
    }

    public function deleteLibraryItem(int $libraryId, int $tenantId, int $userId): bool
    {
        $ip      = cleanIP(getClientIP());
        $browser = getBrowserName($_SESSION['user_agent'] ?? '');
        $device  = getDeviceType($_SESSION['user_agent'] ?? '');
        $geo     = $_SESSION['geo'] ?? [];

        $logger = new ActivityLogger($this->db, $tenantId);

        // Validate ownership
        $sql  = "SELECT file_url FROM zentra_library WHERE library_id = :id AND tenant_id = :tenant_id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $libraryId, 'tenant_id' => $tenantId]);
        $fileUrl = $stmt->fetchColumn();

        if (! $fileUrl) {
            $logger->log($userId, "Failed delete attempt for media ID {$libraryId} — not owned by tenant", "Media Delete Failed", [
                'library_id' => $libraryId,
                'reason'     => 'Media does not belong to tenant',
                'tenant_id'  => (string) $tenantId,
                'ip'         => $ip,
                'browser'    => $browser,
                'device'     => $device,
                'geo_raw'    => $geo['raw'] ?? null,
            ]);
            return false;
        }

        // Check usage
        if ($this->isLibraryItemInUse($libraryId, $tenantId)) {
            $logger->log($userId, "Failed delete attempt for media ID {$libraryId} — media is still in use", "Media Delete Failed", [
                'library_id' => $libraryId,
                'reason'     => 'Media is referenced in Events, Blogs, or Gallery',
                'tenant_id'  => (string) $tenantId,
                'ip'         => $ip,
                'browser'    => $browser,
                'device'     => $device,
                'geo_raw'    => $geo['raw'] ?? null,
            ]);
            return false;
        }

        // Soft delete (SOC2)
        $sql = "UPDATE zentra_library
                SET status = 'deleted',
                    deleted_on_utc = UTC_TIMESTAMP(),
                    deleted_on_localtime = :localtime,
                    deleted_by = :user_id
                WHERE library_id = :id AND tenant_id = :tenant_id";

        $stmt    = $this->db->prepare($sql);
        $success = $stmt->execute([
            'localtime' => $_SESSION['user_localtime'] ?? null,
            'user_id'   => $userId,
            'id'        => $libraryId,
            'tenant_id' => $tenantId,
        ]);

        // Log outcome
        if ($success) {
            $logger->log($userId, "Deleted media ID {$libraryId}", "Media Deleted", [
                'library_id' => $libraryId,
                'file_url'   => $fileUrl,
                'tenant_id'  => (string) $tenantId,
                'ip'         => $ip,
                'browser'    => $browser,
                'device'     => $device,
                'geo_raw'    => $geo['raw'] ?? null,
            ]);
            return true;
        }

        $logger->log($userId, "Failed delete attempt for media ID {$libraryId} — DB or filesystem error", "Media Delete Failed", [
            'library_id' => $libraryId,
            'reason'     => 'Database or filesystem error',
            'tenant_id'  => (string) $tenantId,
            'ip'         => $ip,
            'browser'    => $browser,
            'device'     => $device,
            'geo_raw'    => $geo['raw'] ?? null,
        ]);
        return false;
    }
}
