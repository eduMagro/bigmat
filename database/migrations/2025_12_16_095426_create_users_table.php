<?php

use App\Database\IdempotentSqlMigration;

return new class extends IdempotentSqlMigration
{
    public function up(): void
    {
        $this->runSql(<<<'SQL'
CREATE TABLE IF NOT EXISTS `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `primer_apellido` varchar(100) DEFAULT NULL,
  `segundo_apellido` varchar(100) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `puede_usar_asistente` tinyint(1) NOT NULL DEFAULT 1,
  `puede_modificar_bd` tinyint(1) NOT NULL DEFAULT 0,
  `imagen` varchar(255) DEFAULT NULL,
  `movil_personal` varchar(20) DEFAULT NULL,
  `movil_empresa` varchar(20) DEFAULT NULL,
  `numero_corto` char(4) DEFAULT NULL CHECK (`numero_corto` regexp '^[0-9]{4}$'),
  `dni` varchar(9) DEFAULT NULL,
  `empresa_id` bigint(20) UNSIGNED DEFAULT NULL,
  `rol` varchar(50) DEFAULT NULL,
  `maquina_id` bigint(20) UNSIGNED DEFAULT NULL,
  `turno` varchar(50) DEFAULT NULL,
  `vacaciones_totales` tinyint(3) UNSIGNED NOT NULL DEFAULT 22,
  `password` varchar(255) DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `email_verified_at` datetime DEFAULT NULL,
  `acepta_politica_privacidad` tinyint(1) DEFAULT 0,
  `acepta_politica_cookies` tinyint(1) DEFAULT 0,
  `fecha_aceptacion_politicas` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  `categoria_id` bigint(20) DEFAULT NULL,
  `updated_by` bigint(20) UNSIGNED DEFAULT NULL,
  `estado` enum('activo','despedido') NOT NULL DEFAULT 'activo' COMMENT 'Estado laboral del trabajador',
  `fecha_baja` datetime DEFAULT NULL COMMENT 'Fecha en la que el usuario fue despedido o dado de baja'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
SQL
        );

        $this->runSql(<<<'SQL'
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `dni` (`dni`),
  ADD KEY `fk_categoria` (`categoria_id`),
  ADD KEY `fk_users_empresa` (`empresa_id`),
  ADD KEY `fk_users_especialidad` (`maquina_id`),
  ADD KEY `fk_updated_by_users` (`updated_by`);
SQL
        );

        $this->runSql(<<<'SQL'
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;
SQL
        );

    }

    public function down(): void
    {
    }
};
