<?php

namespace App\Services\Landing;

/**
 * Teks bawaan landing page versi bahasa Inggris (/en).
 * Struktur kunci harus sama persis dengan LandingContent::defaultsId().
 */
class LandingContentEn
{
    public static function defaults(): array
    {
        return [
            'seo' => [
                'title' => 'Vasetra — Asset Lifecycle Platform | Manage Company Assets in One Place',
                'description' => 'Vasetra helps companies register assets, assign them to employees, schedule maintenance, and handle repair requests in one platform. Free {hari_trial}-day trial, no credit card required.',
                'og_image' => null,
            ],
            'hero' => [
                'badge' => 'No credit card · Instant access',
                'title' => 'Every company asset,',
                'highlight' => 'under control in one platform.',
                'lead' => 'Vasetra tracks every asset from purchase, employee assignment, and maintenance schedules to repairs — complete with QR labels, technician notifications, and a clean audit trail.',
                'secondary_cta' => 'See how it works',
                'checks' => ['All features unlocked during the trial', 'Upgrade anytime', 'Import data from Excel'],
                'image' => null,
            ],
            'compare' => [
                'kicker' => 'Before & After',
                'title' => 'Scattered asset data,',
                'highlight' => 'overwhelmed teams.',
                'subtitle' => 'Excel files in different versions, damage reports buried in group chats, forgotten service schedules. Drag the line in the middle to see the difference.',
                'footer_title' => 'Less searching, more doing.',
                'footer_text' => 'Assets, maintenance, and damage reports connected in one place.',
            ],
            'features' => [
                'kicker' => 'Features',
                'title' => 'One place for the entire asset lifecycle',
                'highlight' => '',
                'subtitle' => 'Leave scattered spreadsheets and group chats for damage reports behind. Everything is recorded, scheduled, and traceable.',
                'items' => [
                    ['icon' => 'bi-qr-code', 'title' => 'Asset registry & QR labels', 'text' => 'Automatic asset codes, photos, invoices, warranties, locations, and vendors. Print QR labels and scan to see asset details.'],
                    ['icon' => 'bi-arrow-left-right', 'title' => 'Assignment & handover', 'text' => 'Hand assets to employees, transfer them, or take them back — every movement is recorded with its reason.'],
                    ['icon' => 'bi-calendar2-check', 'title' => 'Scheduled maintenance', 'text' => 'Set service intervals per asset. Overdue, today, and this week schedules appear right on the dashboard and calendar.'],
                    ['icon' => 'bi-tools', 'title' => 'Requests & repairs', 'text' => 'Employees report, technicians take the job, progress is logged with photos. Requesters are notified at every step.'],
                    ['icon' => 'bi-graph-up-arrow', 'title' => 'Value & depreciation', 'text' => 'Calculate straight-line depreciation and track book value and maintenance costs for every asset.'],
                    ['icon' => 'bi-calendar3', 'title' => 'Calendar & meetings', 'text' => 'Maintenance, requests, and team meetings in one calendar. Meeting invitations are sent by email.'],
                    ['icon' => 'bi-shield-lock', 'title' => 'Access control & audit trail', 'text' => 'Set permissions per role. Every change, login, and data export is recorded: who, when, and what changed.'],
                    ['icon' => 'bi-file-earmark-spreadsheet', 'title' => 'Excel import & export', 'text' => 'Move existing asset and user data in bulk via Excel, with a preview and a report of rows that failed.'],
                    ['icon' => 'bi-stars', 'title' => 'Vasetra AI', 'text' => 'Ask about your assets in plain language, e.g. "which assets are overdue for maintenance?"'],
                ],
            ],
            'tour' => [
                'kicker' => 'Product Tour',
                'title' => 'Less re-entering data.',
                'highlight' => 'More work done.',
                'subtitle' => 'These are the screens your team uses every day — clean, fast, and easy to understand without long training.',
                'shot_aset' => null, 'shot_detail' => null, 'shot_jadwal' => null, 'shot_request' => null, 'shot_kalender' => null,
            ],
            'lifecycle' => [
                'kicker' => 'Workflow',
                'title' => 'From purchase to audit, everything is recorded',
                'highlight' => '',
                'subtitle' => 'Every stage is connected, so the asset history is always complete without re-entering data.',
                'steps' => [
                    ['icon' => 'bi-bag-check', 'title' => 'Procurement', 'text' => 'Record purchases, vendors, prices, invoices, and warranties.'],
                    ['icon' => 'bi-upc-scan', 'title' => 'Registration', 'text' => 'Automatic asset codes and print-ready QR labels.'],
                    ['icon' => 'bi-person-badge', 'title' => 'Assignment', 'text' => 'Hand over to employees, transfer, or return.'],
                    ['icon' => 'bi-wrench-adjustable-circle', 'title' => 'Maintenance', 'text' => 'Routine schedules and repair requests handled by technicians.'],
                    ['icon' => 'bi-cash-stack', 'title' => 'Evaluation', 'text' => 'Book value, depreciation, and total maintenance cost.'],
                    ['icon' => 'bi-clipboard2-data', 'title' => 'Audit & reports', 'text' => 'A complete trail of every change, ready to export.'],
                ],
            ],
            'request' => [
                'kicker' => 'Repair Requests',
                'title' => 'Report damage without messy group chats',
                'highlight' => '',
                'subtitle' => 'Every report reaches the technician team instantly, is handled by one person, and the requester sees progress without having to ask.',
                'steps' => [
                    ['title' => 'Employee submits a request', 'text' => 'Choose the asset they hold and describe the issue. The whole technician team is notified.'],
                    ['title' => 'A technician takes it', 'text' => 'First come, first served. The request disappears from other technicians\' queues and shows "being handled by Budi".'],
                    ['title' => 'Progress with photos', 'text' => 'Each update is logged with photos, and the requester is notified.'],
                    ['title' => 'Done & added to history', 'text' => 'Actions, costs, and result photos are saved to the asset\'s maintenance history.'],
                ],
                'image' => null,
            ],
            'roles' => [
                'kicker' => 'Team Roles',
                'title' => 'Everyone sees only what they need',
                'highlight' => '',
                'subtitle' => 'Three built-in roles are ready from day one. No need to set up permissions from scratch.',
                'items' => [
                    ['icon' => 'bi-person-gear', 'title' => 'Administrator', 'sub' => 'Manages the application', 'points' => ['Manage assets, categories, vendors, and users', 'Set permissions for every role', 'Monitor quotas, subscription, and audit trail']],
                    ['icon' => 'bi-tools', 'title' => 'Technician', 'sub' => 'Maintenance team', 'points' => ['Get notified about new requests', 'Take on and complete repairs', 'View maintenance schedules & history']],
                    ['icon' => 'bi-person-badge', 'title' => 'Employee', 'sub' => 'Asset holder', 'points' => ['See the assets they are responsible for', 'Report damage or request service', 'Track the status of their requests']],
                ],
                'note' => 'Need another role, e.g. Warehouse Staff? Create your own role and choose its permissions.',
            ],
            'solutions' => [
                'kicker' => 'Solutions',
                'title' => 'Built for the way',
                'highlight' => 'your organization works.',
                'subtitle' => 'Start with what you need today, and keep growing with it.',
                'items' => [
                    ['icon' => 'bi-buildings', 'label' => 'Offices & Corporates', 'title' => 'Laptops & office inventory under control',
                        'text' => 'Devices change hands every time someone joins, moves, or leaves. Vasetra makes sure every device has a clear owner.',
                        'checks' => ['Laptop handover at onboarding & offboarding', 'Know who holds which device', 'Scheduled AC & printer servicing'],
                        'features' => ['bi-arrow-left-right | Asset assignment & returns', 'bi-qr-code | QR label on every device', 'bi-megaphone | Employees report damage themselves', 'bi-file-earmark-spreadsheet | Data export for the finance team']],
                    ['icon' => 'bi-mortarboard', 'label' => 'Schools & Campuses', 'title' => 'Classrooms & labs always ready',
                        'text' => 'Projectors, lab computers, and classroom equipment are spread across many rooms. Track them by location and service them before they break.',
                        'checks' => ['Inventory per classroom & lab', 'Service schedules for projectors & lab PCs', 'Damage reports straight from teachers'],
                        'features' => ['bi-geo-alt | Asset location per room', 'bi-calendar2-check | Periodic maintenance', 'bi-clock-history | Repair history for every facility', 'bi-clipboard2-data | Inventory data ready for audits']],
                    ['icon' => 'bi-hospital', 'label' => 'Clinics & Hospitals', 'title' => 'Medical equipment maintained, calibrations never missed',
                        'text' => 'Medical equipment must be maintained and calibrated on time. Keep schedules, vendors, warranties, and history in one place.',
                        'checks' => ['Calibration & inspection schedules', 'Service history ready for audits', 'Warranty & vendor recorded for every device'],
                        'features' => ['bi-activity | Maintenance interval per device', 'bi-shield-check | Audit trail for every change', 'bi-truck | Vendor & service contacts', 'bi-camera | Photo proof of repairs']],
                    ['icon' => 'bi-gear-wide-connected', 'label' => 'Factories & Warehouses', 'title' => 'Production machines maintained, less downtime',
                        'text' => 'Machine failures handled late mean production stops. Maintain preventively and speed up technician response.',
                        'checks' => ['Interval-based preventive maintenance', 'Technicians notified instantly when something breaks', 'Maintenance cost per machine at a glance'],
                        'features' => ['bi-tools | Request queue for the technician team', 'bi-hand-index-thumb | One request, one owner', 'bi-cash-stack | Total maintenance cost per asset', 'bi-graph-down-arrow | Machine depreciation & book value']],
                    ['icon' => 'bi-house-door', 'label' => 'Hotels & Properties', 'title' => 'Rooms & guest facilities always in top shape',
                        'text' => 'Every room and area has dozens of assets. Staff simply report, technicians take the job, and guests never notice the problem.',
                        'checks' => ['Assets recorded per room & area', 'Damage reports from housekeeping staff', 'Repairs completed with photo proof'],
                        'features' => ['bi-door-open | Asset location per room', 'bi-bell | Notifications to the technician on duty', 'bi-camera | Progress & result photos', 'bi-calendar3 | Routine facility maintenance schedule']],
                ],
            ],
            'mobile' => [
                'badge' => 'Android & iOS apps coming soon',
                'title' => 'Manage assets right from the field',
                'text' => 'Vasetra works in the browser on laptops and phones. A mobile app is on the way so your team can work faster without opening a laptop.',
                'points' => ['bi-qr-code-scan | Scan QR for asset details', 'bi-megaphone | Report damage from your phone', 'bi-hand-index-thumb | Technicians take requests', 'bi-camera | Progress & result photos'],
                'play_store_url' => '',
                'app_store_url' => '',
                'image' => null,
            ],
            'pricing' => [
                'kicker' => 'Pricing',
                'title' => 'Try it free, pay when you are sure',
                'highlight' => '',
                'subtitle' => 'Start with a {hari_trial}-day free trial, no credit card required. Choose a plan anytime — even on day one.',
                'steps' => [
                    ['title' => 'Sign up in minutes', 'text' => 'Enter your company name & admin account, verify your email.'],
                    ['title' => 'Use it free for {hari_trial} days', 'text' => 'Try every feature with your team.'],
                    ['title' => 'Upgrade anytime', 'text' => 'The new plan is active immediately, trial data is kept.'],
                ],
                'recommended_plan' => '',
                'plan_features' => ['QR, assignment & maintenance', 'Repair requests & team notifications', 'Calendar, audit trail & Vasetra AI', 'Excel import & export'],
                'notes' => [
                    ['icon' => 'bi-lightning-charge-fill', 'title' => 'Upgrades apply instantly', 'text' => 'Still on the trial and want a business plan tomorrow? Sure — the new plan is active right away.'],
                    ['icon' => 'bi-plus-square-fill', 'title' => 'Flexible extra storage', 'text' => 'Running out of storage? Add more without changing plans. Need more users or assets? Simply move up a plan.'],
                    ['icon' => 'bi-credit-card-2-front-fill', 'title' => 'Secure payments', 'text' => 'Processed via the DOKU payment gateway. Prices in Indonesian Rupiah.'],
                ],
            ],
            'faq' => [
                'kicker' => 'FAQ',
                'title' => 'Frequently asked questions',
                'highlight' => '',
                'subtitle' => '',
                'items' => [
                    ['q' => 'Is the trial really free?', 'a' => 'Yes. You can use Vasetra free for {hari_trial} days without entering a credit card or any payment details. There is no automatic billing — you choose a plan when you are ready.'],
                    ['q' => 'Can I upgrade before the trial ends?', 'a' => 'Yes, anytime from the Subscription menu in the dashboard. Once payment succeeds, the trial ends and the new plan is active immediately with larger quotas.'],
                    ['q' => 'What happens to my data when the trial ends?', 'a' => 'Your data stays safe. Access to features is paused until you choose a plan, and once you subscribe everything is available again without re-entering anything.'],
                    ['q' => 'Is my company\'s data separate from other companies?', 'a' => 'Yes. Each company has its own data space, access is limited by role, and important changes are recorded in an audit trail visible only to Administrators.'],
                    ['q' => 'Our asset data is still in Excel. Can we move it?', 'a' => 'Yes. Download the template, fill in your asset or user data, and upload it. Vasetra shows a preview and tells you which rows need fixing before saving.'],
                    ['q' => 'How do I pay?', 'a' => 'Payments are made from the dashboard via the DOKU payment gateway, using the payment methods available on the DOKU checkout page. Payment history and receipts are kept in the Subscription menu.'],
                ],
            ],
            'cta' => [
                'title' => 'Start managing your assets better today',
                'text' => 'Sign up now, invite your team, and feel the difference in {hari_trial} days — free.',
            ],
            'footer' => [
                'tagline' => 'An asset lifecycle management platform for companies in Indonesia — from procurement to audit.',
                'company' => 'PT Arunika Solusi Inovasi',
                'email' => 'info@vasetra.com',
                'whatsapp' => '6281211955805',
                'whatsapp_text' => 'Hi Vasetra, I have a question..',
                'address' => 'Enablerspace.id, Kebayoran Lama, South Jakarta',
                'maps_url' => 'https://maps.app.goo.gl/NMFYRh6x2uAfrxmM9',
            ],
        ];
    }
}
