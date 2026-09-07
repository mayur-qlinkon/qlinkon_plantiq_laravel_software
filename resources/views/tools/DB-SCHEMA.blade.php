<?php
// ---------------------------------------------------------
// BACKEND LOGIC
// ---------------------------------------------------------

$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = env('DB_DATABASE');

/* ===================================================================================
   1. MYSQLI CONNECTION (Legacy Support)
   =================================================================================== */
$con = new mysqli($db_host, $db_user, $db_pass, $db_name);

// 1. Handle Bulk Truncate
if (isset($_POST['ajax_bulk_truncate'])) {
    header('Content-Type: application/json');
    try {
        $tables = json_decode($_POST['ajax_bulk_truncate'], true);
        if (!is_array($tables) || empty($tables)) {
            throw new Exception('No tables selected.');
        }

        $successCount = 0;
        $errors = [];

        foreach ($tables as $table) {
            $table = mysqli_real_escape_string($con, $table);

            // Safety: Skip system tables
            if (strpos($table, 'sys_') === 0) {
                continue;
            }

            if (mysqli_query($con, "TRUNCATE TABLE `$table`")) {
                $successCount++;
            } else {
                $errors[] = "$table: " . mysqli_error($con);
            }
        }

        echo json_encode([
            'status' => 'success',
            'msg' => "$successCount tables cleared.",
            'errors' => $errors,
        ]);
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'msg' => $e->getMessage()]);
    }
    exit();
}

// 2. Handle Single Truncate
if (isset($_POST['ajax_truncate_table'])) {
    header('Content-Type: application/json');
    try {
        $table = mysqli_real_escape_string($con, $_POST['ajax_truncate_table']);
        if (strpos($table, 'sys_') === 0) {
            throw new Exception('Protected table.');
        }

        if (!mysqli_query($con, "TRUNCATE TABLE `$table`")) {
            throw new Exception(mysqli_error($con));
        }

        echo json_encode(['status' => 'success', 'msg' => "Table '$table' emptied."]);
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'msg' => $e->getMessage()]);
    }
    exit();
}

// 3. Handle Bulk Schema Export (TXT format)
if (isset($_POST['ajax_export_schema'])) {
    header('Content-Type: text/plain');
    try {
        $tables = json_decode($_POST['ajax_export_schema'], true);
        if (!is_array($tables) || empty($tables)) {
            throw new Exception('No tables selected.');
        }

        $output = '';
        foreach ($tables as $table) {
            $table = mysqli_real_escape_string($con, $table);

            $output .= "Table: $table\n";
            $output .= str_repeat('-', strlen("Table: $table")) . "\n";

            $sql = "SELECT COLUMN_NAME, COLUMN_TYPE, COLUMN_KEY, IS_NULLABLE, EXTRA 
                    FROM INFORMATION_SCHEMA.COLUMNS 
                    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '$table'";
            $res = mysqli_query($con, $sql);

            while ($col = mysqli_fetch_assoc($res)) {
                $name = $col['COLUMN_NAME'];
                $type = $col['COLUMN_TYPE'];

                $attrs = [$type];
                if ($col['COLUMN_KEY'] == 'PRI') {
                    $attrs[] = 'PRIMARY KEY';
                } elseif ($col['COLUMN_KEY']) {
                    $attrs[] = $col['COLUMN_KEY'];
                }

                $attrs[] = $col['IS_NULLABLE'] == 'YES' ? 'NULL' : 'NOT NULL';
                if (!empty($col['EXTRA'])) {
                    $attrs[] = $col['EXTRA'];
                }

                $output .= "$name - " . implode(', ', $attrs) . "\n";
            }
            $output .= "\n";
        }
        echo $output;
    } catch (Exception $e) {
        http_response_code(400);
        echo 'Error: ' . $e->getMessage();
    }
    exit();
}

// 4. Handle Schema Fetch (For Modal)
if (isset($_GET['ajax_fetch_table'])) {
    header('Content-Type: application/json');
    try {
        $table = mysqli_real_escape_string($con, $_GET['ajax_fetch_table']);
        $sql = "SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT, COLUMN_KEY, EXTRA 
                FROM INFORMATION_SCHEMA.COLUMNS 
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '$table'";
        $res = mysqli_query($con, $sql);
        $raw = [];
        $clean = [];
        while ($col = mysqli_fetch_assoc($res)) {
            $raw[] = $col;
            $clean[] = [
                'name' => $col['COLUMN_NAME'],
                'type' => $col['COLUMN_TYPE'],
                'null' => $col['IS_NULLABLE'] === 'YES',
                'default' => $col['COLUMN_DEFAULT'],
                'key' => $col['COLUMN_KEY'],
                'extra' => $col['EXTRA'],
            ];
        }
        echo json_encode(
            [
                'clean' => ['table' => $table, 'columns' => $clean],
                'raw' => ['table' => $table, 'schema' => $raw],
            ],
            JSON_PRETTY_PRINT,
        );
    } catch (Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit();
}

// 5. Initial Load
$dbName = '';
if ($r = mysqli_fetch_row(mysqli_query($con, 'SELECT DATABASE()'))) {
    $dbName = $r[0];
}

$tables = [];
$sql = "SELECT TABLE_NAME, TABLE_ROWS, ENGINE, (DATA_LENGTH + INDEX_LENGTH) as SIZE 
        FROM information_schema.TABLES 
        WHERE TABLE_SCHEMA = '$dbName' ORDER BY TABLE_NAME";
$result = mysqli_query($con, $sql);

while ($row = mysqli_fetch_assoc($result)) {
    $size = $row['SIZE'];
    $unit = 'B';
    if ($size > 1024) {
        $size /= 1024;
        $unit = 'KB';
    }
    if ($size > 1024) {
        $size /= 1024;
        $unit = 'MB';
    }
    $row['SIZE_FMT'] = round($size, 2) . ' ' . $unit;
    $tables[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DB Manager Pro V4</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/themes/prism-tomorrow.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">

    <style>
        :root {
            --bg-dark: #0f1015;
            --panel: #1b1d25;
            --border: #2b2d35;
            --accent: #7c4dff;
            --text: #a6accd;
            --text-light: #ffffff;
            --danger: #ff5555;
            --success: #50fa7b;
        }

        body {
            background-color: var(--bg-dark);
            color: var(--text);
            font-family: 'Inter', sans-serif;
        }

        .dashboard-container {
            max-width: 1200px;
            margin: 30px auto;
        }

        /* Header */
        .db-header {
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .db-title h1 {
            font-size: 1.5rem;
            color: var(--text-light);
            margin: 0;
            font-weight: 700;
        }

        .db-title span {
            color: var(--accent);
            font-size: 0.9rem;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        /* Search & Actions */
        .header-actions {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .search-box {
            position: relative;
            width: 300px;
        }

        .search-box input {
            background: #14151b;
            border: 1px solid var(--border);
            color: #fff;
            padding: 10px 15px 10px 40px;
            border-radius: 8px;
            width: 100%;
        }

        .search-box input:focus {
            background: #14151b;
            border-color: var(--accent);
            box-shadow: none;
            color: #fff;
        }

        .search-icon {
            position: absolute;
            left: 12px;
            top: 12px;
            color: #555;
        }

        .search-box input::placeholder {
            color: white !important;
            opacity: 1;
        }

        .btn-bulk-danger {
            background: rgba(255, 85, 85, 0.1);
            border: 1px solid var(--danger);
            color: var(--danger);
            font-weight: 600;
            transition: 0.3s;
            display: none;
        }

        .btn-bulk-danger:hover {
            background: var(--danger);
            color: white;
        }

        .btn-bulk-primary {
            background: rgba(124, 77, 255, 0.1);
            border: 1px solid var(--accent);
            color: var(--accent);
            font-weight: 600;
            transition: 0.3s;
            display: none;
        }

        .btn-bulk-primary:hover {
            background: var(--accent);
            color: white;
        }

        /* Table List */
        .table-list {
            display: grid;
            gap: 10px;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
        }

        .db-card {
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 15px 20px 15px 50px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
            transition: transform 0.2s, border-color 0.2s;
        }

        .db-card:hover {
            transform: translateY(-2px);
            border-color: #3e414b;
        }

        .db-card.selected {
            border-color: var(--accent);
            background: rgba(124, 77, 255, 0.05);
        }

        /* Custom Checkbox */
        .card-checkbox {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            width: 20px;
            height: 20px;
            cursor: pointer;
            accent-color: var(--accent);
            z-index: 5;
        }

        .tbl-info h5 {
            margin: 0 0 5px 0;
            color: var(--text-light);
            font-size: 1rem;
            font-weight: 600;
        }

        .tbl-meta {
            font-size: 0.8rem;
            color: #666;
            display: flex;
            gap: 15px;
        }

        .tbl-meta span i {
            margin-right: 5px;
            color: var(--accent);
            opacity: 0.7;
        }

        /* Actions */
        .action-group {
            display: flex;
            gap: 8px;
        }

        .btn-icon {
            width: 32px;
            height: 32px;
            border-radius: 6px;
            display: flex;
            justify-content: center;
            align-items: center;
            border: 1px solid var(--border);
            background: #23252f;
            color: #888;
            transition: 0.2s;
            cursor: pointer;
        }

        .btn-icon:hover {
            color: #fff;
            background: var(--accent);
            border-color: var(--accent);
        }

        .btn-icon.danger:hover {
            background: var(--danger);
            border-color: var(--danger);
        }

        /* Modal */
        .modal-content {
            background: var(--panel);
            border: 1px solid var(--border);
            color: var(--text);
        }

        .modal-header {
            border-bottom: 1px solid var(--border);
        }

        .modal-footer {
            border-top: 1px solid var(--border);
        }

        .btn-close {
            filter: invert(1) grayscale(100%) brightness(200%);
        }

        pre {
            max-height: 500px;
            border-radius: 8px;
            margin: 0;
        }

        .select-all-wrap {
            margin-bottom: 15px;
            margin-left: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--accent);
            font-size: 0.9rem;
            font-weight: bold;
        }
    </style>
</head>

<body>

    <div class="container dashboard-container">

        <div class="db-header">
            <div class="db-title">
                <span>Database Manager</span>
                <h1><?= htmlspecialchars($dbName) ?></h1>
            </div>

            <div class="header-actions">
                <button id="btnBulkExport" class="btn btn-bulk-primary" onclick="exportSelectedSchema()">
                    <i class="fa-solid fa-file-export me-2"></i> Export TXT (<span id="exportCount">0</span>)
                </button>

                <button id="btnBulkTruncate" class="btn btn-bulk-danger" onclick="confirmBulkTruncate()">
                    <i class="fa-solid fa-radiation me-2"></i> Wipe Selected (<span id="selectedCount">0</span>)
                </button>

                <div class="search-box">
                    <i class="fa-solid fa-search search-icon"></i>
                    <input type="text" id="searchInput" class="form-control" placeholder="Search tables...">
                </div>
            </div>
        </div>

        <div class="select-all-wrap">
            <input type="checkbox" id="selectAll" style="width: 18px; height: 18px; accent-color: var(--accent);">
            <label for="selectAll" style="cursor: pointer;">Select All Tables</label>
        </div>

        <div class="table-list" id="tableGrid">
            <?php foreach ($tables as $t): ?>
            <div class="db-card" id="card-<?= $t['TABLE_NAME'] ?>" data-name="<?= strtolower($t['TABLE_NAME']) ?>">
                <input type="checkbox" class="card-checkbox table-select" value="<?= $t['TABLE_NAME'] ?>">

                <div class="tbl-info">
                    <h5><?= $t['TABLE_NAME'] ?></h5>
                    <div class="tbl-meta">
                        <span title="Rows"><i class="fa-solid fa-list-ol"></i> <b
                                id="row-<?= $t['TABLE_NAME'] ?>"><?= $t['TABLE_ROWS'] ?></b></span>
                        <span title="Size"><i class="fa-solid fa-hard-drive"></i> <?= $t['SIZE_FMT'] ?></span>
                        <span title="Engine"><i class="fa-solid fa-cogs"></i> <?= $t['ENGINE'] ?></span>
                    </div>
                </div>
                <div class="action-group">
                    <button class="btn-icon" onclick="inspectTable('<?= $t['TABLE_NAME'] ?>')" title="Inspect Schema">
                        <i class="fa-solid fa-eye"></i>
                    </button>
                    <button class="btn-icon danger" onclick="truncateTable('<?= $t['TABLE_NAME'] ?>')"
                        title="Truncate Table">
                        <i class="fa-solid fa-trash-can"></i>
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

    </div>

    <div class="modal fade" id="schemaModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold text-white"><i class="fa-solid fa-table me-2 text-accent"></i> <span
                            id="modalTitle">Table</span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="d-flex border-bottom border-secondary border-opacity-25 bg-dark">
                        <button id="tab-simple"
                            class="btn btn-link tab-btn text-decoration-none text-light fw-bold px-4 py-3 active-tab"
                            onclick="switchView('simple')">Simple Text</button>
                        <button id="tab-clean" class="btn btn-link tab-btn text-decoration-none text-muted px-4 py-3"
                            onclick="switchView('clean')">Clean JSON</button>
                        <button id="tab-raw" class="btn btn-link tab-btn text-decoration-none text-muted px-4 py-3"
                            onclick="switchView('raw')">Raw JSON</button>
                    </div>
                    <div class="p-0">
                        <pre><code class="language-plaintext" id="jsonOutput">Loading...</code></pre>
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <span class="small text-muted">Generated via Schema API</span>
                    <button class="btn btn-sm btn-light fw-bold" onclick="copyCode()"><i
                            class="fa-regular fa-copy me-2"></i> Copy to Clipboard</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/prism.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-json.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        let schemaData = {};
        const modal = new bootstrap.Modal(document.getElementById('schemaModal'));

        // --- Search Filter ---
        document.getElementById('searchInput').addEventListener('keyup', function(e) {
            const term = e.target.value.toLowerCase();
            document.querySelectorAll('.db-card').forEach(card => {
                const name = card.getAttribute('data-name');
                card.style.display = name.includes(term) ? 'flex' : 'none';
            });
        });

        // --- Checkbox Logic ---
        const selectAll = document.getElementById('selectAll');
        const checkBoxes = document.querySelectorAll('.table-select');
        const bulkBtn = document.getElementById('btnBulkTruncate');
        const exportBtn = document.getElementById('btnBulkExport');
        const countSpan = document.getElementById('selectedCount');
        const exportCountSpan = document.getElementById('exportCount');

        function updateBulkState() {
            const checked = document.querySelectorAll('.table-select:checked');
            const count = checked.length;
            countSpan.innerText = count;
            exportCountSpan.innerText = count;

            // Highlight selected cards
            document.querySelectorAll('.db-card').forEach(c => c.classList.remove('selected'));
            checked.forEach(cb => {
                document.getElementById('card-' + cb.value).classList.add('selected');
            });

            const displayState = count > 0 ? 'inline-block' : 'none';
            bulkBtn.style.display = displayState;
            exportBtn.style.display = displayState;
        }

        selectAll.addEventListener('change', function() {
            const visibleCards = Array.from(document.querySelectorAll('.db-card')).filter(c => c.style.display !==
                'none');
            visibleCards.forEach(card => {
                const cb = card.querySelector('.table-select');
                cb.checked = this.checked;
            });
            updateBulkState();
        });

        checkBoxes.forEach(cb => {
            cb.addEventListener('change', updateBulkState);
        });

        // --- Export Schema (.txt) ---
        function exportSelectedSchema() {
            const checked = Array.from(document.querySelectorAll('.table-select:checked')).map(cb => cb.value);
            if (checked.length === 0) return;

            Swal.fire({
                title: 'Exporting...',
                didOpen: () => Swal.showLoading(),
                background: '#1b1d25',
                color: '#fff'
            });

            const formData = new FormData();
            formData.append('ajax_export_schema', JSON.stringify(checked));

            fetch('', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.text())
                .then(text => {
                    const blob = new Blob([text], {
                        type: 'text/plain'
                    });
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = 'db_schema_export.txt';
                    document.body.appendChild(a);
                    a.click();
                    a.remove();
                    window.URL.revokeObjectURL(url);

                    Swal.fire({
                        icon: 'success',
                        title: 'Exported!',
                        background: '#1b1d25',
                        color: '#fff',
                        timer: 1500,
                        showConfirmButton: false
                    });
                })
                .catch(err => Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: err.message
                }));
        }

        // --- Bulk Truncate ---
        function confirmBulkTruncate() {
            const checked = Array.from(document.querySelectorAll('.table-select:checked')).map(cb => cb.value);
            if (checked.length === 0) return;

            Swal.fire({
                title: `Wipe ${checked.length} Tables?`,
                html: `<div class='text-start small text-muted p-2 border rounded bg-dark'>${checked.join(', ')}</div><br><span class='text-danger fw-bold'>This cannot be undone!</span>`,
                icon: 'warning',
                background: '#1b1d25',
                color: '#fff',
                showCancelButton: true,
                confirmButtonColor: '#ff5555',
                cancelButtonColor: '#333',
                confirmButtonText: 'Yes, Wipe All!'
            }).then((result) => {
                if (result.isConfirmed) {
                    performBulkTruncate(checked);
                }
            });
        }

        function performBulkTruncate(tables) {
            Swal.fire({
                title: 'Processing...',
                didOpen: () => Swal.showLoading(),
                background: '#1b1d25',
                color: '#fff'
            });

            const formData = new FormData();
            formData.append('ajax_bulk_truncate', JSON.stringify(tables));

            fetch('', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Done!',
                            text: data.msg,
                            background: '#1b1d25',
                            color: '#fff',
                            timer: 1500,
                            showConfirmButton: false
                        });

                        tables.forEach(t => {
                            const badge = document.getElementById('row-' + t);
                            if (badge) badge.innerText = '0';
                            const cb = document.querySelector(`.table-select[value="${t}"]`);
                            if (cb) cb.checked = false;
                        });
                        selectAll.checked = false;
                        updateBulkState();
                    } else {
                        throw new Error(data.msg);
                    }
                })
                .catch(err => Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: err.message
                }));
        }

        // --- Single Truncate ---
        function truncateTable(table) {
            Swal.fire({
                title: 'Are you sure?',
                html: `Truncate table <b class="text-danger">${table}</b>?`,
                icon: 'warning',
                background: '#1b1d25',
                color: '#fff',
                showCancelButton: true,
                confirmButtonColor: '#ff5555',
                cancelButtonColor: '#333',
                confirmButtonText: 'Yes, Wipe it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    const formData = new FormData();
                    formData.append('ajax_truncate_table', table);

                    fetch('', {
                            method: 'POST',
                            body: formData
                        })
                        .then(res => res.json())
                        .then(data => {
                            if (data.status === 'success') {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Cleaned!',
                                    background: '#1b1d25',
                                    color: '#fff',
                                    timer: 1000,
                                    showConfirmButton: false
                                });
                                document.getElementById(`row-${table}`).innerText = '0';
                            } else {
                                throw new Error(data.msg);
                            }
                        })
                        .catch(err => Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: err.message
                        }));
                }
            });
        }

        // --- Schema View ---
        function inspectTable(table) {
            document.getElementById('modalTitle').innerText = table;
            document.getElementById('jsonOutput').innerText = "Loading schema...";
            modal.show();
            fetch(`?ajax_fetch_table=${table}`).then(res => res.json()).then(data => {
                schemaData = data;
                switchView('simple'); // Default to the simple, clean format
            }).catch(err => document.getElementById('jsonOutput').innerText = err);
        }

        function switchView(type) {
            if (!schemaData.clean) return;

            // Tab UI updates
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('text-light', 'fw-bold', 'active-tab');
                btn.classList.add('text-muted');
            });
            const activeTab = document.getElementById('tab-' + type);
            activeTab.classList.remove('text-muted');
            activeTab.classList.add('text-light', 'fw-bold', 'active-tab');

            const codeBlock = document.getElementById('jsonOutput');

            if (type === 'simple') {
                let out = `Table: ${schemaData.clean.table}\n`;
                out += "-".repeat(out.length - 1) + "\n";
                schemaData.clean.columns.forEach(col => {
                    let attrs = [col.type];
                    if (col.key === 'PRI') attrs.push('PRIMARY KEY');
                    else if (col.key) attrs.push(col.key);
                    attrs.push(col.null ? 'NULL' : 'NOT NULL');
                    if (col.extra) attrs.push(col.extra);

                    out += `${col.name} - ${attrs.join(', ')}\n`;
                });
                codeBlock.className = "language-plaintext";
                codeBlock.textContent = out;
            } else {
                const data = (type === 'clean') ? schemaData.clean : schemaData.raw;
                codeBlock.className = "language-json";
                codeBlock.textContent = JSON.stringify(data, null, 4);
            }
            Prism.highlightElement(codeBlock);
        }

        function copyCode() {
            navigator.clipboard.writeText(document.getElementById('jsonOutput').textContent);
            Swal.fire({
                toast: true,
                position: 'bottom-end',
                icon: 'success',
                title: 'Copied',
                showConfirmButton: false,
                timer: 1000,
                background: '#2b2d35',
                color: '#fff'
            });
        }
    </script>

</body>

</html>
