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
    public function isModuleEnabled(string $typeKey): bool
    {
        $sql = "SELECT is_enabled
                FROM zentra_module_types
                WHERE type_key = :key
                LIMIT 1";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['key' => $typeKey]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? (bool) $row['is_enabled'] : false;
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
