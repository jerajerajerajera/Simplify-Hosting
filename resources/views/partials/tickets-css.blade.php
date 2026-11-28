.sh-desk{--sh-line:var(--ps-line,rgb(0 0 0 / .1));--sh-raised:var(--ps-raised,rgb(0 0 0 / .04));--sh-field:var(--ps-field,transparent);--sh-muted:var(--ps-muted,#71717a);--sh-note:rgb(234 179 8 / .12);--sh-note-line:rgb(234 179 8 / .35);--sh-log:#131a20;--sh-log-ink:#e4e4e7}
.dark .sh-desk{--sh-line:var(--ps-line,rgb(255 255 255 / .1));--sh-raised:var(--ps-raised,rgb(255 255 255 / .05));--sh-muted:var(--ps-muted,#a1a1aa)}
.sh-desk .fi-section-header{flex-wrap:wrap;gap:.75rem}
.sh-desk .fi-section-header>:last-child{display:flex;flex-wrap:wrap;gap:.5rem;align-items:center}
.sh-search{min-width:14rem}
.sh-filters{margin-bottom:1rem}
.sh-empty{color:var(--sh-muted);text-align:center;padding:2.5rem 1rem}
.sh-table{overflow-x:auto}
.sh-table table{width:100%;border-collapse:collapse;font-size:.875rem;min-width:36rem}
.sh-table th{text-align:left;font-weight:500;color:var(--sh-muted);font-size:.75rem;padding:.4rem .6rem;border-bottom:1px solid var(--sh-line)}
.sh-table td{padding:.65rem .6rem;border-bottom:1px solid var(--sh-line);vertical-align:top}
.sh-table tr:last-child td{border-bottom:0}
.sh-table tbody tr{cursor:pointer}
.sh-table tbody tr:hover{background:var(--sh-raised)}
.sh-table tr.is-unread .sh-link{font-weight:600}
.sh-link{all:unset;cursor:pointer;display:block}
.sh-link:focus-visible{outline:2px solid currentColor;outline-offset:2px;border-radius:4px}
.sh-sub,.sh-hint{display:block;color:var(--sh-muted);font-size:.75rem}
.sh-num{font-variant-numeric:tabular-nums;color:var(--sh-muted);white-space:nowrap}
.sh-form{display:grid;grid-template-columns:repeat(auto-fit,minmax(14rem,1fr));gap:1rem}
.sh-field{display:grid;gap:.35rem;min-width:0}
.is-wide{grid-column:1/-1}
.sh-label{font-size:.875rem;font-weight:500}
.sh-area{width:100%;min-height:6rem;resize:vertical;padding:.5rem .75rem;background:transparent;border:0;font:inherit}
.sh-error{color:var(--ps-danger,#dc2626);font-size:.8125rem}
.sh-check{display:flex;gap:.6rem;align-items:flex-start;font-size:.875rem}
.sh-check input{margin-top:.2rem}
.sh-ticket{display:grid;gap:1.25rem}
.sh-ticket.has-side{grid-template-columns:minmax(0,1fr) 16rem}
@media (max-width:64rem){.sh-ticket.has-side{grid-template-columns:minmax(0,1fr)}}
.sh-thread{display:grid;gap:.9rem;min-width:0}
.sh-msg{display:grid;gap:.3rem;max-width:80ch}
.sh-meta{display:flex;flex-wrap:wrap;gap:.5rem;align-items:center;font-size:.75rem;color:var(--sh-muted)}
.sh-meta strong{color:inherit;font-weight:600}
.sh-text{white-space:pre-wrap;overflow-wrap:anywhere;border:1px solid var(--sh-line);border-radius:.6rem;padding:.6rem .75rem;background:var(--sh-field);font-size:.875rem}
.sh-msg.is-staff .sh-text{background:var(--sh-raised)}
.sh-msg.is-note .sh-text{background:var(--sh-note);border-color:var(--sh-note-line)}
.sh-log{margin:0;max-height:16rem;overflow:auto;font:.75rem/1.5 ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;background:var(--sh-log);color:var(--sh-log-ink);border-radius:.5rem;padding:.6rem .75rem;white-space:pre}
.sh-reply{display:grid;gap:.5rem;border-top:1px solid var(--sh-line);padding-top:1rem}
.sh-row{display:flex;flex-wrap:wrap;gap:.6rem;align-items:center;justify-content:space-between}
.sh-side{display:grid;gap:.4rem;align-content:start;border-left:1px solid var(--sh-line);padding-left:1.25rem}
@media (max-width:64rem){.sh-side{border-left:0;padding-left:0;border-top:1px solid var(--sh-line);padding-top:1rem}}
.sh-facts{display:grid;gap:.15rem;margin-top:.75rem;font-size:.875rem}
.sh-facts dt{color:var(--sh-muted);font-size:.75rem;margin-top:.5rem}
.sh-facts dd{margin:0;overflow-wrap:anywhere}
