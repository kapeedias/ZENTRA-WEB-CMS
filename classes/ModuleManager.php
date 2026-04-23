<?php

class ModuleManager
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Check if a module is enabled in App Config
     */
    public function isModuleEnabled(string $moduleSlug): bool
    {
        $sql = "SELECT is_enabled
                FROM zentra_module_types
                WHERE module_slug = :slug
                LIMIT 1";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['slug' => $moduleSlug]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (! $row) {
            return false; // module not found
        }

        return (bool) $row['is_enabled'];
    }

    /**
     * Get all enabled modules
     */
    public function getEnabledModules(): array
    {
        $sql = "SELECT module_slug
                FROM zentra_module_types
                WHERE is_enabled = 1";

        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}
