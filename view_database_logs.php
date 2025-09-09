<?php
require_once 'config.php';

// Check if this is an AJAX request for data
if (isset($_GET['ajax']) && $_GET['ajax'] === '1') {
    header('Content-Type: application/json');
    
    try {
        $pdo = getDatabaseConnection();
        
        $page = $_GET['page'] ?? 1;
        $limit = $_GET['limit'] ?? 50;
        $type = $_GET['type'] ?? '';
        $userId = $_GET['user_id'] ?? '';
        $dateFrom = $_GET['date_from'] ?? '';
        $dateTo = $_GET['date_to'] ?? '';
        
        $offset = ($page - 1) * $limit;
        
        // Build WHERE clause
        $whereConditions = [];
        $params = [];
        
        if ($type) {
            $whereConditions[] = "activity_type = :type";
            $params[':type'] = $type;
        }
        
        if ($userId) {
            $whereConditions[] = "user_id = :user_id";
            $params[':user_id'] = $userId;
        }
        
        if ($dateFrom) {
            $whereConditions[] = "DATE(created_at) >= :date_from";
            $params[':date_from'] = $dateFrom;
        }
        
        if ($dateTo) {
            $whereConditions[] = "DATE(created_at) <= :date_to";
            $params[':date_to'] = $dateTo;
        }
        
        $whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";
        
        // Get logs
        $stmt = $pdo->prepare("
            SELECT l.*, u.username, u.email
            FROM user_activity_logs l
            LEFT JOIN users u ON l.user_id = u.id
            $whereClause
            ORDER BY l.created_at DESC
            LIMIT :limit OFFSET :offset
        ");
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get total count
        $countStmt = $pdo->prepare("
            SELECT COUNT(*) as total 
            FROM user_activity_logs l
            LEFT JOIN users u ON l.user_id = u.id
            $whereClause
        ");
        foreach ($params as $key => $value) {
            $countStmt->bindValue($key, $value);
        }
        $countStmt->execute();
        $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Get activity type statistics
        $typeStats = $pdo->query("
            SELECT activity_type, COUNT(*) as count
            FROM user_activity_logs
            GROUP BY activity_type
            ORDER BY count DESC
        ")->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'data' => $logs,
            'pagination' => [
                'page' => (int)$page,
                'limit' => (int)$limit,
                'total' => (int)$total,
                'pages' => ceil($total / $limit)
            ],
            'stats' => [
                'total_logs' => $total,
                'type_distribution' => $typeStats
            ]
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to load logs: ' . $e->getMessage()]);
    }
    exit;
}

// Main page HTML
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Logs Viewer - Casino Admin</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background: #1a202c;
            color: #e2e8f0;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        h1 {
            color: #fbbf24;
            text-align: center;
            margin-bottom: 30px;
        }
        
        .filters {
            background: #2d3748;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .filter-group {
            display: flex;
            flex-direction: column;
        }
        
        .filter-group label {
            margin-bottom: 5px;
            color: #a0aec0;
            font-weight: bold;
        }
        
        .filter-group input, .filter-group select {
            padding: 8px 12px;
            border: 2px solid #4a5568;
            border-radius: 6px;
            background: #1a202c;
            color: white;
            font-size: 14px;
        }
        
        .filter-group input:focus, .filter-group select:focus {
            outline: none;
            border-color: #fbbf24;
        }
        
        .filter-actions {
            display: flex;
            gap: 10px;
            align-items: end;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #48bb78, #38a169);
            color: white;
        }
        
        .btn-secondary {
            background: linear-gradient(135deg, #4a5568, #2d3748);
            color: white;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .stat-card {
            background: #2d3748;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            border: 2px solid #4a5568;
        }
        
        .stat-value {
            font-size: 2em;
            font-weight: bold;
            color: #fbbf24;
            margin-bottom: 10px;
        }
        
        .stat-label {
            color: #a0aec0;
            font-size: 14px;
        }
        
        .logs-table {
            background: #2d3748;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
        }
        
        .table-header {
            background: #4a5568;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .table-title {
            font-size: 18px;
            font-weight: bold;
            color: #fbbf24;
        }
        
        .pagination {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .pagination button {
            padding: 5px 10px;
            border: 1px solid #4a5568;
            background: #2d3748;
            color: white;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .pagination button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .pagination button.active {
            background: #fbbf24;
            color: #1a202c;
            border-color: #fbbf24;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #4a5568;
        }
        
        th {
            background: #1a202c;
            color: #fbbf24;
            font-weight: bold;
            font-size: 14px;
        }
        
        td {
            color: #e2e8f0;
            font-size: 14px;
        }
        
        .activity-type {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .type-registration { background: #48bb78; color: white; }
        .type-login { background: #38b2ac; color: white; }
        .type-admin_action { background: #ed8936; color: white; }
        .type-game_activity { background: #9f7aea; color: white; }
        .type-transaction { background: #f56565; color: white; }
        
        .loading {
            text-align: center;
            padding: 40px;
            color: #a0aec0;
        }
        
        .error {
            background: #fed7d7;
            color: #c53030;
            padding: 15px;
            border-radius: 6px;
            margin: 20px 0;
            border-left: 4px solid #c53030;
        }
        
        @media (max-width: 768px) {
            .filters {
                grid-template-columns: 1fr;
            }
            
            .filter-actions {
                justify-content: center;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .table-header {
                flex-direction: column;
                gap: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🗄️ Database Logs Viewer</h1>
        
        <!-- Filters -->
        <div class="filters">
            <div class="filter-group">
                <label for="activityType">Activity Type</label>
                <select id="activityType">
                    <option value="">All Types</option>
                    <option value="registration">Registration</option>
                    <option value="login">Login</option>
                    <option value="admin_action">Admin Action</option>
                    <option value="game_activity">Game Activity</option>
                    <option value="transaction">Transaction</option>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="userId">User ID</label>
                <input type="number" id="userId" placeholder="Enter user ID">
            </div>
            
            <div class="filter-group">
                <label for="dateFrom">Date From</label>
                <input type="date" id="dateFrom">
            </div>
            
            <div class="filter-group">
                <label for="dateTo">Date To</label>
                <input type="date" id="dateTo">
            </div>
            
            <div class="filter-actions">
                <button class="btn btn-primary" onclick="loadLogs()">🔍 Search</button>
                <button class="btn btn-secondary" onclick="resetFilters()">🔄 Reset</button>
                <button class="btn btn-secondary" onclick="exportLogs()">📤 Export</button>
            </div>
        </div>
        
        <!-- Statistics -->
        <div class="stats-grid" id="statsGrid">
            <div class="stat-card">
                <div class="stat-value" id="totalLogs">-</div>
                <div class="stat-label">Total Logs</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" id="todayLogs">-</div>
                <div class="stat-label">Today's Logs</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" id="uniqueUsers">-</div>
                <div class="stat-label">Unique Users</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" id="adminActions">-</div>
                <div class="stat-label">Admin Actions</div>
            </div>
        </div>
        
        <!-- Logs Table -->
        <div class="logs-table">
            <div class="table-header">
                <div class="table-title">Activity Logs</div>
                <div class="pagination" id="pagination"></div>
            </div>
            
            <div id="logsContent">
                <div class="loading">Loading logs...</div>
            </div>
        </div>
    </div>
    
    <script>
        let currentPage = 1;
        let currentFilters = {};
        
        // Load logs on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadLogs();
        });
        
        async function loadLogs(page = 1) {
            try {
                currentPage = page;
                
                // Show loading
                document.getElementById('logsContent').innerHTML = '<div class="loading">Loading logs...</div>';
                
                // Build filters
                const filters = {
                    page: page,
                    limit: 50,
                    type: document.getElementById('activityType').value,
                    user_id: document.getElementById('userId').value,
                    date_from: document.getElementById('dateFrom').value,
                    date_to: document.getElementById('dateTo').value
                };
                
                // Remove empty filters
                Object.keys(filters).forEach(key => {
                    if (!filters[key]) delete filters[key];
                });
                
                currentFilters = filters;
                
                // Build query string
                const queryString = new URLSearchParams(filters).toString();
                const response = await fetch(`view_database_logs.php?ajax=1&${queryString}`);
                
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                
                const data = await response.json();
                
                if (data.success) {
                    displayLogs(data.data);
                    displayPagination(data.pagination);
                    displayStats(data.stats);
                } else {
                    throw new Error(data.error || 'Failed to load logs');
                }
                
            } catch (error) {
                console.error('Failed to load logs:', error);
                document.getElementById('logsContent').innerHTML = `
                    <div class="error">
                        ❌ Failed to load logs: ${error.message}
                    </div>
                `;
            }
        }
        
        function displayLogs(logs) {
            if (logs.length === 0) {
                document.getElementById('logsContent').innerHTML = `
                    <div class="loading">No logs found matching the current filters.</div>
                `;
                return;
            }
            
            let tableHTML = `
                <table>
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>User</th>
                            <th>Activity Type</th>
                            <th>Description</th>
                            <th>IP Address</th>
                        </tr>
                    </thead>
                    <tbody>
            `;
            
            logs.forEach(log => {
                const activityClass = `type-${log.activity_type.replace('_', '-')}`;
                const username = log.username || `User #${log.user_id}`;
                const time = new Date(log.created_at).toLocaleString();
                
                tableHTML += `
                    <tr>
                        <td>${time}</td>
                        <td>${username}</td>
                        <td><span class="activity-type ${activityClass}">${log.activity_type}</span></td>
                        <td>${log.description || 'No description'}</td>
                        <td>${log.ip_address || 'Unknown'}</td>
                    </tr>
                `;
            });
            
            tableHTML += '</tbody></table>';
            document.getElementById('logsContent').innerHTML = tableHTML;
        }
        
        function displayPagination(pagination) {
            const paginationDiv = document.getElementById('pagination');
            
            if (pagination.pages <= 1) {
                paginationDiv.innerHTML = '';
                return;
            }
            
            let paginationHTML = '';
            
            // Previous button
            paginationHTML += `
                <button onclick="loadLogs(${pagination.page - 1})" 
                        ${pagination.page <= 1 ? 'disabled' : ''}>
                    ← Previous
                </button>
            `;
            
            // Page numbers
            for (let i = 1; i <= pagination.pages; i++) {
                if (i === 1 || i === pagination.pages || (i >= pagination.page - 2 && i <= pagination.page + 2)) {
                    paginationHTML += `
                        <button onclick="loadLogs(${i})" 
                                class="${i === pagination.page ? 'active' : ''}">
                            ${i}
                        </button>
                    `;
                } else if (i === pagination.page - 3 || i === pagination.page + 3) {
                    paginationHTML += '<span>...</span>';
                }
            }
            
            // Next button
            paginationHTML += `
                <button onclick="loadLogs(${pagination.page + 1})" 
                        ${pagination.page >= pagination.pages ? 'disabled' : ''}>
                    Next →
                </button>
            `;
            
            paginationDiv.innerHTML = paginationHTML;
        }
        
        function displayStats(stats) {
            document.getElementById('totalLogs').textContent = stats.total_logs.toLocaleString();
            
            // Calculate today's logs
            const today = new Date().toISOString().split('T')[0];
            const todayLogs = stats.type_distribution.reduce((total, type) => total + type.count, 0);
            document.getElementById('todayLogs').textContent = todayLogs.toLocaleString();
            
            // Get unique users count (approximate)
            document.getElementById('uniqueUsers').textContent = '~' + Math.ceil(stats.total_logs / 10);
            
            // Get admin actions count
            const adminActions = stats.type_distribution.find(type => type.activity_type === 'admin_action');
            document.getElementById('adminActions').textContent = adminActions ? adminActions.count : 0;
        }
        
        function resetFilters() {
            document.getElementById('activityType').value = '';
            document.getElementById('userId').value = '';
            document.getElementById('dateFrom').value = '';
            document.getElementById('dateTo').value = '';
            loadLogs(1);
        }
        
        async function exportLogs() {
            try {
                const filters = { ...currentFilters };
                delete filters.page;
                delete filters.limit;
                
                const queryString = new URLSearchParams(filters).toString();
                const response = await fetch(`export_database.php`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        type: 'logs',
                        format: 'csv'
                    })
                });
                
                if (response.ok) {
                    const blob = await response.blob();
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = `casino_logs_${new Date().toISOString().split('T')[0]}.csv`;
                    document.body.appendChild(a);
                    a.click();
                    window.URL.revokeObjectURL(url);
                    document.body.removeChild(a);
                } else {
                    throw new Error('Export failed');
                }
                
            } catch (error) {
                console.error('Export failed:', error);
                alert('❌ Export failed: ' + error.message);
            }
        }
    </script>
</body>
</html>

