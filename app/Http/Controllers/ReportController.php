<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Ticket;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index()
    {
        // Include all clients (even inactive) for historical reporting
        $clients = Client::orderBy('business_name')->get();

        return view('reports.index', compact('clients'));
    }

    public function data(Request $request)
    {
        $request->validate([
            'date_from' => 'required|date',
            'date_to'   => 'required|date|after_or_equal:date_from',
            'client_id' => 'nullable|integer',
        ]);

        $from = Carbon::parse($request->date_from)->startOfDay();
        $to   = Carbon::parse($request->date_to)->endOfDay();

        if ($from->diffInDays($to) > 366) {
            return response()->json(['error' => __('reports.err_server_range')], 422);
        }

        $query = Ticket::with(['client', 'assignee'])
            ->whereBetween('created_at', [$from, $to]);

        if ($request->filled('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        $tickets = $query->get();

        // --- KPIs ---
        $total    = $tickets->count();
        $resolved = $tickets->where('board_column', 'done')->count();
        $pending  = $total - $resolved;

        $resolvedWithTime = $tickets->filter(function ($t) {
            return $t->resolved_at !== null && $t->board_column === 'done';
        });

        $avgHours = null;
        if ($resolvedWithTime->count() > 0) {
            $totalHours = $resolvedWithTime->sum(function ($t) {
                return $t->created_at->diffInHours($t->resolved_at);
            });
            $avgHours = round($totalHours / $resolvedWithTime->count(), 1);
        }

        // --- By board column ---
        $byStatus = [];
        foreach (Ticket::BOARD_COLUMNS as $col) {
            $byStatus[__('tickets.col_' . $col)] = $tickets->where('board_column', $col)->count();
        }

        // --- By incident type ---
        $byIncident = [];
        foreach (array_keys(config('support.incident_types')) as $key) {
            $byIncident[__('reports.incident_' . $key)] = $tickets->where('incident_type', $key)->count();
        }

        // --- By issue_priority ---
        $byPriority = [];
        foreach (['critical' => 'Critical', 'high' => 'High', 'medium' => 'Medium', 'low' => 'Low'] as $key => $label) {
            $byPriority[$label] = $tickets->where('issue_priority', $key)->count();
        }

        // --- By client (top 10) ---
        $byClient = $tickets
            ->groupBy('client_id')
            ->map(function ($group) {
                return [
                    'name'  => optional($group->first()->client)->business_name ?? __('reports.csv_no_client'),
                    'count' => $group->count(),
                ];
            })
            ->sortByDesc('count')
            ->take(10)
            ->values();

        // --- By assignee ---
        $byAssignee = $tickets
            ->groupBy(function ($t) {
                return $t->assigned_to ?? 0;
            })
            ->map(function ($group) {
                return [
                    'name'  => optional($group->first()->assignee)->name ?? __('reports.csv_unassigned'),
                    'count' => $group->count(),
                ];
            })
            ->sortByDesc('count')
            ->values();

        // --- By module ---
        $moduleLabels = array_column(config('support.modules', []), 'label', 'key');

        $byModuleRaw = $tickets->groupBy('module')->map(function ($g) { return $g->count(); })->sortDesc();
        $byModule    = [];
        foreach ($byModuleRaw as $key => $count) {
            $label            = $moduleLabels[$key] ?? ucfirst(str_replace('_', ' ', $key ?? '—'));
            $byModule[$label] = $count;
        }

        return response()->json([
            'kpis'        => compact('total', 'resolved', 'pending', 'avgHours'),
            'by_status'   => $byStatus,
            'by_incident' => $byIncident,
            'by_priority' => $byPriority,
            'by_client'   => $byClient,
            'by_assignee' => $byAssignee,
            'by_module'   => $byModule,
        ]);
    }

    public function exportCsv(Request $request)
    {
        $request->validate([
            'date_from' => 'required|date',
            'date_to'   => 'required|date|after_or_equal:date_from',
            'client_id' => 'nullable|integer',
        ]);

        $from = Carbon::parse($request->date_from)->startOfDay();
        $to   = Carbon::parse($request->date_to)->endOfDay();

        if ($from->diffInDays($to) > 366) {
            abort(422, __('reports.err_server_range'));
        }

        $query = Ticket::with(['client', 'assignee'])
            ->whereBetween('created_at', [$from, $to])
            ->orderBy('created_at');

        if ($request->filled('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        $tickets       = $query->get();
        $incidentTypes = config('support.incident_types');
        $columnLabels = [];
        foreach (Ticket::BOARD_COLUMNS as $col) {
            $columnLabels[$col] = __('tickets.col_' . $col);
        }

        $filename = __('reports.csv_filename') . '-' . $request->date_from . '-' . $request->date_to . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($tickets, $incidentTypes, $columnLabels) {
            $handle = fopen('php://output', 'w');
            fputs($handle, "\xEF\xBB\xBF"); // UTF-8 BOM so Excel opens it correctly

            fputcsv($handle, [
                __('reports.csv_ticket'),
                __('reports.csv_subject'),
                __('reports.csv_client'),
                __('reports.csv_status'),
                __('reports.csv_client_priority'),
                __('reports.csv_incident_type'),
                __('reports.csv_assigned_dev'),
                __('reports.csv_module'),
                __('reports.csv_effective_priority'),
                __('reports.csv_created'),
                __('reports.csv_resolved'),
                __('reports.csv_resolution_hours'),
            ]);

            foreach ($tickets as $t) {
                $incidentLabel = $t->incident_type
                    ? __('reports.incident_' . $t->incident_type)
                    : '—';

                fputcsv($handle, [
                    $t->ticket_number,
                    $t->subject,
                    optional($t->client)->business_name ?? '—',
                    $columnLabels[$t->board_column] ?? $t->board_column,
                    strtoupper($t->issue_priority ?? '—'),
                    $incidentLabel,
                    optional($t->assignee)->name ?? __('reports.csv_unassigned'),
                    ucfirst(str_replace('_', ' ', $t->module ?? '—')),
                    $t->effective_priority ?? '—',
                    $t->created_at->format('d/m/Y H:i'),
                    $t->resolved_at ? $t->resolved_at->format('d/m/Y H:i') : '',
                    $t->resolved_at ? $t->created_at->diffInHours($t->resolved_at) : '',
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}
