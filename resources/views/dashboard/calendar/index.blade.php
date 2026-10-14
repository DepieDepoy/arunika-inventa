
@extends('dashboard.layouts.wrapper')

@section('title', 'Calendar')

@section('content')
@php
    $calendarConfig = [
        'currentUserId' => auth()->id(),
        'csrfToken' => csrf_token(),

        // Meeting / Announcement
        'storeUrl' => route('calendar.events.store'),
        'updateUrlTemplate' => route('calendar.events.update', [
            'id' => '__EVENT_ID__',
        ]),
        'deleteUrlTemplate' => route('calendar.events.destroy', [
            'id' => '__EVENT_ID__',
        ]),

        // Maintenance / Repair Request
        'cancelRequestUrlTemplate' => route('maintenance.requests.cancel', [
            'maintenanceRequest' => '__EVENT_ID__',
        ]),

        'events' => collect($events ?? [])->values(),

        'users' => collect($users ?? [])->map(function ($user) {
            $photo = trim((string) ($user->photo ?? ''));
            $photoUrl = null;

            if ($photo !== '') {
                if (preg_match('#^https?://#i', $photo)) {
                    $photoUrl = $photo;
                } elseif (str_starts_with($photo, 'storage/')) {
                    $photoUrl = asset($photo);
                } elseif (str_starts_with($photo, '/')) {
                    $photoUrl = asset(ltrim($photo, '/'));
                } else {
                    $photoUrl = \Illuminate\Support\Facades\Storage::url($photo);
                }
            }

            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'photoUrl' => $photoUrl,
            ];
        })->values(),
    ];

    $calendarConfigJson = json_encode(
        $calendarConfig,
        JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
    );
@endphp

<style>
    /* =====================================================
       BASE
    ===================================================== */
    .calendar-page {
        color: #263449;
    }

    .calendar-toolbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 12px;
        margin-bottom: 20px;
    }

    .calendar-title {
        font-size: 24px;
        font-weight: 700;
        margin: 0;
    }

    .calendar-subtitle {
        color: #7b8798;
        font-size: 13px;
        margin-top: 5px;
    }

    /* =====================================================
       CALENDAR CARD
    ===================================================== */
    .calendar-card {
        background: #fff;
        border: 1px solid #e8edf4;
        border-radius: 14px;
        overflow: hidden;
        box-shadow: 0 4px 18px rgba(28, 46, 74, .04);
    }

    .calendar-navigation {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 12px;
        padding: 18px 20px;
        border-bottom: 1px solid #edf0f5;
    }

    .calendar-month-title {
        font-size: 19px;
        font-weight: 700;
        min-width: 190px;
        text-align: center;
    }

    .calendar-nav-group {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .calendar-nav-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 36px;
        height: 36px;
        border: 1px solid #e0e6ef;
        background: #fff;
        border-radius: 8px;
        color: #34445c;
        cursor: pointer;
        transition: background .2s, border-color .2s;
    }

    .calendar-nav-btn:hover {
        background: #f4f7fb;
        border-color: #cbd5e1;
    }

    .calendar-today-btn {
        padding: 0 12px;
        font-size: 13px;
        font-weight: 600;
    }

    /* =====================================================
       GRID DAN TANGGAL
    ===================================================== */
    .calendar-grid {
        position: relative;
        display: grid;
        grid-template-columns: repeat(7, minmax(0, 1fr));
    }

    /* Lapisan khusus untuk jadwal yang berlangsung beberapa hari */
    .calendar-span-layer {
        position: absolute;
        inset: 0;
        z-index: 6;
        pointer-events: none;
    }

    .calendar-span-layer .calendar-span-event {
        position: absolute;
        box-sizing: border-box;
        min-width: 0;
        margin: 0;
        pointer-events: auto;
    }

    .calendar-weekday {
        padding: 13px 5px;
        text-align: center;
        font-size: 12px;
        font-weight: 700;
        color: #758198;
        background: #f8fafd;
        border-bottom: 1px solid #edf0f5;
    }

    .calendar-day {
        position: relative;
        min-width: 0;
        min-height: 125px;
        padding: 8px;
        border-right: 1px solid #edf0f5;
        border-bottom: 1px solid #edf0f5;
        cursor: pointer;
    }

    .calendar-day:hover {
        background: #f8fbff;
    }

    .calendar-day:nth-child(7n) {
        border-right: 0;
    }

    .calendar-day.other-month {
        background: #fafbfd;
    }

    .calendar-day.other-month .calendar-day-number {
        color: #b7bfcc;
    }

    .calendar-day-number {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 28px;
        height: 28px;
        margin-bottom: 5px;
        font-size: 13px;
        font-weight: 600;
        color: #35445c;
        border-radius: 50%;
    }

    .calendar-day.today .calendar-day-number {
        color: #fff;
        background: #246bfd;
    }

    /* =====================================================
       EVENT HARIAN
    ===================================================== */
    .calendar-event {
        display: block;
        width: 100%;
        margin: 3px 0;
        padding: 5px 7px;
        border: 0;
        border-left: 3px solid #246bfd;
        border-radius: 4px;
        background: #eaf1ff;
        color: #2056bc;
        font-size: 11px;
        line-height: 1.4;
        text-align: left;
        cursor: pointer;
        overflow-wrap: anywhere;
    }

    .calendar-event.meeting {
        border-left-color: #246bfd;
        background: #eaf1ff;
        color: #2056bc;
    }

    .calendar-event.announcement {
        border-left-color: #0d9b72;
        background: #e5f8f0;
        color: #087653;
    }

    .calendar-event.maintenance {
        border-left-color: #0d9488;
        background: #e0f7f4;
        color: #0f766e;
    }

    .calendar-event.maintenance_request {
        border-left-color: #d97706;
        background: #fff4df;
        color: #92400e;
    }

    .calendar-event.repair_request {
        border-left-color: #dc3545;
        background: #fdebed;
        color: #b42332;
    }

    .calendar-event.cancelled {
        border-left-color: #98a2b3;
        background: #f0f2f5;
        color: #7a8494;
        text-decoration: line-through;
    }

    .calendar-event-time {
        display: block;
        font-size: 10px;
        line-height: 1.3;
        font-weight: 600;
        margin-bottom: 2px;
        white-space: normal;
    }

    .calendar-event-title {
        display: block;
        overflow-wrap: anywhere;
    }

    .calendar-more {
        display: block;
        margin-top: 4px;
        color: #68778e;
        font-size: 11px;
        cursor: pointer;
    }

    /* =====================================================
       LEGENDA
    ===================================================== */
    .calendar-legend {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 18px;
        padding: 14px 20px;
        color: #6e7b90;
        font-size: 12px;
    }

    .calendar-legend-item {
        display: flex;
        align-items: center;
        gap: 7px;
    }

    .calendar-legend-dot {
        width: 9px;
        height: 9px;
        flex: 0 0 9px;
        border-radius: 3px;
    }

    /* =====================================================
       AVATAR PENGGUNA
    ===================================================== */
    .calendar-avatar {
        position: relative;
        display: inline-flex;
        flex: 0 0 auto;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        border-radius: 50%;
        background: #dbeafe;
        color: #1d4ed8;
        font-weight: 700;
        text-transform: uppercase;
        line-height: 1;
        user-select: none;
    }

    .calendar-avatar span {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        height: 100%;
    }

    .calendar-avatar img {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
        border-radius: 50%;
    }

    .calendar-avatar.avatar-color-0 {
        background: #dbeafe;
        color: #1d4ed8;
    }

    .calendar-avatar.avatar-color-1 {
        background: #dcfce7;
        color: #15803d;
    }

    .calendar-avatar.avatar-color-2 {
        background: #f3e8ff;
        color: #7e22ce;
    }

    .calendar-avatar.avatar-color-3 {
        background: #ffedd5;
        color: #c2410c;
    }

    .calendar-avatar.avatar-color-4 {
        background: #fce7f3;
        color: #be185d;
    }

    .calendar-avatar.avatar-xs {
        width: 21px;
        height: 21px;
        font-size: 8px;
    }

    .calendar-avatar.avatar-sm {
        width: 30px;
        height: 30px;
        font-size: 10px;
    }

    .calendar-avatar.avatar-md {
        width: 36px;
        height: 36px;
        font-size: 12px;
    }

    .calendar-avatar.avatar-lg {
        width: 40px;
        height: 40px;
        font-size: 13px;
    }

    .calendar-avatar-stack {
        display: flex;
        align-items: center;
        flex-wrap: nowrap;
        padding-left: 2px;
        margin-top: 5px;
    }

    .calendar-avatar-stack .calendar-avatar {
        border: 2px solid #fff;
        margin-left: -5px;
    }

    .calendar-avatar-stack .calendar-avatar:first-child {
        margin-left: 0;
    }

    .calendar-avatar-more {
        display: inline-flex;
        flex: 0 0 auto;
        align-items: center;
        justify-content: center;
        width: 23px;
        height: 23px;
        margin-left: -5px;
        border: 2px solid #fff;
        border-radius: 50%;
        background: #e2e8f0;
        color: #475569;
        font-size: 8px;
        font-weight: 700;
    }

    /* =====================================================
       PENCARIAN PESERTA
    ===================================================== */
    .attendee-autocomplete {
        position: relative;
    }

    .attendee-search-wrap {
        position: relative;
    }

    .attendee-search-wrap .search-icon {
        position: absolute;
        left: 13px;
        top: 50%;
        transform: translateY(-50%);
        color: #8491a5;
        pointer-events: none;
    }

    #attendeeSearch {
        padding-left: 36px;
    }

    .attendee-results {
        display: none;
        position: absolute;
        top: calc(100% + 4px);
        left: 0;
        right: 0;
        max-height: 210px;
        overflow-y: auto;
        overscroll-behavior: contain;
        background: #fff;
        border: 1px solid #dfe5ee;
        border-radius: 9px;
        box-shadow: 0 8px 24px rgba(30, 50, 80, .14);
        z-index: 1085;
    }

    .attendee-results.show {
        display: block;
    }

    .attendee-option {
        display: flex;
        align-items: center;
        gap: 10px;
        width: 100%;
        padding: 10px 12px;
        border: 0;
        background: #fff;
        text-align: left;
        cursor: pointer;
    }

    .attendee-option:hover,
    .attendee-option:focus {
        background: #f1f6ff;
        outline: none;
    }

    .attendee-option + .attendee-option {
        border-top: 1px solid #f0f2f6;
    }

    .attendee-option-info {
        display: block;
        min-width: 0;
    }

    .attendee-option-name {
        display: block;
        color: #263449;
        font-size: 13px;
        font-weight: 600;
        overflow-wrap: anywhere;
    }

    .attendee-option-email {
        display: block;
        color: #8491a5;
        font-size: 11px;
        margin-top: 2px;
        overflow-wrap: anywhere;
    }

    .attendee-no-result {
        padding: 12px;
        color: #8491a5;
        font-size: 12px;
    }

    .attendee-selected-list {
        display: flex;
        flex-wrap: wrap;
        gap: 7px;
        margin-top: 10px;
    }

    .attendee-chip {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 4px 8px 4px 4px;
        background: #edf3ff;
        color: #2056bc;
        border-radius: 22px;
        font-size: 12px;
        max-width: 100%;
    }

    .attendee-chip-name {
        overflow-wrap: anywhere;
    }

    .attendee-chip-remove {
        border: 0;
        background: transparent;
        color: #2056bc;
        padding: 0;
        cursor: pointer;
        font-size: 14px;
        line-height: 1;
    }

    /* =====================================================
       MODAL TAMBAH / EDIT / DETAIL
    ===================================================== */
    #eventModal .modal-dialog,
    #eventDetailModal .modal-dialog {
        width: calc(100% - 24px);
        margin: 16px auto;
    }

    #eventModal .modal-content,
    #eventDetailModal .modal-content {
        display: flex;
        flex-direction: column;
        height: auto;
        max-height: calc(100vh - 32px);
        max-height: calc(100dvh - 32px);
        overflow: hidden;
        border: 0;
        border-radius: 14px;
    }

    #eventModal .modal-header,
    #eventDetailModal .modal-header {
        flex: 0 0 auto;
        padding: 16px 22px;
        background: #fff;
        border-bottom: 1px solid #edf0f5;
    }

    #eventModal .modal-body,
    #eventDetailModal .modal-body {
        flex: 1 1 auto;
        min-height: 0;
        overflow-y: auto !important;
        overscroll-behavior: contain;
        -webkit-overflow-scrolling: touch;
        padding: 22px;
    }

    #eventModal .modal-footer,
    #eventDetailModal .modal-footer {
        flex: 0 0 auto;
        padding: 14px 22px;
        border-top: 1px solid #edf0f5;
        background: #fff;
    }

    .calendar-modal .modal-title {
        font-size: 17px;
        font-weight: 700;
    }

    .calendar-modal .form-label {
        font-size: 13px;
        font-weight: 600;
        color: #3a4960;
        margin-bottom: 7px;
    }

    .calendar-modal .form-control,
    .calendar-modal .form-select {
        min-height: 42px;
        border-radius: 8px;
        border-color: #dfe5ee;
        font-size: 13px;
    }

    .calendar-modal textarea.form-control {
        min-height: 90px;
    }

    .calendar-modal .form-control:focus,
    .calendar-modal .form-select:focus {
        border-color: #83aaff;
        box-shadow: 0 0 0 3px rgba(36, 107, 253, .1);
    }

    .calendar-action-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 7px;
        padding: 9px 14px;
        border: 0;
        border-radius: 8px;
        background: #246bfd;
        color: #fff;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        transition: background .2s, opacity .2s;
    }

    .calendar-action-btn:hover {
        background: #1558dc;
        color: #fff;
    }

    .calendar-action-btn:disabled {
        opacity: .65;
        cursor: wait;
    }

    .calendar-secondary-btn {
        padding: 9px 13px;
        border: 1px solid #dfe5ee;
        border-radius: 8px;
        background: #fff;
        color: #45546b;
        font-size: 13px;
        cursor: pointer;
    }

    .calendar-danger-btn {
        padding: 9px 13px;
        border: 1px solid #f1c7cb;
        border-radius: 8px;
        background: #fff;
        color: #c93442;
        font-size: 13px;
        cursor: pointer;
    }

    .calendar-form-error {
        display: none;
        color: #dc3545;
        font-size: 12px;
        margin-top: 5px;
    }

    .calendar-form-error.show {
        display: block;
    }

    /* =====================================================
       DETAIL JADWAL
    ===================================================== */
    .calendar-detail-row {
        display: flex;
        gap: 12px;
        margin-bottom: 18px;
    }

    .calendar-detail-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 36px;
        width: 36px;
        height: 36px;
        border-radius: 9px;
        background: #edf3ff;
        color: #246bfd;
    }

    .calendar-detail-label {
        font-size: 11px;
        color: #8590a2;
        margin-bottom: 4px;
    }

    .calendar-detail-value {
        font-size: 13px;
        color: #35445c;
        white-space: pre-wrap;
        overflow-wrap: anywhere;
    }

    .calendar-attendee-list {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .calendar-attendee-item {
        display: flex;
        align-items: center;
        gap: 10px;
        min-width: 0;
    }

    .calendar-attendee-info {
        min-width: 0;
    }

    .calendar-attendee-name {
        font-size: 12px;
        font-weight: 600;
        color: #35445c;
        overflow-wrap: anywhere;
    }

    .calendar-attendee-email {
        font-size: 11px;
        color: #8590a2;
        overflow-wrap: anywhere;
    }

    /* =====================================================
       BADGE JENIS DAN STATUS
    ===================================================== */
    .calendar-status {
        display: inline-block;
        padding: 5px 9px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
    }

    .calendar-status-meeting {
        color: #2056bc;
        background: #eaf1ff;
    }

    .calendar-status-announcement {
        color: #087653;
        background: #e5f8f0;
    }

    .calendar-status-maintenance {
        color: #0f766e;
        background: #e0f7f4;
    }

    .calendar-status-repair {
        color: #b42332;
        background: #fdebed;
    }

    .calendar-status-cancelled {
        color: #687386;
        background: #f0f2f5;
    }

    /* =====================================================
       BAR JADWAL MULTI-HARI
    ===================================================== */
    .calendar-span-event {
        position: relative;
        z-index: 5;
        display: flex;
        align-items: center;
        min-width: 0;
        height: 23px;
        margin: 0 4px;
        padding: 2px 7px;
        overflow: hidden;
        border: 0;
        border-left: 3px solid #246bfd;
        border-radius: 4px;
        background: #eaf1ff;
        color: #2056bc;
        font-size: 11px;
        line-height: 1.3;
        text-align: left;
        white-space: nowrap;
        cursor: pointer;
    }

    .calendar-span-event .calendar-span-title {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .calendar-span-event.meeting {
        border-left-color: #246bfd;
        background: #eaf1ff;
        color: #2056bc;
    }

    .calendar-span-event.announcement {
        border-left-color: #0d9b72;
        background: #e5f8f0;
        color: #087653;
    }

    .calendar-span-event.maintenance {
        border-left-color: #0d9488;
        background: #e0f7f4;
        color: #0f766e;
    }

    .calendar-span-event.maintenance_request {
        border-left-color: #d97706;
        background: #fff4df;
        color: #92400e;
    }

    .calendar-span-event.repair_request {
        border-left-color: #dc3545;
        background: #fdebed;
        color: #b42332;
    }

    .calendar-span-event.cancelled {
        border-left-color: #98a2b3;
        background: #f0f2f5;
        color: #7a8494;
        text-decoration: line-through;
    }

    /* =====================================================
       RESPONSIVE TABLET / HP
    ===================================================== */
    @media (max-width: 767px) {
        .calendar-title {
            font-size: 21px;
        }

        .calendar-navigation {
            padding: 12px;
        }

        .calendar-month-title {
            min-width: 150px;
            font-size: 16px;
        }

        .calendar-day {
            min-height: 90px;
            padding: 4px;
        }

        .calendar-day-number {
            width: 24px;
            height: 24px;
            font-size: 11px;
        }

        .calendar-event {
            padding: 4px;
            font-size: 10px;
        }

        .calendar-event-time {
            font-size: 9px;
        }

        .calendar-weekday {
            font-size: 10px;
            padding: 10px 2px;
        }

        .calendar-legend {
            padding: 12px;
            gap: 12px;
        }

        .calendar-span-event {
            height: 19px;
            padding: 2px 3px;
            font-size: 9px;
        }

        #eventModal .modal-dialog,
        #eventDetailModal .modal-dialog {
            width: calc(100% - 16px);
            margin: 8px auto;
        }

        #eventModal .modal-content,
        #eventDetailModal .modal-content {
            max-height: calc(100vh - 16px);
            max-height: calc(100dvh - 16px);
        }

        #eventModal .modal-header,
        #eventDetailModal .modal-header {
            padding: 14px 16px;
        }

        #eventModal .modal-body,
        #eventDetailModal .modal-body {
            padding: 16px;
        }

        #eventModal .modal-footer,
        #eventDetailModal .modal-footer {
            padding: 12px 16px;
        }
    }

/* Popup daftar seluruh jadwal harian */
#dayEventsModal .modal-dialog {
    width: calc(100% - 24px);
    max-width: 650px;
    margin: 16px auto;
}

#dayEventsModal .modal-content {
    max-height: calc(100vh - 32px);
    max-height: calc(100dvh - 32px);
    display: flex;
    flex-direction: column;
    overflow: hidden;
    border: 0;
    border-radius: 14px;
}

#dayEventsModal .modal-header,
#dayEventsModal .modal-footer {
    flex: 0 0 auto;
    background: #fff;
    padding: 14px 20px;
}

#dayEventsModal .modal-body {
    flex: 1 1 auto;
    min-height: 0;
    overflow-y: auto;
    overscroll-behavior: contain;
    -webkit-overflow-scrolling: touch;
    padding: 12px 20px;
}

.calendar-day-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.calendar-day-list-item {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    width: 100%;
    padding: 12px;
    border: 1px solid #e8edf4;
    border-left: 4px solid #246bfd;
    border-radius: 9px;
    background: #fff;
    color: #263449;
    text-align: left;
    cursor: pointer;
}

.calendar-day-list-item:hover {
    background: #f6f9ff;
    border-color: #cbdaf7;
}

.calendar-day-list-item.announcement {
    border-left-color: #0d9b72;
}

.calendar-day-list-item.maintenance {
    border-left-color: #0d9488;
}

.calendar-day-list-item.maintenance_request {
    border-left-color: #d97706;
}

.calendar-day-list-item.repair_request {
    border-left-color: #dc3545;
}

.calendar-day-list-time {
    flex: 0 0 100px;
    font-size: 12px;
    font-weight: 700;
    color: #52627a;
}

.calendar-day-list-info {
    flex: 1;
    min-width: 0;
}

.calendar-day-list-title {
    font-size: 13px;
    font-weight: 600;
    overflow-wrap: anywhere;
}

.calendar-day-list-subtitle {
    margin-top: 4px;
    color: #7b8798;
    font-size: 11px;
}

.calendar-day-list-empty {
    padding: 28px 12px;
    color: #7b8798;
    text-align: center;
}

@media (max-width: 575px) {
    #dayEventsModal .modal-dialog {
        width: calc(100% - 16px);
        margin: 8px auto;
    }

    #dayEventsModal .modal-content {
        max-height: calc(100vh - 16px);
        max-height: calc(100dvh - 16px);
    }

    #dayEventsModal .modal-body {
        padding: 10px;
    }

    .calendar-day-list-item {
        gap: 8px;
        padding: 10px;
    }

    .calendar-day-list-time {
        flex-basis: 78px;
        font-size: 11px;
    }
}

</style>

<div class="content-wrapper">
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="row">
            <div class="col-xxl-12 mb-4">

                <div class="card">
                    {{-- ================= CARD HEADER ================= --}}
                    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-3">
                        <div>
                            <h5 class="mb-1">
                                <i class="fas fa-calendar-alt me-2 text-primary"></i>
                                Calendar
                            </h5>
                            <div class="calendar-subtitle">
                                Kelola jadwal meeting dan pengumuman perusahaan.
                            </div>
                        </div>

                        <button type="button" class="calendar-action-btn" id="btnAddEvent">
                            <i class="fas fa-plus"></i>
                            Tambah Jadwal
                        </button>
                    </div>

                    {{-- ================= CALENDAR ================= --}}
                    <div class="calendar-card">
                        <div class="calendar-navigation">
                            <div class="calendar-nav-group">
                                <button
                                    type="button"
                                    class="calendar-nav-btn"
                                    id="btnPrevMonth"
                                    aria-label="Bulan sebelumnya"
                                >
                                    <i class="fas fa-chevron-left"></i>
                                </button>

                                <button
                                    type="button"
                                    class="calendar-nav-btn"
                                    id="btnNextMonth"
                                    aria-label="Bulan berikutnya"
                                >
                                    <i class="fas fa-chevron-right"></i>
                                </button>

                                <button
                                    type="button"
                                    class="calendar-nav-btn calendar-today-btn"
                                    id="btnToday"
                                >
                                    Hari Ini
                                </button>
                            </div>

                            <div class="calendar-month-title" id="calendarMonthTitle">
                                -
                            </div>

                            <div class="calendar-nav-group">
                                <span class="text-muted small">Tampilan Bulanan</span>
                            </div>
                        </div>

                        <div class="calendar-grid" id="calendarGrid">
                            <div class="calendar-weekday">Min</div>
                            <div class="calendar-weekday">Sen</div>
                            <div class="calendar-weekday">Sel</div>
                            <div class="calendar-weekday">Rab</div>
                            <div class="calendar-weekday">Kam</div>
                            <div class="calendar-weekday">Jum</div>
                            <div class="calendar-weekday">Sab</div>
                        </div>

                        {{-- ================= LEGEND ================= --}}
                        <div class="calendar-legend">
                            <div class="calendar-legend-item">
                                <span class="calendar-legend-dot" style="background:#246bfd"></span>
                                Meeting
                            </div>

                            <div class="calendar-legend-item">
                                <span class="calendar-legend-dot" style="background:#0d9b72"></span>
                                Pengumuman
                            </div>

                            <div class="calendar-legend-item">
                                <span class="calendar-legend-dot" style="background:#0d9488"></span>
                                Jadwal Maintenance
                            </div>

                            <div class="calendar-legend-item">
                                <span class="calendar-legend-dot" style="background:#d97706"></span>
                                Permintaan Maintenance
                            </div>

                            <div class="calendar-legend-item">
                                <span class="calendar-legend-dot" style="background:#dc3545"></span>
                                Permintaan Repair
                            </div>

                            <div class="calendar-legend-item">
                                <span class="calendar-legend-dot" style="background:#98a2b3"></span>
                                Dibatalkan
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

{{-- ================= CALENDAR CONFIG ================= --}}
<script type="application/json" id="calendar-config">{!! $calendarConfigJson !!}</script>

{{-- ================= MODAL TAMBAH / EDIT JADWAL ================= --}}
<div class="modal fade calendar-modal" id="eventModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title" id="eventModalTitle">Tambah Jadwal</h5>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                    aria-label="Tutup"
                ></button>
            </div>

            <form id="eventForm" class="d-flex flex-column" style="min-height:0; overflow:hidden;">
                <div class="modal-body">
                    <div
                        id="eventFormAlert"
                        class="alert alert-danger d-none"
                        role="alert"
                    ></div>

                    <input type="hidden" id="eventId">

                    {{-- Judul --}}
                    <div class="mb-3">
                        <label for="eventTitle" class="form-label">
                            Judul <span class="text-danger">*</span>
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            id="eventTitle"
                            maxlength="255"
                            required
                            placeholder="Contoh: Meeting Evaluasi Bulanan"
                        >

                        <div class="calendar-form-error" data-error="title"></div>
                    </div>

                    {{-- Jenis jadwal dan peserta --}}
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="eventType" class="form-label">
                                Jenis Jadwal <span class="text-danger">*</span>
                            </label>

                            <select class="form-select" id="eventType" required>
                                <option value="meeting">Meeting</option>
                                <option value="announcement">Pengumuman Perusahaan</option>
                            </select>

                            <div class="calendar-form-error" data-error="event_type"></div>
                        </div>

                        <div class="col-md-6 mb-3" id="attendeesGroup">
                            <label for="attendeeSearch" class="form-label">
                                Cari dan Undang Peserta
                            </label>

                            <div class="attendee-autocomplete">
                                <div class="attendee-search-wrap">
                                    <i class="fas fa-search search-icon"></i>

                                    <input
                                        type="text"
                                        class="form-control"
                                        id="attendeeSearch"
                                        placeholder="Ketik nama atau email..."
                                        autocomplete="off"
                                    >
                                </div>

                                <div class="attendee-results" id="attendeeResults"></div>
                            </div>

                            <div class="attendee-selected-list" id="selectedAttendees"></div>

                            <small class="text-muted d-block mt-2">
                                Ketik nama/email, lalu pilih hasilnya.
                                Anda bisa memilih beberapa peserta.
                            </small>

                            <div class="calendar-form-error" data-error="attendees"></div>
                        </div>
                    </div>

                    {{-- Waktu --}}
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="eventStart" class="form-label">
                                Mulai <span class="text-danger">*</span>
                            </label>

                            <input
                                type="text"
                                class="form-control"
                                id="eventStart"
                                placeholder="Pilih tanggal dan jam"
                                required
                            >

                            <div class="calendar-form-error" data-error="start_at"></div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="eventEnd" class="form-label">
                                Selesai <span class="text-danger">*</span>
                            </label>

                            <input
                                type="text"
                                class="form-control"
                                id="eventEnd"
                                placeholder="Pilih tanggal dan jam"
                                required
                            >

                            <div class="calendar-form-error" data-error="end_at"></div>
                        </div>
                    </div>

                    {{-- Lokasi --}}
                    <div class="mb-3">
                        <label for="eventLocation" class="form-label">Lokasi</label>

                        <input
                            type="text"
                            class="form-control"
                            id="eventLocation"
                            maxlength="255"
                            placeholder="Contoh: Ruang Meeting / Online"
                        >

                        <div class="calendar-form-error" data-error="location"></div>
                    </div>

                    {{-- Deskripsi --}}
                    <div class="mb-3">
                        <label for="eventDescription" class="form-label">Deskripsi</label>

                        <textarea
                            class="form-control"
                            id="eventDescription"
                            maxlength="5000"
                            placeholder="Agenda atau keterangan tambahan"
                        ></textarea>

                        <div class="calendar-form-error" data-error="description"></div>
                    </div>

                    {{-- Informasi sesuai jenis jadwal --}}
                    <div class="alert alert-info py-2 small mb-0" id="announcementInfo">
                        Pengumuman akan terlihat oleh pengguna dalam perusahaan yang sama.
                    </div>

                    <div class="alert alert-info py-2 small mb-0 d-none" id="meetingInfo">
                        Meeting hanya terlihat oleh pembuat dan peserta yang diundang.
                    </div>
                </div>

                <div class="modal-footer">
                    <button
                        type="button"
                        class="calendar-secondary-btn"
                        data-bs-dismiss="modal"
                    >
                        Batal
                    </button>

                    <button type="submit" class="calendar-action-btn" id="btnSaveEvent">
                        <i class="fas fa-save"></i>
                        <span>Simpan Jadwal</span>
                    </button>
                </div>
            </form>

        </div>
    </div>
</div>

{{-- ================= MODAL DETAIL JADWAL ================= --}}
<div class="modal fade calendar-modal" id="eventDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-md modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">Detail Jadwal</h5>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                    aria-label="Tutup"
                ></button>
            </div>

            <div class="modal-body">
                {{-- Jenis dan status --}}
                <div class="mb-3">
                    <span class="calendar-status calendar-status-meeting" id="detailType">
                        Meeting
                    </span>

                    <span class="calendar-status calendar-status-cancelled d-none" id="detailCancelled">
                        Dibatalkan
                    </span>
                </div>

                <h5 class="fw-bold mb-4" id="detailTitle">-</h5>

                {{-- Waktu --}}
                <div class="calendar-detail-row">
                    <div class="calendar-detail-icon">
                        <i class="far fa-clock"></i>
                    </div>

                    <div>
                        <div class="calendar-detail-label">Waktu</div>
                        <div class="calendar-detail-value" id="detailDate">-</div>
                    </div>
                </div>

                {{-- Lokasi --}}
                <div class="calendar-detail-row" id="detailLocationRow">
                    <div class="calendar-detail-icon">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>

                    <div>
                        <div class="calendar-detail-label">Lokasi</div>
                        <div class="calendar-detail-value" id="detailLocation">-</div>
                    </div>
                </div>

                {{-- Deskripsi --}}
                <div class="calendar-detail-row" id="detailDescriptionRow">
                    <div class="calendar-detail-icon">
                        <i class="far fa-file-alt"></i>
                    </div>

                    <div>
                        <div class="calendar-detail-label">Deskripsi</div>
                        <div class="calendar-detail-value" id="detailDescription">-</div>
                    </div>
                </div>

                {{-- Peserta --}}
                <div class="calendar-detail-row" id="detailAttendeesRow">
                    <div class="calendar-detail-icon">
                        <i class="fas fa-users"></i>
                    </div>

                    <div class="flex-grow-1">
                        <div class="calendar-detail-label">Peserta</div>
                        <div class="calendar-attendee-list" id="detailAttendees"></div>
                    </div>
                </div>
            </div>

            <div class="modal-footer justify-content-between">
                <button
                    type="button"
                    class="calendar-danger-btn d-none"
                    id="btnCancelEvent"
                >
                    <i class="fas fa-ban me-1"></i>
                    Batalkan
                </button>

                <div class="ms-auto d-flex gap-2">
                    <button
                        type="button"
                        class="calendar-secondary-btn"
                        data-bs-dismiss="modal"
                    >
                        Tutup
                    </button>

                    <button
                        type="button"
                        class="calendar-action-btn d-none"
                        id="btnEditEvent"
                    >
                        <i class="fas fa-edit"></i>
                        Edit
                    </button>
                </div>
            </div>

        </div>
    </div>
</div>


<!-- MODAL DAFTAR JADWAL HARIAN -->
<div class="modal fade calendar-modal"
     id="dayEventsModal"
     tabindex="-1"
     aria-hidden="true">

    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header">
                <div>
                    <h5 class="modal-title">Daftar Jadwal Harian</h5>
                    <div class="calendar-subtitle"
                         id="dayEventsDate">-</div>
                </div>

                <button type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                        aria-label="Tutup"></button>
            </div>

            <div class="modal-body">
                <div class="calendar-day-list"
                     id="dayEventsList"></div>
            </div>

            <div class="modal-footer">
                <button type="button"
                        class="calendar-secondary-btn"
                        data-bs-dismiss="modal">
                    Tutup
                </button>
            </div>

        </div>
    </div>
</div>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    'use strict';

    const config = JSON.parse(document.getElementById('calendar-config').textContent || '{}');
    const currentUserId = Number(config.currentUserId);
    const csrfToken = config.csrfToken;
    const storeUrl = config.storeUrl;
    const updateUrlTemplate = config.updateUrlTemplate;
    const deleteUrlTemplate = config.deleteUrlTemplate;
    const cancelRequestUrlTemplate = config.cancelRequestUrlTemplate;
    const users = Array.isArray(config.users) ? config.users : [];

    let events = Array.isArray(config.events) ? config.events : [];
    let currentDate = new Date();
    let selectedEvent = null;
    let selectedAttendees = [];

    const calendarGrid = document.getElementById('calendarGrid');
    const monthTitle = document.getElementById('calendarMonthTitle');
    const form = document.getElementById('eventForm');
    const formAlert = document.getElementById('eventFormAlert');
    const btnSave = document.getElementById('btnSaveEvent');
    const attendeeSearch = document.getElementById('attendeeSearch');
    const attendeeResults = document.getElementById('attendeeResults');
    const selectedAttendeesContainer = document.getElementById('selectedAttendees');

    if (!window.bootstrap || !bootstrap.Modal) {
        console.error('Bootstrap JavaScript belum tersedia.');
        return;
    }

    const eventModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('eventModal'));
    const detailModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('eventDetailModal'));
    const dayEventsModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('dayEventsModal'));


    const monthFormatter = new Intl.DateTimeFormat('id-ID', { month:'long', year:'numeric' });
    const dateFormatter = new Intl.DateTimeFormat('id-ID', {
        weekday:'long', day:'numeric', month:'long', year:'numeric'
    });
    const dateTimeFormatter = new Intl.DateTimeFormat('id-ID', {
        day:'numeric', month:'short', year:'numeric',
        hour:'2-digit', minute:'2-digit', hourCycle:'h23'
    });

    function parseDate(value) {
        if (!value) return null;
        if (value instanceof Date) return isNaN(value.getTime()) ? null : value;
        const date = new Date(String(value).replace(' ', 'T'));
        return isNaN(date.getTime()) ? null : date;
    }

    function getEventStart(event) { return parseDate(event.start || event.start_at); }
    function getEventEnd(event) { return parseDate(event.end || event.end_at); }

    function formatTime(value) {
        const date = parseDate(value);
        return date ? date.toLocaleTimeString('id-ID', {
            hour:'2-digit', minute:'2-digit', hourCycle:'h23'
        }) : '-';
    }

    function formatDateTime(value) {
        const date = parseDate(value);
        return date ? dateTimeFormatter.format(date) : '-';
    }

    function escapeHtml(value) {
        return String(value ?? '').replace(/[&<>"']/g, character => ({
            '&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#039;'
        })[character]);
    }

    function getEventId(event) { return event.id ?? event.event_id ?? null; }

    function getCreatorId(event) {
        return Number(event.creator_id ?? event.created_by ?? event.user_id ?? event.created_by_user_id ?? 0);
    }

    
    function getEventStatus(event) {
        return String(event?.status || '')
            .trim()
            .toLowerCase()
            .replace(/[\s-]+/g, '_');
    }

    function isCancelled(event) {
        return ['cancelled', 'canceled'].includes(getEventStatus(event));
    }

    function isAnnouncement(event) {
        return getEventType(event) === 'announcement';
    }

    function getEventType(event) {
        return String(event?.event_type || event?.type || '')
            .trim()
            .toLowerCase();
    }

    function isMaintenanceRequest(event) {
        return [
            'maintenance_request',
            'repair_request'
        ].includes(getEventType(event));
    }

    function getStatusLabel(event) {
        if (!isMaintenanceRequest(event)) {
            return '';
        }

        const status = getEventStatus(event);

        const labels = {
            pending: 'Menunggu',
            approved: 'Disetujui',
            in_progress: 'In Progress',
            completed: 'Selesai',
            complete: 'Selesai',
            rejected: 'Ditolak',
            cancelled: 'Dibatalkan',
            canceled: 'Dibatalkan'
        };

        return labels[status] || '';
    }

    function canManage(event) {
        // Request maintenance/repair dikelola melalui modul request,
        // bukan melalui form edit Calendar.
        if (isMaintenanceRequest(event)) {
            return false;
        }

        return getCreatorId(event) === currentUserId
            && !isCancelled(event);
    }

    function canCancelEvent(event) {
        if (!event || isCancelled(event)) {
            return false;
        }

        if (isMaintenanceRequest(event)) {
            // Hanya pemohon yang boleh membatalkan request
            // yang masih menunggu.
            return getEventStatus(event) === 'pending'
                && getCreatorId(event) === currentUserId;
        }

        return getCreatorId(event) === currentUserId;
    }




    /*
     * Avatar helpers:
     * - Gunakan foto dari kolom users.photo.
     * - Jika foto tidak tersedia atau gagal dimuat, tampilkan inisial nama.
     */
    function getInitials(name) {
        const words = String(name || '').trim().split(/\s+/).filter(Boolean);

        if (!words.length) return '?';
        if (words.length === 1) return words[0].charAt(0).toUpperCase();

        return (words[0].charAt(0) + words[1].charAt(0)).toUpperCase();
    }

    function getAvatarColor(user) {
        const key = String(user?.id ?? user?.name ?? '?');
        let hash = 0;

        for (let i = 0; i < key.length; i++) {
            hash = (hash * 31 + key.charCodeAt(i)) | 0;
        }

        return Math.abs(hash) % 5;
    }

    function findUserById(id) {
        if (id === null || id === undefined) return null;
        return users.find(user => Number(user.id) === Number(id)) || null;
    }

    function normalizeAttendee(attendee) {
        if (attendee === null || attendee === undefined) return null;

        const id = typeof attendee === 'object'
            ? (attendee.id ?? attendee.user_id ?? attendee.pivot?.user_id)
            : attendee;

        const savedUser = findUserById(id);
        const source = typeof attendee === 'object' ? attendee : {};

        if (!savedUser && !source.name) return null;

        return {
            ...(savedUser || {}),
            ...source,
            id: id ?? savedUser?.id,
            name: source.name || savedUser?.name || 'Pengguna',
            email: source.email || savedUser?.email || '',
            photoUrl: source.photoUrl || source.photo_url ||
                      savedUser?.photoUrl || null
        };
    }

    function getEventAttendees(event) {
        let source = [];

        // Utamakan ID peserta agar bisa dicocokkan dengan data users.
        if (Array.isArray(event.attendee_ids) && event.attendee_ids.length) {
            source = event.attendee_ids;
        } else if (Array.isArray(event.attendees)) {
            source = event.attendees;
        }

        const normalized = source.map(normalizeAttendee).filter(Boolean);

        return normalized.filter((user, index, list) =>
            list.findIndex(item => String(item.id) === String(user.id)) === index
        );
    }


    function createAvatar(user, size = 'sm') {
        const safeUser = user || {};
        const name = safeUser.name || 'Pengguna';
        const wrapper = document.createElement('span');

        wrapper.className = 'calendar-avatar avatar-' + size +
            ' avatar-color-' + getAvatarColor(safeUser);
        wrapper.setAttribute('aria-label', name);
        wrapper.title = name;

        const initials = document.createElement('span');
        initials.textContent = getInitials(name);
        wrapper.appendChild(initials);

        const photoUrl = safeUser.photoUrl || safeUser.photo_url || null;

        if (photoUrl) {
            const image = document.createElement('img');
            image.src = photoUrl;
            image.alt = name;
            image.loading = 'lazy';

            image.addEventListener('error', function () {
                image.remove();
            });

            wrapper.appendChild(image);
        }

        return wrapper;
    }

    function createAvatarStack(attendees, maxVisible = 3) {
        const stack = document.createElement('div');
        stack.className = 'calendar-avatar-stack';

        const visible = attendees.slice(0, maxVisible);

        visible.forEach(user => {
            stack.appendChild(createAvatar(user, 'xs'));
        });

        const remaining = attendees.length - visible.length;

        if (remaining > 0) {
            const more = document.createElement('span');
            more.className = 'calendar-avatar-more';
            more.textContent = '+' + remaining;
            more.title = remaining + ' peserta lainnya';
            stack.appendChild(more);
        }

        return stack;
    }

    function clearFormErrors() {
        formAlert.classList.add('d-none');
        formAlert.textContent = '';

        document.querySelectorAll('.calendar-form-error').forEach(element => {
            element.textContent = '';
            element.classList.remove('show');
        });
    }

    function showFormErrors(errors) {
        clearFormErrors();
        const messages = [];

        Object.keys(errors || {}).forEach(field => {
            if (!errors[field]) return;
            messages.push(errors[field]);

            const target = document.querySelector('[data-error="' + field + '"]');

            if (target) {
                target.textContent = errors[field];
                target.classList.add('show');
            }
        });

        if (messages.length) {
            formAlert.textContent = messages.join(' ');
            formAlert.classList.remove('d-none');
        }
    }

    // Autocomplete peserta dengan avatar.
    function renderAttendeeResults() {
        const query = attendeeSearch.value.trim().toLowerCase();
        attendeeResults.replaceChildren();

        if (!query) {
            attendeeResults.classList.remove('show');
            return;
        }

        const selectedIds = new Set(selectedAttendees.map(user => Number(user.id)));

        const matches = users.filter(user => {
            const searchable = ((user.name || '') + ' ' + (user.email || '')).toLowerCase();
            return searchable.includes(query) && !selectedIds.has(Number(user.id));
        }).slice(0, 10);

        if (!matches.length) {
            const empty = document.createElement('div');
            empty.className = 'attendee-no-result';
            empty.textContent = 'Tidak ada peserta yang cocok.';
            attendeeResults.appendChild(empty);
            attendeeResults.classList.add('show');
            return;
        }

        matches.forEach(user => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'attendee-option';

            const avatar = createAvatar(user, 'md');

            const info = document.createElement('span');
            info.className = 'attendee-option-info';

            const name = document.createElement('span');
            name.className = 'attendee-option-name';
            name.textContent = user.name || 'Tanpa nama';

            const email = document.createElement('span');
            email.className = 'attendee-option-email';
            email.textContent = user.email || 'Tidak ada email';

            info.append(name, email);
            button.append(avatar, info);

            button.addEventListener('click', function () {
                selectedAttendees.push(user);
                attendeeSearch.value = '';
                attendeeResults.classList.remove('show');
                renderSelectedAttendees();
                attendeeSearch.focus();
            });

            attendeeResults.appendChild(button);
        });

        attendeeResults.classList.add('show');
    }

    function renderSelectedAttendees() {
        selectedAttendeesContainer.replaceChildren();

        selectedAttendees.forEach(user => {
            const chip = document.createElement('span');
            chip.className = 'attendee-chip';

            chip.appendChild(createAvatar(user, 'sm'));

            const name = document.createElement('span');
            name.className = 'attendee-chip-name';
            name.textContent = user.name || user.email || ('User #' + user.id);

            const remove = document.createElement('button');
            remove.type = 'button';
            remove.className = 'attendee-chip-remove';
            remove.innerHTML = '&times;';
            remove.setAttribute('aria-label', 'Hapus ' + (user.name || 'peserta'));

            remove.addEventListener('click', function () {
                selectedAttendees = selectedAttendees.filter(
                    item => Number(item.id) !== Number(user.id)
                );

                renderSelectedAttendees();
                renderAttendeeResults();
            });

            chip.append(name, remove);
            selectedAttendeesContainer.appendChild(chip);
        });
    }

    function setSelectedAttendees(ids) {
        const normalized = (ids || [])
            .map(normalizeAttendee)
            .filter(Boolean);

        selectedAttendees = normalized.filter((user, index, list) =>
            list.findIndex(item => Number(item.id) === Number(user.id)) === index
        );

        renderSelectedAttendees();
        attendeeSearch.value = '';
        attendeeResults.classList.remove('show');
    }

    function updateTypeFields() {
        const isMeeting = document.getElementById('eventType').value === 'meeting';

        document.getElementById('attendeesGroup').classList.toggle('d-none', !isMeeting);
        document.getElementById('meetingInfo').classList.toggle('d-none', !isMeeting);
        document.getElementById('announcementInfo').classList.toggle('d-none', isMeeting);
    }

    const startPicker = flatpickr('#eventStart', {
        enableTime:true,
        time_24hr:true,
        dateFormat:'Y-m-d H:i',
        minuteIncrement:5,
        disableMobile:true,
        allowInput:true
    });

    const endPicker = flatpickr('#eventEnd', {
        enableTime:true,
        time_24hr:true,
        dateFormat:'Y-m-d H:i',
        minuteIncrement:5,
        disableMobile:true,
        allowInput:true
    });

    function openCreateForm(date) {
        form.reset();
        clearFormErrors();

        selectedEvent = null;

        document.getElementById('eventId').value = '';
        document.getElementById('eventModalTitle').textContent = 'Tambah Jadwal';
        document.querySelector('#btnSaveEvent span').textContent = 'Simpan Jadwal';
        document.getElementById('eventType').value = 'meeting';

        const selectedDate = date ? new Date(date) : new Date();

        const start = new Date(
            selectedDate.getFullYear(),
            selectedDate.getMonth(),
            selectedDate.getDate(),
            9, 0, 0, 0
        );

        const end = new Date(
            selectedDate.getFullYear(),
            selectedDate.getMonth(),
            selectedDate.getDate(),
            18, 0, 0, 0
        );

        startPicker.setDate(start, true);
        endPicker.setDate(end, true);

        setSelectedAttendees([]);
        updateTypeFields();

        eventModal.show();
    }

    function openEditForm(event) {
        if (!canManage(event)) {
            alert('Anda hanya dapat mengubah jadwal yang Anda buat sendiri.');
            return;
        }

        selectedEvent = event;
        detailModal.hide();
        form.reset();
        clearFormErrors();

        document.getElementById('eventId').value = getEventId(event);
        document.getElementById('eventModalTitle').textContent = 'Edit Jadwal';
        document.querySelector('#btnSaveEvent span').textContent = 'Simpan Perubahan';
        document.getElementById('eventTitle').value = event.title || '';
        document.getElementById('eventType').value = event.event_type || 'meeting';
        document.getElementById('eventLocation').value = event.location || '';
        document.getElementById('eventDescription').value = event.description || '';

        const start = getEventStart(event);
        const end = getEventEnd(event);

        if (start) startPicker.setDate(start, true);
        if (end) endPicker.setDate(end, true);

        setSelectedAttendees(getEventAttendees(event));
        updateTypeFields();
        eventModal.show();
    }

    
function openDayEvents(dayDate, dayEvents) {
    const list = document.getElementById('dayEventsList');

    document.getElementById('dayEventsDate').textContent =
        dateFormatter.format(dayDate);

    list.replaceChildren();

    if (!dayEvents.length) {
        const empty = document.createElement('div');
        empty.className = 'calendar-day-list-empty';
        empty.textContent = 'Tidak ada jadwal pada tanggal ini.';
        list.appendChild(empty);
    }

    dayEvents.forEach(event => {
        const button = document.createElement('button');
        button.type = 'button';
        button.className =
            'calendar-day-list-item ' + eventTypeClassForList(event);

        const start = getEventStart(event);
        const end = getEventEnd(event);

        const time = document.createElement('span');
        time.className = 'calendar-day-list-time';
        time.textContent = formatTime(start) +
            (end ? ' - ' + formatTime(end) : '');

        const info = document.createElement('span');
        info.className = 'calendar-day-list-info';

        const title = document.createElement('span');
        title.className = 'calendar-day-list-title';
        title.textContent = event.title || 'Tanpa judul';

        const subtitle = document.createElement('span');
        subtitle.className = 'calendar-day-list-subtitle';

        const type = getEventType(event);
        const typeLabels = {
            meeting: 'Meeting',
            announcement: 'Pengumuman',
            maintenance: 'Jadwal Maintenance',
            maintenance_request: 'Permintaan Maintenance',
            repair_request: 'Permintaan Repair'
        };

        const statusLabel = getStatusLabel(event);

        subtitle.textContent =
            (typeLabels[type] || 'Agenda') +
            (statusLabel ? ' · ' + statusLabel : '');

        info.append(title, subtitle);
        button.append(time, info);

        button.addEventListener('click', function () {
            dayEventsModal.hide();

            // Tunggu popup daftar tertutup sebelum membuka detail.
            setTimeout(function () {
                openDetail(event);
            }, 250);
        });

        list.appendChild(button);
    });

    dayEventsModal.show();
}

function eventTypeClassForList(event) {
    const type = getEventType(event);

    const allowedTypes = [
        'meeting',
        'announcement',
        'maintenance',
        'maintenance_request',
        'repair_request'
    ];

    return allowedTypes.includes(type) ? type : 'meeting';
}

    function renderCalendar() {
        calendarGrid.querySelectorAll(
            '.calendar-day, .calendar-span-layer'
        ).forEach(element => element.remove());

        monthTitle.textContent = monthFormatter.format(currentDate);

        const year = currentDate.getFullYear();
        const month = currentDate.getMonth();
        const firstDay = new Date(year, month, 1);
        const lastDay = new Date(year, month + 1, 0);
        const offset = firstDay.getDay();

        const today = new Date();
        today.setHours(0, 0, 0, 0);

        const totalCells =
            Math.ceil((offset + lastDay.getDate()) / 7) * 7;

        const firstGridDate = new Date(year, month, 1 - offset);
        firstGridDate.setHours(0, 0, 0, 0);

        const dayDates = [];
        const dayCells = [];

        function dayOnly(value) {
            const date = new Date(value);
            date.setHours(0, 0, 0, 0);
            return date;
        }

        function eventEndDay(event) {
            const start = getEventStart(event);
            const end = getEventEnd(event);

            if (!start) return null;
            if (!end) return dayOnly(start);

            const result = dayOnly(end);

            // Selesai tepat pukul 00:00 berarti hari selesai
            // tidak dihitung sebagai hari event.
            if (
                end.getHours() === 0 &&
                end.getMinutes() === 0 &&
                end.getSeconds() === 0 &&
                result > dayOnly(start)
            ) {
                result.setDate(result.getDate() - 1);
            }

            return result;
        }

        function isMultiDay(event) {
            const start = getEventStart(event);
            const end = eventEndDay(event);

            return !!start && !!end &&
                dayOnly(start).getTime() !== end.getTime();
        }

        function eventTypeClass(event) {
            const type = String(
                event.event_type || event.type || ''
            ).toLowerCase();

            const allowedTypes = [
                'meeting',
                'announcement',
                'maintenance',
                'maintenance_request',
                'repair_request'
            ];

            return allowedTypes.includes(type) ? type : 'meeting';
        }

        function createSpanButton(event, continuesBefore, continuesAfter) {
            const button = document.createElement('button');
            button.type = 'button';
            button.className =
                'calendar-span-event ' + eventTypeClass(event);

            if (isCancelled(event)) {
                button.classList.add('cancelled');
            }

            if (continuesBefore) {
                button.style.borderTopLeftRadius = '0';
                button.style.borderBottomLeftRadius = '0';
                button.style.marginLeft = '0';
                button.style.borderLeftColor = 'transparent';
            }

            if (continuesAfter) {
                button.style.borderTopRightRadius = '0';
                button.style.borderBottomRightRadius = '0';
                button.style.marginRight = '0';
            }

            const title = document.createElement('span');
            title.className = 'calendar-span-title';

            const start = getEventStart(event);
            const end = getEventEnd(event);
            const time = start
                ? formatTime(start) + (end ? '–' + formatTime(end) : '')
                : '';

            title.textContent =
                (continuesBefore ? '' : (time ? time + ' · ' : '')) +
                (event.title || 'Tanpa judul');

            button.appendChild(title);
            button.title = (event.title || 'Tanpa judul') +
                (time ? ' | ' + time : '');

            button.addEventListener('click', function (e) {
                e.stopPropagation();
                openDetail(event);
            });

            return button;
        }

        // Buat tanggal kalender seperti struktur asli: 7 kolom.
        for (let i = 0; i < totalCells; i++) {
            const dayDate = new Date(firstGridDate);
            dayDate.setDate(firstGridDate.getDate() + i);
            dayDates.push(dayDate);

            const cell = document.createElement('div');
            cell.className = 'calendar-day';

            if (dayDate.getMonth() !== month) {
                cell.classList.add('other-month');
            }

            if (dayOnly(dayDate).getTime() === today.getTime()) {
                cell.classList.add('today');
            }

            const dayNumber = document.createElement('div');
            dayNumber.className = 'calendar-day-number';
            dayNumber.textContent = dayDate.getDate();
            cell.appendChild(dayNumber);

            // Event multi-hari ditampilkan sebagai bar, bukan
            // sebagai kartu berulang di setiap tanggal.
            const dayEvents = events.filter(event => {
                if (isMultiDay(event)) return false;

                const start = getEventStart(event);

                return start &&
                    start.getFullYear() === dayDate.getFullYear() &&
                    start.getMonth() === dayDate.getMonth() &&
                    start.getDate() === dayDate.getDate();
            }).sort((a, b) =>
                (getEventStart(a)?.getTime() || 0) -
                (getEventStart(b)?.getTime() || 0)
            );

            const maxVisible = window.innerWidth < 768 ? 2 : 3;

            dayEvents.slice(0, maxVisible).forEach(event => {
                const button = document.createElement('button');
                button.type = 'button';
                button.className =
                    'calendar-event ' + eventTypeClass(event);

                if (isCancelled(event)) {
                    button.classList.add('cancelled');
                }

                const start = getEventStart(event);
                const end = getEventEnd(event);
                const timeRange = formatTime(start) +
                    (end ? ' - ' + formatTime(end) : '');

                const time = document.createElement('span');
                time.className = 'calendar-event-time';
                time.textContent = timeRange;

                const title = document.createElement('span');
                title.className = 'calendar-event-title';
                title.textContent = event.title || 'Tanpa judul';

                button.append(time, title);

                const attendees = isAnnouncement(event)
                    ? []
                    : getEventAttendees(event);

                if (attendees.length) {
                    button.appendChild(createAvatarStack(attendees, 3));
                }

                button.title = timeRange + ' | ' +
                    (event.title || 'Tanpa judul');

                button.addEventListener('click', function (e) {
                    e.stopPropagation();
                    openDetail(event);
                });

                cell.appendChild(button);
            });

            
if (dayEvents.length > maxVisible) {
    const more = document.createElement('button');
    more.type = 'button';
    more.className = 'calendar-more border-0 bg-transparent p-0';
    more.textContent =
        '+' + (dayEvents.length - maxVisible) + ' jadwal lagi';

    more.setAttribute(
        'aria-label',
        'Lihat semua ' + dayEvents.length +
        ' jadwal tanggal ' + dateFormatter.format(dayDate)
    );

    more.addEventListener('click', function (e) {
        e.stopPropagation();
        openDayEvents(dayDate, dayEvents);
    });

    cell.appendChild(more);
}


            cell.addEventListener('click', function () {
                openCreateForm(dayDate);
            });

            calendarGrid.appendChild(cell);
            dayCells.push(cell);
        }

        // Buat lapisan terpisah agar bar multi-hari tidak
        // menjadi item baru dalam CSS Grid utama.
        const spanLayer = document.createElement('div');
        spanLayer.className = 'calendar-span-layer';
        calendarGrid.appendChild(spanLayer);

        const spanEvents = events
            .filter(isMultiDay)
            .map(event => {
                const start = getEventStart(event);
                const end = eventEndDay(event);

                if (!start || !end) return null;

                return {
                    event,
                    startDay: dayOnly(start),
                    endDay: end
                };
            })
            .filter(Boolean)
            .sort((a, b) =>
                a.startDay.getTime() - b.startDay.getTime()
            );

        spanEvents.forEach(({ event, startDay, endDay }) => {
            const startIndex = Math.round(
                (startDay.getTime() - firstGridDate.getTime()) / 86400000
            );

            const endIndex = Math.round(
                (endDay.getTime() - firstGridDate.getTime()) / 86400000
            );

            // Abaikan event yang sama sekali berada di luar kalender.
            if (endIndex < 0 || startIndex >= totalCells) return;

            let segmentStart = Math.max(0, startIndex);

            while (segmentStart <= Math.min(endIndex, totalCells - 1)) {
                const weekIndex = Math.floor(segmentStart / 7);

                const weekEnd = Math.min(
                    (weekIndex + 1) * 7 - 1,
                    totalCells - 1,
                    endIndex
                );

                const firstCell = dayCells[segmentStart];
                const lastCell = dayCells[weekEnd];

                if (firstCell && lastCell) {
                    const button = createSpanButton(
                        event,
                        startIndex < segmentStart,
                        endIndex > weekEnd
                    );

                    spanLayer.appendChild(button);

                    // Hitung posisi berdasarkan kotak tanggal sebenarnya,
                    // bukan berdasarkan nomor item CSS Grid.
                    const layerRect = spanLayer.getBoundingClientRect();
                    const firstRect = firstCell.getBoundingClientRect();
                    const lastRect = lastCell.getBoundingClientRect();

                    button.style.left =
                        (firstRect.left - layerRect.left) + 'px';

                    button.style.top =
                        (firstRect.top - layerRect.top + 40) + 'px';

                    button.style.width =
                        (lastRect.right - firstRect.left) + 'px';

                    button.style.height = '23px';
                }

                segmentStart = weekEnd + 1;
            }
        });
    }

    function openDetail(event) {
        selectedEvent = event;

        const type = String(
            event.event_type || event.type || ''
        ).toLowerCase();

        const cancelled = isCancelled(event);

        const labels = {
            meeting: 'Meeting',
            announcement: 'Pengumuman',
            maintenance: 'Jadwal Maintenance',
            maintenance_request: 'Permintaan Maintenance',
            repair_request: 'Permintaan Repair'
        };

        const badgeClasses = {
            meeting: 'calendar-status-meeting',
            announcement: 'calendar-status-announcement',
            maintenance: 'calendar-status-maintenance',
            maintenance_request: 'calendar-status-maintenance',
            repair_request: 'calendar-status-repair'
        };

        const typeBadge = document.getElementById('detailType');

        typeBadge.textContent = labels[type] || 'Agenda';
        typeBadge.className =
            'calendar-status ' +
            (badgeClasses[type] || 'calendar-status-meeting');

        document.getElementById('detailCancelled')
            .classList.toggle('d-none', !cancelled);

        document.getElementById('detailTitle').textContent =
            event.title || 'Tanpa judul';

        const start = getEventStart(event);
        const end = getEventEnd(event);

        let dateText = start ? dateFormatter.format(start) : '-';

        if (start) {
            dateText += '\n' + formatTime(start);

            if (end) {
                dateText += ' - ' + (
                    start.toDateString() === end.toDateString()
                        ? formatTime(end)
                        : formatDateTime(end)
                );
            }
        }

        document.getElementById('detailDate').textContent = dateText;

        const location = event.location || '';

        document.getElementById('detailLocation').textContent =
            location || '-';

        document.getElementById('detailLocationRow')
            .classList.toggle('d-none', !location);

        const description = event.description || '';

        document.getElementById('detailDescription').textContent =
            description || '-';

        document.getElementById('detailDescriptionRow')
            .classList.toggle('d-none', !description);

        const attendees = getEventAttendees(event);
        const container = document.getElementById('detailAttendees');

        container.replaceChildren();

        attendees.forEach(attendee => {
            const item = document.createElement('div');
            item.className = 'calendar-attendee-item';

            item.appendChild(createAvatar(attendee, 'md'));

            const info = document.createElement('div');
            info.className = 'calendar-attendee-info';

            const name = document.createElement('div');
            name.className = 'calendar-attendee-name';
            name.textContent =
                attendee.name || ('User #' + attendee.id);

            info.appendChild(name);

            if (attendee.email) {
                const email = document.createElement('div');
                email.className = 'calendar-attendee-email';
                email.textContent = attendee.email;
                info.appendChild(email);
            }

            item.appendChild(info);
            container.appendChild(item);
        });

        const isCompanyAnnouncement = type === 'announcement';

        document.getElementById('detailAttendeesRow')
            .classList.toggle(
                'd-none',
                isCompanyAnnouncement || attendees.length === 0
            );
        
        document.getElementById('btnEditEvent')
            .classList.toggle('d-none', !canManage(event));

        document.getElementById('btnCancelEvent')
            .classList.toggle('d-none', !canCancelEvent(event));



        detailModal.show();
    }

    async function submitEvent(e) {
        e.preventDefault();
        clearFormErrors();

        const id = document.getElementById('eventId').value;
        const type = document.getElementById('eventType').value;

        const payload = {
            title: document.getElementById('eventTitle').value.trim(),
            event_type: type,
            start_at: startPicker.input.value.trim(),
            end_at: endPicker.input.value.trim(),
            location: document.getElementById('eventLocation').value.trim() || null,
            description: document.getElementById('eventDescription').value.trim() || null,
            attendees: type === 'meeting'
                ? selectedAttendees.map(user => Number(user.id))
                : []
        };

        const errors = {};

        if (!payload.title) errors.title = 'Judul wajib diisi.';
        if (!payload.start_at) errors.start_at = 'Waktu mulai wajib diisi.';
        if (!payload.end_at) errors.end_at = 'Waktu selesai wajib diisi.';

        if (Object.keys(errors).length) {
            showFormErrors(errors);
            return;
        }

        const start = parseDate(payload.start_at);
        const end = parseDate(payload.end_at);

        if (!start || !end || end <= start) {
            showFormErrors({ end_at:'Waktu selesai harus setelah waktu mulai.' });
            return;
        }

        const originalButton = btnSave.innerHTML;
        btnSave.disabled = true;
        btnSave.innerHTML = '<i class="fas fa-spinner fa-spin"></i><span>Menyimpan...</span>';

        try {
            const url = id
                ? updateUrlTemplate.replace('__EVENT_ID__', encodeURIComponent(id))
                : storeUrl;

            const response = await fetch(url, {
                method:id ? 'PUT' : 'POST',
                headers:{
                    'Content-Type':'application/json',
                    'Accept':'application/json',
                    'X-CSRF-TOKEN':csrfToken,
                    'X-Requested-With':'XMLHttpRequest'
                },
                body:JSON.stringify(payload)
            });

            const result = await response.json().catch(() => ({}));

            if (!response.ok) {
                if (response.status === 422 && result.errors) {
                    const validationErrors = {};

                    Object.entries(result.errors).forEach(([key, value]) => {
                        validationErrors[key] = Array.isArray(value) ? value[0] : value;
                    });

                    showFormErrors(validationErrors);
                } else {
                    console.error('Calendar save error:', {
                        status: response.status,
                        result: result
                    });

                    formAlert.textContent =
                        result.message ||
                        (result.errors
                            ? Object.values(result.errors).flat().join(' ')
                            : 'HTTP ' + response.status + ' - Periksa Console browser.');

                    formAlert.classList.remove('d-none');
                }

                return;
            }

            eventModal.hide();
            window.location.reload();
        } catch (error) {
            console.error(error);
            formAlert.textContent = 'Terjadi kesalahan saat menghubungi server. Silakan coba lagi.';
            formAlert.classList.remove('d-none');
        } finally {
            btnSave.disabled = false;
            btnSave.innerHTML = originalButton;
        }
    }

    async function cancelEvent() {
        if (!selectedEvent || !canCancelEvent(selectedEvent)) return;

        const isRequest = isMaintenanceRequest(selectedEvent);

        const message = isRequest
            ? 'Yakin ingin membatalkan permintaan "' +
            (selectedEvent.title || 'Maintenance/Repair') + '"?'
            : 'Yakin ingin membatalkan jadwal "' +
            (selectedEvent.title || 'Tanpa judul') + '"?';

        if (!confirm(message)) return;

        const rawId = getEventId(selectedEvent);

        const id = isRequest
            ? String(rawId).replace(/^request-/, '')
            : rawId;


        if (!id) {
            alert('ID jadwal tidak ditemukan.');
            return;
        }

        // Request maintenance menggunakan endpoint khusus.
        const urlTemplate = isRequest
            ? cancelRequestUrlTemplate
            : deleteUrlTemplate;

        if (!urlTemplate) {
            alert('URL pembatalan belum dikonfigurasi.');
            return;
        }

        const button = document.getElementById('btnCancelEvent');
        const originalText = button.innerHTML;

        button.disabled = true;
        button.innerHTML = 'Memproses...';

        try {
            const response = await fetch(
                urlTemplate.replace('__EVENT_ID__', encodeURIComponent(id)),
                {
                    method: isRequest ? 'POST' : 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                }
            );

            const result = await response.json().catch(() => ({}));

            if (!response.ok || result.success === false) {
                alert(result.message || 'Jadwal gagal dibatalkan.');
                return;
            }

            window.location.reload();

        } catch (error) {
            console.error('Calendar cancel error:', error);
            alert('Terjadi kesalahan saat membatalkan jadwal.');
        } finally {
            button.disabled = false;
            button.innerHTML = originalText;
        }
    }

    attendeeSearch.addEventListener('input', renderAttendeeResults);
    attendeeSearch.addEventListener('focus', renderAttendeeResults);

    document.addEventListener('click', function (e) {
        if (!e.target.closest('.attendee-autocomplete')) {
            attendeeResults.classList.remove('show');
        }
    });

    document.getElementById('btnAddEvent').addEventListener('click', () => openCreateForm(new Date()));

    document.getElementById('btnPrevMonth').addEventListener('click', function () {
        currentDate.setMonth(currentDate.getMonth() - 1);
        renderCalendar();
    });

    document.getElementById('btnNextMonth').addEventListener('click', function () {
        currentDate.setMonth(currentDate.getMonth() + 1);
        renderCalendar();
    });

    document.getElementById('btnToday').addEventListener('click', function () {
        currentDate = new Date();
        renderCalendar();
    });

    document.getElementById('eventType').addEventListener('change', updateTypeFields);

    document.getElementById('btnEditEvent').addEventListener('click', () => {
        if (selectedEvent) openEditForm(selectedEvent);
    });

    document.getElementById('btnCancelEvent').addEventListener('click', cancelEvent);
    form.addEventListener('submit', submitEvent);

    renderCalendar();
    updateTypeFields();
});
</script>
@endsection