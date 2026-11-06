<style>
    .cms-card { background:var(--bs-body-bg); border:1px solid rgba(15,27,45,.08); border-radius:16px; box-shadow:0 1px 2px rgba(15,27,45,.04), 0 8px 24px -16px rgba(15,27,45,.16); }
    .cms-badge { display:inline-flex; align-items:center; gap:6px; font-size:.72rem; font-weight:800; padding:4px 10px; border-radius:999px; background:#e8f0fd; color:#1f5fbf; }
    .cms-section { display:flex; gap:14px; align-items:flex-start; height:100%; padding:18px; border-radius:16px; border:1px solid rgba(15,27,45,.08); background:var(--bs-body-bg); color:inherit; transition:border-color .15s, box-shadow .15s, transform .15s; }
    .cms-section:hover { border-color:#1f5fbf; box-shadow:0 12px 26px -18px rgba(31,95,191,.6); transform:translateY(-2px); color:inherit; }
    .cms-section-icon { width:44px; height:44px; border-radius:12px; flex:none; display:grid; place-items:center; font-size:1.25rem; color:#fff; background:linear-gradient(125deg,#0b2447 0%,#163f86 38%,#1f5fbf 64%,#14b8a6 118%); }
    .cms-section-title { font-size:.98rem; }
    .cms-section-desc { display:block; font-size:.82rem; color:#8592a6; margin-top:3px; }
    .cms-section-meta { display:block; font-size:.74rem; color:#14b8a6; margin-top:6px; font-weight:600; }
    .cms-chip { font-size:.66rem; font-weight:800; padding:2px 8px; border-radius:999px; background:#eef2f7; color:#4b5a6e; white-space:nowrap; }
    .cms-chip-blue { background:#dbeafe; color:#1d4ed8; }
    .cms-field { margin-bottom:1.25rem; }
    .cms-field > label { font-weight:700; font-size:.88rem; margin-bottom:6px; display:block; }
    .cms-help { font-size:.76rem; color:#8592a6; margin-top:4px; }
    .cms-list-row { border:1px solid rgba(15,27,45,.1); border-radius:14px; padding:14px 16px 4px; margin-bottom:10px; background:var(--bs-tertiary-bg, #f8fafd); }
    .cms-list-head { display:flex; justify-content:space-between; align-items:center; margin-bottom:10px; }
    .cms-list-head .num { font-size:.75rem; font-weight:800; color:#8592a6; letter-spacing:.06em; }
    .cms-list-tools { display:flex; gap:4px; }
    .cms-list-tools button { width:30px; height:30px; border-radius:8px; border:1px solid rgba(15,27,45,.12); background:var(--bs-body-bg); display:grid; place-items:center; color:#4b5a6e; }
    .cms-list-tools button:hover { border-color:#1f5fbf; color:#1f5fbf; }
    .cms-list-tools button.danger:hover { border-color:#dc2626; color:#dc2626; }
    .cms-icon-input { display:flex; align-items:center; gap:8px; }
    .cms-icon-input .preview { width:38px; height:38px; flex:none; border-radius:10px; display:grid; place-items:center; background:#e8f0fd; color:#1f5fbf; font-size:1.1rem; }
    .cms-image { display:flex; gap:14px; align-items:flex-start; flex-wrap:wrap; }
    .cms-image-preview { width:180px; height:110px; border-radius:12px; border:1px dashed rgba(15,27,45,.2); display:grid; place-items:center; overflow:hidden; background:#f4f7fb; color:#8592a6; font-size:.78rem; text-align:center; }
    .cms-image-preview img { width:100%; height:100%; object-fit:cover; }
    .cms-sticky { position:sticky; bottom:0; z-index:5; background:var(--bs-body-bg); border-top:1px solid rgba(15,27,45,.08); padding:14px 20px; border-radius:0 0 16px 16px; display:flex; justify-content:space-between; gap:10px; flex-wrap:wrap; }
</style>
