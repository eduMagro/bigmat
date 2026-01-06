<?php

use App\Database\IdempotentSqlMigration;

return new class extends IdempotentSqlMigration
{
    public function up(): void
    {
        $this->runSql(<<<'SQL'
CREATE TABLE IF NOT EXISTS `incorporaciones` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `token` varchar(64) NOT NULL,
  `estado` enum('pendiente','datos_recibidos','en_proceso','completada','cancelada') DEFAULT 'pendiente',
  `empresa_destino` enum('hpr_servicios','hierros_paco_reyes') NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `primer_apellido` varchar(255) DEFAULT NULL,
  `segundo_apellido` varchar(255) DEFAULT NULL,
  `email_provisional` varchar(255) DEFAULT NULL,
  `telefono_provisional` varchar(20) DEFAULT NULL,
  `dni` varchar(9) DEFAULT NULL,
  `dni_frontal` varchar(255) DEFAULT NULL,
  `dni_trasero` varchar(255) DEFAULT NULL,
  `numero_afiliacion_ss` varchar(12) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `certificado_bancario` varchar(255) DEFAULT NULL,
  `datos_completados_at` timestamp NULL DEFAULT NULL,
  `enlace_enviado_at` timestamp NULL DEFAULT NULL,
  `recordatorio_enviado_at` timestamp NULL DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `updated_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `aprobado_rrhh` tinyint(1) DEFAULT 0,
  `aprobado_rrhh_at` datetime DEFAULT NULL,
  `aprobado_rrhh_by` bigint(20) UNSIGNED DEFAULT NULL,
  `aprobado_ceo` tinyint(1) DEFAULT 0,
  `aprobado_ceo_at` datetime DEFAULT NULL,
  `aprobado_ceo_by` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL
        );

        $this->runSql(<<<'SQL'
ALTER TABLE `incorporaciones`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `fk_incorporaciones_created_by` (`created_by`),
  ADD KEY `fk_incorporaciones_updated_by` (`updated_by`),
  ADD KEY `idx_incorporaciones_estado` (`estado`),
  ADD KEY `idx_incorporaciones_empresa` (`empresa_destino`),
  ADD KEY `idx_incorporaciones_created` (`created_at`),
  ADD KEY `idx_incorporaciones_user_id` (`user_id`);
SQL
        );

        $this->runSql(<<<'SQL'
ALTER TABLE `incorporaciones`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;
SQL
        );

    }

    public function down(): void
    {
    }
};
