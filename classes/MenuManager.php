<?php

class MenuManager
{
    private $pdo;
    private $moduleManager;

    public function __construct($pdo, $moduleManager)
    {
        $this->pdo           = $pdo;
        $this->moduleManager = $moduleManager;
    }

    public function getMenuItems($tenantId = null)
    {
        $sql = "SELECT * FROM zentra_nav_menu
                WHERE (tenant_id = :tenant_id OR tenant_id IS NULL)
                AND is_enabled = 1
                ORDER BY sort_order ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['tenant_id' => $tenantId]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Filter by module status
        return array_filter($items, function ($item) {
            if (! $item['required_module']) {
                return true;
            }
            return $this->moduleManager->isModuleEnabled($item['required_module']);
        });
    }
}
