<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Tester</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: system-ui, -apple-system, sans-serif; background: #0f172a; color: #e2e8f0; padding: 20px; }
        h1 { text-align: center; margin-bottom: 24px; color: #38bdf8; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; max-width: 1200px; margin: 0 auto; }
        .card { background: #1e293b; border-radius: 12px; padding: 20px; border: 1px solid #334155; }
        .card h2 { color: #38bdf8; font-size: 16px; margin-bottom: 12px; display: flex; align-items: center; gap: 8px; }
        .badge { font-size: 11px; padding: 2px 8px; border-radius: 4px; font-weight: 700; }
        .badge-post { background: #16a34a; color: #fff; }
        .badge-get { background: #2563eb; color: #fff; }
        label { display: block; font-size: 13px; color: #94a3b8; margin-bottom: 4px; margin-top: 10px; }
        input, textarea { width: 100%; padding: 8px 10px; border-radius: 6px; border: 1px solid #475569; background: #0f172a; color: #e2e8f0; font-size: 14px; }
        textarea { resize: vertical; min-height: 80px; font-family: monospace; }
        button { margin-top: 12px; padding: 8px 20px; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 14px; }
        .btn-primary { background: #2563eb; color: #fff; }
        .btn-primary:hover { background: #1d4ed8; }
        .btn-green { background: #16a34a; color: #fff; }
        .btn-green:hover { background: #15803d; }
        .btn-red { background: #dc2626; color: #fff; }
        .btn-red:hover { background: #b91c1c; }
        .output { margin-top: 12px; background: #0f172a; border: 1px solid #334155; border-radius: 8px; padding: 12px; max-height: 300px; overflow-y: auto; }
        .output pre { font-size: 12px; white-space: pre-wrap; word-break: break-all; color: #a5f3fc; }
        .status { font-size: 12px; padding: 2px 6px; border-radius: 4px; margin-left: 8px; }
        .status-ok { background: #16a34a33; color: #4ade80; }
        .status-err { background: #dc262633; color: #f87171; }
        .token-bar { max-width: 1200px; margin: 0 auto 16px; background: #1e293b; border-radius: 12px; padding: 16px 20px; border: 1px solid #334155; }
        .token-bar .token-display { font-family: monospace; font-size: 12px; color: #4ade80; word-break: break-all; margin-top: 6px; min-height: 20px; }
        .full-width { grid-column: 1 / -1; }
    </style>
</head>
<body>
    <h1>🏋️ API Tester — Gym Routines</h1>

    <!-- Token Bar -->
    <div class="token-bar">
        <strong style="color:#38bdf8">🔑 Token actual:</strong>
        <span id="noToken" style="color:#f87171; font-size:13px;"> Sin token — haz login o register primero</span>
        <div class="token-display" id="tokenDisplay"></div>
    </div>

    <div class="grid">
        <!-- REGISTER -->
        <div class="card">
            <h2><span class="badge badge-post">POST</span> /api/register</h2>
            <label>Name</label>
            <input id="regName" value="Test User">
            <label>Email</label>
            <input id="regEmail" value="nuevo@test.com">
            <label>Password</label>
            <input id="regPass" type="password" value="password123">
            <label>Confirm Password</label>
            <input id="regPassC" type="password" value="password123">
            <button class="btn-green" onclick="doRegister()">Register</button>
            <div class="output"><pre id="outRegister">—</pre></div>
        </div>

        <!-- LOGIN -->
        <div class="card">
            <h2><span class="badge badge-post">POST</span> /api/login</h2>
            <label>Email</label>
            <input id="loginEmail" value="nuevo@test.com">
            <label>Password</label>
            <input id="loginPass" type="password" value="password123">
            <button class="btn-primary" onclick="doLogin()">Login</button>
            <div class="output"><pre id="outLogin">—</pre></div>
        </div>

        <!-- GET USER -->
        <div class="card">
            <h2><span class="badge badge-get">GET</span> /api/user</h2>
            <p style="font-size:13px;color:#94a3b8">Devuelve el usuario autenticado.</p>
            <button class="btn-primary" onclick="doGetUser()">Get User</button>
            <div class="output"><pre id="outUser">—</pre></div>
        </div>

        <!-- LOGOUT -->
        <div class="card">
            <h2><span class="badge badge-post">POST</span> /api/logout</h2>
            <p style="font-size:13px;color:#94a3b8">Revoca el token actual.</p>
            <button class="btn-red" onclick="doLogout()">Logout</button>
            <div class="output"><pre id="outLogout">—</pre></div>
        </div>

        <!-- GET ROUTINES -->
        <div class="card">
            <h2><span class="badge badge-get">GET</span> /api/routines</h2>
            <p style="font-size:13px;color:#94a3b8">Rutinas del usuario autenticado con ejercicios y datos pivot.</p>
            <button class="btn-primary" onclick="doGetRoutines()">Get Routines</button>
            <div class="output"><pre id="outRoutines">—</pre></div>
        </div>

        <!-- GET ROUTINE DETAIL -->
        <div class="card">
            <h2><span class="badge badge-get">GET</span> /api/routines/{id}</h2>
            <label>Routine ID</label>
            <input id="routineDetailId" value="1" type="number">
            <button class="btn-primary" onclick="doGetRoutineDetail()">Get Detail</button>
            <div class="output"><pre id="outRoutineDetail">—</pre></div>
        </div>

        <!-- POST ROUTINE -->
        <div class="card full-width">
            <h2><span class="badge badge-post">POST</span> /api/routines</h2>
            <label>JSON Body</label>
            <textarea id="routineBody" rows="10">{
    "name": "Mi Rutina Push",
    "description": "Día de empuje",
    "exercises": [
        { "id": 1, "sequence": 1, "target_sets": 4, "target_reps": 10, "rest_seconds": 90 },
        { "id": 2, "sequence": 2, "target_sets": 3, "target_reps": 12, "rest_seconds": 60 }
    ]
}</textarea>
            <button class="btn-green" onclick="doPostRoutine()">Create Routine</button>
            <div class="output"><pre id="outPostRoutine">—</pre></div>
        </div>
    </div>

    <script>
        const BASE = '/api';
        let token = null;

        function setToken(t) {
            token = t;
            document.getElementById('tokenDisplay').textContent = t;
            document.getElementById('noToken').style.display = t ? 'none' : 'inline';
        }

        function headers(json = true) {
            const h = { 'Accept': 'application/json' };
            if (json) h['Content-Type'] = 'application/json';
            if (token) h['Authorization'] = 'Bearer ' + token;
            return h;
        }

        function pretty(data) {
            return JSON.stringify(data, null, 2);
        }

        async function apiCall(method, url, body, outputId) {
            const el = document.getElementById(outputId);
            el.textContent = '⏳ Loading...';
            try {
                const opts = { method, headers: headers() };
                if (body) opts.body = JSON.stringify(body);
                const res = await fetch(BASE + url, opts);
                const data = await res.json();
                const statusClass = res.ok ? 'status-ok' : 'status-err';
                el.innerHTML = `<span class="${statusClass}">${res.status}</span>\n${pretty(data)}`;
                return { ok: res.ok, data };
            } catch (e) {
                el.textContent = '❌ Error: ' + e.message;
                return { ok: false };
            }
        }

        async function doRegister() {
            const body = {
                name: document.getElementById('regName').value,
                email: document.getElementById('regEmail').value,
                password: document.getElementById('regPass').value,
                password_confirmation: document.getElementById('regPassC').value,
            };
            const { ok, data } = await apiCall('POST', '/register', body, 'outRegister');
            if (ok && data.token) setToken(data.token);
        }

        async function doLogin() {
            const body = {
                email: document.getElementById('loginEmail').value,
                password: document.getElementById('loginPass').value,
            };
            const { ok, data } = await apiCall('POST', '/login', body, 'outLogin');
            if (ok && data.token) setToken(data.token);
        }

        async function doGetUser() {
            await apiCall('GET', '/user', null, 'outUser');
        }

        async function doLogout() {
            const { ok } = await apiCall('POST', '/logout', null, 'outLogout');
            if (ok) setToken(null);
        }

        async function doGetRoutines() {
            await apiCall('GET', '/routines', null, 'outRoutines');
        }

        async function doGetRoutineDetail() {
            const id = document.getElementById('routineDetailId').value;
            await apiCall('GET', '/routines/' + id, null, 'outRoutineDetail');
        }

        async function doPostRoutine() {
            try {
                const body = JSON.parse(document.getElementById('routineBody').value);
                await apiCall('POST', '/routines', body, 'outPostRoutine');
            } catch (e) {
                document.getElementById('outPostRoutine').textContent = '❌ JSON inválido: ' + e.message;
            }
        }
    </script>
</body>
</html>
