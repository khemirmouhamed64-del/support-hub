<?php

return [
    // Page
    'title'          => 'Ticket Reports',
    'print_subtitle' => 'Ticket Report',
    'generated'      => 'Generated:',
    'period_prefix'  => 'Period',

    // Filters
    'from'           => 'From',
    'to'             => 'To',
    'client'         => 'Client',
    'all_clients'    => 'All clients',
    'generate'       => 'Generate Report',

    // States
    'empty_hint'     => 'Select a date range and click <strong>Generate Report</strong>',
    'max_range'      => 'Maximum 1 year per query',
    'loading'        => 'Loading data...',

    // Actions
    'export_pdf'     => 'Export PDF',
    'export_csv'     => 'Export Excel (.csv)',

    // KPIs
    'total_tickets'  => 'Total Tickets',
    'in_period'      => 'in the period',
    'resolved'       => 'Resolved',
    'pending'        => 'Pending',
    'avg_resolution' => 'Avg. Resolution Time',
    'in_resolved'    => 'in resolved tickets',
    'pct_of_total'   => '% of total',
    'nd'             => 'N/A',

    // Charts
    'by_status'      => 'By Status',
    'by_incident'    => 'By Incident Type (AI)',
    'by_assignee'    => 'By Assigned Developer',
    'by_module'      => 'By Module',
    'by_client'      => 'By Client',
    'top_10'         => 'top 10',
    'tickets_label'  => 'Tickets',
    'no_data'        => 'No data in period',

    // Client table headers
    'tbl_rank'         => '#',
    'tbl_client'       => 'Client',
    'tbl_tickets'      => 'Tickets',
    'tbl_distribution' => 'Distribution',

    // Incident types
    'incident_operacion_bloqueada'   => 'Blocked Operation',
    'incident_funcionalidad_critica' => 'Critical Feature',
    'incident_funcionalidad_menor'   => 'Minor Feature',
    'incident_configuracion'         => 'Configuration',
    'incident_consulta'              => 'Inquiry',

    // CSV headers
    'csv_ticket'            => 'Ticket',
    'csv_subject'           => 'Subject',
    'csv_client'            => 'Client',
    'csv_status'            => 'Status',
    'csv_client_priority'   => 'Client Priority',
    'csv_incident_type'     => 'Incident Type',
    'csv_assigned_dev'      => 'Assigned Dev',
    'csv_module'            => 'Module',
    'csv_effective_priority'=> 'Effective Priority',
    'csv_created'           => 'Created',
    'csv_resolved'          => 'Resolved',
    'csv_resolution_hours'  => 'Resolution Hours',
    'csv_filename'          => 'ticket-report',
    'csv_unassigned'        => 'Unassigned',
    'csv_no_client'         => 'No client',

    // Validation / JS errors
    'err_select_dates'  => 'Please select both dates.',
    'err_date_order'    => 'The "From" date must be before or equal to "To".',
    'err_max_range'     => 'Maximum range is 1 year.',
    'err_loading'       => 'Error loading data.',
    'err_server_range'  => 'Maximum range is 1 year.',

    // Time format (JS)
    'time_less_1hr' => '< 1 hr',
    'time_hrs'      => 'hrs',
    'time_days'     => 'days',
];
