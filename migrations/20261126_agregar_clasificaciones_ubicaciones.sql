-- Migración: clasificación de riesgo para ubicaciones
-- Motor objetivo: MySQL 8 / MariaDB compatible
-- Ejecutar una sola vez después de respaldar la base de datos.

UPDATE ubicaciones
SET descripcion = 'sin descripción'
WHERE descripcion IS NULL
   OR TRIM(descripcion) = ''
   OR TRIM(descripcion) IN ('descripción', 'descripsion');

ALTER TABLE ubicaciones
    MODIFY COLUMN descripcion VARCHAR(250) NOT NULL DEFAULT 'sin descripción',
    ADD COLUMN tipo_infraestructura VARCHAR(64) NOT NULL DEFAULT 'otro',
    ADD COLUMN estado_infraestructura VARCHAR(64) NOT NULL DEFAULT 'en_pie_con_afectaciones_parcial',
    ADD COLUMN riesgos_directos VARCHAR(64) NOT NULL DEFAULT 'ninguno',
    ADD COLUMN riesgos_indirectos VARCHAR(64) NOT NULL DEFAULT 'ninguno',
    ADD COLUMN actividades_delictivas VARCHAR(64) NOT NULL DEFAULT 'ninguna',
    ADD COLUMN condiciones_sociales VARCHAR(64) NOT NULL DEFAULT 'ninguna';

ALTER TABLE ubicaciones
    ADD CONSTRAINT chk_ubicaciones_tipo_infraestructura
        CHECK (tipo_infraestructura IN (
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
            'otro'
        )),
    ADD CONSTRAINT chk_ubicaciones_estado_infraestructura
        CHECK (estado_infraestructura IN (
            'en_pie_con_afectaciones_parcial',
            'en_pie_con_afectaciones_estructurales_graves',
            'totalmente_colapsada',
            'en_proceso_de_demolicion',
            'en_proceso_de_reconstruccion'
        )),
    ADD CONSTRAINT chk_ubicaciones_riesgos_directos
        CHECK (riesgos_directos IN (
            'desprendimiento_de_muros',
            'desprendimiento_de_cubiertas_o_techos',
            'desprendimiento_de_fachadas',
            'desprendimiento_de_ventanas_o_vidrios',
            'riesgo_electrico_o_cables_expuestos',
            'tuberias_de_gas_expuestas',
            'todas_los_anteriores',
            'ninguno'
        )),
    ADD CONSTRAINT chk_ubicaciones_riesgos_indirectos
        CHECK (riesgos_indirectos IN (
            'acumulacion_de_aguas_estancadas',
            'acumulacion_o_vertedero_de_residuos_o_basura',
            'acumulacion_o_vertedero_de_escombros',
            'proliferacion_plagas',
            'todas_las_anteriores',
            'ninguno'
        )),
    ADD CONSTRAINT chk_ubicaciones_actividades_delictivas
        CHECK (actividades_delictivas IN (
            'expendio_de_sustancias_ilicitas',
            'comercio_informal_no_autorizado_dentro_o_fuera_de_los_bienes_afectados',
            'intimidacion_o_extorsion_a_residentes_de_la_zona',
            'vandalismo_o_hurto_a_infraestructura_o_bienes_afectados',
            'vandalismo_o_hurto_a_infraestructura_o_bienes_no_afectados',
            'hurto_a_personas',
            'todas_las_anteriores',
            'ninguna'
        )),
    ADD CONSTRAINT chk_ubicaciones_condiciones_sociales
        CHECK (condiciones_sociales IN (
            'ocupacion_de_bienes_afectados_por_habitantes_de_calle',
            'aumento_del_transito_o_permanencia_temporal_por_habitantes_de_calle',
            'consumo_de_sustancias_psicoactivas',
            'uso_de_bienes_afectados_como_bano_publico',
            'todas_las_anteriores',
            'ninguna'
        ));
