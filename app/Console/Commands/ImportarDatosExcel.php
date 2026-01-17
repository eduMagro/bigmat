<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Categoria;
use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportarDatosExcel extends Command
{
    protected $signature = 'usuarios:importar-excel {--preview : Solo mostrar lo que se importaría sin hacer cambios}';
    protected $description = 'Importar datos de usuarios desde el Excel (movil empresa, movil personal, extension, departamento)';

    public function handle()
    {
        $rutaExcel = base_path('base datos trabajadores.xlsx');

        if (!file_exists($rutaExcel)) {
            $this->error("No se encontró el archivo: {$rutaExcel}");
            return 1;
        }

        $this->info("Leyendo archivo Excel...");

        $spreadsheet = IOFactory::load($rutaExcel);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();

        // Buscar fila de cabeceras (primera fila con datos)
        $headerRowIndex = 0;
        for ($i = 0; $i < min(10, count($rows)); $i++) {
            $nonEmpty = array_filter($rows[$i], fn($cell) => !empty($cell));
            if (count($nonEmpty) >= 3) {
                $headerRowIndex = $i;
                break;
            }
        }

        $this->info("Cabeceras encontradas en fila: {$headerRowIndex}");

        $headers = $rows[$headerRowIndex];
        $this->info("Columnas encontradas:");
        foreach ($headers as $i => $header) {
            $this->line("  [{$i}] {$header}");
        }

        $this->newLine();

        // Buscar índices de columnas relevantes
        $colNombre = $this->findColumn($headers, ['nombre', 'name']);
        $colApellidos = $this->findColumn($headers, ['apellidos', 'apellido', 'primer apellido', 'apellido1']);
        $colDni = $this->findColumn($headers, ['dni', 'nif', 'documento']);
        $colMovilEmpresa = $this->findColumn($headers, ['tel empresa', 'telefono empresa', 'movil empresa', 'numero empresa']);
        $colMovilPersonal = $this->findColumn($headers, ['tel personal', 'telefono personal', 'movil personal', 'numero personal']);
        $colExtension = $this->findColumn($headers, ['extension', 'extensión', 'ext', 'numero corto']);
        $colDepartamento = $this->findColumn($headers, ['departamento', 'categoria', 'dept']);

        $this->info("Columnas identificadas:");
        $this->line("  Nombre: " . ($colNombre !== null ? "[{$colNombre}] {$headers[$colNombre]}" : "NO ENCONTRADA"));
        $this->line("  Apellidos: " . ($colApellidos !== null ? "[{$colApellidos}] {$headers[$colApellidos]}" : "NO ENCONTRADA"));
        $this->line("  DNI: " . ($colDni !== null ? "[{$colDni}] {$headers[$colDni]}" : "NO ENCONTRADA"));
        $this->line("  Movil Empresa: " . ($colMovilEmpresa !== null ? "[{$colMovilEmpresa}] {$headers[$colMovilEmpresa]}" : "NO ENCONTRADA"));
        $this->line("  Movil Personal: " . ($colMovilPersonal !== null ? "[{$colMovilPersonal}] {$headers[$colMovilPersonal]}" : "NO ENCONTRADA"));
        $this->line("  Extension: " . ($colExtension !== null ? "[{$colExtension}] {$headers[$colExtension]}" : "NO ENCONTRADA"));
        $this->line("  Departamento: " . ($colDepartamento !== null ? "[{$colDepartamento}] {$headers[$colDepartamento]}" : "NO ENCONTRADA"));

        $this->newLine();

        // Obtener categorías existentes
        $categorias = Categoria::pluck('id', 'nombre')->mapWithKeys(function ($id, $nombre) {
            return [strtolower($nombre) => $id];
        })->toArray();

        $this->info("Categorías disponibles: " . implode(', ', array_keys($categorias)));
        $this->newLine();

        $preview = $this->option('preview');
        $actualizados = 0;
        $noEncontrados = 0;
        $sinDni = 0;

        // Procesar filas (saltando cabeceras)
        for ($i = $headerRowIndex + 1; $i < count($rows); $i++) {
            $row = $rows[$i];

            // Obtener DNI para buscar usuario
            $dni = $colDni !== null ? strtoupper(trim($row[$colDni] ?? '')) : null;

            // Si no hay DNI, saltar
            if (empty($dni)) {
                $sinDni++;
                continue;
            }

            // Buscar usuario
            $usuario = User::where('dni', $dni)->first();

            if (!$usuario) {
                $nombre = $colNombre !== null ? ($row[$colNombre] ?? '') : '';
                $apellidos = $colApellidos !== null ? ($row[$colApellidos] ?? '') : '';
                $this->warn("  [NO ENCONTRADO] DNI: {$dni} - {$nombre} {$apellidos}");
                $noEncontrados++;
                continue;
            }

            // Preparar datos a actualizar
            $datosActualizar = [];

            if ($colMovilEmpresa !== null && !empty($row[$colMovilEmpresa])) {
                $datosActualizar['movil_empresa'] = trim($row[$colMovilEmpresa]);
            }

            if ($colMovilPersonal !== null && !empty($row[$colMovilPersonal])) {
                $datosActualizar['movil_personal'] = trim($row[$colMovilPersonal]);
            }

            if ($colExtension !== null && !empty($row[$colExtension])) {
                $datosActualizar['numero_corto'] = trim($row[$colExtension]);
            }

            if ($colDepartamento !== null && !empty($row[$colDepartamento])) {
                $nombreCategoria = strtolower(trim($row[$colDepartamento]));
                if (isset($categorias[$nombreCategoria])) {
                    $datosActualizar['categoria_id'] = $categorias[$nombreCategoria];
                } else {
                    $this->warn("  Categoría no encontrada: '{$row[$colDepartamento]}' para {$usuario->nombre_completo}");
                }
            }

            if (empty($datosActualizar)) {
                continue;
            }

            if ($preview) {
                $this->line("  [PREVIEW] {$usuario->nombre_completo}: " . json_encode($datosActualizar));
            } else {
                $usuario->update($datosActualizar);
                $this->line("  [ACTUALIZADO] {$usuario->nombre_completo}: " . json_encode($datosActualizar));
            }

            $actualizados++;
        }

        $this->newLine();
        $this->info("Resumen:");
        $this->info("  - " . ($preview ? "Se actualizarían" : "Actualizados") . ": {$actualizados}");
        $this->info("  - No encontrados: {$noEncontrados}");
        $this->info("  - Sin DNI: {$sinDni}");

        return 0;
    }

    private function findColumn(array $headers, array $posibleNames): ?int
    {
        foreach ($headers as $i => $header) {
            $headerLower = strtolower(trim($header ?? ''));
            foreach ($posibleNames as $name) {
                if ($headerLower === strtolower($name) || str_contains($headerLower, strtolower($name))) {
                    return $i;
                }
            }
        }
        return null;
    }
}
