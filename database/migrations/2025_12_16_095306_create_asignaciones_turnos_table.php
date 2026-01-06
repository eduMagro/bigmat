<?php

use App\Database\IdempotentSqlMigration;

return new class extends IdempotentSqlMigration
{
    public function up(): void
    {
        $this->runSql(<<<'SQL'
CREATE TABLE IF NOT EXISTS `asignaciones_turnos` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `turno_id` bigint(20) UNSIGNED DEFAULT NULL,
  `estado` varchar(50) DEFAULT 'activo',
  `maquina_id` bigint(20) UNSIGNED DEFAULT NULL,
  `obra_id` bigint(20) UNSIGNED DEFAULT NULL,
  `entrada` time DEFAULT NULL,
  `salida` time DEFAULT NULL,
  `fecha` date NOT NULL,
  `justificante_ruta` varchar(255) DEFAULT NULL,
  `horas_justificadas` decimal(4,2) DEFAULT NULL,
  `justificante_observaciones` text DEFAULT NULL,
  `justificante_subido_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
SQL
        );

        $this->runSql(<<<'SQL'
ALTER TABLE `asignaciones_turnos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `turno_id` (`turno_id`),
  ADD KEY `fk_asignaciones_turnos_maquina` (`maquina_id`),
  ADD KEY `fk_asignaciones_turnos_obra` (`obra_id`);
SQL
        );

        $this->runSql(<<<'SQL'
ALTER TABLE `asignaciones_turnos`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;
SQL
        );

    }

    public function down(): void
    {
    }
};
