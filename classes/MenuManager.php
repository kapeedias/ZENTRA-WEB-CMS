<?php
declare (strict_types = 1);
class MenuManager
{
    private PDO $pdo;
    private ModuleManager $moduleManager;

    public function __construct(PDO $pdo, ModuleManager $moduleManager)
    {
        $this->pdo           = $pdo;
        $this->moduleManager = $moduleManager;
    }

    public function getMenuItems(?int $tenantId = null): array
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
