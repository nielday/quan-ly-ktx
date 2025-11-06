<?php
/**
 * Xử lý các action của Students
 */

session_start();
require_once __DIR__ . '/../functions/auth.php';
require_once __DIR__ . '/../functions/helpers.php';
require_once __DIR__ . '/../functions/students.php';

$action = getAction();
$currentUser = getCurrentUser();

// Kiểm tra đăng nhập
if (!isLoggedIn()) {
    redirect('../index.php');
}

switch ($action) {
    case 'update':
        handleUpdateStudent();
        break;
    
    default:
        // Nếu là manager, redirect về manager students
        if ($currentUser['role'] == 'manager') {
            setErrorMessage('Action không hợp lệ!');
            redirect('../views/manager/students.php');
        } else {
            setErrorMessage('Action không hợp lệ!');
            redirect('../views/student/profile.php');
        }
        break;
}

/**
 * Xử lý cập nhật thông tin sinh viên
 * Student có thể tự cập nhật thông tin của mình
 */
function handleUpdateStudent() {
    if (!isMethod('POST')) {
        setErrorMessage('Phương thức không hợp lệ!');
        redirect('../views/student/profile.php');
    }
    
    $currentUser = getCurrentUser();
    
    // Lấy student_id từ POST hoặc từ current user
    $studentId = intval($_POST['id'] ?? 0);
    
    // Nếu là student, chỉ được sửa thông tin của chính mình
    if ($currentUser['role'] == 'student') {
        $student = getStudentByUserId($currentUser['id']);
        if (!$student) {
            setErrorMessage('Không tìm thấy thông tin sinh viên!');
            redirect('../views/student/profile.php');
        }
        $studentId = $student['id'];
    }
    
    if ($studentId <= 0) {
        setErrorMessage('ID không hợp lệ!');
        if ($currentUser['role'] == 'manager') {
            redirect('../views/manager/students.php');
        } else {
            redirect('../views/student/profile.php');
        }
    }
    
    // Kiểm tra quyền: Student chỉ được sửa thông tin của chính mình
    if ($currentUser['role'] == 'student') {
        $student = getStudentById($studentId);
        if (!$student || $student['user_id'] != $currentUser['id']) {
            setErrorMessage('Bạn không có quyền sửa thông tin này!');
            redirect('../views/student/profile.php');
        }
    }
    
    $data = [
        'student_code' => $_POST['student_code'] ?? '',
        'full_name' => $_POST['full_name'] ?? '',
        'date_of_birth' => $_POST['date_of_birth'] ?? null,
        'gender' => $_POST['gender'] ?? null,
        'phone' => $_POST['phone'] ?? '',
        'email' => $_POST['email'] ?? '',
        'address' => $_POST['address'] ?? '',
        'university' => $_POST['university'] ?? '',
        'major' => $_POST['major'] ?? '',
        'year' => $_POST['year'] ?? '',
        'id_card' => $_POST['id_card'] ?? '',
        'status' => $_POST['status'] ?? 'active'
    ];
    
    // Student không được thay đổi status
    if ($currentUser['role'] == 'student') {
        $existingStudent = getStudentById($studentId);
        $data['status'] = $existingStudent['status'];
    }
    
    $result = updateStudent($studentId, $data);
    
    if ($result['success']) {
        setSuccessMessage($result['message']);
    } else {
        setErrorMessage($result['message']);
    }
    
    if ($currentUser['role'] == 'manager') {
        redirect('../views/manager/students/view_student.php?id=' . $studentId);
    } else {
        redirect('../views/student/profile.php');
    }
}

?>

