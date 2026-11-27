<?php

declare(strict_types=1);

namespace UbicacionesFuncionalidad\Dominio\ClasificacionesDeRiesgo;

/**
 * Catálogo válido de clasificaciones utilizadas al crear una ubicación.
 */
enum OpcionesNuevaUbicacion: string
{
    case TIPO_INFRAESTRUCTURA   = 'tipo_infraestructura';
    case ESTADO_INFRAESTRUCTURA = 'estado_infraestructura';
    case RIESGOS_DIRECTOS       = 'riesgos_directos';
    case RIESGOS_INDIRECTOS     = 'riesgos_indirectos';
    case ACTIVIDADES_DELICTIVAS = 'actividades_delictivas';
    case CONDICIONES_SOCIALES   = 'condiciones_sociales';

    /**
     * Devuelve la lista de clasificacionUbicacion granulares concedidos a cada rol.
     */
    public function clasificacionUbicacion(): array
    {
        return match ($this) {
            self::TIPO_INFRAESTRUCTURA => [
                'vivienda_unifamiliar',
                'edificio_residencial_multifamiliar',
                'edificio_gubernamental',
                'centro_comercial',
                'local_comercial',
                'lugar_de_culto_religioso',
                'institucion_educativa',
                'hospital_o_centro_de_salud',
                'bodega_industrial',
                'parqueadero_multinivel',
                'centro_cultural_o_deportivo',
                'puente_peatonal',
                'puente_vehicular',
                'otro',
            ],

            self::ESTADO_INFRAESTRUCTURA => [
                'en_pie_con_afectaciones_parcial',
                'en_pie_con_afectaciones_estructurales_graves',
                'totalmente_colapsada',
                'en_proceso_de_demolicion',
                'en_proceso_de_reconstruccion',
            ],

            self::RIESGOS_DIRECTOS => [
                'desprendimiento_de_muros',
                'desprendimiento_de_cubiertas_o_techos',
                'desprendimiento_de_fachadas',
                'desprendimiento_de_ventanas_o_vidrios',
                'riesgo_electrico_o_cables_expuestos',
                'tuberias_de_gas_expuestas',
                'todas_los_anteriores',
                'ninguno',
            ],

            self::RIESGOS_INDIRECTOS => [
                'acumulacion_deaguas_estancadas',
                'acumulacion_o_vertedero_de_residuos_o_basura',
                'acumulacion_o_vertedero_de_escombros',
                'proliferacion_plagas',
                'todas_las_anteriores',
                'ninguno',
            ],

            self::ACTIVIDADES_DELICTIVAS => [
                'expendio_de_sustancias_ilicitas',
                'comercio_informal_no_autorizado_dentro_o_fuera_de_los_bienes_afectados',
                'intimidacion_o_extorsion_a_residentes_de_la_zona',
                'vandalismo_o_hurto_a_infraestructura_o_bienes_afectados',
                'vandalismo_o_hurto_a_infraestructura_o_bienes_no_afectados',
                'hurto_a_personas',
                'todas_las_anteriores',
                'ninguna',
            ],

            self::CONDICIONES_SOCIALES => [
                'ocupacion_de_bienes_afectados_por_habitantes_de_calle',
                'aumento_del_transito_o_permanencia_temporal_por_habitantes_de_calle',
                'consumo_de_sustancias_psicoactivas',
                'uso_de_bienes_afectados_como_bano_publico',
                'todas_las_anteriores',
                'ninguna',
            ],

        };
    }
}
