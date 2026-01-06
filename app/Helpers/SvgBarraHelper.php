<?php

namespace App\Helpers;

/**
 * Helper para generar planos de ensamblado en formato SVG.
 *
 * Genera representaciones visuales profesionales de entidades de armadura
 * para que los operarios puedan ensamblar correctamente.
 */
class SvgBarraHelper
{
    // Colores para cada tipo de elemento
    const COLORES = [
        'A' => '#2563eb', // Azul
        'B' => '#059669', // Verde
        'C' => '#dc2626', // Rojo
        'D' => '#7c3aed', // Púrpura
        'E' => '#f59e0b', // Naranja (estribos)
        'F' => '#06b6d4', // Cyan
    ];

    /**
     * Genera el plano completo de ensamblado.
     */
    public static function generarPlanoEnsamblado(array $datos): string
    {
        $longitudCm = ($datos['longitud'] ?? 0) * 100;
        $estriboAncho = $datos['estriboAncho'] ?? 25;
        $estriboAlto = $datos['estriboAlto'] ?? 30;
        $totalSuperiores = $datos['totalSuperiores'] ?? 2;
        $totalInferiores = $datos['totalInferiores'] ?? 2;
        $cantidadEstribos = $datos['cantidadEstribos'] ?? 10;
        $separacionEstribos = $datos['separacionEstribos'] ?? 15;
        $armaduraConLetras = $datos['armaduraConLetras'] ?? [];
        $elementosPorLetra = $datos['elementosPorLetra'] ?? collect();
        $letraSup = $datos['letraSup'] ?? 'A';
        $letraInf = $datos['letraInf'] ?? 'B';
        $letraEstribo = $datos['letraEstribo'] ?? 'C';
        $composicion = $datos['composicion'] ?? [];

        $svg = '';

        // === VISTA 3D ISOMÉTRICA (izquierda) ===
        $svg .= self::renderizarVista3D([
            'x' => 5,
            'y' => 5,
            'width' => 280,
            'height' => 180,
            'longitudCm' => $longitudCm,
            'estriboAncho' => $estriboAncho,
            'estriboAlto' => $estriboAlto,
            'cantidadEstribos' => $cantidadEstribos,
            'separacionEstribos' => $separacionEstribos,
            'armaduraConLetras' => $armaduraConLetras,
            'letraSup' => $letraSup,
            'letraInf' => $letraInf,
            'letraEstribo' => $letraEstribo,
        ]);

        // === SECCIÓN TRANSVERSAL CON POSICIONES (derecha arriba) ===
        $svg .= self::renderizarSeccionDetallada([
            'x' => 295,
            'y' => 5,
            'width' => 260,
            'height' => 180,
            'estriboAncho' => $estriboAncho,
            'estriboAlto' => $estriboAlto,
            'armaduraConLetras' => $armaduraConLetras,
            'composicion' => $composicion,
            'letraEstribo' => $letraEstribo,
        ]);

        // === LEYENDA DE ELEMENTOS (abajo) ===
        $svg .= self::renderizarLeyenda([
            'x' => 5,
            'y' => 190,
            'width' => 550,
            'height' => 55,
            'armaduraConLetras' => $armaduraConLetras,
            'elementosPorLetra' => $elementosPorLetra,
            'longitudCm' => $longitudCm,
        ]);

        return $svg;
    }

    /**
     * Renderiza vista 3D isométrica simplificada.
     */
    public static function renderizarVista3D(array $opciones): string
    {
        $x = $opciones['x'] ?? 5;
        $y = $opciones['y'] ?? 5;
        $width = $opciones['width'] ?? 280;
        $height = $opciones['height'] ?? 180;
        $longitudCm = $opciones['longitudCm'] ?? 350;
        $estriboAncho = $opciones['estriboAncho'] ?? 25;
        $estriboAlto = $opciones['estriboAlto'] ?? 30;
        $cantidadEstribos = $opciones['cantidadEstribos'] ?? 10;
        $separacionEstribos = $opciones['separacionEstribos'] ?? 15;
        $armaduraConLetras = $opciones['armaduraConLetras'] ?? [];
        $letraEstribo = $opciones['letraEstribo'] ?? 'E';

        $svg = "<g transform=\"translate({$x}, {$y})\">";

        // Título
        $svg .= "<text x=\"" . ($width/2) . "\" y=\"12\" text-anchor=\"middle\" font-size=\"10\" font-weight=\"bold\" fill=\"#1f2937\">VISTA 3D</text>";

        // Parámetros isométricos
        $isoAngle = 30; // grados
        $cos30 = cos(deg2rad($isoAngle));
        $sin30 = sin(deg2rad($isoAngle));

        // Escala para que quepa
        $maxLongitud = max($longitudCm, 100);
        $escalaL = ($width - 80) / $maxLongitud * 50; // Escala longitudinal
        $escalaS = min(($height - 60) / max($estriboAlto, 20), 2); // Escala sección

        // Centro de la vista
        $cx = $width / 2;
        $cy = $height / 2 + 15;

        // Dimensiones escaladas
        $longPx = min($longitudCm * $escalaL / 50, $width - 100);
        $anchoPx = $estriboAncho * $escalaS;
        $altoPx = $estriboAlto * $escalaS;

        // Función para proyectar punto 3D a 2D isométrico
        $proyecto = function($px, $py, $pz) use ($cx, $cy, $cos30, $sin30) {
            $isoX = $cx + ($px - $pz) * $cos30;
            $isoY = $cy - $py + ($px + $pz) * $sin30 * 0.5;
            return [$isoX, $isoY];
        };

        // Dibujar estribos (rectángulos en perspectiva)
        $numEstribosVis = min($cantidadEstribos, 8);
        $espacioEstribo = $longPx / max($numEstribosVis + 1, 2);
        $colorEstribo = self::COLORES[$letraEstribo] ?? '#f59e0b';

        for ($i = 1; $i <= $numEstribosVis; $i++) {
            $posZ = -$longPx/2 + $i * $espacioEstribo;

            // Esquinas del estribo
            $p1 = $proyecto(-$anchoPx/2, $altoPx/2, $posZ);
            $p2 = $proyecto($anchoPx/2, $altoPx/2, $posZ);
            $p3 = $proyecto($anchoPx/2, -$altoPx/2, $posZ);
            $p4 = $proyecto(-$anchoPx/2, -$altoPx/2, $posZ);

            // Dibujar estribo
            $svg .= "<path d=\"M {$p1[0]} {$p1[1]} L {$p2[0]} {$p2[1]} L {$p3[0]} {$p3[1]} L {$p4[0]} {$p4[1]} Z\" fill=\"none\" stroke=\"{$colorEstribo}\" stroke-width=\"2\" opacity=\"0.7\"/>";
        }

        // Dibujar barras longitudinales
        $barrasPos = self::calcularPosicionesBarras($armaduraConLetras, $anchoPx, $altoPx);

        foreach ($barrasPos as $barra) {
            $letra = $barra['letra'];
            $bx = $barra['x'];
            $by = $barra['y'];
            $color = self::COLORES[$letra] ?? '#2563eb';

            // Línea de la barra (de frente a atrás)
            $pStart = $proyecto($bx, $by, -$longPx/2);
            $pEnd = $proyecto($bx, $by, $longPx/2);

            $svg .= "<line x1=\"{$pStart[0]}\" y1=\"{$pStart[1]}\" x2=\"{$pEnd[0]}\" y2=\"{$pEnd[1]}\" stroke=\"{$color}\" stroke-width=\"4\" stroke-linecap=\"round\"/>";

            // Círculo en el extremo frontal
            $svg .= "<circle cx=\"{$pEnd[0]}\" cy=\"{$pEnd[1]}\" r=\"5\" fill=\"{$color}\" stroke=\"#fff\" stroke-width=\"1\"/>";
            $svg .= "<text x=\"{$pEnd[0]}\" y=\"" . ($pEnd[1] + 3) . "\" text-anchor=\"middle\" font-size=\"7\" fill=\"#fff\" font-weight=\"bold\">{$letra}</text>";
        }

        // Cotas de longitud
        $cotaY = $cy + $altoPx/2 + 25;
        $cotaX1 = $cx - $longPx/2 * $cos30;
        $cotaX2 = $cx + $longPx/2 * $cos30;

        $svg .= "<line x1=\"{$cotaX1}\" y1=\"{$cotaY}\" x2=\"{$cotaX2}\" y2=\"{$cotaY}\" stroke=\"#666\" stroke-width=\"0.5\"/>";
        $svg .= "<line x1=\"{$cotaX1}\" y1=\"" . ($cotaY - 3) . "\" x2=\"{$cotaX1}\" y2=\"" . ($cotaY + 3) . "\" stroke=\"#666\" stroke-width=\"0.5\"/>";
        $svg .= "<line x1=\"{$cotaX2}\" y1=\"" . ($cotaY - 3) . "\" x2=\"{$cotaX2}\" y2=\"" . ($cotaY + 3) . "\" stroke=\"#666\" stroke-width=\"0.5\"/>";

        $longitudTexto = number_format($longitudCm / 100, 2) . 'm';
        $svg .= "<text x=\"{$cx}\" y=\"" . ($cotaY + 12) . "\" text-anchor=\"middle\" font-size=\"10\" fill=\"#1f2937\" font-weight=\"bold\">L = {$longitudTexto}</text>";

        // Indicador de separación de estribos
        if ($separacionEstribos > 0) {
            $svg .= "<text x=\"" . ($width - 10) . "\" y=\"30\" text-anchor=\"end\" font-size=\"9\" fill=\"#666\">Estribos c/{$separacionEstribos}cm</text>";
        }

        $svg .= "</g>";
        return $svg;
    }

    /**
     * Renderiza sección transversal con posiciones exactas de barras.
     */
    public static function renderizarSeccionDetallada(array $opciones): string
    {
        $x = $opciones['x'] ?? 295;
        $y = $opciones['y'] ?? 5;
        $width = $opciones['width'] ?? 260;
        $height = $opciones['height'] ?? 180;
        $estriboAncho = $opciones['estriboAncho'] ?? 25;
        $estriboAlto = $opciones['estriboAlto'] ?? 30;
        $armaduraConLetras = $opciones['armaduraConLetras'] ?? [];
        $composicion = $opciones['composicion'] ?? [];
        $letraEstribo = $opciones['letraEstribo'] ?? 'E';

        $svg = "<g transform=\"translate({$x}, {$y})\">";

        // Título
        $svg .= "<text x=\"" . ($width/2) . "\" y=\"12\" text-anchor=\"middle\" font-size=\"10\" font-weight=\"bold\" fill=\"#1f2937\">SECCIÓN TRANSVERSAL</text>";

        // Área de dibujo
        $drawX = 40;
        $drawY = 25;
        $drawW = $width - 80;
        $drawH = $height - 60;

        // Escala para el estribo
        $escalaX = $drawW / max($estriboAncho, 20);
        $escalaY = $drawH / max($estriboAlto, 20);
        $escala = min($escalaX, $escalaY) * 0.8;

        $estriboW = $estriboAncho * $escala;
        $estriboH = $estriboAlto * $escala;

        // Centrar estribo
        $estriboX = $drawX + ($drawW - $estriboW) / 2;
        $estriboY = $drawY + ($drawH - $estriboH) / 2;

        // Color del estribo
        $colorEstribo = self::COLORES[$letraEstribo] ?? '#f59e0b';

        // Fondo del estribo
        $svg .= "<rect x=\"{$estriboX}\" y=\"{$estriboY}\" width=\"{$estriboW}\" height=\"{$estriboH}\" fill=\"#fef3c7\" stroke=\"{$colorEstribo}\" stroke-width=\"3\" rx=\"3\"/>";

        // Badge del estribo
        $svg .= "<rect x=\"" . ($estriboX + $estriboW - 20) . "\" y=\"{$estriboY}\" width=\"20\" height=\"16\" fill=\"{$colorEstribo}\" rx=\"0 3 0 3\"/>";
        $svg .= "<text x=\"" . ($estriboX + $estriboW - 10) . "\" y=\"" . ($estriboY + 11) . "\" text-anchor=\"middle\" font-size=\"9\" fill=\"#fff\" font-weight=\"bold\">{$letraEstribo}</text>";

        // Calcular posiciones de barras dentro del estribo
        $recubrimiento = 3 * $escala; // 3cm recubrimiento
        $innerX = $estriboX + $recubrimiento;
        $innerY = $estriboY + $recubrimiento;
        $innerW = $estriboW - 2 * $recubrimiento;
        $innerH = $estriboH - 2 * $recubrimiento;

        // Dibujar barras según posición
        $barrasComp = $composicion['barras'] ?? [];
        $radioBase = min($innerW, $innerH) / 12;

        foreach ($armaduraConLetras as $arm) {
            if ($arm['tipo'] !== 'longitudinal') continue;

            $letra = $arm['letra'];
            $cantidad = $arm['cantidad'] ?? 0;
            $posicion = $arm['posicion'] ?? 'superior';
            $color = self::COLORES[$letra] ?? '#2563eb';
            $radio = $radioBase * (($arm['diametro'] ?? 12) / 12);

            // Buscar info adicional en composición
            $posicionComp = null;
            foreach ($barrasComp as $bc) {
                if (($bc['diametro'] ?? 0) == ($arm['diametro'] ?? 0)) {
                    $posicionComp = $bc['posicion'] ?? null;
                    break;
                }
            }

            // Determinar coordenadas según posición
            $coords = self::calcularCoordenadasSeccion($posicion, $posicionComp, $cantidad, $innerX, $innerY, $innerW, $innerH, $radio);

            foreach ($coords as $i => $coord) {
                $svg .= "<circle cx=\"{$coord['x']}\" cy=\"{$coord['y']}\" r=\"{$radio}\" fill=\"{$color}\" stroke=\"#fff\" stroke-width=\"1.5\"/>";

                // Mostrar letra solo en la primera barra de cada tipo
                if ($i === 0) {
                    $svg .= "<text x=\"{$coord['x']}\" y=\"" . ($coord['y'] + $radio/3) . "\" text-anchor=\"middle\" font-size=\"" . max(7, $radio * 0.8) . "\" fill=\"#fff\" font-weight=\"bold\">{$letra}</text>";
                }
            }
        }

        // Cotas del estribo
        // Cota horizontal (ancho)
        $cotaHY = $estriboY + $estriboH + 10;
        $svg .= "<line x1=\"{$estriboX}\" y1=\"{$cotaHY}\" x2=\"" . ($estriboX + $estriboW) . "\" y2=\"{$cotaHY}\" stroke=\"#666\" stroke-width=\"0.5\"/>";
        $svg .= "<line x1=\"{$estriboX}\" y1=\"" . ($cotaHY - 3) . "\" x2=\"{$estriboX}\" y2=\"" . ($cotaHY + 3) . "\" stroke=\"#666\" stroke-width=\"0.5\"/>";
        $svg .= "<line x1=\"" . ($estriboX + $estriboW) . "\" y1=\"" . ($cotaHY - 3) . "\" x2=\"" . ($estriboX + $estriboW) . "\" y2=\"" . ($cotaHY + 3) . "\" stroke=\"#666\" stroke-width=\"0.5\"/>";
        $svg .= "<text x=\"" . ($estriboX + $estriboW/2) . "\" y=\"" . ($cotaHY + 12) . "\" text-anchor=\"middle\" font-size=\"9\" fill=\"#333\">{$estriboAncho} cm</text>";

        // Cota vertical (alto)
        $cotaVX = $estriboX + $estriboW + 10;
        $svg .= "<line x1=\"{$cotaVX}\" y1=\"{$estriboY}\" x2=\"{$cotaVX}\" y2=\"" . ($estriboY + $estriboH) . "\" stroke=\"#666\" stroke-width=\"0.5\"/>";
        $svg .= "<line x1=\"" . ($cotaVX - 3) . "\" y1=\"{$estriboY}\" x2=\"" . ($cotaVX + 3) . "\" y2=\"{$estriboY}\" stroke=\"#666\" stroke-width=\"0.5\"/>";
        $svg .= "<line x1=\"" . ($cotaVX - 3) . "\" y1=\"" . ($estriboY + $estriboH) . "\" x2=\"" . ($cotaVX + 3) . "\" y2=\"" . ($estriboY + $estriboH) . "\" stroke=\"#666\" stroke-width=\"0.5\"/>";

        // Texto vertical
        $textY = $estriboY + $estriboH/2;
        $svg .= "<text x=\"" . ($cotaVX + 12) . "\" y=\"{$textY}\" text-anchor=\"middle\" font-size=\"9\" fill=\"#333\" transform=\"rotate(90, " . ($cotaVX + 12) . ", {$textY})\">{$estriboAlto} cm</text>";

        // Indicador de recubrimiento
        $svg .= "<text x=\"" . ($estriboX + 5) . "\" y=\"" . ($estriboY - 3) . "\" font-size=\"7\" fill=\"#999\">rec. 3cm</text>";

        $svg .= "</g>";
        return $svg;
    }

    /**
     * Renderiza leyenda de elementos con cantidades y diámetros.
     */
    public static function renderizarLeyenda(array $opciones): string
    {
        $x = $opciones['x'] ?? 5;
        $y = $opciones['y'] ?? 190;
        $width = $opciones['width'] ?? 550;
        $height = $opciones['height'] ?? 55;
        $armaduraConLetras = $opciones['armaduraConLetras'] ?? [];
        $elementosPorLetra = $opciones['elementosPorLetra'] ?? collect();
        $longitudCm = $opciones['longitudCm'] ?? 0;

        $svg = "<g transform=\"translate({$x}, {$y})\">";

        // Fondo de leyenda
        $svg .= "<rect x=\"0\" y=\"0\" width=\"{$width}\" height=\"{$height}\" fill=\"#f8fafc\" stroke=\"#e2e8f0\" stroke-width=\"1\" rx=\"3\"/>";

        // Título
        $svg .= "<text x=\"10\" y=\"14\" font-size=\"9\" font-weight=\"bold\" fill=\"#1f2937\">LISTA DE MATERIALES:</text>";

        // Elementos en horizontal
        $itemX = 10;
        $itemY = 28;
        $itemWidth = 130;

        foreach ($armaduraConLetras as $arm) {
            $letra = $arm['letra'] ?? '?';
            $tipo = $arm['tipo'] ?? 'longitudinal';
            $diametro = $arm['diametro'] ?? '?';
            $cantidad = $arm['cantidad'] ?? 0;
            $esEstribo = $tipo === 'transversal';
            $color = self::COLORES[$letra] ?? '#666';

            // Badge
            if ($esEstribo) {
                $svg .= "<rect x=\"{$itemX}\" y=\"" . ($itemY - 8) . "\" width=\"16\" height=\"16\" fill=\"{$color}\" rx=\"2\"/>";
            } else {
                $svg .= "<circle cx=\"" . ($itemX + 8) . "\" cy=\"{$itemY}\" r=\"8\" fill=\"{$color}\"/>";
            }
            $svg .= "<text x=\"" . ($itemX + 8) . "\" y=\"" . ($itemY + 3) . "\" text-anchor=\"middle\" font-size=\"9\" fill=\"#fff\" font-weight=\"bold\">{$letra}</text>";

            // Descripción
            $desc = "{$cantidad}× Ø{$diametro}";
            if ($esEstribo && isset($arm['separacion'])) {
                $desc .= " c/" . $arm['separacion'] . "cm";
            } elseif (!$esEstribo && isset($arm['posicion'])) {
                $pos = $arm['posicion'];
                $posAbrev = match($pos) {
                    'superior' => 'sup',
                    'inferior' => 'inf',
                    'lateral' => 'lat',
                    'esquina' => 'esq',
                    default => $pos
                };
                $desc .= " ({$posAbrev})";
            }

            $svg .= "<text x=\"" . ($itemX + 22) . "\" y=\"" . ($itemY + 4) . "\" font-size=\"9\" fill=\"#374151\">{$desc}</text>";

            // Buscar longitud del elemento
            if ($elementosPorLetra && isset($elementosPorLetra[$letra])) {
                $elem = $elementosPorLetra[$letra]->first();
                if ($elem && $elem->longitud > 0) {
                    $longM = number_format($elem->longitud / 1000, 2) . 'm';
                    $svg .= "<text x=\"" . ($itemX + 22) . "\" y=\"" . ($itemY + 15) . "\" font-size=\"7\" fill=\"#6b7280\">L: {$longM}</text>";
                }
            }

            $itemX += $itemWidth;

            // Nueva fila si no cabe
            if ($itemX + $itemWidth > $width - 10) {
                $itemX = 10;
                $itemY += 24;
            }
        }

        $svg .= "</g>";
        return $svg;
    }

    /**
     * Calcula posiciones de barras para la vista 3D.
     */
    private static function calcularPosicionesBarras(array $armaduraConLetras, float $ancho, float $alto): array
    {
        $barras = [];
        $recub = min($ancho, $alto) * 0.15; // 15% recubrimiento

        foreach ($armaduraConLetras as $arm) {
            if ($arm['tipo'] !== 'longitudinal') continue;

            $letra = $arm['letra'];
            $cantidad = $arm['cantidad'] ?? 0;
            $posicion = $arm['posicion'] ?? 'superior';

            // Distribuir barras según posición
            for ($i = 0; $i < min($cantidad, 4); $i++) {
                $bx = 0;
                $by = 0;

                switch ($posicion) {
                    case 'superior':
                        $by = $alto/2 - $recub;
                        $bx = -$ancho/2 + $recub + ($i * ($ancho - 2*$recub) / max($cantidad - 1, 1));
                        break;
                    case 'inferior':
                        $by = -$alto/2 + $recub;
                        $bx = -$ancho/2 + $recub + ($i * ($ancho - 2*$recub) / max($cantidad - 1, 1));
                        break;
                    case 'lateral':
                    case 'piel':
                        $lado = $i % 2 == 0 ? -1 : 1;
                        $bx = $lado * ($ancho/2 - $recub);
                        $by = -$alto/4 + ($i/2) * ($alto/2);
                        break;
                    case 'esquina':
                    default:
                        // Esquinas
                        $esquinas = [
                            [-$ancho/2 + $recub, $alto/2 - $recub],
                            [$ancho/2 - $recub, $alto/2 - $recub],
                            [-$ancho/2 + $recub, -$alto/2 + $recub],
                            [$ancho/2 - $recub, -$alto/2 + $recub],
                        ];
                        if ($i < count($esquinas)) {
                            $bx = $esquinas[$i][0];
                            $by = $esquinas[$i][1];
                        }
                        break;
                }

                $barras[] = ['letra' => $letra, 'x' => $bx, 'y' => $by];
            }
        }

        return $barras;
    }

    /**
     * Calcula coordenadas de barras en la sección transversal.
     */
    private static function calcularCoordenadasSeccion(
        string $posicion,
        ?string $posicionComp,
        int $cantidad,
        float $innerX,
        float $innerY,
        float $innerW,
        float $innerH,
        float $radio
    ): array {
        $coords = [];
        $margen = $radio * 1.5;

        // Usar posición de composición si está disponible
        $pos = $posicionComp ?? $posicion;

        switch ($pos) {
            case 'esquina':
                // 4 esquinas
                $esquinas = [
                    ['x' => $innerX + $margen, 'y' => $innerY + $margen],
                    ['x' => $innerX + $innerW - $margen, 'y' => $innerY + $margen],
                    ['x' => $innerX + $margen, 'y' => $innerY + $innerH - $margen],
                    ['x' => $innerX + $innerW - $margen, 'y' => $innerY + $innerH - $margen],
                ];
                for ($i = 0; $i < min($cantidad, 4); $i++) {
                    $coords[] = $esquinas[$i];
                }
                break;

            case 'superior':
                // Distribuir en la parte superior
                $espacio = ($innerW - 2 * $margen) / max($cantidad - 1, 1);
                for ($i = 0; $i < $cantidad; $i++) {
                    $coords[] = [
                        'x' => $innerX + $margen + $i * $espacio,
                        'y' => $innerY + $margen,
                    ];
                }
                break;

            case 'inferior':
                // Distribuir en la parte inferior
                $espacio = ($innerW - 2 * $margen) / max($cantidad - 1, 1);
                for ($i = 0; $i < $cantidad; $i++) {
                    $coords[] = [
                        'x' => $innerX + $margen + $i * $espacio,
                        'y' => $innerY + $innerH - $margen,
                    ];
                }
                break;

            case 'lateral':
            case 'piel':
                // Distribuir en los laterales
                $porLado = ceil($cantidad / 2);
                $espacioY = ($innerH - 2 * $margen) / max($porLado + 1, 2);
                for ($i = 0; $i < $cantidad; $i++) {
                    $lado = $i % 2 == 0 ? 0 : 1; // 0=izquierda, 1=derecha
                    $posY = floor($i / 2) + 1;
                    $coords[] = [
                        'x' => $lado == 0 ? $innerX + $margen : $innerX + $innerW - $margen,
                        'y' => $innerY + $margen + $posY * $espacioY,
                    ];
                }
                break;

            default:
                // Distribución automática (superior/inferior mitad y mitad)
                $mitad = ceil($cantidad / 2);
                $espacioSup = ($innerW - 2 * $margen) / max($mitad - 1, 1);
                $espacioInf = ($innerW - 2 * $margen) / max($cantidad - $mitad - 1, 1);

                for ($i = 0; $i < $cantidad; $i++) {
                    if ($i < $mitad) {
                        $coords[] = [
                            'x' => $innerX + $margen + $i * $espacioSup,
                            'y' => $innerY + $margen,
                        ];
                    } else {
                        $idx = $i - $mitad;
                        $coords[] = [
                            'x' => $innerX + $margen + $idx * $espacioInf,
                            'y' => $innerY + $innerH - $margen,
                        ];
                    }
                }
                break;
        }

        return $coords;
    }

    /**
     * Dibuja la forma de un elemento basándose en sus dimensiones.
     */
    public static function dibujarFormaElemento(string $dimensiones, float $x, float $y, float $width, float $height, bool $esEstribo = false): string
    {
        $color = $esEstribo ? '#f59e0b' : '#2563eb';
        $dims = self::parsearDimensionesCompleto($dimensiones);

        if (empty($dims)) {
            $centerY = $y + $height / 2;
            return "<line x1=\"{$x}\" y1=\"{$centerY}\" x2=\"" . ($x + $width - 5) . "\" y2=\"{$centerY}\" stroke=\"{$color}\" stroke-width=\"2\" stroke-linecap=\"round\"/>";
        }

        $puntos = self::calcularPuntosCompleto($dims);

        if (count($puntos) < 2) {
            $centerY = $y + $height / 2;
            return "<line x1=\"{$x}\" y1=\"{$centerY}\" x2=\"" . ($x + $width - 5) . "\" y2=\"{$centerY}\" stroke=\"{$color}\" stroke-width=\"2\" stroke-linecap=\"round\"/>";
        }

        $bounds = self::calcularBounds($puntos);
        $needsRotation = $bounds['height'] > $bounds['width'];

        if ($needsRotation) {
            $cx = ($bounds['minX'] + $bounds['maxX']) / 2;
            $cy = ($bounds['minY'] + $bounds['maxY']) / 2;
            $puntos = array_map(fn($p) => self::rotarPunto($p, $cx, $cy, 90), $puntos);
            $bounds = self::calcularBounds($puntos);
        }

        $margen = 3;
        $anchoDisp = $width - (2 * $margen);
        $altoDisp = $height - (2 * $margen);

        $escalaX = $anchoDisp / max(1, $bounds['width']);
        $escalaY = $altoDisp / max(1, $bounds['height']);
        $escala = min($escalaX, $escalaY);

        $centroX = $x + $width / 2;
        $centroY = $y + $height / 2;
        $midX = ($bounds['minX'] + $bounds['maxX']) / 2;
        $midY = ($bounds['minY'] + $bounds['maxY']) / 2;

        $pathData = '';
        foreach ($puntos as $i => $punto) {
            $px = round($centroX + ($punto['x'] - $midX) * $escala, 2);
            $py = round($centroY + ($punto['y'] - $midY) * $escala, 2);
            $pathData .= ($i === 0 ? "M {$px} {$py}" : " L {$px} {$py}");
        }

        return "<path d=\"{$pathData}\" fill=\"none\" stroke=\"{$color}\" stroke-width=\"2\" stroke-linecap=\"round\" stroke-linejoin=\"round\"/>";
    }

    /**
     * Parsea dimensiones FerraWin completo.
     */
    public static function parsearDimensionesCompleto(string $dimensiones): array
    {
        if (empty(trim($dimensiones))) return [];

        $dims = [];
        $tokens = preg_split('/\s+/', trim($dimensiones));

        for ($i = 0; $i < count($tokens); $i++) {
            $token = trim($tokens[$i]);
            if (empty($token)) continue;

            if (preg_match('/^([\d.]+)r$/i', $token, $matches)) {
                $radius = (float)$matches[1];
                $arcAngle = 360;
                if ($i + 1 < count($tokens) && preg_match('/^([\d.]+)d$/i', $tokens[$i + 1], $m)) {
                    $arcAngle = (float)$m[1];
                    $i++;
                }
                $dims[] = ['type' => 'arc', 'radius' => $radius, 'arcAngle' => $arcAngle];
            } elseif (preg_match('/^([\d.]+)d$/i', $token, $matches)) {
                $dims[] = ['type' => 'turn', 'angle' => (float)$matches[1]];
            } elseif (is_numeric($token)) {
                $length = (float)$token;
                if ($length > 0) $dims[] = ['type' => 'line', 'length' => $length];
            }
        }

        return $dims;
    }

    private static function calcularPuntosCompleto(array $dims): array
    {
        $x = 0; $y = 0; $ang = 0;
        $puntos = [['x' => $x, 'y' => $y]];

        foreach ($dims as $d) {
            $type = $d['type'] ?? '';

            if ($type === 'line') {
                $length = $d['length'] ?? 0;
                if ($length <= 0) continue;
                $rad = deg2rad($ang);
                $x += cos($rad) * $length;
                $y += sin($rad) * $length;
                $puntos[] = ['x' => $x, 'y' => $y];
            } elseif ($type === 'turn') {
                $ang += $d['angle'] ?? 0;
            } elseif ($type === 'arc') {
                $radius = $d['radius'] ?? 0;
                $arcAngle = $d['arcAngle'] ?? 360;
                $radStart = deg2rad($ang + 90);
                $cx = $x + $radius * cos($radStart);
                $cy = $y + $radius * sin($radStart);
                $startAngle = atan2($y - $cy, $x - $cx);
                $endAngle = $startAngle + deg2rad($arcAngle);
                $x = $cx + $radius * cos($endAngle);
                $y = $cy + $radius * sin($endAngle);
                $ang += $arcAngle;
                $puntos[] = ['x' => $x, 'y' => $y];
            }
        }

        return $puntos;
    }

    private static function rotarPunto(array $punto, float $cx, float $cy, float $angGrados): array
    {
        $rad = deg2rad($angGrados);
        $dx = $punto['x'] - $cx;
        $dy = $punto['y'] - $cy;
        return [
            'x' => $cx + $dx * cos($rad) - $dy * sin($rad),
            'y' => $cy + $dx * sin($rad) + $dy * cos($rad),
        ];
    }

    private static function calcularBounds(array $puntos): array
    {
        if (empty($puntos)) return ['minX' => 0, 'maxX' => 0, 'minY' => 0, 'maxY' => 0, 'width' => 0, 'height' => 0];

        $minX = $maxX = $puntos[0]['x'];
        $minY = $maxY = $puntos[0]['y'];

        foreach ($puntos as $punto) {
            $minX = min($minX, $punto['x']);
            $maxX = max($maxX, $punto['x']);
            $minY = min($minY, $punto['y']);
            $maxY = max($maxY, $punto['y']);
        }

        return [
            'minX' => $minX, 'maxX' => $maxX,
            'minY' => $minY, 'maxY' => $maxY,
            'width' => max($maxX - $minX, 1),
            'height' => max($maxY - $minY, 1),
        ];
    }

    // Legacy methods
    public static function parsearDimensiones(string $dimensiones): array
    {
        return self::parsearDimensionesCompleto($dimensiones);
    }

    public static function renderizarSeccionFormas($composicion, $cotas, $armadura, $longitud): string
    {
        return '';
    }

    public static function renderizarElementosParaEtiqueta($elementosPorLetra, $opciones = []): string
    {
        return self::renderizarLeyenda(array_merge($opciones, ['elementosPorLetra' => $elementosPorLetra]));
    }

    // Métodos legacy para compatibilidad
    public static function renderizarVistaLongitudinal(array $opciones): string
    {
        return self::renderizarVista3D($opciones);
    }

    public static function renderizarSeccionTransversal(array $opciones): string
    {
        return self::renderizarSeccionDetallada($opciones);
    }

    public static function renderizarTablaElementos(array $opciones): string
    {
        return self::renderizarLeyenda($opciones);
    }
}
