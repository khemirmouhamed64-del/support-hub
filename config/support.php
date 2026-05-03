<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Incident Types
    |--------------------------------------------------------------------------
    | Used by the AI classifier (Phase 2) and manual override (Phase 3).
    | incident_weight drives effective_priority = client_weight * incident_weight
    */
    'incident_types' => [
        'operacion_bloqueada'   => ['label' => 'Operación Bloqueada',  'weight' => 1, 'badge' => 'danger'],
        'funcionalidad_critica' => ['label' => 'Funcionalidad Crítica', 'weight' => 2, 'badge' => 'warning'],
        'funcionalidad_menor'   => ['label' => 'Funcionalidad Menor',  'weight' => 3, 'badge' => 'info'],
        'configuracion'         => ['label' => 'Configuración',         'weight' => 4, 'badge' => 'default'],
        'consulta'              => ['label' => 'Consulta',              'weight' => 5, 'badge' => 'default'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Client Priority Weights
    |--------------------------------------------------------------------------
    | Maps the client_priority enum to a numeric weight for the formula.
    */
    'client_priority_weights' => [
        'vip'    => 1,
        'high'   => 2,
        'medium' => 3,
        'low'    => 4,
    ],

    /*
    |--------------------------------------------------------------------------
    | AI Fallback
    |--------------------------------------------------------------------------
    | Used when Gemini fails or returns invalid output.
    | funcionalidad_menor (weight 3) is the neutral midpoint — not too urgent,
    | not invisible. ai_status will be set to 'failed' for manual review.
    */
    'ai_fallback_incident_type' => 'funcionalidad_menor',
    'ai_fallback_weight'        => 3,

    /*
    |--------------------------------------------------------------------------
    | System Modules
    |--------------------------------------------------------------------------
    | Modules available when creating or filtering tickets.
    | Customize these to match the modules of your ERP or business system.
    | The 'key' is stored in the database; 'label' is shown in the UI.
    */
    'modules' => [
        ['key' => 'pos',           'label' => 'POS / Point of Sale'],
        ['key' => 'sales',         'label' => 'Sales / Invoicing'],
        ['key' => 'purchases',     'label' => 'Purchases'],
        ['key' => 'inventory',     'label' => 'Inventory'],
        ['key' => 'accounting',    'label' => 'Accounting'],
        ['key' => 'banking',       'label' => 'Banking'],
        ['key' => 'crm',           'label' => 'CRM'],
        ['key' => 'hr',            'label' => 'HR'],
        ['key' => 'reports',       'label' => 'Reports'],
        ['key' => 'configuration', 'label' => 'Configuration'],
    ],

];
