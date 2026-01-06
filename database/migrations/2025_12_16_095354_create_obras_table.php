<?php

use App\Database\IdempotentSqlMigration;

return new class extends IdempotentSqlMigration
{
    public function up(): void
    {
        $this->runSql(<<<'SQL'
CREATE TABLE IF NOT EXISTS `obras` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `obra` varchar(255) NOT NULL,
  `cod_obra` varchar(50) NOT NULL,
  `cliente_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ciudad` varchar(255) DEFAULT NULL,
  `direccion` varchar(255) DEFAULT NULL,
  `estado` varchar(255) DEFAULT NULL,
  `tipo` enum('montaje','suministro') DEFAULT 'suministro',
  `latitud` decimal(17,15) DEFAULT NULL,
  `longitud` decimal(17,15) DEFAULT NULL,
  `distancia` int(10) UNSIGNED DEFAULT NULL COMMENT 'Distancia en metros',
  `ancho_m` int(11) DEFAULT NULL,
  `largo_m` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
SQL
        );

        $this->runSql(<<<'SQL'
ALTER TABLE `obras`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `cod_obra` (`cod_obra`),
  ADD KEY `fk_obras_clientes` (`cliente_id`);
SQL
        );

        $this->runSql(<<<'SQL'
ALTER TABLE `obras`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;
SQL
        );

    }

    public function down(): void
    {
    }
};
