<?php

return [
    // Page
    'title'          => 'Reporte de Tickets',
    'print_subtitle' => 'Reporte de Tickets',
    'generated'      => 'Generado:',
    'period_prefix'  => 'Período',

    // Filters
    'from'           => 'Desde',
    'to'             => 'Hasta',
    'client'         => 'Cliente',
    'all_clients'    => 'Todos los clientes',
    'generate'       => 'Generar reporte',

    // States
    'empty_hint'     => 'Selecciona el rango de fechas y haz clic en <strong>Generar reporte</strong>',
    'max_range'      => 'Máximo 1 año por consulta',
    'loading'        => 'Cargando datos...',

    // Actions
    'export_pdf'     => 'Exportar PDF',
    'export_csv'     => 'Exportar Excel (.csv)',

    // KPIs
    'total_tickets'  => 'Total tickets',
    'in_period'      => 'en el período',
    'resolved'       => 'Resueltos',
    'pending'        => 'Pendientes',
    'avg_resolution' => 'Tiempo prom. resolución',
    'in_resolved'    => 'en tickets resueltos',
    'pct_of_total'   => '% del total',
    'nd'             => 'N/D',

    // Charts
    'by_status'      => 'Por estado',
    'by_incident'    => 'Por tipo de incidencia (IA)',
    'by_assignee'    => 'Por desarrollador asignado',
    'by_module'      => 'Por módulo',
    'by_client'      => 'Por cliente',
    'top_10'         => 'top 10',
    'tickets_label'  => 'Tickets',
    'no_data'        => 'Sin datos en el período',

    // Client table headers
    'tbl_rank'         => '#',
    'tbl_client'       => 'Cliente',
    'tbl_tickets'      => 'Tickets',
    'tbl_distribution' => 'Distribución',

    // Incident types
    'incident_operacion_bloqueada'   => 'Operación Bloqueada',
    'incident_funcionalidad_critica' => 'Funcionalidad Crítica',
    'incident_funcionalidad_menor'   => 'Funcionalidad Menor',
    'incident_configuracion'         => 'Configuración',
    'incident_consulta'              => 'Consulta',

    // CSV headers
    'csv_ticket'            => 'Ticket',
    'csv_subject'           => 'Asunto',
    'csv_client'            => 'Cliente',
    'csv_status'            => 'Estado',
    'csv_client_priority'   => 'Prioridad cliente',
    'csv_incident_type'     => 'Tipo incidencia',
    'csv_assigned_dev'      => 'Dev asignado',
    'csv_module'            => 'Módulo',
    'csv_effective_priority'=> 'Prioridad efectiva',
    'csv_created'           => 'Creado',
    'csv_resolved'          => 'Resuelto',
    'csv_resolution_hours'  => 'Horas resolución',
    'csv_filename'          => 'reporte-tickets',
    'csv_unassigned'        => 'Sin asignar',
    'csv_no_client'         => 'Sin cliente',

    // Validation / JS errors
    'err_select_dates'  => 'Selecciona ambas fechas.',
    'err_date_order'    => 'La fecha "Desde" debe ser anterior o igual a "Hasta".',
    'err_max_range'     => 'El rango máximo es 1 año.',
    'err_loading'       => 'Error al cargar los datos.',
    'err_server_range'  => 'El rango máximo es 1 año.',

    // Time format (JS)
    'time_less_1hr' => '< 1 hr',
    'time_hrs'      => 'hrs',
    'time_days'     => 'días',
];
