<?php

use App\Database\IdempotentSqlMigration;

return new class extends IdempotentSqlMigration
{
    public function up(): void
    {
        $this->runSql(<<<'SQL'
CREATE TABLE IF NOT EXISTS `tasas_irpf` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `anio` int(11) NOT NULL,
  `base_desde` decimal(15,2) NOT NULL COMMENT 'Límite inferior de la base imponible',
  `base_hasta` decimal(15,2) DEFAULT NULL COMMENT 'Límite superior de la base imponible (NULL = sin límite)',
  `porcentaje` decimal(5,4) NOT NULL COMMENT 'Tipo de retención en porcentaje',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL
        );

        $this->runSql(<<<'SQL'
ALTER TABLE `tasas_irpf`
  ADD PRIMARY KEY (`id`),
  ADD KEY `anio` (`anio`);
SQL
        );

        $this->runSql(<<<'SQL'
ALTER TABLE `tasas_irpf`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;
SQL
        );

    }

    public function down(): void
    {
    }
};
