<?php

use App\Database\IdempotentSqlMigration;

return new class extends IdempotentSqlMigration
{
    public function up(): void
    {
        $this->runSql(<<<'SQL'
CREATE TABLE IF NOT EXISTS `salida_cliente` (
  `id` bigint(20) NOT NULL,
  `salida_id` bigint(20) NOT NULL,
  `cliente_id` bigint(20) UNSIGNED NOT NULL,
  `obra_id` bigint(20) UNSIGNED NOT NULL,
  `horas_paralizacion` decimal(10,2) DEFAULT 0.00,
  `importe_paralizacion` decimal(10,2) DEFAULT 0.00,
  `horas_grua` decimal(10,2) DEFAULT 0.00,
  `importe_grua` decimal(10,2) DEFAULT 0.00,
  `horas_almacen` decimal(5,2) DEFAULT 0.00,
  `importe` decimal(10,2) DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
SQL
        );

        $this->runSql(<<<'SQL'
ALTER TABLE `salida_cliente`
  ADD PRIMARY KEY (`id`),
  ADD KEY `salida_id` (`salida_id`),
  ADD KEY `cliente_id` (`cliente_id`),
  ADD KEY `obra_id` (`obra_id`);
SQL
        );

        $this->runSql(<<<'SQL'
ALTER TABLE `salida_cliente`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;
SQL
        );

    }

    public function down(): void
    {
    }
};
