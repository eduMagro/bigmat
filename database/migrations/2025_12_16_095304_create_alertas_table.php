<?php

use App\Database\IdempotentSqlMigration;

return new class extends IdempotentSqlMigration
{
    public function up(): void
    {
        $this->runSql(<<<'SQL'
CREATE TABLE IF NOT EXISTS `alertas` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `mensaje` text DEFAULT NULL,
  `parent_id` bigint(20) UNSIGNED DEFAULT NULL,
  `user_id_1` bigint(20) UNSIGNED DEFAULT NULL,
  `user_id_2` bigint(20) UNSIGNED DEFAULT NULL,
  `destino` varchar(100) DEFAULT NULL,
  `destinatario` varchar(100) DEFAULT NULL,
  `destinatario_id` bigint(20) UNSIGNED DEFAULT NULL,
  `tipo` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
SQL
        );

        $this->runSql(<<<'SQL'
ALTER TABLE `alertas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id_1` (`user_id_1`),
  ADD KEY `user_id_2` (`user_id_2`),
  ADD KEY `fk_destinatario_id` (`destinatario_id`),
  ADD KEY `alertas_parent_id_index` (`parent_id`);
SQL
        );

        $this->runSql(<<<'SQL'
ALTER TABLE `alertas`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;
SQL
        );

    }

    public function down(): void
    {
    }
};
