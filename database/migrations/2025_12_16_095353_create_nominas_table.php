<?php

use App\Database\IdempotentSqlMigration;

return new class extends IdempotentSqlMigration
{
    public function up(): void
    {
        $this->runSql(<<<'SQL'
CREATE TABLE IF NOT EXISTS `nominas` (
  `id` int(11) NOT NULL,
  `empleado_id` bigint(20) UNSIGNED DEFAULT NULL,
  `categoria_id` bigint(20) DEFAULT NULL,
  `dias_trabajados` decimal(5,2) DEFAULT NULL,
  `salario_base` decimal(10,2) DEFAULT NULL,
  `plus_ajustado` tinyint(1) DEFAULT 0,
  `plus_actividad` decimal(10,2) DEFAULT NULL,
  `plus_asistencia` decimal(8,2) DEFAULT NULL,
  `plus_transporte` decimal(8,2) DEFAULT NULL,
  `plus_dieta` decimal(8,2) DEFAULT NULL,
  `plus_turnicidad` decimal(10,2) DEFAULT NULL,
  `plus_productividad` decimal(8,2) DEFAULT NULL,
  `prorrateo` decimal(10,2) DEFAULT NULL,
  `horas_extra` decimal(10,2) DEFAULT NULL,
  `valor_hora_extra` decimal(10,2) DEFAULT NULL,
  `total_devengado` decimal(10,2) DEFAULT NULL,
  `total_deducciones_ss` decimal(10,2) DEFAULT NULL,
  `irpf_mensual` decimal(10,2) DEFAULT NULL,
  `irpf_porcentaje` decimal(5,2) DEFAULT NULL,
  `liquido` decimal(10,2) DEFAULT NULL,
  `coste_empresa` decimal(10,2) DEFAULT NULL,
  `bruto_anual_estimado` decimal(12,2) DEFAULT NULL,
  `base_irpf_previa` decimal(12,2) DEFAULT NULL,
  `cuota_irpf_anual_sin_minimo` decimal(12,2) DEFAULT NULL,
  `cuota_minimo_personal` decimal(12,2) DEFAULT NULL,
  `cuota_irpf_anual` decimal(12,2) DEFAULT NULL,
  `fecha` date DEFAULT curdate(),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
SQL
        );

        $this->runSql(<<<'SQL'
ALTER TABLE `nominas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_nominas_empleado` (`empleado_id`),
  ADD KEY `fk_nominas_categoria` (`categoria_id`);
SQL
        );

        $this->runSql(<<<'SQL'
ALTER TABLE `nominas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
SQL
        );

    }

    public function down(): void
    {
    }
};
