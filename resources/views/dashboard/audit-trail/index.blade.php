@extends('dashboard.layouts.wrapper')

@section('title', __('Audit Trail'))

@section('content')
<div class="content-wrapper">
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <h5 class="mb-0">{{ __('Audit Trail') }}</h5>
                            <small class="text-muted">
                                {{ __('Catatan seluruh aktivitas user di perusahaan Anda.') }}
                            </small>
                        </div>
                        <span class="badge bg-light text-dark border">
                            <i class="fas fa-lock me-1"></i>
                            {{ __('Hanya Administrator') }}
                        </span>
                    </div>

                    <div class="card-body">

                        {{-- FILTER --}}
                        <form id="audit-filter" class="row g-2 align-items-end mb-4">
                            <div class="col-6 col-md-2">
                                <label class="form-label small mb-1">{{ __('Dari Tanggal') }}</label>
                                <input type="date" name="date_from" class="form-control form-control-sm"
                                       value="{{ now()->subDays(30)->format('Y-m-d') }}">
                            </div>
                            <div class="col-6 col-md-2">
                                <label class="form-label small mb-1">{{ __('Sampai Tanggal') }}</label>
                                <input type="date" name="date_to" class="form-control form-control-sm"
                                       value="{{ now()->format('Y-m-d') }}">
                            </div>
                            <div class="col-6 col-md-2">
                                <label class="form-label small mb-1">{{ __('Modul') }}</label>
                                <select name="module" class="form-select form-select-sm">
                                    <option value="">{{ __('Semua Modul') }}</option>
                                    @foreach($modules as $key => $label)
                                        <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-6 col-md-2">
                                <label class="form-label small mb-1">{{ __('Aktivitas') }}</label>
                                <select name="event" class="form-select form-select-sm">
                                    <option value="">{{ __('Semua Aktivitas') }}</option>
                                    @foreach($events as $key => $label)
                                        <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-6 col-md-2">
                                <label class="form-label small mb-1">{{ __('User') }}</label>
                                <select name="user_id" class="form-select form-select-sm">
                                    <option value="">{{ __('Semua User') }}</option>
                                    @foreach($users as $u)
                                        <option value="{{ $u->id }}">{{ $u->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-6 col-md-1">
                                <label class="form-label small mb-1">{{ __('Sumber') }}</label>
                                <select name="source" class="form-select form-select-sm">
                                    <option value="">{{ __('Semua') }}</option>
                                    <option value="web">{{ __('Web') }}</option>
                                    <option value="api">{{ __('Mobile') }}</option>
                                    <option value="system">{{ __('Sistem') }}</option>
                                </select>
                            </div>
                            <div class="col-12 col-md-1 d-flex gap-1">
                                <button type="submit" class="btn btn-sm btn-primary flex-fill" title="{{ __('Terapkan filter') }}">
                                    <i class="fas fa-filter"></i>
                                </button>
                                <button type="button" id="audit-reset" class="btn btn-sm btn-light flex-fill" title="{{ __('Reset filter') }}">
                                    <i class="fas fa-undo"></i>
                                </button>
                            </div>
                        </form>

                        <div class="table-responsive">
                            <table class="table table-hover align-middle" id="table-audit-trail" width="100%">
                                <thead>
                                    <tr>
                                        <th width="5%">{{ __('No') }}</th>
                                        <th width="150">{{ __('Waktu') }}</th>
                                        <th>{{ __('User') }}</th>
                                        <th>{{ __('Aktivitas') }}</th>
                                        <th>{{ __('Modul') }}</th>
                                        <th>{{ __('Keterangan') }}</th>
                                        <th>{{ __('IP') }}</th>
                                        <th width="60">{{ __('Detail') }}</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- MODAL DETAIL --}}
<div class="modal fade" id="modal-audit-detail" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('Detail Aktivitas') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Tutup') }}"></button>
            </div>
            <div class="modal-body" id="audit-detail-body">
                <div class="text-center text-muted py-4">{{ __('Memuat...') }}</div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
$(function () {
    function escapeHtml(value) {
        if (value === null || value === undefined) {
            return '';
        }

        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function formatValue(value) {
        if (value === null || value === undefined || value === '') {
            return '<span class="text-muted">-</span>';
        }

        if (Array.isArray(value)) {
            return value.length
                ? value.map(v => `<span class="badge bg-light text-dark border me-1 mb-1">${escapeHtml(v)}</span>`).join('')
                : '<span class="text-muted">-</span>';
        }

        if (typeof value === 'object') {
            return `<code class="small">${escapeHtml(JSON.stringify(value))}</code>`;
        }

        if (value === true || value === false) {
            return value ? 'Ya' : 'Tidak';
        }

        return escapeHtml(value);
    }

    const EVENT_STYLE = {
        created: 'bg-success',
        updated: 'bg-primary',
        deleted: 'bg-danger',
        restored: 'bg-info text-dark',
        login: 'bg-secondary',
        logout: 'bg-light text-dark border',
        login_failed: 'bg-warning text-dark',
        login_locked: 'bg-danger',
        registered: 'bg-success',
        password_reset: 'bg-warning text-dark',
        email_verified: 'bg-info text-dark',
        permission_updated: 'bg-primary',
        exported: 'bg-dark',
        printed: 'bg-dark',
        import_started: 'bg-info text-dark',
        import_finished: 'bg-info text-dark'
    };

    const SOURCE_ICON = {
        web: 'fa-desktop',
        api: 'fa-mobile-alt',
        system: 'fa-cog'
    };

    const $filter = $('#audit-filter');
    const defaultFilter = $filter.serializeArray();
    const detailUrlTemplate = "{{ route('audit-trail.show', ['id' => '__ID__']) }}";

    const table = $('#table-audit-trail').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        autoWidth: false,
        searchDelay: 400,
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        ajax: {
            url: "{{ route('audit-trail.data') }}",
            data: function (d) {
                $filter.serializeArray().forEach(function (item) {
                    d[item.name] = item.value;
                });
            }
        },
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
            {
                data: 'created_at',
                name: 'created_at',
                searchable: false,
                render: (data, type) => type === 'display'
                    ? `<span class="text-nowrap">${escapeHtml(data)}</span>`
                    : data
            },
            {
                data: 'user_name',
                name: 'user_name',
                render: function (data, type, row) {
                    if (type !== 'display') {
                        return data || '';
                    }

                    const icon = SOURCE_ICON[row.source] || 'fa-question';

                    return `
                        <div class="fw-semibold">${escapeHtml(data || 'Sistem')}</div>
                        <small class="text-muted">
                            <i class="fas ${icon} me-1"></i>${escapeHtml(row.source === 'api' ? 'mobile' : row.source)}
                        </small>
                    `;
                }
            },
            {
                data: 'event_label',
                name: 'event',
                render: (data, type, row) => type === 'display'
                    ? `<span class="badge ${EVENT_STYLE[row.event] || 'bg-secondary'}">${escapeHtml(data)}</span>`
                    : data
            },
            {
                data: 'module_label',
                name: 'module',
                render: (data, type) => type === 'display'
                    ? `<span class="badge bg-light text-dark border">${escapeHtml(data)}</span>`
                    : data
            },
            {
                data: 'description',
                name: 'description',
                render: (data, type) => type === 'display'
                    ? `<div class="text-wrap" style="max-width:420px">${escapeHtml(data)}</div>`
                    : data
            },
            {
                data: 'ip_address',
                name: 'ip_address',
                render: (data, type) => type === 'display'
                    ? `<small class="text-muted">${escapeHtml(data || '-')}</small>`
                    : data
            },
            {
                data: 'encrypted_id',
                name: 'encrypted_id',
                orderable: false,
                searchable: false,
                className: 'text-center',
                render: (data, type) => type === 'display' && data
                    ? `<button type="button" class="btn btn-sm btn-outline-primary btn-audit-detail"
                               data-id="${escapeHtml(data)}" title="Lihat detail">
                           <i class="fas fa-eye"></i>
                       </button>`
                    : ''
            }
        ],
        order: [[1, 'desc']]
    });

    $filter.on('submit', function (e) {
        e.preventDefault();
        table.ajax.reload();
    });

    $('#audit-reset').on('click', function () {
        $filter[0].reset();
        defaultFilter.forEach(function (item) {
            $filter.find(`[name="${item.name}"]`).val(item.value);
        });
        table.ajax.reload();
    });

    const modalEl = document.getElementById('modal-audit-detail');
    const modal = new bootstrap.Modal(modalEl);
    const $body = $('#audit-detail-body');

    $('#table-audit-trail').on('click', '.btn-audit-detail', function () {
        const id = $(this).attr('data-id');

        if (!id) {
            return;
        }

        $body.html('<div class="text-center text-muted py-4">Memuat...</div>');
        modal.show();

        $.get(detailUrlTemplate.replace('__ID__', encodeURIComponent(id)))
            .done(function (res) {
                const d = res.data || {};

                const info = [
                    ['Waktu', d.created_at],
                    ['User', d.user_name],
                    ['Aktivitas', d.event],
                    ['Modul', d.module],
                    ['Keterangan', d.description],
                    ['Data', d.record],
                    ['Sumber', d.source === 'api' ? 'mobile' : d.source],
                    ['IP Address', d.ip_address],
                    ['Request', d.method && d.url ? `${d.method} ${d.url}` : null],
                    ['Browser / Device', d.user_agent]
                ].map(([label, value]) => `
                    <tr>
                        <th class="text-muted fw-normal" width="170">${escapeHtml(label)}</th>
                        <td class="text-break">${formatValue(value)}</td>
                    </tr>
                `).join('');

                let changes = '';

                if ((d.fields || []).length) {
                    const rows = d.fields.map(f => `
                        <tr>
                            <td class="fw-semibold text-nowrap">${escapeHtml(f.field)}</td>
                            <td class="text-break bg-danger-subtle">${formatValue(f.old)}</td>
                            <td class="text-break bg-success-subtle">${formatValue(f.new)}</td>
                        </tr>
                    `).join('');

                    changes = `
                        <h6 class="mt-4 mb-2">Perubahan Data</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th width="25%">Field</th>
                                        <th>Sebelum</th>
                                        <th>Sesudah</th>
                                    </tr>
                                </thead>
                                <tbody>${rows}</tbody>
                            </table>
                        </div>
                    `;
                }

                $body.html(`
                    <table class="table table-sm mb-0"><tbody>${info}</tbody></table>
                    ${changes}
                `);
            })
            .fail(function () {
                $body.html('<div class="alert alert-danger mb-0">Gagal memuat detail aktivitas.</div>');
            });
    });
});
</script>
@endsection
