<?php

return [
    'title'           => 'Clientes',
    'add_client'      => 'Agregar cliente',
    'edit_client'     => 'Editar cliente',
    'clients_registered' => ':count clientes registrados',
    'client'          => 'Cliente',
    'identifier'      => 'Identificador',
    'identifier_note' => '(slug unico)',
    'business_name'   => 'Nombre comercial',
    'priority_level'  => 'Nivel de prioridad',
    'api_key'         => 'API Key',
    'callback_url'    => 'URL de Callback',
    'callback_note'   => '(URL base del ERP)',
    'tickets'         => 'Tickets',

    // Priority levels
    'priority_low'    => 'Baja',
    'priority_medium' => 'Media',
    'priority_high'   => 'Alta',
    'priority_vip'    => 'VIP',

    // Placeholders
    'identifier_placeholder' => 'velocity',
    'name_placeholder'       => 'Velocity & DDA',
    'callback_placeholder'   => 'https://erp.tucliente.com',

    // Actions & messages
    'create_client'    => 'Crear cliente',
    'update_client'    => 'Actualizar cliente',
    'client_created'   => 'Cliente creado.',
    'client_updated'   => 'Cliente actualizado.',
    'regenerate_key'   => 'Regenerar key',
    'regenerate_confirm' => 'Regenerar API key? El ERP necesitara la nueva key.',
    'key_regenerated'  => 'API key regenerada.',
    'key_help'         => 'Use "Regenerar" desde la lista de clientes para crear una nueva key.',
    'key_copied'       => 'API key copiada al portapapeles.',
    'no_clients'       => 'Aun no hay clientes. Agrega el primero para comenzar a recibir tickets.',
    'activated'        => 'Cliente activado.',
    'deactivated'      => 'Cliente desactivado.',

    // Test connection
    'test_connection'  => 'Probar conexión',
    'testing'          => 'Probando...',
    'test_ok'          => 'Conexión exitosa. El ERP respondió correctamente.',
    'test_unreachable' => 'No se pudo conectar al ERP',
    'test_no_url'      => 'Este cliente no tiene URL de callback configurada.',
];
