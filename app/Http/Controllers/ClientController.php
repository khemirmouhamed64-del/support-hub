<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function index()
    {
        $clients = Client::withCount('tickets')->orderBy('business_name')->get();
        return view('clients.index', compact('clients'));
    }

    public function create()
    {
        return view('clients.form', ['client' => new Client()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'client_identifier' => 'required|string|max:100|unique:clients',
            'business_name'     => 'required|string|max:255',
            'api_callback_url'  => 'nullable|url|max:500',
            'priority_level'    => 'required|in:low,medium,high,vip',
        ]);

        $data['api_key'] = Client::generateApiKey();

        Client::create($data);

        return redirect()->route('clients.index')->with('success', __('clients.client_created'));
    }

    public function edit(Client $client)
    {
        return view('clients.form', compact('client'));
    }

    public function update(Request $request, Client $client)
    {
        $data = $request->validate([
            'client_identifier' => 'required|string|max:100|unique:clients,client_identifier,' . $client->id,
            'business_name'     => 'required|string|max:255',
            'api_callback_url'  => 'nullable|url|max:500',
            'priority_level'    => 'required|in:low,medium,high,vip',
        ]);

        $client->update($data);

        return redirect()->route('clients.index')->with('success', __('clients.client_updated'));
    }

    public function toggleActive(Client $client)
    {
        $client->update(['is_active' => !$client->is_active]);

        return back()->with('success', $client->is_active ? __('clients.activated') : __('clients.deactivated'));
    }

    public function regenerateKey(Client $client)
    {
        $client->update(['api_key' => Client::generateApiKey()]);

        return back()->with('success', __('clients.key_regenerated'));
    }

    /**
     * AJAX: test the HTTP connection from the Hub to the client's ERP.
     */
    public function testConnection(Client $client)
    {
        if (empty($client->api_callback_url)) {
            return response()->json([
                'success' => false,
                'message' => __('clients.test_no_url'),
            ]);
        }

        try {
            $http = new \GuzzleHttp\Client(['timeout' => 8, 'verify' => false]);
            $response = $http->get(rtrim($client->api_callback_url, '/') . '/api/support/ping');
            $body = json_decode((string) $response->getBody(), true);

            return response()->json([
                'success' => true,
                'message' => __('clients.test_ok'),
                'details' => $body,
            ]);
        } catch (\GuzzleHttp\Exception\ConnectException $e) {
            return response()->json([
                'success' => false,
                'message' => __('clients.test_unreachable') . ': ' . $client->api_callback_url,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
