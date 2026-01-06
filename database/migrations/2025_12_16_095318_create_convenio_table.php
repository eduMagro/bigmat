<?php

use App\Database\IdempotentSqlMigration;

return new class extends IdempotentSqlMigration
{
    public function up(): void
    {
        $this->runSql(<<<'SQL'
CREATE TABLE IF NOT EXISTS `convenio` (
  `id` bigint(20) NOT NULL,
  `categoria_id` bigint(20) DEFAULT NULL,
  `salario_base` decimal(10,2) NOT NULL,
  `liquido_minimo_pactado` decimal(8,2) DEFAULT NULL,
  `plus_asistencia` decimal(10,2) NOT NULL,
  `plus_turnicidad` int(11) DEFAULT NULL,
  `plus_productividad` decimal(10,2) NOT NULL,
  `plus_transporte` decimal(10,2) NOT NULL,
  `prorrateo_pagasextras` decimal(10,2) NOT NULL,
  `plus_dieta` int(11) NOT NULL DEFAULT 1200,
  `plus_actividad` int(11) NOT NULL DEFAULT 1200
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL
        );

        $this->runSql(<<<'SQL'
ALTER TABLE `convenio`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_convenio_categoria` (`categoria_id`);
SQL
        );

        $this->runSql(<<<'SQL'
ALTER TABLE `convenio`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;
SQL
        );

    }

    public function down(): void
    {
    }
};
