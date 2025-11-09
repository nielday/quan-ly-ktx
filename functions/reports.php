<?php
/**
 * Reports functions - Các hàm thống kê và báo cáo
 */

require_once __DIR__ . '/db_connection.php';

/**
 * Lấy thống kê phòng
 * @return array
 */
function getRoomStatistics() {
    $conn = getDbConnection();
    
    $sql = "SELECT 
                COUNT(*) as total_rooms,
                SUM(CASE WHEN status = 'available' AND current_occupancy = 0 THEN 1 ELSE 0 END) as available_rooms,
                SUM(CASE WHEN current_occupancy > 0 AND status != 'maintenance' THEN 1 ELSE 0 END) as occupied_rooms,
                SUM(CASE WHEN status = 'maintenance' THEN 1 ELSE 0 END) as maintenance_rooms,
                SUM(capacity) as total_capacity,
                SUM(current_occupancy) as total_occupancy
            FROM rooms";
    
    $result = mysqli_query($conn, $sql);
    
    $stats = [
        'total_rooms' => 0,
        'available_rooms' => 0,
        'occupied_rooms' => 0,
        'maintenance_rooms' => 0,
        'total_capacity' => 0,
        'total_occupancy' => 0,
        'occupancy_rate' => 0
    ];
    
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $stats = [
            'total_rooms' => intval($row['total_rooms']),
            'available_rooms' => intval($row['available_rooms']),
            'occupied_rooms' => intval($row['occupied_rooms']),
            'maintenance_rooms' => intval($row['maintenance_rooms']),
            'total_capacity' => intval($row['total_capacity']),
            'total_occupancy' => intval($row['total_occupancy'])
        ];
        
        if ($stats['total_capacity'] > 0) {
            $stats['occupancy_rate'] = round(($stats['total_occupancy'] / $stats['total_capacity']) * 100, 2);
        }
    }
    
    mysqli_close($conn);
    return $stats;
}

/**
 * Lấy thống kê sinh viên
 * @return array
 */
function getStudentStatistics() {
    $conn = getDbConnection();
    
    $sql = "SELECT 
                COUNT(*) as total_students,
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_students,
                SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive_students,
                SUM(CASE WHEN status = 'graduated' THEN 1 ELSE 0 END) as graduated_students
            FROM students";
    
    $result = mysqli_query($conn, $sql);
    
    $stats = [
        'total_students' => 0,
        'active_students' => 0,
        'inactive_students' => 0,
        'graduated_students' => 0
    ];
    
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $stats = [
            'total_students' => intval($row['total_students']),
            'active_students' => intval($row['active_students']),
            'inactive_students' => intval($row['inactive_students']),
            'graduated_students' => intval($row['graduated_students'])
        ];
    }
    
    // Lấy số sinh viên mới trong tháng hiện tại
    $currentMonth = date('Y-m');
    $sqlNew = "SELECT COUNT(*) as new_students 
               FROM students 
               WHERE DATE_FORMAT(created_at, '%Y-%m') = ?";
    $stmt = mysqli_prepare($conn, $sqlNew);
    $newStudents = 0;
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "s", $currentMonth);
        mysqli_stmt_execute($stmt);
        $resultNew = mysqli_stmt_get_result($stmt);
        if ($resultNew && mysqli_num_rows($resultNew) > 0) {
            $rowNew = mysqli_fetch_assoc($resultNew);
            $newStudents = intval($rowNew['new_students']);
        }
        mysqli_stmt_close($stmt);
    }
    $stats['new_students_this_month'] = $newStudents;
    
    mysqli_close($conn);
    return $stats;
}

/**
 * Lấy thống kê tài chính
 * @param string|null $month Tháng (YYYY-MM), null = tất cả
 * @return array
 */
function getFinancialStatistics($month = null) {
    $conn = getDbConnection();
    
    // Thống kê hóa đơn
    $sqlInvoices = "SELECT 
                        COUNT(*) as total_invoices,
                        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_invoices,
                        SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) as paid_invoices,
                        SUM(CASE WHEN status = 'overdue' THEN 1 ELSE 0 END) as overdue_invoices,
                        SUM(total_amount) as total_amount,
                        SUM(CASE WHEN status = 'paid' THEN total_amount ELSE 0 END) as paid_amount,
                        SUM(CASE WHEN status IN ('pending', 'overdue') THEN total_amount ELSE 0 END) as unpaid_amount
                    FROM invoices";
    
    if ($month) {
        $sqlInvoices .= " WHERE invoice_month = ?";
    }
    
    $stmt = mysqli_prepare($conn, $sqlInvoices);
    $invoiceStats = [
        'total_invoices' => 0,
        'pending_invoices' => 0,
        'paid_invoices' => 0,
        'overdue_invoices' => 0,
        'total_amount' => 0,
        'paid_amount' => 0,
        'unpaid_amount' => 0
    ];
    
    if ($stmt) {
        if ($month) {
            mysqli_stmt_bind_param($stmt, "s", $month);
        }
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if ($result && mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);
            $invoiceStats = [
                'total_invoices' => intval($row['total_invoices']),
                'pending_invoices' => intval($row['pending_invoices']),
                'paid_invoices' => intval($row['paid_invoices']),
                'overdue_invoices' => intval($row['overdue_invoices']),
                'total_amount' => floatval($row['total_amount'] ?? 0),
                'paid_amount' => floatval($row['paid_amount'] ?? 0),
                'unpaid_amount' => floatval($row['unpaid_amount'] ?? 0)
            ];
        }
        mysqli_stmt_close($stmt);
    }
    
    // Thống kê thanh toán
    $sqlPayments = "SELECT 
                        COUNT(*) as total_payments,
                        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_payments,
                        SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed_payments,
                        SUM(CASE WHEN status = 'confirmed' THEN amount ELSE 0 END) as confirmed_amount
                    FROM payments";
    
    if ($month) {
        $sqlPayments .= " WHERE DATE_FORMAT(payment_date, '%Y-%m') = ?";
    }
    
    $stmt = mysqli_prepare($conn, $sqlPayments);
    $paymentStats = [
        'total_payments' => 0,
        'pending_payments' => 0,
        'confirmed_payments' => 0,
        'confirmed_amount' => 0
    ];
    
    if ($stmt) {
        if ($month) {
            mysqli_stmt_bind_param($stmt, "s", $month);
        }
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if ($result && mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);
            $paymentStats = [
                'total_payments' => intval($row['total_payments']),
                'pending_payments' => intval($row['pending_payments']),
                'confirmed_payments' => intval($row['confirmed_payments']),
                'confirmed_amount' => floatval($row['confirmed_amount'] ?? 0)
            ];
        }
        mysqli_stmt_close($stmt);
    }
    
    mysqli_close($conn);
    
    return [
        'invoices' => $invoiceStats,
        'payments' => $paymentStats,
        'revenue' => $paymentStats['confirmed_amount'] // Doanh thu = tổng thanh toán đã xác nhận
    ];
}

/**
 * Lấy doanh thu theo tháng
 * @param int $months Số tháng gần đây
 * @return array
 */
function getRevenueByMonth($months = 6) {
    $conn = getDbConnection();
    
    $sql = "SELECT 
                DATE_FORMAT(payment_date, '%Y-%m') as month,
                SUM(amount) as revenue,
                COUNT(*) as payment_count
            FROM payments
            WHERE status = 'confirmed'
            AND payment_date >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
            GROUP BY DATE_FORMAT(payment_date, '%Y-%m')
            ORDER BY month DESC
            LIMIT ?";
    
    $stmt = mysqli_prepare($conn, $sql);
    $revenue = [];
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "ii", $months, $months);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($result && mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                $revenue[] = [
                    'month' => $row['month'],
                    'revenue' => floatval($row['revenue']),
                    'payment_count' => intval($row['payment_count'])
                ];
            }
        }
        mysqli_stmt_close($stmt);
    }
    
    mysqli_close($conn);
    return $revenue;
}

/**
 * Lấy thống kê theo tòa nhà
 * @return array
 */
function getBuildingStatistics() {
    $conn = getDbConnection();
    
    $sql = "SELECT 
                b.id,
                b.building_code,
                b.building_name,
                COUNT(r.id) as total_rooms,
                SUM(CASE WHEN r.status = 'available' AND r.current_occupancy = 0 THEN 1 ELSE 0 END) as available_rooms,
                SUM(CASE WHEN r.current_occupancy > 0 AND r.status != 'maintenance' THEN 1 ELSE 0 END) as occupied_rooms,
                SUM(r.capacity) as total_capacity,
                SUM(r.current_occupancy) as total_occupancy
            FROM buildings b
            LEFT JOIN rooms r ON b.id = r.building_id
            GROUP BY b.id, b.building_code, b.building_name
            ORDER BY b.building_code ASC";
    
    $result = mysqli_query($conn, $sql);
    $buildings = [];
    
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $occupancyRate = 0;
            if ($row['total_capacity'] > 0) {
                $occupancyRate = round(($row['total_occupancy'] / $row['total_capacity']) * 100, 2);
            }
            
            $buildings[] = [
                'id' => intval($row['id']),
                'building_code' => $row['building_code'],
                'building_name' => $row['building_name'],
                'total_rooms' => intval($row['total_rooms']),
                'available_rooms' => intval($row['available_rooms']),
                'occupied_rooms' => intval($row['occupied_rooms']),
                'total_capacity' => intval($row['total_capacity']),
                'total_occupancy' => intval($row['total_occupancy']),
                'occupancy_rate' => $occupancyRate
            ];
        }
    }
    
    mysqli_close($conn);
    return $buildings;
}

