<?php
function loadEnv($path) {
    if (!file_exists($path)) {
        throw new Exception(".env file not found");
    }
    
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value, '"\'');
            $_ENV[$key] = $value;
            putenv("{$key}={$value}");
        }
    }
}

function loginQTime($prodHost, $webPrefix, $username, $password) {
    $url = "{$prodHost}/{$webPrefix}/auth/login";
    $data = ['username' => $username, 'password' => $password];
    $jsonData = json_encode($data);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($jsonData)
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_errno($ch)) {
        $error = curl_error($ch);
        curl_close($ch);
        throw new Exception("cURL Error: {$error}");
    }
    
    curl_close($ch);
    
    return [
        'status' => $httpCode,
        'data' => json_decode($response, true),
        'raw' => $response
    ];
}

function loginBioTime($prodHost, $username, $password) {
    $url = "{$prodHost}/jwt-api-token-auth/";
    $data = ['username' => $username, 'password' => $password];
    $jsonData = json_encode($data);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($jsonData)
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_errno($ch)) {
        $error = curl_error($ch);
        curl_close($ch);
        throw new Exception("cURL Error: {$error}");
    }
    
    curl_close($ch);
    
    return [
        'status' => $httpCode,
        'data' => json_decode($response, true),
        'raw' => $response
    ];
}

function getAllQTimeEmployees($prodHost, $webPrefix, $token, $clientId, $projectId, $limit = 999) {
    $url = "{$prodHost}/{$webPrefix}/employee/get-all?limit={$limit}";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $token,
        'client_id: ' . $clientId,
        'project_id: ' . $projectId
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_errno($ch)) {
        $error = curl_error($ch);
        curl_close($ch);
        throw new Exception("cURL Error: {$error}");
    }
    
    curl_close($ch);
    
    return [
        'status' => $httpCode,
        'data' => json_decode($response, true)
    ];
}

function getAllBioTimeEmployees($prodHost, $areaId, $token, $pageSize = 999) {
    $url = "{$prodHost}/personnel/api/employees/?page_size={$pageSize}&areas={$areaId}";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: JWT ' . $token,
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_errno($ch)) {
        $error = curl_error($ch);
        curl_close($ch);
        throw new Exception("cURL Error: {$error}");
    }
    
    curl_close($ch);
    
    return [
        'status' => $httpCode,
        'data' => json_decode($response, true)
    ];
}

function hasValidRfid($rfid) {
    return !empty($rfid) && $rfid !== null && trim($rfid) !== '';
}

$qtimeTypeMap = [
    4 => 'Operator',
    3 => 'Subcon Worker',
    2 => 'Staff',
    1 => 'General Worker'
];

$fullyRegistered = [];
$faceNotRegistered = [];
$rfidNotRegistered = [];
$notRegistered = [];
$error = null;

try {
    // loadEnv(__DIR__ . '/.env');

    $bioTimeEndpoint = "http://biotime.metrio.com.my:8082";
    $bioTimeUsername = "admin";
    $bioTimePassword = "admin@221";
    $biotimeAreaIdBkt2sa = 25;
    $qtimeEndpoint = "https://qtime-api.qubit-it.com.my";
    $qtimePrefix = "qtime/web";
    $qtimeUsername = "system";
    $qtimePassword = "123";
    $qtimeClientId = 1;
    $qtimeAreaIdBkt2sa = 3;
    
    $result = loginQTime($qtimeEndpoint, $qtimePrefix, $qtimeUsername, $qtimePassword);
    $qtimeToken = $result['data']['data']['token'];
    $result = getAllQTimeEmployees($qtimeEndpoint, $qtimePrefix, $qtimeToken, $qtimeClientId, $qtimeAreaIdBkt2sa);
    
    $qtimeEmployeeArray = [];
    foreach ($result['data']['data']['data'] as $item) {
        $qtimeEmployeeArray[] = [
            'code' => $item['code'],
            'rfid' => $item['rfid_code'],
            'name' => $item['name'],
            'type' => $qtimeTypeMap[$item['employee_type_id']]
        ];
    }
    
    $result = loginBioTime($bioTimeEndpoint, $bioTimeUsername, $bioTimePassword);
    $bioTimeToken = $result['data']['token'];
    $result = getAllBioTimeEmployees($bioTimeEndpoint, $biotimeAreaIdBkt2sa, $bioTimeToken);
    
    $bioTimeEmployeeArray = [];
    foreach ($result['data']['data'] as $item) {
        $bioTimeEmployeeArray[] = [
            'code' => $item['emp_code'],
            'name' => $item['first_name'] . " " . $item['last_name']
        ];
    }
    
    $bioTimeCodes = array_column($bioTimeEmployeeArray, 'code');
    
    foreach ($qtimeEmployeeArray as $employee) {
        $code = $employee['code'];
        $hasRfid = hasValidRfid($employee['rfid']);
        $inBioTime = in_array($code, $bioTimeCodes);
        
        if ($inBioTime && $hasRfid) {
            $fullyRegistered[] = $employee;
        }
        elseif (!$inBioTime && $hasRfid) {
            $faceNotRegistered[] = $employee;
        }
        elseif ($inBioTime && !$hasRfid) {
            $rfidNotRegistered[] = $employee;
        }
        elseif (!$inBioTime && !$hasRfid) {
            $notRegistered[] = $employee;
        }
    }
    
} catch (Exception $e) {
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Registration Status</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <style>
        body { padding: 20px; background-color: #f8f9fa; }
        .status-card { margin-bottom: 20px; }
        .table-responsive { margin-top: 15px; }
        .badge-large { font-size: 1.2rem; padding: 10px 15px; }
        .dataTables_wrapper { padding-top: 20px; }
        th { cursor: pointer; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <h1 class="mb-4">Employee Registration Status Dashboard</h1>
        
        <?php if ($error): ?>
            <div class="alert alert-danger" role="alert">
                <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php else: ?>
        
        <!-- Summary Cards -->
        <div class="row mb-4">
            <div class="col-lg-3 col-md-6">
                <div class="card text-white bg-success status-card">
                    <div class="card-body">
                        <h5 class="card-title">Fully Registered</h5>
                        <p class="display-4"><?php echo count($fullyRegistered); ?></p>
                        <p class="card-text">RFID + Face</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="card text-white bg-warning status-card">
                    <div class="card-body">
                        <h5 class="card-title">Face Not Registered</h5>
                        <p class="display-4"><?php echo count($faceNotRegistered); ?></p>
                        <p class="card-text">RFID Only</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="card text-white bg-info status-card">
                    <div class="card-body">
                        <h5 class="card-title">RFID Not Registered</h5>
                        <p class="display-4"><?php echo count($rfidNotRegistered); ?></p>
                        <p class="card-text">Face Only</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="card text-white bg-danger status-card">
                    <div class="card-body">
                        <h5 class="card-title">Not Registered</h5>
                        <p class="display-4"><?php echo count($notRegistered); ?></p>
                        <p class="card-text">No RFID, No Face</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabs -->
        <ul class="nav nav-tabs" id="myTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="full-tab" data-bs-toggle="tab" data-bs-target="#full" type="button">
                    Fully Registered <span class="badge bg-success"><?php echo count($fullyRegistered); ?></span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="face-tab" data-bs-toggle="tab" data-bs-target="#face" type="button">
                    Face Not Registered <span class="badge bg-warning"><?php echo count($faceNotRegistered); ?></span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="rfid-tab" data-bs-toggle="tab" data-bs-target="#rfid" type="button">
                    RFID Not Registered <span class="badge bg-info"><?php echo count($rfidNotRegistered); ?></span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="not-tab" data-bs-toggle="tab" data-bs-target="#not" type="button">
                    Not Registered <span class="badge bg-danger"><?php echo count($notRegistered); ?></span>
                </button>
            </li>
        </ul>

        <div class="tab-content" id="myTabContent">
            <!-- Fully Registered Tab -->
            <div class="tab-pane fade show active" id="full" role="tabpanel">
                <div class="table-responsive">
                    <table id="fullTable" class="table table-striped table-hover">
                        <thead class="table-success">
                            <tr>
                                <th>#</th>
                                <th>Code</th>
                                <th>Name</th>
                                <th>Type</th>
                                <th>RFID</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($fullyRegistered as $index => $emp): ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td><?php echo htmlspecialchars($emp['code']); ?></td>
                                <td><?php echo htmlspecialchars($emp['name']); ?></td>
                                <td><?php echo htmlspecialchars($emp['type']); ?></td>
                                <td><?php echo htmlspecialchars($emp['rfid']); ?></td>
                                <td><span class="badge bg-success">RFID + Face</span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Face Not Registered Tab -->
            <div class="tab-pane fade" id="face" role="tabpanel">
                <div class="table-responsive">
                    <table id="faceTable" class="table table-striped table-hover">
                        <thead class="table-warning">
                            <tr>
                                <th>#</th>
                                <th>Code</th>
                                <th>Name</th>
                                <th>Type</th>
                                <th>RFID</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($faceNotRegistered as $index => $emp): ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td><?php echo htmlspecialchars($emp['code']); ?></td>
                                <td><?php echo htmlspecialchars($emp['name']); ?></td>
                                <td><?php echo htmlspecialchars($emp['type']); ?></td>
                                <td><?php echo htmlspecialchars($emp['rfid']); ?></td>
                                <td><span class="badge bg-warning">RFID Only</span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- RFID Not Registered Tab -->
            <div class="tab-pane fade" id="rfid" role="tabpanel">
                <div class="table-responsive">
                    <table id="rfidTable" class="table table-striped table-hover">
                        <thead class="table-info">
                            <tr>
                                <th>#</th>
                                <th>Code</th>
                                <th>Name</th>
                                <th>Type</th>
                                <th>RFID</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rfidNotRegistered as $index => $emp): ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td><?php echo htmlspecialchars($emp['code']); ?></td>
                                <td><?php echo htmlspecialchars($emp['name']); ?></td>
                                <td><?php echo htmlspecialchars($emp['type']); ?></td>
                                <td><span class="text-muted">No RFID</span></td>
                                <td><span class="badge bg-info">Face Only</span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Not Registered Tab -->
            <div class="tab-pane fade" id="not" role="tabpanel">
                <div class="table-responsive">
                    <table id="notTable" class="table table-striped table-hover">
                        <thead class="table-danger">
                            <tr>
                                <th>#</th>
                                <th>Code</th>
                                <th>Name</th>
                                <th>Type</th>
                                <th>RFID</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($notRegistered as $index => $emp): ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td><?php echo htmlspecialchars($emp['code']); ?></td>
                                <td><?php echo htmlspecialchars($emp['name']); ?></td>
                                <td><?php echo htmlspecialchars($emp['type']); ?></td>
                                <td><span class="text-muted">No RFID</span></td>
                                <td><span class="badge bg-danger">Not Registered</span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <?php endif; ?>
        
        <div class="mt-4 text-muted text-center">
            <small>Last updated: <?php echo date('Y-m-d H:i:s'); ?></small>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            const tableConfig = {
                pageLength: 25,
                order: [[1, 'asc']],
                language: {
                    search: "Search:",
                    lengthMenu: "Show _MENU_ entries",
                    info: "Showing _START_ to _END_ of _TOTAL_ employees",
                    infoEmpty: "No employees found",
                    infoFiltered: "(filtered from _MAX_ total employees)"
                }
            };
            
            $('#fullTable').DataTable(tableConfig);
            $('#faceTable').DataTable(tableConfig);
            $('#rfidTable').DataTable(tableConfig);
            $('#notTable').DataTable(tableConfig);
        });
    </script>
</body>
</html>
