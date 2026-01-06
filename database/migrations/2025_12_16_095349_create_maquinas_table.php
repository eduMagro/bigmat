<?php

use App\Database\IdempotentSqlMigration;

return new class extends IdempotentSqlMigration
{
    public function up(): void
    {
        $this->runSql(<<<'SQL'
CREATE TABLE IF NOT EXISTS `maquinas` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `codigo` varchar(255) NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `imagen` varchar(255) DEFAULT NULL,
  `estado` varchar(255) DEFAULT NULL,
  `tipo` varchar(50) DEFAULT NULL,
  `tiene_carro` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1 = la máquina tiene carro',
  `obra_id` bigint(20) UNSIGNED DEFAULT NULL,
  `tipo_material` varchar(20) DEFAULT NULL,
  `diametro_min` int(11) DEFAULT NULL,
  `diametro_max` int(11) DEFAULT NULL,
  `peso_min` int(11) DEFAULT NULL,
  `peso_max` int(11) DEFAULT NULL,
  `ancho_m` decimal(6,2) DEFAULT NULL,
  `largo_m` decimal(6,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
SQL
        );

        $this->runSql(<<<'SQL'
ALTER TABLE `maquinas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `maquinas_obra_id_foreign` (`obra_id`);
SQL
        );

        $this->runSql(<<<'SQL'
ALTER TABLE `maquinas`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;
SQL
        );

    }

    public function down(): void
    {
    }
};
