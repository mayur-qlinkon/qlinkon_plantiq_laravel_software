<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Mark Attendance — {{ $store->name }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f0fdf4;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .card {
            background: #fff;
            border-radius: 24px;
            box-shadow: 0 8px 40px rgba(0,0,0,0.10);
            padding: 32px 28px;
            width: 100%;
            max-width: 380px;
            text-align: center;
        }

        .store-icon {
            width: 64px; height: 64px;
            background: #dcfce7;
            border-radius: 18px;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 16px;
            font-size: 28px;
        }

        .store-name {
            font-size: 22px;
            font-weight: 900;
            color: #111827;
            margin-bottom: 4px;
        }

        .store-sub {
            font-size: 13px;
            color: #9ca3af;
            margin-bottom: 28px;
        }

        /* Status pill */
        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border-radius: 100px;
            font-size: 13px;
            font-weight: 700;
            margin-bottom: 28px;
        }
        .status-pill .dot { width: 8px; height: 8px; border-radius: 50%; }
        .status-present { background: #dcfce7; color: #166534; }
        .status-present .dot { background: #22c55e; }
        .status-pending  { background: #fef9c3; color: #854d0e; }
        .status-pending .dot  { background: #eab308; }
        .status-none    { background: #f3f4f6; color: #6b7280; }
        .status-none .dot    { background: #9ca3af; }

        /* GPS status */
        .gps-bar {
            background: #f8fafc;
            border: 1.5px solid #e5e7eb;
            border-radius: 12px;
            padding: 12px 16px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .gps-icon { font-size: 18px; flex-shrink: 0; }
        .gps-text { flex: 1; text-align: left; }
        .gps-text p { font-size: 12px; font-weight: 700; color: #374151; }
        .gps-text span { font-size: 11px; color: #9ca3af; }

        /* Main button */
        .btn-main {
            width: 100%;
            padding: 18px;
            border-radius: 16px;
            border: none;
            font-size: 17px;
            font-weight: 900;
            color: #fff;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: transform 100ms, opacity 150ms;
            letter-spacing: 0.01em;
        }
        .btn-main:active { transform: scale(0.97); }
        .btn-main:disabled { opacity: 0.6; cursor: not-allowed; }
        .btn-checkin  { background: linear-gradient(135deg, #16a34a, #22c55e); }
        .btn-checkout { background: linear-gradient(135deg, #d97706, #f59e0b); }
        .btn-done     { background: #e5e7eb; color: #374151; cursor: default; }

        /* Result overlay */
        .result-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.5);
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
            z-index: 100;
        }
        .result-overlay.show { display: flex; }
        .result-card {
            background: #fff;
            border-radius: 24px;
            padding: 40px 32px;
            text-align: center;
            width: 100%;
            max-width: 340px;
            animation: popIn 300ms cubic-bezier(0.34,1.56,0.64,1);
        }
        @keyframes popIn {
            from { transform: scale(0.7); opacity: 0; }
            to   { transform: scale(1);   opacity: 1; }
        }
        .result-icon {
            width: 72px; height: 72px;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 36px;
            margin: 0 auto 20px;
        }
        .result-icon.success { background: #dcfce7; }
        .result-icon.error   { background: #fee2e2; }
        .result-title { font-size: 22px; font-weight: 900; color: #111827; margin-bottom: 8px; }
        .result-msg   { font-size: 14px; color: #6b7280; margin-bottom: 28px; line-height: 1.5; }
        .btn-back {
            display: block; width: 100%; padding: 14px; border-radius: 12px;
            border: 2px solid #e5e7eb; background: #fff; font-size: 15px;
            font-weight: 700; color: #374151; cursor: pointer;
        }

        .time-display {
            font-size: 13px;
            color: #6b7280;
            margin-top: 12px;
        }

        .employee-info {
            background: #f8fafc;
            border-radius: 12px;
            padding: 10px 14px;
            margin-bottom: 20px;
            text-align: left;
        }
        .employee-info p { font-size: 13px; font-weight: 700; color: #374151; }
        .employee-info span { font-size: 11px; color: #9ca3af; }

        .spinner {
            width: 22px; height: 22px;
            border: 3px solid rgba(255,255,255,0.4);
            border-top-color: #fff;
            border-radius: 50%;
            animation: spin 0.7s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
    </style>
</head>
<body>

<div class="card">

    <!-- Store Icon -->
    <div class="store-icon">🏢</div>
    <p class="store-name">{{ $store->name }}</p>
    <p class="store-sub">{{ now()->format('l, d M Y') }}</p>

    @if($employee)
    <!-- Employee Info -->
    <div class="employee-info">
        <p>{{ $employee->user?->name ?? 'Employee' }}</p>
        <span>{{ $employee->employee_code }} @if($employee->department) · {{ $employee->department->name }} @endif</span>
    </div>

    <!-- Attendance Status Pill -->
    @if($action === 'done')
        <div class="status-pill status-present">
            <span class="dot"></span>
            Attendance Complete — {{ $todayAttendance->check_out_time->format('h:i A') }}
        </div>
    @elseif($action === 'check-out')
        <div class="status-pill status-pending">
            <span class="dot"></span>
            Checked In at {{ $todayAttendance->check_in_time->format('h:i A') }}
        </div>
    @else
        <div class="status-pill status-none">
            <span class="dot"></span>
            Not checked in yet
        </div>
    @endif

    <!-- GPS Status Bar -->
    <div class="gps-bar" id="gpsBar">
        <span class="gps-icon" id="gpsIcon">📍</span>
        <div class="gps-text">
            <p id="gpsTitle">Getting location...</p>
            <span id="gpsSub">Please allow location access</span>
        </div>
    </div>

    <!-- Main Action Button -->
    @if($action === 'done')
        <button class="btn-main btn-done" disabled>✅ Attendance Complete</button>
    @elseif($action === 'check-out')
        <button class="btn-main btn-checkout" id="actionBtn" onclick="markAttendance()" disabled>
            <span id="btnText">Check Out</span>
        </button>
    @else
        <button class="btn-main btn-checkin" id="actionBtn" onclick="markAttendance()" disabled>
            <span id="btnText">Check In</span>
        </button>
    @endif

    <p class="time-display" id="currentTime"></p>

    @else
    <!-- No employee profile -->
    <div style="background:#fef2f2; border-radius:12px; padding:20px; margin-top:10px;">
        <p style="font-size:15px; font-weight:800; color:#991b1b; margin-bottom:6px;">Not Registered</p>
        <p style="font-size:13px; color:#b91c1c;">Your employee profile has not been set up yet. Please contact HR.</p>
    </div>
    @endif

</div>

<!-- Result Overlay -->
<div class="result-overlay" id="resultOverlay">
    <div class="result-card">
        <div class="result-icon" id="resultIcon"></div>
        <p class="result-title" id="resultTitle"></p>
        <p class="result-msg" id="resultMsg"></p>
        <button class="btn-back" onclick="closeResult()">Done</button>
    </div>
</div>

<script>
let gpsLat = null;
let gpsLng = null;
let gpsReady = false;

// Live clock
function updateClock() {
    const el = document.getElementById('currentTime');
    if (el) el.textContent = new Date().toLocaleTimeString('en-IN', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true });
}
setInterval(updateClock, 1000);
updateClock();

// Get GPS on page load
@if($action !== 'done')
if (navigator.geolocation) {
    navigator.geolocation.watchPosition(
        (pos) => {
            gpsLat = pos.coords.latitude;
            gpsLng = pos.coords.longitude;
            gpsReady = true;

            document.getElementById('gpsIcon').textContent = '✅';
            document.getElementById('gpsTitle').textContent = 'Location acquired';
            document.getElementById('gpsSub').textContent = `${gpsLat.toFixed(5)}, ${gpsLng.toFixed(5)}`;
            document.getElementById('gpsBar').style.borderColor = '#bbf7d0';

            const btn = document.getElementById('actionBtn');
            if (btn) btn.disabled = false;
        },
        (err) => {
            document.getElementById('gpsIcon').textContent = '❌';
            document.getElementById('gpsTitle').textContent = 'Location denied';
            document.getElementById('gpsSub').textContent = 'Please allow location access in your browser';
            document.getElementById('gpsBar').style.borderColor = '#fca5a5';
        },
        { enableHighAccuracy: true, timeout: 15000, maximumAge: 10000 }
    );
} else {
    document.getElementById('gpsTitle').textContent = 'GPS not supported';
    document.getElementById('gpsSub').textContent = 'Your browser does not support location services';
}
@endif

async function markAttendance() {
    if (!gpsReady) {
        alert('Location not ready. Please allow location access and try again.');
        return;
    }

    const btn = document.getElementById('actionBtn');
    const btnText = document.getElementById('btnText');
    btn.disabled = true;
    btnText.innerHTML = '<span class="spinner"></span>';

    try {
        const sendScan = async (forceCheckout = false) => fetch('{{ route('admin.hrm.attendance.scan') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                store_id: {{ $store->id }},
                latitude: gpsLat,
                longitude: gpsLng,
                force_checkout: forceCheckout,
            }),
        });

        let resp = await sendScan();
        let data = await resp.json();

        if (data.requires_confirmation) {
            const confirmed = window.confirm(data.message);

            if (!confirmed) {
                btn.disabled = false;
                btnText.textContent = '{{ $action === "check-out" ? "Check Out" : "Check In" }}';
                return;
            }

            resp = await sendScan(true);
            data = await resp.json();
        }

        if (data.success) {
            showResult(true, data.action === 'check_in' ? '✅' : '👋',
                data.action === 'check_in' ? 'Checked In!' : 'Checked Out!',
                data.message);
        } else {
            showResult(false, '⚠️', 'Could Not Mark Attendance', data.message);
            btn.disabled = false;
            btnText.textContent = '{{ $action === "check-out" ? "Check Out" : "Check In" }}';
        }
    } catch (e) {
        showResult(false, '❌', 'Network Error', 'Please check your connection and try again.');
        btn.disabled = false;
        btnText.textContent = '{{ $action === "check-out" ? "Check Out" : "Check In" }}';
    }
}

function showResult(success, icon, title, msg) {
    document.getElementById('resultIcon').textContent = icon;
    document.getElementById('resultIcon').className = 'result-icon ' + (success ? 'success' : 'error');
    document.getElementById('resultTitle').textContent = title;
    document.getElementById('resultMsg').textContent   = msg;
    document.getElementById('resultOverlay').classList.add('show');
}

function closeResult() {
    document.getElementById('resultOverlay').classList.remove('show');
    if (document.getElementById('resultTitle').textContent.includes('Checked')) {
        location.reload();
    }
}
</script>

</body>
</html>
