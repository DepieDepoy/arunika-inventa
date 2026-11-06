<style>
    .bx-kpi { padding:18px 20px; height:100%; }
    .bx-kpi .lbl { font-size:.78rem; font-weight:700; color:#8592a6; display:flex; align-items:center; gap:6px; }
    .bx-kpi .val { font-size:1.6rem; font-weight:800; color:#0b2447; line-height:1.15; margin-top:6px; font-variant-numeric:tabular-nums; }
    .bx-kpi .sub { font-size:.76rem; color:#8592a6; margin-top:4px; }
    .bx-kpi.hero { background:linear-gradient(125deg,#0b2447 0%,#163f86 45%,#1f5fbf 75%,#14b8a6 130%); color:#fff; border:0; }
    .bx-kpi.hero .lbl, .bx-kpi.hero .sub { color:rgba(255,255,255,.75); }
    .bx-kpi.hero .val { color:#fff; }
    .bx-h { padding:18px 20px 0; display:flex; justify-content:space-between; align-items:flex-start; gap:10px; flex-wrap:wrap; }
    .bx-h h6 { margin:0; font-weight:800; color:#0b2447; }
    .bx-h .hint { font-size:.76rem; color:#8592a6; }
    .bx-h a.more { font-size:.8rem; font-weight:700; }
    .bx-empty { padding:26px 20px; text-align:center; color:#8592a6; font-size:.86rem; }
    .bx-table th { font-size:.7rem; letter-spacing:.06em; text-transform:uppercase; color:#8592a6; font-weight:800; white-space:nowrap; }
    .bx-table td { font-size:.84rem; }
    .bx-co { display:flex; gap:10px; align-items:center; min-width:0; }
    .bx-co .av { width:34px; height:34px; border-radius:10px; flex:none; display:grid; place-items:center; font-weight:800; font-size:.8rem; color:#1f5fbf; background:#e8f0fd; }
    .bx-co b { display:block; color:#0b2447; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .bx-co small { color:#8592a6; }
    .bx-meter { min-width:120px; }
    .bx-meter .top { display:flex; justify-content:space-between; gap:6px; font-size:.76rem; color:#4b5a6e; font-variant-numeric:tabular-nums; margin-bottom:4px; white-space:nowrap; }
    .bx-meter .track { height:6px; border-radius:3px; background:#eef2f7; overflow:hidden; }
    .bx-meter .fill { height:100%; border-radius:3px; background:#2a78d6; min-width:2px; }
    .bx-meter .fill.warn { background:#eda100; }
    .bx-meter .fill.crit { background:#e34948; }
    .bx-st { font-size:.7rem; font-weight:800; padding:3px 9px; border-radius:999px; white-space:nowrap; display:inline-flex; align-items:center; gap:4px; }
    .bx-st.trial { background:#fef3c7; color:#92400e; }
    .bx-st.paid { background:#dcfce7; color:#166534; }
    .bx-st.expired { background:#eef2f7; color:#4b5a6e; }
    .bx-st.suspended { background:#fee2e2; color:#991b1b; }
    .bx-bars { padding:14px 20px 18px; }
    .bx-bar { margin-bottom:12px; }
    .bx-bar:last-child { margin-bottom:0; }
    .bx-bar .top { display:flex; justify-content:space-between; font-size:.84rem; margin-bottom:5px; }
    .bx-bar .top span:first-child { font-weight:600; color:#24364d; }
    .bx-bar .top span:last-child { color:#4b5a6e; font-variant-numeric:tabular-nums; }
    .bx-bar .track { height:8px; border-radius:4px; background:#eef2f7; overflow:hidden; }
    .bx-bar .fill { height:100%; border-radius:4px; background:#2a78d6; min-width:3px; }
    .bx-list { list-style:none; margin:0; padding:8px 20px 16px; }
    .bx-list li { display:flex; justify-content:space-between; align-items:center; gap:10px; padding:10px 0; border-bottom:1px dashed rgba(15,27,45,.08); }
    .bx-list li:last-child { border-bottom:0; }
    .bx-list .r { text-align:right; font-size:.8rem; color:#4b5a6e; white-space:nowrap; }
    .bx-list .r b { display:block; color:#0b2447; font-size:.86rem; }
</style>
@php
    if (!function_exists('bx_bytes')) {
        function bx_bytes($b) {
            $b = (float) $b;
            if ($b >= 1073741824) return number_format($b / 1073741824, 2, ',', '.') . ' GB';
            if ($b >= 1048576) return number_format($b / 1048576, 1, ',', '.') . ' MB';
            if ($b >= 1024) return number_format($b / 1024, 0, ',', '.') . ' KB';
            return number_format($b, 0, ',', '.') . ' B';
        }
        function bx_num($v) { return number_format((float) $v, 0, ',', '.'); }
        function bx_rp($v) { return 'Rp' . number_format((float) $v, 0, ',', '.'); }
        function bx_initials($name) {
            $w = preg_split('/\s+/', trim(preg_replace('/^(PT|CV|UD)\.?\s+/i', '', (string) $name)));
            return strtoupper(mb_substr($w[0] ?? '?', 0, 1) . mb_substr($w[1] ?? '', 0, 1));
        }
        function bx_pct($used, $limit) { return $limit ? min(100, round($used / $limit * 100, 1)) : 0; }
        function bx_level($pct) { return $pct >= 95 ? 'crit' : ($pct >= 80 ? 'warn' : ''); }
    }

@endphp
