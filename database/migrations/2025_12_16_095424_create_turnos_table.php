<?php

use App\Database\IdempotentSqlMigration;

return new class extends IdempotentSqlMigration
{
    public function up(): void
    {
        $this->runSql(<<<'SQL'
CREATE TABLE IF NOT EXISTS `turnos` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `hora_inicio` time DEFAULT NULL COMMENT 'Hora de inicio del turno',
  `hora_fin` time DEFAULT NULL COMMENT 'Hora de fin del turno',
  `offset_dias_inicio` tinyint(4) NOT NULL DEFAULT 0 COMMENT 'Offset días inicio (-1, 0, 1)',
  `offset_dias_fin` tinyint(4) NOT NULL DEFAULT 0 COMMENT 'Offset días fin (-1, 0, 1)',
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `orden` int(11) NOT NULL DEFAULT 0,
  `color` varchar(7) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
SQL
        );

        $this->runSql(<<<'SQL'
ALTER TABLE `turnos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_turnos_activo` (`activo`);
SQL
        );

        $this->runSql(<<<'SQL'
ALTER TABLE `turnos`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;
SQL
        );

    }

    public function down(): void
    {
    }
};
