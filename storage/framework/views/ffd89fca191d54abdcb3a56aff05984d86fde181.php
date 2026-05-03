<?php $__env->startSection('page-title', __('reports.title')); ?>

<?php $__env->startPush('styles'); ?>
<style>
    .report-kpi-card {
        border: none;
        border-radius: 6px;
        box-shadow: 0 1px 4px rgba(0,0,0,.08);
    }
    .chart-card {
        border: none;
        border-radius: 6px;
        box-shadow: 0 1px 4px rgba(0,0,0,.08);
    }
    .chart-card .card-header {
        background: #fff;
        border-bottom: 1px solid #dee2e6;
        font-size: .88rem;
        padding: .5rem 1rem;
    }

    /* ── Print / PDF styles ── */
    @media  print {
        .sidebar,
        .top-navbar,
        .report-filters,
        .report-actions,
        #report-empty,
        #report-loading {
            display: none !important;
        }
        .main-content  { margin-left: 0 !important; }
        .content-wrapper { padding: 8px !important; }
        .print-header  { display: flex !important; }
        * {
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        .card {
            box-shadow: none !important;
            border: 1px solid #dee2e6 !important;
            break-inside: avoid;
        }
        .row { break-inside: avoid; }
        .page-break { break-after: always; }
    }
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>

    
    <div class="print-header" style="display:none; justify-content:space-between; align-items:flex-start; padding-bottom:12px; margin-bottom:16px; border-bottom:2px solid #1a2035;">
        <div>
            <h5 class="mb-0 font-weight-bold" style="color:#1a2035;">
                <i class="fas fa-headset mr-2"></i>Support Hub
            </h5>
            <p class="mb-0 text-muted small"><?php echo e(__('reports.print_subtitle')); ?></p>
        </div>
        <div class="text-right">
            <p class="mb-0 small font-weight-bold" id="print-range-label"></p>
            <p class="mb-0 small text-muted"><?php echo e(__('reports.generated')); ?> <?php echo e(now()->format('d/m/Y H:i')); ?></p>
        </div>
    </div>

    
    <div class="report-filters card mb-4" style="border:none; box-shadow:0 1px 4px rgba(0,0,0,.08);">
        <div class="card-body py-3">
            <div class="form-row align-items-end">
                <div class="form-group col-auto mb-0 mr-2">
                    <label class="small font-weight-bold mb-1"><?php echo e(__('reports.from')); ?></label>
                    <input type="date" id="filter-from" class="form-control form-control-sm"
                           value="<?php echo e(date('Y-m-01')); ?>" max="<?php echo e(date('Y-m-d')); ?>">
                </div>
                <div class="form-group col-auto mb-0 mr-2">
                    <label class="small font-weight-bold mb-1"><?php echo e(__('reports.to')); ?></label>
                    <input type="date" id="filter-to" class="form-control form-control-sm"
                           value="<?php echo e(date('Y-m-d')); ?>" max="<?php echo e(date('Y-m-d')); ?>">
                </div>
                <div class="form-group col-auto mb-0 mr-2">
                    <label class="small font-weight-bold mb-1"><?php echo e(__('reports.client')); ?></label>
                    <select id="filter-client" class="form-control form-control-sm" style="min-width:180px;">
                        <option value=""><?php echo e(__('reports.all_clients')); ?></option>
                        <?php $__currentLoopData = $clients; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($c->id); ?>"><?php echo e($c->business_name); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
                <div class="col-auto mb-0">
                    <button id="btn-apply" class="btn btn-primary btn-sm px-4">
                        <i class="fas fa-chart-bar mr-1"></i><?php echo e(__('reports.generate')); ?>

                    </button>
                </div>
                <div class="col-auto mb-0 ml-2" id="filter-error" style="display:none;">
                    <small class="text-danger" id="filter-error-msg"></small>
                </div>
            </div>
        </div>
    </div>

    
    <div id="report-empty" class="text-center py-5 text-muted">
        <i class="fas fa-chart-bar fa-3x mb-3 d-block" style="opacity:.2;"></i>
        <p class="mb-1"><?php echo __('reports.empty_hint'); ?></p>
        <small><?php echo e(__('reports.max_range')); ?></small>
    </div>

    
    <div id="report-loading" class="text-center py-5" style="display:none;">
        <i class="fas fa-spinner fa-spin fa-2x text-primary mb-3 d-block"></i>
        <p class="text-muted mb-0"><?php echo e(__('reports.loading')); ?></p>
    </div>

    
    <div id="report-content" style="display:none;">

        
        <div class="report-actions d-flex justify-content-between align-items-center mb-3">
            <p class="mb-0 text-muted small" id="report-range-display"></p>
            <div>
                <button id="btn-export-pdf" class="btn btn-sm btn-outline-danger mr-2">
                    <i class="fas fa-file-pdf mr-1"></i><?php echo e(__('reports.export_pdf')); ?>

                </button>
                <a id="btn-export-csv" href="#" class="btn btn-sm btn-success">
                    <i class="fas fa-file-excel mr-1"></i><?php echo e(__('reports.export_csv')); ?>

                </a>
            </div>
        </div>

        
        <div class="row mb-4">
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card report-kpi-card" style="border-left:4px solid #3498db;">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div>
                                <div class="text-uppercase font-weight-bold text-muted" style="font-size:.68rem; letter-spacing:.06em;"><?php echo e(__('reports.total_tickets')); ?></div>
                                <div class="h2 mb-0 font-weight-bold mt-1" id="kpi-total">—</div>
                                <div class="small text-muted"><?php echo e(__('reports.in_period')); ?></div>
                            </div>
                            <i class="fas fa-ticket-alt ml-auto fa-2x" style="color:#3498db; opacity:.25;"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card report-kpi-card" style="border-left:4px solid #27ae60;">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div>
                                <div class="text-uppercase font-weight-bold text-muted" style="font-size:.68rem; letter-spacing:.06em;"><?php echo e(__('reports.resolved')); ?></div>
                                <div class="h2 mb-0 font-weight-bold mt-1" id="kpi-resolved">—</div>
                                <div class="small text-muted" id="kpi-resolved-pct"></div>
                            </div>
                            <i class="fas fa-check-circle ml-auto fa-2x" style="color:#27ae60; opacity:.25;"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card report-kpi-card" style="border-left:4px solid #f39c12;">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div>
                                <div class="text-uppercase font-weight-bold text-muted" style="font-size:.68rem; letter-spacing:.06em;"><?php echo e(__('reports.pending')); ?></div>
                                <div class="h2 mb-0 font-weight-bold mt-1" id="kpi-pending">—</div>
                                <div class="small text-muted" id="kpi-pending-pct"></div>
                            </div>
                            <i class="fas fa-clock ml-auto fa-2x" style="color:#f39c12; opacity:.25;"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card report-kpi-card" style="border-left:4px solid #9b59b6;">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div>
                                <div class="text-uppercase font-weight-bold text-muted" style="font-size:.68rem; letter-spacing:.06em;"><?php echo e(__('reports.avg_resolution')); ?></div>
                                <div class="h2 mb-0 font-weight-bold mt-1" id="kpi-avg">—</div>
                                <div class="small text-muted"><?php echo e(__('reports.in_resolved')); ?></div>
                            </div>
                            <i class="fas fa-hourglass-half ml-auto fa-2x" style="color:#9b59b6; opacity:.25;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        
        <div class="row mb-4">
            <div class="col-md-4 mb-3">
                <div class="card chart-card h-100">
                    <div class="card-header">
                        <i class="fas fa-circle-notch mr-1 text-muted"></i><strong><?php echo e(__('reports.by_status')); ?></strong>
                    </div>
                    <div class="card-body d-flex justify-content-center align-items-center">
                        <div style="position:relative; width:230px; height:230px;">
                            <canvas id="chart-status"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-8 mb-3">
                <div class="card chart-card h-100">
                    <div class="card-header">
                        <i class="fas fa-robot mr-1 text-muted"></i><strong><?php echo e(__('reports.by_incident')); ?></strong>
                    </div>
                    <div class="card-body">
                        <div style="position:relative; height:230px;">
                            <canvas id="chart-incident"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="page-break"></div>

        
        <div class="row mb-4">
            <div class="col-md-6 mb-3">
                <div class="card chart-card h-100">
                    <div class="card-header">
                        <i class="fas fa-user-cog mr-1 text-muted"></i><strong><?php echo e(__('reports.by_assignee')); ?></strong>
                    </div>
                    <div class="card-body">
                        <div style="position:relative; height:230px;">
                            <canvas id="chart-assignee"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 mb-3">
                <div class="card chart-card h-100">
                    <div class="card-header">
                        <i class="fas fa-cubes mr-1 text-muted"></i><strong><?php echo e(__('reports.by_module')); ?></strong>
                    </div>
                    <div class="card-body">
                        <div style="position:relative; height:230px;">
                            <canvas id="chart-module"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        
        <div class="row mb-4">
            <div class="col-12">
                <div class="card chart-card">
                    <div class="card-header">
                        <i class="fas fa-building mr-1 text-muted"></i>
                        <strong><?php echo e(__('reports.by_client')); ?></strong>
                        <small class="text-muted ml-1">(<?php echo e(__('reports.top_10')); ?>)</small>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-sm mb-0" id="table-clients">
                            <thead class="thead-light">
                                <tr>
                                    <th style="width:4%"><?php echo e(__('reports.tbl_rank')); ?></th>
                                    <th><?php echo e(__('reports.tbl_client')); ?></th>
                                    <th class="text-right" style="width:14%"><?php echo e(__('reports.tbl_tickets')); ?></th>
                                    <th style="width:48%"><?php echo e(__('reports.tbl_distribution')); ?></th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>

<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<?php
$statusColors = [
    __('tickets.col_to_do')             => '#3498db',
    __('tickets.col_in_progress')       => '#f39c12',
    __('tickets.col_blocked')           => '#e74c3c',
    __('tickets.col_code_review')       => '#9b59b6',
    __('tickets.col_qa_testing')        => '#1abc9c',
    __('tickets.col_ready_for_release') => '#2ecc71',
    __('tickets.col_done')              => '#27ae60',
];
$incidentColors = [
    __('reports.incident_operacion_bloqueada')   => '#dc3545',
    __('reports.incident_funcionalidad_critica') => '#ffc107',
    __('reports.incident_funcionalidad_menor')   => '#17a2b8',
    __('reports.incident_configuracion')         => '#6c757d',
    __('reports.incident_consulta')              => '#adb5bd',
];
$reportLang = [
    'period_prefix'    => __('reports.period_prefix'),
    'pct_of_total'     => __('reports.pct_of_total'),
    'nd'               => __('reports.nd'),
    'tickets_label'    => __('reports.tickets_label'),
    'no_data'          => __('reports.no_data'),
    'err_select_dates' => __('reports.err_select_dates'),
    'err_date_order'   => __('reports.err_date_order'),
    'err_max_range'    => __('reports.err_max_range'),
    'err_loading'      => __('reports.err_loading'),
    'time_less_1hr'    => __('reports.time_less_1hr'),
    'time_hrs'         => __('reports.time_hrs'),
    'time_days'        => __('reports.time_days'),
    'status_colors'    => $statusColors,
    'incident_colors'  => $incidentColors,
];
?>
<script>
// Translations passed from Blade — never hardcode strings in JS
var LANG = <?php echo json_encode($reportLang, 15, 512) ?>;
</script>
<script>
(function () {
    'use strict';

    var chartStatus   = null;
    var chartIncident = null;
    var chartAssignee = null;
    var chartModule   = null;
    var currentParams = {};

    var PALETTE = [
        '#4e73df','#1cc88a','#36b9cc','#f6c23e','#e74a3b',
        '#858796','#5a5c69','#2e59d9','#17a673','#2c9faf',
    ];

    $(document).ready(function () {
        $('#btn-apply').on('click', loadReport);

        $('#btn-export-pdf').on('click', function () {
            window.print();
        });

        $('#filter-from, #filter-to').on('keydown', function (e) {
            if (e.key === 'Enter') { loadReport(); }
        });
    });

    function loadReport() {
        var from     = $('#filter-from').val();
        var to       = $('#filter-to').val();
        var clientId = $('#filter-client').val();

        $('#filter-error').hide();

        if (!from || !to) {
            showError(LANG.err_select_dates);
            return;
        }
        if (from > to) {
            showError(LANG.err_date_order);
            return;
        }
        var msPerYear = 366 * 24 * 60 * 60 * 1000;
        if ((new Date(to) - new Date(from)) > msPerYear) {
            showError(LANG.err_max_range);
            return;
        }

        currentParams = { date_from: from, date_to: to };
        if (clientId) { currentParams.client_id = clientId; }

        $('#report-empty').hide();
        $('#report-content').hide();
        $('#report-loading').show();

        $.getJSON('/reports/data', currentParams)
            .done(function (data) {
                $('#report-loading').hide();
                renderReport(data, from, to);
                $('#report-content').show();

                $('#btn-export-csv').attr('href', '/reports/export-csv?' + $.param(currentParams));

                var rangeText = LANG.period_prefix + ': ' + fmtDate(from) + ' – ' + fmtDate(to);
                $('#print-range-label').text(rangeText);
                $('#report-range-display').html('<i class="fas fa-calendar-alt mr-1"></i>' + rangeText);
            })
            .fail(function (xhr) {
                $('#report-loading').hide();
                $('#report-empty').show();
                var msg = LANG.err_loading;
                if (xhr.responseJSON && xhr.responseJSON.error) { msg = xhr.responseJSON.error; }
                showError(msg);
            });
    }

    function renderReport(data, from, to) {
        var total    = data.kpis.total;
        var resolved = data.kpis.resolved;
        var pending  = data.kpis.pending;

        $('#kpi-total').text(total);
        $('#kpi-resolved').text(resolved);
        $('#kpi-pending').text(pending);

        if (total > 0) {
            $('#kpi-resolved-pct').text(Math.round(resolved / total * 100) + LANG.pct_of_total);
            $('#kpi-pending-pct').text(Math.round(pending  / total * 100) + LANG.pct_of_total);
        } else {
            $('#kpi-resolved-pct, #kpi-pending-pct').text('');
        }

        $('#kpi-avg').text(data.kpis.avgHours !== null ? fmtTime(data.kpis.avgHours) : LANG.nd);

        renderStatusChart(data.by_status);
        renderIncidentChart(data.by_incident);
        renderAssigneeChart(data.by_assignee);
        renderModuleChart(data.by_module);
        renderClientTable(data.by_client, total);
    }

    function renderStatusChart(byStatus) {
        var labels = Object.keys(byStatus);
        var values = Object.values(byStatus);
        var colors = labels.map(function (l) { return LANG.status_colors[l] || '#adb5bd'; });

        if (chartStatus) { chartStatus.destroy(); }
        chartStatus = new Chart($('#chart-status')[0].getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{ data: values, backgroundColor: colors, borderWidth: 2, borderColor: '#fff' }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '60%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { font: { size: 10 }, padding: 8, boxWidth: 12 }
                    }
                }
            }
        });
    }

    function renderIncidentChart(byIncident) {
        var labels = Object.keys(byIncident);
        var values = Object.values(byIncident);
        var colors = labels.map(function (l) { return LANG.incident_colors[l] || '#6c757d'; });

        if (chartIncident) { chartIncident.destroy(); }
        chartIncident = new Chart($('#chart-incident')[0].getContext('2d'), {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: LANG.tickets_label,
                    data: values,
                    backgroundColor: colors,
                    borderRadius: 4,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, ticks: { stepSize: 1, precision: 0 } }
                }
            }
        });
    }

    function renderAssigneeChart(byAssignee) {
        var labels = byAssignee.map(function (x) { return x.name; });
        var values = byAssignee.map(function (x) { return x.count; });
        var colors = labels.map(function (_, i) { return PALETTE[i % PALETTE.length]; });

        if (chartAssignee) { chartAssignee.destroy(); }
        chartAssignee = new Chart($('#chart-assignee')[0].getContext('2d'), {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: LANG.tickets_label,
                    data: values,
                    backgroundColor: colors,
                    borderRadius: 4,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                plugins: { legend: { display: false } },
                scales: {
                    x: { beginAtZero: true, ticks: { stepSize: 1, precision: 0 } }
                }
            }
        });
    }

    function renderModuleChart(byModule) {
        var moduleColors = [
            '#667eea','#764ba2','#43e97b','#4facfe','#f093fb',
            '#fa709a','#fee140','#30cfd0','#a18cd1','#fda085',
        ];
        var labels = Object.keys(byModule);
        var values = Object.values(byModule);
        var colors = labels.map(function (_, i) { return moduleColors[i % moduleColors.length]; });

        if (chartModule) { chartModule.destroy(); }
        chartModule = new Chart($('#chart-module')[0].getContext('2d'), {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: LANG.tickets_label,
                    data: values,
                    backgroundColor: colors,
                    borderRadius: 4,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                plugins: { legend: { display: false } },
                scales: {
                    x: { beginAtZero: true, ticks: { stepSize: 1, precision: 0 } }
                }
            }
        });
    }

    function renderClientTable(byClient, total) {
        var tbody   = $('#table-clients tbody');
        var maxCnt  = byClient.length ? Math.max.apply(null, byClient.map(function (x) { return x.count; })) : 1;

        tbody.empty();

        if (!byClient.length) {
            tbody.append('<tr><td colspan="4" class="text-center text-muted py-3">' + LANG.no_data + '</td></tr>');
            return;
        }

        $.each(byClient, function (i, row) {
            var pct      = total > 0 ? Math.round(row.count / total * 100) : 0;
            var barWidth = maxCnt  > 0 ? Math.round(row.count / maxCnt  * 100) : 0;
            var color    = PALETTE[i % PALETTE.length];

            tbody.append(
                '<tr>' +
                '<td class="text-muted small align-middle">' + (i + 1) + '</td>' +
                '<td class="align-middle font-weight-bold">' + escHtml(row.name) + '</td>' +
                '<td class="text-right align-middle">' + row.count +
                    ' <small class="text-muted font-weight-normal">(' + pct + '%)</small></td>' +
                '<td class="align-middle">' +
                    '<div style="background:#e9ecef; border-radius:4px; height:10px; overflow:hidden;">' +
                        '<div style="width:' + barWidth + '%; height:100%; background:' + color + '; border-radius:4px;"></div>' +
                    '</div>' +
                '</td>' +
                '</tr>'
            );
        });
    }

    function fmtTime(hours) {
        if (hours === null || hours === undefined) return LANG.nd;
        if (hours < 1)  return LANG.time_less_1hr;
        if (hours < 24) return hours.toFixed(1) + ' ' + LANG.time_hrs;
        return (hours / 24).toFixed(1) + ' ' + LANG.time_days;
    }

    function fmtDate(ymd) {
        var p = ymd.split('-');
        return p.length === 3 ? p[2] + '/' + p[1] + '/' + p[0] : ymd;
    }

    function showError(msg) {
        $('#filter-error-msg').text(msg);
        $('#filter-error').show();
    }

    function escHtml(str) {
        if (!str) return '';
        var d = document.createElement('div');
        d.appendChild(document.createTextNode(str));
        return d.innerHTML;
    }

})();
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\laragon\www\support-hub\resources\views/reports/index.blade.php ENDPATH**/ ?>