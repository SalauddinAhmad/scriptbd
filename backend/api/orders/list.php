<?php
/**
 * ScriptBD - Orders API: List Orders
 *
 * GET /api/orders/list.php
 * Requires: X-API-Key header
 * Optional query params: ?status=pending&page=1&limit=20
 * Returns JSON array of orders
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helpers.php';

setCorsHeaders();

// Only accept GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(['success' => false, 'message' => 'Method not allowed. Use GET.'], 405);
}

// Authentication required
authenticateApiKey();

try {
    $pdo = getDBConnection();

    // Pagination
    $page  = max(1, (int) ($_GET['page'] ?? 1));
    $limit = max(1, min(100, (int) ($_GET['limit'] ?? 20)));
    $offset = ($page - 1) * $limit;

    // Status filter
    $statusFilter = trim($_GET['status'] ?? '');
    $validStatuses = ['pending', 'processing', 'completed', 'cancelled'];

    // Search filter
    $search = trim($_GET['search'] ?? '');

    // Build query
    $where = [];
    $params = [];

    if ($statusFilter !== '' && in_array($statusFilter, $validStatuses, true)) {
        $where[] = 'status = :status';
        $params[':status'] = $statusFilter;
    }

    if ($search !== '') {
        $where[] = '(name LIKE :search_name OR email LIKE :search_email OR phone LIKE :search_phone OR topic LIKE :search_topic)';
        $searchParam = '%' . $search . '%';
        $params[':search_name']  = $searchParam;
        $params[':search_email'] = $searchParam;
        $params[':search_phone'] = $searchParam;
        $params[':search_topic'] = $searchParam;
    }

    $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

    // Count total
    $countStmt = $pdo->prepare("SELECT COUNT(*) as total FROM orders {$whereClause}");
    $countStmt->execute($params);
    $total = (int) $countStmt->fetch()['total'];

    // Fetch orders
    $stmt = $pdo->prepare(
        "SELECT id, name, email, phone, plan, topic, message, status, created_at, updated_at 
         FROM orders {$whereClause} 
         ORDER BY created_at DESC 
         LIMIT :limit OFFSET :offset"
    );

    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    $orders = $stmt->fetchAll();

    jsonResponse([
        'success' => true,
        'data'    => $orders,
        'meta'    => [
            'total'      => $total,
            'page'       => $page,
            'limit'      => $limit,
            'total_pages' => ceil($total / $limit),
        ],
    ]);

} catch (PDOException $e) {
    error_log('Order List Error: ' . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Database error.'], 500);
} catch (Exception $e) {
    error_log('Order List Error: ' . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Server error.'], 500);
}
