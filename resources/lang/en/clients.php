<?php

return [
    'title'           => 'Clients',
    'add_client'      => 'Add Client',
    'edit_client'     => 'Edit Client',
    'clients_registered' => ':count clients registered',
    'client'          => 'Client',
    'identifier'      => 'Identifier',
    'identifier_note' => '(unique slug)',
    'business_name'   => 'Business Name',
    'priority_level'  => 'Priority Level',
    'api_key'         => 'API Key',
    'callback_url'    => 'Callback URL',
    'callback_note'   => '(ERP base URL)',
    'tickets'         => 'Tickets',

    // Priority levels
    'priority_low'    => 'Low',
    'priority_medium' => 'Medium',
    'priority_high'   => 'High',
    'priority_vip'    => 'VIP',

    // Placeholders
    'identifier_placeholder' => 'velocity',
    'name_placeholder'       => 'Velocity & DDA',
    'callback_placeholder'   => 'https://erp.yourclient.com',

    // Actions & messages
    'create_client'    => 'Create Client',
    'update_client'    => 'Update Client',
    'client_created'   => 'Client created.',
    'client_updated'   => 'Client updated.',
    'regenerate_key'   => 'Regenerate key',
    'regenerate_confirm' => 'Regenerate API key? The ERP will need the new key.',
    'key_regenerated'  => 'API key regenerated.',
    'key_help'         => 'Use "Regenerate" from the clients list to create a new key.',
    'key_copied'       => 'API key copied to clipboard.',
    'no_clients'       => 'No clients yet. Add your first client to start receiving tickets.',
    'activated'        => 'Client activated.',
    'deactivated'      => 'Client deactivated.',

    // Test connection
    'test_connection'  => 'Test connection',
    'testing'          => 'Testing...',
    'test_ok'          => 'Connection successful. The ERP responded correctly.',
    'test_unreachable' => 'Could not reach the ERP',
    'test_no_url'      => 'No callback URL configured for this client.',
];
