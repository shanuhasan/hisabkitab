<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#1e40af">
    <title>UdhaarHisab — Mobile App Demo</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --primary: #1e40af; --primary-2: #3b82f6; --primary-bg: #dbeafe;
            --red: #b91c1c; --red-bg: #fee2e2; --green: #15803d; --green-bg: #dcfce7;
            --amber: #b45309; --amber-bg: #fef3c7;
            --ink: #0f172a; --muted: #64748b; --line: #e2e8f0; --bg: #f1f5f9; --card: #ffffff;
            --radius: 16px;
        }
        * { box-sizing: border-box; -webkit-tap-highlight-color: transparent; }
        html, body { margin: 0; height: 100%; }
        body {
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Noto Sans", sans-serif;
            background: #0f172a; color: var(--ink); display: flex; justify-content: center; align-items: center;
        }
        /* Phone frame on desktop, full screen on a real phone */
        .phone {
            width: 390px; height: min(844px, 100vh - 32px); background: var(--bg); border-radius: 36px;
            overflow: hidden; position: relative; display: flex; flex-direction: column;
            box-shadow: 0 0 0 10px #1e293b, 0 30px 60px rgba(0,0,0,.5);
        }
        @media (max-width: 480px) {
            body { display: block; background: var(--bg); }
            .phone { width: 100%; height: 100vh; height: 100dvh; border-radius: 0; box-shadow: none; }
        }
        .side-note {
            position: fixed; left: 24px; top: 24px; max-width: 280px; color: #cbd5e1; font-size: 14px; line-height: 1.5;
        }
        .side-note h1 { color: #fff; font-size: 20px; margin: 0 0 8px; }
        .side-note code { background: #1e293b; padding: 1px 6px; border-radius: 6px; color: #93c5fd; }
        @media (max-width: 1100px) { .side-note { display: none; } }

        /* App bar */
        .appbar {
            background: var(--primary); color: #fff; padding: 14px 16px; padding-top: max(14px, env(safe-area-inset-top));
            display: flex; align-items: center; gap: 10px; min-height: 60px; flex-shrink: 0;
        }
        .appbar h2 { font-size: 18px; margin: 0; flex: 1; font-weight: 700; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .appbar small { display: block; font-weight: 400; font-size: 12px; opacity: .8; }
        .icon-btn { background: rgba(255,255,255,.15); border: 0; color: #fff; width: 38px; height: 38px; border-radius: 12px; font-size: 18px; cursor: pointer; flex-shrink: 0; }
        .api-chip { background: #fff; color: var(--primary); border: 0; border-radius: 999px; padding: 6px 10px; font-size: 12px; font-weight: 700; cursor: pointer; flex-shrink: 0; }

        .screen { flex: 1; overflow-y: auto; padding: 16px; padding-bottom: 96px; }
        .screen.no-nav { padding-bottom: 24px; }

        /* Bottom nav */
        .bottom-nav {
            position: absolute; bottom: 0; left: 0; right: 0; background: #fff; border-top: 1px solid var(--line);
            display: flex; padding-bottom: env(safe-area-inset-bottom);
        }
        .bottom-nav button {
            flex: 1; background: none; border: 0; padding: 10px 0 12px; color: var(--muted); font-size: 12px; cursor: pointer; font-weight: 600;
        }
        .bottom-nav button i { display: block; font-size: 22px; margin-bottom: 2px; }
        .bottom-nav button.active { color: var(--primary); }

        /* Building blocks */
        .card { background: var(--card); border-radius: var(--radius); padding: 16px; margin-bottom: 12px; border: 1px solid var(--line); }
        .hero { background: linear-gradient(135deg, var(--primary), var(--primary-2)); color: #fff; border: 0; }
        .hero .label { opacity: .85; font-size: 14px; }
        .hero .big { font-size: 34px; font-weight: 800; margin: 4px 0 2px; letter-spacing: -.5px; }
        .grid2 { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px; }
        .stat { background: #fff; border-radius: var(--radius); padding: 14px; border: 1px solid var(--line); }
        .stat .label { color: var(--muted); font-size: 13px; }
        .stat .val { font-size: 20px; font-weight: 800; margin-top: 4px; }
        .red { color: var(--red); } .green { color: var(--green); } .muted { color: var(--muted); }

        .big-actions { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 16px; }
        .big-btn {
            border: 0; border-radius: var(--radius); padding: 18px 10px; font-size: 16px; font-weight: 800; color: #fff; cursor: pointer;
            display: flex; flex-direction: column; align-items: center; gap: 6px;
        }
        .big-btn i { font-size: 28px; }
        .big-btn small { font-weight: 500; font-size: 12px; opacity: .9; }
        .big-btn.red-bg { background: var(--red); } .big-btn.green-bg { background: var(--green); }

        .section-title { font-size: 15px; font-weight: 800; margin: 18px 4px 10px; display: flex; justify-content: space-between; align-items: center; }
        .section-title a { font-size: 13px; color: var(--primary); font-weight: 600; cursor: pointer; text-decoration: none; }

        .list { background: #fff; border-radius: var(--radius); border: 1px solid var(--line); overflow: hidden; }
        .row { display: flex; align-items: center; gap: 12px; padding: 14px 16px; border-bottom: 1px solid var(--line); cursor: pointer; }
        .row:last-child { border-bottom: 0; }
        .row:active { background: #f8fafc; }
        .avatar { width: 42px; height: 42px; border-radius: 50%; background: var(--primary-bg); color: var(--primary); display: flex; align-items: center; justify-content: center; font-weight: 800; flex-shrink: 0; }
        .avatar.red { background: var(--red-bg); color: var(--red); } .avatar.green { background: var(--green-bg); color: var(--green); }
        .row .main { flex: 1; min-width: 0; }
        .row .title { font-weight: 700; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .row .sub { color: var(--muted); font-size: 13px; margin-top: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .row .amt { font-weight: 800; text-align: right; white-space: nowrap; }
        .row .amt small { display: block; font-weight: 500; font-size: 11px; color: var(--muted); }
        .strike { text-decoration: line-through; opacity: .55; }

        .search { display: flex; align-items: center; gap: 8px; background: #fff; border: 1px solid var(--line); border-radius: 14px; padding: 0 14px; margin-bottom: 10px; }
        .search input { border: 0; outline: 0; flex: 1; padding: 14px 0; font-size: 16px; background: transparent; }
        .chips { display: flex; gap: 8px; overflow-x: auto; margin-bottom: 12px; padding-bottom: 2px; }
        .chip { border: 1px solid var(--line); background: #fff; border-radius: 999px; padding: 8px 14px; font-size: 14px; font-weight: 600; white-space: nowrap; cursor: pointer; color: var(--ink); }
        .chip.active { background: var(--primary); color: #fff; border-color: var(--primary); }

        .fab {
            position: absolute; right: 18px; bottom: 86px; background: var(--primary); color: #fff; border: 0; border-radius: 999px;
            padding: 14px 20px; font-size: 16px; font-weight: 800; box-shadow: 0 8px 20px rgba(30,64,175,.35); cursor: pointer;
        }

        /* Forms */
        label.f { display: block; font-size: 14px; font-weight: 700; margin: 14px 2px 6px; }
        label.f span { font-weight: 400; color: var(--muted); }
        .input, textarea.input, select.input {
            width: 100%; border: 1.5px solid var(--line); border-radius: 14px; padding: 14px; font-size: 17px; background: #fff; color: var(--ink); font-family: inherit;
        }
        .input:focus { outline: 0; border-color: var(--primary-2); }
        .amount-input { font-size: 34px; font-weight: 800; text-align: center; padding: 18px; }
        .quick-amounts { display: flex; gap: 8px; margin-top: 8px; flex-wrap: wrap; }
        .btn {
            width: 100%; border: 0; border-radius: 14px; padding: 16px; font-size: 17px; font-weight: 800; cursor: pointer; margin-top: 18px;
            background: var(--primary); color: #fff; display: flex; align-items: center; justify-content: center; gap: 8px;
        }
        .btn.red-bg { background: var(--red); } .btn.green-bg { background: var(--green); } .btn.wa { background: #16a34a; }
        .btn.ghost { background: #fff; color: var(--ink); border: 1.5px solid var(--line); }
        .btn.danger-ghost { background: #fff; color: var(--red); border: 1.5px solid #fecaca; }
        .btn:disabled { opacity: .6; cursor: wait; }
        .toggle { display: flex; background: #e2e8f0; border-radius: 14px; padding: 4px; gap: 4px; }
        .toggle button { flex: 1; border: 0; background: transparent; padding: 12px; border-radius: 11px; font-weight: 800; font-size: 15px; cursor: pointer; color: var(--muted); }
        .toggle button.on-red { background: var(--red); color: #fff; } .toggle button.on-green { background: var(--green); color: #fff; }
        .check { display: flex; gap: 10px; align-items: center; margin-top: 14px; font-size: 14px; }
        .check input { width: 20px; height: 20px; }
        .err { color: var(--red); font-size: 13px; margin: 6px 2px 0; }
        .alert { border-radius: 14px; padding: 12px 14px; font-size: 14px; margin-bottom: 12px; }
        .alert.red { background: var(--red-bg); color: var(--red); } .alert.amber { background: var(--amber-bg); color: var(--amber); }

        .balance-card { text-align: center; padding: 22px 16px; }
        .balance-card .big { font-size: 38px; font-weight: 800; margin: 6px 0; }
        .pill { display: inline-block; padding: 4px 10px; border-radius: 999px; font-size: 12px; font-weight: 700; }
        .pill.red { background: var(--red-bg); } .pill.green { background: var(--green-bg); } .pill.grey { background: #e2e8f0; color: var(--muted); }
        .action-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-bottom: 8px; }
        .action-row button { border: 0; border-radius: 14px; padding: 12px 4px; font-weight: 800; font-size: 13px; cursor: pointer; display: flex; flex-direction: column; align-items: center; gap: 4px; }
        .action-row button i { font-size: 22px; }
        .action-row .a-red { background: var(--red-bg); color: var(--red); }
        .action-row .a-green { background: var(--green-bg); color: var(--green); }
        .action-row .a-wa { background: #dcfce7; color: #166534; }
        .kv { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px dashed var(--line); font-size: 15px; }
        .kv:last-child { border-bottom: 0; }
        .kv span:first-child { color: var(--muted); }
        .kv span:last-child { font-weight: 700; text-align: right; max-width: 60%; word-break: break-word; }

        .template { border: 1.5px solid var(--line); border-radius: 14px; padding: 12px; margin-bottom: 10px; cursor: pointer; background: #fff; }
        .template.active { border-color: #16a34a; background: #f0fdf4; }
        .template b { font-size: 14px; }
        .template pre { white-space: pre-wrap; font-family: inherit; font-size: 13px; color: var(--muted); margin: 6px 0 0; }

        .empty { text-align: center; color: var(--muted); padding: 40px 16px; }
        .empty i { font-size: 40px; display: block; margin-bottom: 8px; }
        .loading { text-align: center; padding: 40px; color: var(--muted); }

        .login-hero { text-align: center; padding: 34px 10px 10px; }
        .login-hero .logo { width: 72px; height: 72px; border-radius: 22px; background: var(--primary); color: #fff; font-size: 36px; display: inline-flex; align-items: center; justify-content: center; }
        .login-hero h1 { margin: 14px 0 4px; font-size: 26px; }
        .login-hero h1 span { color: var(--primary-2); }
        .link { color: var(--primary); font-weight: 700; cursor: pointer; text-align: center; display: block; margin-top: 18px; }

        .toast {
            position: absolute; left: 16px; right: 16px; bottom: 92px; background: var(--ink); color: #fff; padding: 14px 16px; border-radius: 14px;
            font-size: 14px; z-index: 50; transform: translateY(20px); opacity: 0; transition: .25s; pointer-events: none;
        }
        .toast.show { transform: none; opacity: 1; }

        /* API inspector sheet */
        .sheet-backdrop { position: absolute; inset: 0; background: rgba(15,23,42,.5); z-index: 60; display: none; }
        .sheet {
            position: absolute; left: 0; right: 0; bottom: 0; max-height: 80%; background: #0f172a; color: #e2e8f0; z-index: 61;
            border-radius: 22px 22px 0 0; padding: 16px; overflow-y: auto; display: none; font-size: 13px;
        }
        .sheet.open, .sheet-backdrop.open { display: block; }
        .sheet h3 { margin: 0 0 4px; color: #fff; font-size: 16px; display: flex; justify-content: space-between; }
        .sheet .call { background: #1e293b; border-radius: 12px; padding: 10px 12px; margin: 10px 0; }
        .method { font-weight: 800; padding: 2px 8px; border-radius: 6px; font-size: 11px; margin-right: 6px; }
        .m-GET { background: #1d4ed8; } .m-POST { background: #15803d; } .m-PUT { background: #b45309; } .m-DELETE { background: #b91c1c; }
        .status-ok { color: #4ade80; } .status-bad { color: #f87171; }
        .sheet pre { white-space: pre-wrap; word-break: break-word; background: #020617; padding: 10px; border-radius: 10px; max-height: 260px; overflow: auto; margin: 8px 0 0; font-size: 12px; color: #a5f3fc; }
    </style>
</head>
<body data-api="{{ url('/api/v1') }}">
    <div class="side-note">
        <h1>Mobile App Demo</h1>
        This demo shows how a shopkeeper app could work. Every screen calls the real
        <code>/api/v1</code> endpoints of this server.<br><br>
        Tap the <b>API</b> button on any screen to see the request and the live JSON response.<br><br>
        Full reference: <code>docs/API.md</code>
    </div>

    <div class="phone" id="phone">
        <div class="appbar" id="appbar"></div>
        <div class="screen" id="screen"></div>
        <nav class="bottom-nav" id="nav">
            <button data-tab="home"><i class="bi bi-house-door-fill"></i>Home</button>
            <button data-tab="customers"><i class="bi bi-people-fill"></i>Grahak</button>
            <button data-tab="entries"><i class="bi bi-journal-text"></i>Entries</button>
            <button data-tab="shop"><i class="bi bi-shop"></i>Dukaan</button>
        </nav>
        <div class="toast" id="toast"></div>
        <div class="sheet-backdrop" id="sheetBackdrop"></div>
        <div class="sheet" id="sheet"></div>
    </div>

<script>
(() => {
    const API = document.body.dataset.api;
    const TOKEN_KEY = 'udhaarhisab_demo_token';

    // ---------- state ----------
    const state = { token: null, me: null, stack: [], calls: [] };
    try { state.token = localStorage.getItem(TOKEN_KEY); } catch (e) {}

    const $ = (id) => document.getElementById(id);
    const esc = (v) => String(v ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const rupee = (v) => '₹' + Number(v || 0).toLocaleString('en-IN', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
    const today = () => new Date().toISOString().slice(0, 10);
    const initials = (n) => esc((n || '?').trim().split(/\s+/).map(w => w[0]).slice(0, 2).join('').toUpperCase());
    const fmtDate = (d) => d ? new Date(d).toLocaleDateString('en-IN', { day: 'numeric', month: 'short' }) : '';

    // ---------- API client (records every call for the inspector) ----------
    async function api(method, path, body) {
        const headers = { 'Accept': 'application/json' };
        if (state.token) headers['Authorization'] = 'Bearer ' + state.token;
        if (body) headers['Content-Type'] = 'application/json';

        const call = { method, path, body, status: null, response: null };
        state.calls.push(call);

        let res, data;
        try {
            res = await fetch(API + path, { method, headers, body: body ? JSON.stringify(body) : undefined });
            data = await res.json().catch(() => ({}));
        } catch (e) {
            call.status = 'network error';
            throw { message: 'Server se connect nahi ho paya. Internet check karein.' };
        }
        call.status = res.status;
        call.response = data;

        if (res.status === 401 && state.token) {
            setToken(null);
            go('login', {}, true);
            throw { message: 'Session expired. Please log in again.' };
        }
        if (!res.ok) throw { status: res.status, message: data.message || 'Kuch galat ho gaya.', errors: data.errors || {} };
        return data;
    }

    function setToken(t) {
        state.token = t;
        try { t ? localStorage.setItem(TOKEN_KEY, t) : localStorage.removeItem(TOKEN_KEY); } catch (e) {}
    }

    function toast(msg) {
        const el = $('toast');
        el.textContent = msg;
        el.classList.add('show');
        clearTimeout(toast.t);
        toast.t = setTimeout(() => el.classList.remove('show'), 2600);
    }

    function showErrors(form, err) {
        form.querySelectorAll('.err').forEach(e => e.remove());
        const errors = err.errors || {};
        let placed = false;
        Object.entries(errors).forEach(([field, msgs]) => {
            const input = form.querySelector(`[name="${field}"]`);
            if (input) {
                input.insertAdjacentHTML('afterend', `<div class="err">${esc(msgs[0])}</div>`);
                placed = true;
            }
        });
        if (!placed) toast(err.message || 'Kuch galat ho gaya.');
    }

    // ---------- navigation ----------
    const TABS = ['home', 'customers', 'entries', 'shop'];

    function go(name, params = {}, replace = false) {
        if (TABS.includes(name) || name === 'login' || name === 'register') state.stack = [];
        else if (!replace && state.current) state.stack.push(state.current);
        render(name, params);
    }
    function back() {
        const prev = state.stack.pop();
        prev ? render(prev.name, prev.params) : render('home', {});
    }

    function appbar({ title, sub = '', backBtn = false, right = '' }) {
        $('appbar').innerHTML = `
            ${backBtn ? '<button class="icon-btn" id="backBtn" aria-label="Back"><i class="bi bi-arrow-left"></i></button>' : ''}
            <h2>${esc(title)}${sub ? `<small>${esc(sub)}</small>` : ''}</h2>
            ${right}
            <button class="api-chip" id="apiBtn"><i class="bi bi-braces"></i> API</button>`;
        if (backBtn) $('backBtn').onclick = back;
        $('apiBtn').onclick = openInspector;
    }

    async function render(name, params) {
        state.current = { name, params };
        state.calls = [];
        const screens = { login, register, home, customers, customer, addCustomer, addEntry, entry, reminder, entries, shop, editShop };
        const authed = !!state.token;

        if (!authed && name !== 'login' && name !== 'register') return render('login', {});

        const showNav = TABS.includes(name);
        $('nav').style.display = showNav ? 'flex' : 'none';
        $('screen').classList.toggle('no-nav', !showNav);
        document.querySelectorAll('#nav button').forEach(b => b.classList.toggle('active', b.dataset.tab === name));
        document.querySelectorAll('.fab').forEach(f => f.remove());
        $('screen').scrollTop = 0;
        $('screen').innerHTML = '<div class="loading">Loading…</div>';

        try {
            await screens[name](params);
        } catch (err) {
            $('screen').innerHTML = `<div class="empty"><i class="bi bi-exclamation-circle"></i>${esc(err.message || 'Error')}</div>`;
        }
    }

    document.querySelectorAll('#nav button').forEach(b => b.onclick = () => go(b.dataset.tab));

    // ---------- API inspector ----------
    function openInspector() {
        const calls = state.calls;
        $('sheet').innerHTML = `
            <h3>API calls on this screen <span style="cursor:pointer" id="closeSheet"><i class="bi bi-x-lg"></i></span></h3>
            <div style="color:#94a3b8">Base URL: ${esc(API)}</div>
            ${calls.length ? calls.map(c => `
                <div class="call">
                    <div><span class="method m-${esc(c.method)}">${esc(c.method)}</span><b>${esc(c.path)}</b>
                        <span class="${c.status >= 200 && c.status < 300 ? 'status-ok' : 'status-bad'}" style="float:right">${esc(c.status ?? '…')}</span></div>
                    ${c.body ? `<div style="margin-top:8px;color:#94a3b8">Request body</div><pre>${esc(JSON.stringify(redact(c.body), null, 2))}</pre>` : ''}
                    <div style="margin-top:8px;color:#94a3b8">Response</div>
                    <pre>${esc(JSON.stringify(redact(c.response), null, 2))}</pre>
                </div>`).join('') : '<div class="call">No API call yet on this screen. Submit the form to see the request.</div>'}`;
        $('sheet').classList.add('open');
        $('sheetBackdrop').classList.add('open');
        $('closeSheet').onclick = closeInspector;
    }
    function closeInspector() { $('sheet').classList.remove('open'); $('sheetBackdrop').classList.remove('open'); }
    $('sheetBackdrop').onclick = closeInspector;

    function redact(obj) {
        if (!obj || typeof obj !== 'object') return obj;
        const copy = Array.isArray(obj) ? [...obj] : { ...obj };
        for (const k of Object.keys(copy)) {
            if (['password', 'password_confirmation'].includes(k)) copy[k] = '••••••••';
            else if (k === 'token' && typeof copy[k] === 'string') copy[k] = copy[k].slice(0, 6) + '…(hidden)';
        }
        return copy;
    }

    // ---------- reusable bits ----------
    function customerRow(c) {
        const cls = c.balance_status === 'due' ? 'red' : (c.balance_status === 'advance' ? 'green' : 'muted');
        return `<div class="row" data-customer="${c.id}">
            <div class="avatar">${initials(c.name)}</div>
            <div class="main"><div class="title">${esc(c.name)}</div><div class="sub">${esc(c.phone)}</div></div>
            <div class="amt ${cls}">${c.balance_status === 'settled' ? '₹0' : rupee(Math.abs(c.balance))}
                <small>${c.balance_status === 'due' ? 'lena hai' : (c.balance_status === 'advance' ? 'advance' : 'barabar')}</small></div>
        </div>`;
    }
    function entryRow(t, showCustomer = true) {
        const isCredit = t.type === 'credit', isPay = t.type === 'payment';
        const cls = isCredit ? 'red' : (isPay ? 'green' : 'muted');
        const icon = isCredit ? 'bi-arrow-up-right' : (isPay ? 'bi-arrow-down-left' : 'bi-arrow-counterclockwise');
        const title = showCustomer && t.customer ? t.customer.name : t.type_label;
        const sub = [showCustomer ? t.type_label : null, t.description, fmtDate(t.date)].filter(Boolean).join(' · ');
        return `<div class="row" data-entry="${t.id}">
            <div class="avatar ${cls}"><i class="bi ${icon}"></i></div>
            <div class="main"><div class="title">${esc(title)}</div><div class="sub">${esc(sub)}</div></div>
            <div class="amt ${cls} ${t.is_reversed ? 'strike' : ''}">${isCredit ? '+' : (isPay ? '−' : '')}${rupee(t.amount)}
                <small>bal ${rupee(t.balance_after)}</small></div>
        </div>`;
    }
    function bindRows(root) {
        root.querySelectorAll('[data-customer]').forEach(r => r.onclick = () => go('customer', { id: r.dataset.customer }));
        root.querySelectorAll('[data-entry]').forEach(r => r.onclick = () => go('entry', { id: r.dataset.entry }));
    }
    function fab(label, onClick) {
        const b = document.createElement('button');
        b.className = 'fab'; b.innerHTML = label; b.onclick = onClick;
        $('phone').appendChild(b);
    }
    async function submitting(btn, fn) {
        btn.disabled = true;
        try { await fn(); } finally { btn.disabled = false; }
    }

    // ================= SCREENS =================

    // 1. Login — POST /auth/login
    async function login() {
        appbar({ title: 'Login' });
        $('screen').innerHTML = `
            <div class="login-hero">
                <div class="logo"><i class="bi bi-journal-bookmark-fill"></i></div>
                <h1>Udhaar<span>Hisab</span></h1>
                <div class="muted">Digital udhaar khata for your dukaan</div>
            </div>
            <form id="f">
                <label class="f">Mobile number or email</label>
                <input class="input" name="login" inputmode="tel" autocomplete="username" placeholder="98765 43210" required>
                <label class="f">Password</label>
                <input class="input" name="password" type="password" autocomplete="current-password" required>
                <button class="btn" type="submit"><i class="bi bi-box-arrow-in-right"></i> Login</button>
            </form>
            <a class="link" id="toRegister">Nayi dukaan? Register karein</a>`;
        $('toRegister').onclick = () => go('register');
        $('f').onsubmit = (e) => {
            e.preventDefault();
            const fd = new FormData(e.target);
            submitting(e.target.querySelector('button'), async () => {
                try {
                    const res = await api('POST', '/auth/login', { login: fd.get('login'), password: fd.get('password'), device_name: 'demo-web' });
                    setToken(res.token);
                    toast('Namaste ' + res.user.name + '!');
                    go('home');
                } catch (err) { showErrors(e.target, err); }
            });
        };
    }

    // 2. Register — POST /auth/register
    async function register() {
        appbar({ title: 'Register your dukaan', backBtn: false });
        $('screen').innerHTML = `
            <form id="f">
                <label class="f">Dukaan ka naam</label>
                <input class="input" name="shop_name" placeholder="Sharma Kirana Store" required>
                <label class="f">Aapka naam</label>
                <input class="input" name="owner_name" placeholder="Ramesh Sharma" required>
                <label class="f">Mobile number</label>
                <input class="input" name="phone" inputmode="numeric" maxlength="10" placeholder="9876543210" required>
                <label class="f">Password <span>(min 8)</span></label>
                <input class="input" name="password" type="password" autocomplete="new-password" required>
                <label class="check"><input type="checkbox" name="terms" checked> I agree to the Terms & Privacy Policy</label>
                <button class="btn" type="submit"><i class="bi bi-shop"></i> Start 30-day free trial</button>
            </form>
            <a class="link" id="toLogin">Already registered? Login</a>`;
        $('toLogin').onclick = () => go('login');
        $('f').onsubmit = (e) => {
            e.preventDefault();
            const fd = new FormData(e.target);
            submitting(e.target.querySelector('button'), async () => {
                try {
                    const res = await api('POST', '/auth/register', {
                        shop_name: fd.get('shop_name'), owner_name: fd.get('owner_name'), phone: fd.get('phone'),
                        password: fd.get('password'), password_confirmation: fd.get('password'),
                        terms: fd.get('terms') ? true : false, device_name: 'demo-web',
                    });
                    setToken(res.token);
                    toast(res.message);
                    go('home');
                } catch (err) { showErrors(e.target, err); }
            });
        };
    }

    // 3. Home — GET /auth/me + GET /dashboard
    async function home() {
        appbar({ title: 'UdhaarHisab' });
        const [me, d] = await Promise.all([api('GET', '/auth/me'), api('GET', '/dashboard')]);
        state.me = me;
        appbar({ title: me.shop.name, sub: 'Namaste, ' + me.user.name });
        const t = d.totals, sub = me.subscription;

        $('screen').innerHTML = `
            ${sub && sub.is_expired ? `<div class="alert red"><b>Plan expired.</b> Renew on the website to continue.</div>`
              : sub && sub.is_near_expiry ? `<div class="alert amber">${esc(sub.plan)} ends in ${esc(sub.days_remaining)} days.</div>` : ''}
            <div class="card hero">
                <div class="label">Total baki (customers se lena hai)</div>
                <div class="big">${rupee(t.total_due)}</div>
                <div class="label">${esc(t.total_customers)} grahak${Number(t.total_advance) > 0 ? ' · Advance ' + rupee(t.total_advance) : ''}</div>
            </div>
            <div class="grid2">
                <div class="stat"><div class="label">Aaj udhaar diya</div><div class="val red">${rupee(t.today_udhaar)}</div></div>
                <div class="stat"><div class="label">Aaj jama mila</div><div class="val green">${rupee(t.today_collection)}</div></div>
            </div>
            <div class="big-actions">
                <button class="big-btn red-bg" id="qCredit"><i class="bi bi-arrow-up-right-circle"></i>Udhaar Diya<small>Gave credit</small></button>
                <button class="big-btn green-bg" id="qPay"><i class="bi bi-arrow-down-left-circle"></i>Jama Mila<small>Got payment</small></button>
            </div>
            ${d.top_due_customers.length ? `<div class="section-title">Sabse zyada baki <a id="allDue">Sab dekhein</a></div>
                <div class="list">${d.top_due_customers.map(customerRow).join('')}</div>` : ''}
            <div class="section-title">Recent entries <a id="allEntries">Sab dekhein</a></div>
            ${d.recent_transactions.length ? `<div class="list">${d.recent_transactions.map(x => entryRow(x)).join('')}</div>`
              : `<div class="card empty"><i class="bi bi-journal-plus"></i>Abhi koi entry nahi. Pehle ek grahak jodiye.</div>`}`;

        bindRows($('screen'));
        $('qCredit').onclick = () => go('addEntry', { type: 'credit' });
        $('qPay').onclick = () => go('addEntry', { type: 'payment' });
        $('allEntries').onclick = () => go('entries');
        if ($('allDue')) $('allDue').onclick = () => go('customers', { filter: 'due' });
    }

    // 4. Customers — GET /customers?search=&filter=
    async function customers(params) {
        appbar({ title: 'Grahak', sub: 'Customers' });
        const filter = params.filter || 'all';
        $('screen').innerHTML = `
            <div class="search"><i class="bi bi-search muted"></i><input id="q" placeholder="Naam ya mobile se search" value="${esc(params.search || '')}"></div>
            <div class="chips">
                ${[['all', 'Sab'], ['due', 'Baki wale'], ['advance', 'Advance'], ['settled', 'Barabar']].map(([k, l]) =>
                    `<button class="chip ${k === filter ? 'active' : ''}" data-filter="${k}">${l}</button>`).join('')}
            </div>
            <div id="summary"></div>
            <div id="list"><div class="loading">Loading…</div></div>`;
        fab('<i class="bi bi-person-plus-fill"></i> Grahak jodein', () => go('addCustomer'));

        const load = async () => {
            const q = $('q').value.trim();
            const qs = new URLSearchParams({ filter, per_page: 50 });
            if (q) qs.set('search', q);
            state.calls = [];
            const res = await api('GET', '/customers?' + qs);
            $('summary').innerHTML = `<div class="grid2">
                <div class="stat"><div class="label">Lena hai</div><div class="val red">${rupee(res.summary.total_due)}</div></div>
                <div class="stat"><div class="label">Advance</div><div class="val green">${rupee(res.summary.total_advance)}</div></div></div>`;
            $('list').innerHTML = res.data.length
                ? `<div class="list">${res.data.map(customerRow).join('')}</div>`
                : `<div class="card empty"><i class="bi bi-people"></i>${q ? 'Koi grahak nahi mila.' : 'Abhi koi grahak nahi. Neeche button se jodiye.'}</div>`;
            bindRows($('list'));
        };
        let timer;
        $('q').oninput = () => { clearTimeout(timer); timer = setTimeout(() => load().catch(e => toast(e.message)), 300); };
        document.querySelectorAll('[data-filter]').forEach(c => c.onclick = () => render('customers', { filter: c.dataset.filter, search: $('q').value }));
        await load();
    }

    // 5. Add customer — POST /customers
    async function addCustomer() {
        appbar({ title: 'Naya grahak', sub: 'Add customer', backBtn: true });
        $('screen').innerHTML = `
            <form id="f">
                <label class="f">Naam</label>
                <input class="input" name="name" placeholder="Suresh Yadav" required>
                <label class="f">Mobile number</label>
                <input class="input" name="phone" inputmode="numeric" maxlength="10" placeholder="9876543210" required>
                <label class="f">Purana baki <span>(optional)</span></label>
                <input class="input" name="opening_balance" inputmode="decimal" placeholder="₹ 0">
                <label class="f">Address <span>(optional)</span></label>
                <input class="input" name="address" placeholder="Gali no. 4, Main Bazar">
                <button class="btn" type="submit"><i class="bi bi-check-lg"></i> Save grahak</button>
            </form>`;
        $('f').onsubmit = (e) => {
            e.preventDefault();
            const fd = new FormData(e.target);
            submitting(e.target.querySelector('button'), async () => {
                try {
                    const body = { name: fd.get('name'), phone: fd.get('phone'), address: fd.get('address') || null };
                    if (Number(fd.get('opening_balance')) > 0) { body.opening_balance = Number(fd.get('opening_balance')); body.opening_balance_type = 'credit'; }
                    const res = await api('POST', '/customers', body);
                    toast(res.message);
                    go('customer', { id: res.data.id }, true);
                } catch (err) { showErrors(e.target, err); }
            });
        };
    }

    // 6. Customer khata — GET /customers/{id} + GET /customers/{id}/transactions
    async function customer({ id }) {
        appbar({ title: 'Khata', backBtn: true });
        const [c, ledger] = await Promise.all([api('GET', `/customers/${id}`), api('GET', `/customers/${id}/transactions?per_page=50`)]);
        const cu = c.data;
        appbar({ title: cu.name, sub: cu.phone, backBtn: true,
            right: `<a class="icon-btn" href="tel:${esc(cu.phone)}" style="display:flex;align-items:center;justify-content:center;text-decoration:none" aria-label="Call"><i class="bi bi-telephone-fill"></i></a>` });
        const cls = cu.balance_status === 'due' ? 'red' : (cu.balance_status === 'advance' ? 'green' : 'grey');

        $('screen').innerHTML = `
            <div class="card balance-card">
                <div class="muted">${cu.balance_status === 'due' ? 'Aapko lena hai' : (cu.balance_status === 'advance' ? 'Customer ka advance' : 'Hisab barabar')}</div>
                <div class="big ${cls === 'grey' ? 'muted' : cls}">${rupee(Math.abs(cu.balance))}</div>
                <span class="pill ${cls}">Udhaar ${rupee(c.totals.total_udhaar)} · Jama ${rupee(c.totals.total_jama)}</span>
            </div>
            <div class="action-row">
                <button class="a-red" id="aCredit"><i class="bi bi-arrow-up-right-circle-fill"></i>Udhaar Diya</button>
                <button class="a-green" id="aPay"><i class="bi bi-arrow-down-left-circle-fill"></i>Jama Mila</button>
                <button class="a-wa" id="aWa"><i class="bi bi-whatsapp"></i>Reminder</button>
            </div>
            <div class="section-title">Len-den (${ledger.meta.total})</div>
            ${ledger.data.length ? `<div class="list">${ledger.data.map(t => entryRow(t, false)).join('')}</div>`
              : `<div class="card empty"><i class="bi bi-journal"></i>Abhi koi entry nahi.</div>`}
            <button class="btn danger-ghost" id="archive"><i class="bi bi-archive"></i> Archive grahak</button>`;

        bindRows($('screen'));
        $('aCredit').onclick = () => go('addEntry', { type: 'credit', customer: cu });
        $('aPay').onclick = () => go('addEntry', { type: 'payment', customer: cu });
        $('aWa').onclick = () => go('reminder', { customer: cu });
        $('archive').onclick = async () => {
            if (!confirm(`Archive ${cu.name}? You can restore later from the website.`)) return;
            try { const r = await api('DELETE', `/customers/${id}`); toast(r.message); back(); } catch (err) { toast(err.message); }
        };
    }

    // 7. Add entry — POST /transactions (customer picker uses GET /customers)
    async function addEntry(params) {
        let type = params.type || 'credit';
        let picked = params.customer || null;
        appbar({ title: 'Nayi entry', backBtn: true });

        const draw = () => {
            $('screen').innerHTML = `
                <div class="toggle">
                    <button type="button" data-type="credit" class="${type === 'credit' ? 'on-red' : ''}">Udhaar Diya</button>
                    <button type="button" data-type="payment" class="${type === 'payment' ? 'on-green' : ''}">Jama Mila</button>
                </div>
                <form id="f">
                    <label class="f">Grahak</label>
                    <div id="picker">${picked
                        ? `<div class="list"><div class="row" id="changeCust"><div class="avatar">${initials(picked.name)}</div>
                             <div class="main"><div class="title">${esc(picked.name)}</div><div class="sub">${esc(picked.balance_label)}</div></div>
                             <span class="muted" style="font-size:13px">Badlein</span></div></div>`
                        : `<div class="search" style="margin:0"><i class="bi bi-search muted"></i><input id="cq" placeholder="Grahak ka naam ya mobile"></div><div id="cres"></div>`}
                    </div>
                    <input type="hidden" name="customer_id" value="${picked ? picked.id : ''}">
                    <label class="f">Amount</label>
                    <input class="input amount-input ${type === 'credit' ? 'red' : 'green'}" name="amount" inputmode="decimal" placeholder="₹ 0" autocomplete="off">
                    <div class="quick-amounts">${[50, 100, 200, 500, 1000].map(a => `<button type="button" class="chip" data-amt="${a}">₹${a}</button>`).join('')}</div>
                    ${type === 'payment' ? `
                        <label class="f">Payment mode</label>
                        <div class="chips" id="modes">${[['cash', 'Cash'], ['upi', 'UPI'], ['bank_transfer', 'Bank'], ['cheque', 'Cheque']].map(([k, l], i) =>
                            `<button type="button" class="chip ${i === 0 ? 'active' : ''}" data-mode="${k}">${l}</button>`).join('')}</div>
                        <input type="hidden" name="payment_mode" value="cash">
                        <label class="check"><input type="checkbox" name="allow_advance"> Allow advance (more than baki)</label>` : ''}
                    <label class="f">Note <span>(kya saman diya / optional)</span></label>
                    <input class="input" name="description" placeholder="${type === 'credit' ? 'Atta 5kg, chini' : 'Cash payment'}">
                    <label class="f">Date</label>
                    <input class="input" type="date" name="transaction_date" value="${today()}" max="${today()}">
                    <button class="btn ${type === 'credit' ? 'red-bg' : 'green-bg'}" type="submit"><i class="bi bi-check-lg"></i> Save ${type === 'credit' ? 'Udhaar' : 'Jama'}</button>
                </form>`;

            document.querySelectorAll('[data-type]').forEach(b => b.onclick = () => { type = b.dataset.type; draw(); });
            document.querySelectorAll('[data-amt]').forEach(b => b.onclick = () => { document.querySelector('[name=amount]').value = b.dataset.amt; });
            document.querySelectorAll('[data-mode]').forEach(b => b.onclick = () => {
                document.querySelectorAll('[data-mode]').forEach(x => x.classList.remove('active'));
                b.classList.add('active');
                document.querySelector('[name=payment_mode]').value = b.dataset.mode;
            });
            if ($('changeCust')) $('changeCust').onclick = () => { picked = null; draw(); };
            if ($('cq')) {
                let timer;
                const search = async () => {
                    const q = $('cq').value.trim();
                    const res = await api('GET', '/customers?per_page=8' + (q ? '&search=' + encodeURIComponent(q) : ''));
                    $('cres').innerHTML = res.data.length
                        ? `<div class="list" style="margin-top:8px">${res.data.map(customerRow).join('')}</div>`
                        : `<div class="muted" style="padding:10px 4px">Koi grahak nahi mila.</div>`;
                    $('cres').querySelectorAll('[data-customer]').forEach(r => r.onclick = () => {
                        picked = res.data.find(x => String(x.id) === r.dataset.customer);
                        draw();
                    });
                };
                $('cq').oninput = () => { clearTimeout(timer); timer = setTimeout(() => search().catch(e => toast(e.message)), 250); };
                search().catch(e => toast(e.message));
            }

            $('f').onsubmit = (e) => {
                e.preventDefault();
                const fd = new FormData(e.target);
                if (!fd.get('customer_id')) return toast('Pehle grahak chuniye.');
                const body = {
                    customer_id: Number(fd.get('customer_id')), type, amount: Number(fd.get('amount')),
                    transaction_date: fd.get('transaction_date'), description: fd.get('description') || null,
                };
                if (type === 'payment') { body.payment_mode = fd.get('payment_mode'); body.allow_advance = !!fd.get('allow_advance'); }
                submitting(e.target.querySelector('[type=submit]'), async () => {
                    try {
                        const res = await api('POST', '/transactions', body);
                        toast(res.message);
                        go('customer', { id: body.customer_id }, true);
                    } catch (err) { showErrors(e.target, err); }
                });
            };
        };
        draw();
    }

    // 8. Entry detail — GET /transactions/{id}, POST /transactions/{id}/reverse
    async function entry({ id }) {
        appbar({ title: 'Entry', backBtn: true });
        if (!state.me) state.me = await api('GET', '/auth/me');
        const res = await api('GET', `/transactions/${id}`);
        const t = res.data;
        const cls = t.type === 'credit' ? 'red' : (t.type === 'payment' ? 'green' : 'muted');
        appbar({ title: t.type_label, sub: t.number, backBtn: true });

        $('screen').innerHTML = `
            <div class="card balance-card">
                <div class="muted">${esc(t.type_label)}${t.customer ? ' · ' + esc(t.customer.name) : ''}</div>
                <div class="big ${cls} ${t.is_reversed ? 'strike' : ''}">${rupee(t.amount)}</div>
                ${t.is_reversed ? `<span class="pill red">Reversed${res.reversal_reason ? ': ' + esc(res.reversal_reason) : ''}</span>` : ''}
            </div>
            <div class="card">
                <div class="kv"><span>Date</span><span>${esc(t.date)}</span></div>
                <div class="kv"><span>Balance after</span><span>${rupee(t.balance_after)}</span></div>
                <div class="kv"><span>Mode</span><span>${esc((t.payment_mode || '').replace('_', ' '))}</span></div>
                ${t.description ? `<div class="kv"><span>Note</span><span>${esc(t.description)}</span></div>` : ''}
                <div class="kv"><span>Recorded by</span><span>${esc(t.recorded_by || '')}</span></div>
            </div>
            ${t.customer ? '<button class="btn ghost" id="openCust"><i class="bi bi-person"></i> Open khata</button>' : ''}
            ${t.can_reverse && state.me.user.can_reverse_entries ? `
                <form id="rf" class="card" style="margin-top:16px">
                    <b>Galat entry? Reverse karein</b>
                    <div class="muted" style="font-size:13px;margin-top:4px">A reversal entry is added; nothing is deleted.</div>
                    <input class="input" name="reason" placeholder="Reason (min 5 letters)" style="margin-top:10px">
                    <button class="btn danger-ghost" type="submit" style="margin-top:10px"><i class="bi bi-arrow-counterclockwise"></i> Reverse entry</button>
                </form>` : ''}`;

        if ($('openCust')) $('openCust').onclick = () => go('customer', { id: t.customer.id });
        if ($('rf')) $('rf').onsubmit = (e) => {
            e.preventDefault();
            submitting(e.target.querySelector('button'), async () => {
                try {
                    const r = await api('POST', `/transactions/${id}/reverse`, { reason: new FormData(e.target).get('reason') });
                    toast(r.message);
                    render('entry', { id });
                } catch (err) { showErrors(e.target, err); }
            });
        };
    }

    // 9. WhatsApp reminder — GET + POST /customers/{id}/reminder
    async function reminder({ customer: cu }) {
        appbar({ title: 'WhatsApp reminder', sub: cu.name, backBtn: true });
        const res = await api('GET', `/customers/${cu.id}/reminder`);
        let chosen = res.templates[0].key;

        $('screen').innerHTML = `
            ${cu.balance_status !== 'due' ? '<div class="alert amber">This customer has no baki right now.</div>' : ''}
            <div class="section-title" style="margin-top:0">Message chuniye</div>
            ${res.templates.map((t, i) => `<div class="template ${i === 0 ? 'active' : ''}" data-key="${esc(t.key)}"><b>${esc(t.name)}</b><pre>${esc(t.message)}</pre></div>`).join('')}
            <button class="btn wa" id="send"><i class="bi bi-whatsapp"></i> Open WhatsApp</button>`;

        document.querySelectorAll('.template').forEach(el => el.onclick = () => {
            document.querySelectorAll('.template').forEach(x => x.classList.remove('active'));
            el.classList.add('active');
            chosen = el.dataset.key;
        });
        $('send').onclick = (e) => submitting(e.currentTarget, async () => {
            try {
                const r = await api('POST', `/customers/${cu.id}/reminder`, { template: chosen });
                window.open(r.whatsapp_url, '_blank', 'noopener');
                toast('Reminder logged. WhatsApp khul raha hai…');
            } catch (err) { toast(err.message); }
        });
    }

    // 10. Entries register — GET /transactions?type=
    async function entries(params) {
        appbar({ title: 'Entries', sub: 'Len-den register' });
        const type = params.type || '';
        const qs = new URLSearchParams({ per_page: 50 });
        if (type) qs.set('type', type);
        const res = await api('GET', '/transactions?' + qs);

        $('screen').innerHTML = `
            <div class="chips">${[['', 'Sab'], ['credit', 'Udhaar'], ['payment', 'Jama'], ['reversal', 'Reversal']].map(([k, l]) =>
                `<button class="chip ${k === type ? 'active' : ''}" data-type="${k}">${l}</button>`).join('')}</div>
            <div class="grid2">
                <div class="stat"><div class="label">Total udhaar</div><div class="val red">${rupee(res.summary.total_udhaar)}</div></div>
                <div class="stat"><div class="label">Total jama</div><div class="val green">${rupee(res.summary.total_jama)}</div></div>
            </div>
            ${res.data.length ? `<div class="list">${res.data.map(x => entryRow(x)).join('')}</div>`
              : '<div class="card empty"><i class="bi bi-journal"></i>Koi entry nahi.</div>'}`;
        bindRows($('screen'));
        document.querySelectorAll('[data-type]').forEach(c => c.onclick = () => render('entries', { type: c.dataset.type }));
        fab('<i class="bi bi-plus-lg"></i> Nayi entry', () => go('addEntry', { type: 'credit' }));
    }

    // 11. Dukaan — GET /auth/me, POST /auth/logout
    async function shop() {
        appbar({ title: 'Dukaan', sub: 'Shop & account' });
        const me = await api('GET', '/auth/me');
        state.me = me;
        const s = me.shop, sub = me.subscription;
        $('screen').innerHTML = `
            <div class="card" style="text-align:center">
                <div class="avatar" style="width:64px;height:64px;font-size:22px;margin:0 auto 10px">${initials(s.name)}</div>
                <div style="font-weight:800;font-size:18px">${esc(s.name)}</div>
                <div class="muted">${esc(s.owner_name)} · ${esc(s.phone)}</div>
            </div>
            <div class="card">
                <div class="kv"><span>Plan</span><span>${esc(sub.plan)}</span></div>
                <div class="kv"><span>Status</span><span>${esc(sub.status)}${sub.is_expired ? ' (expired)' : ''}</span></div>
                <div class="kv"><span>Days left</span><span>${esc(sub.days_remaining)}</span></div>
                <div class="kv"><span>Grahak</span><span>${esc(sub.customer_count)} / ${sub.customer_limit === -1 ? 'Unlimited' : esc(sub.customer_limit)}</span></div>
            </div>
            <div class="card">
                <div class="kv"><span>Logged in as</span><span>${esc(me.user.name)}</span></div>
                <div class="kv"><span>Role</span><span>${esc(me.user.role.replace('_', ' '))}</span></div>
                ${s.address ? `<div class="kv"><span>Address</span><span>${esc(s.address)}</span></div>` : ''}
                ${s.gstin ? `<div class="kv"><span>GSTIN</span><span>${esc(s.gstin)}</span></div>` : ''}
            </div>
            ${me.user.can_edit_shop ? '<button class="btn ghost" id="edit"><i class="bi bi-pencil"></i> Edit dukaan details</button>' : ''}
            <button class="btn danger-ghost" id="logout"><i class="bi bi-box-arrow-right"></i> Logout</button>`;
        if ($('edit')) $('edit').onclick = () => go('editShop', { shop: s });
        $('logout').onclick = async () => {
            try { await api('POST', '/auth/logout'); } catch (e) {}
            setToken(null); state.me = null;
            toast('Logged out.');
            go('login');
        };
    }

    // 12. Edit dukaan — GET /shop, PUT /shop
    async function editShop() {
        appbar({ title: 'Edit dukaan', backBtn: true });
        const s = (await api('GET', '/shop')).data;
        $('screen').innerHTML = `
            <form id="f">
                <label class="f">Dukaan ka naam</label><input class="input" name="name" value="${esc(s.name)}" required>
                <label class="f">Owner ka naam</label><input class="input" name="owner_name" value="${esc(s.owner_name)}" required>
                <label class="f">Mobile</label><input class="input" name="phone" inputmode="numeric" maxlength="10" value="${esc(s.phone)}" required>
                <label class="f">Address <span>(optional)</span></label><input class="input" name="address" value="${esc(s.address || '')}">
                <label class="f">GSTIN <span>(optional)</span></label><input class="input" name="gstin" value="${esc(s.gstin || '')}">
                <button class="btn" type="submit"><i class="bi bi-check-lg"></i> Save</button>
            </form>`;
        $('f').onsubmit = (e) => {
            e.preventDefault();
            const fd = new FormData(e.target);
            submitting(e.target.querySelector('button'), async () => {
                try {
                    const r = await api('PUT', '/shop', {
                        name: fd.get('name'), owner_name: fd.get('owner_name'), phone: fd.get('phone'),
                        address: fd.get('address') || null, gstin: fd.get('gstin') || null, email: s.email, currency: s.currency || 'INR',
                    });
                    toast(r.message);
                    back();
                } catch (err) { showErrors(e.target, err); }
            });
        };
    }

    go(state.token ? 'home' : 'login');
})();
</script>
</body>
</html>
