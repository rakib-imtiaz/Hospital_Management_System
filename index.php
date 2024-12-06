<?php
require_once 'includes/db_connect.php';
session_start();

// Verify login status
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

include_once 'includes/header.php';

// Fetch user-specific data
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$role = $_SESSION['role'];
$is_admin = ($role === 'Administrator');
$is_doctor = ($role === 'Doctor');
$is_nurse = ($role === 'Nurse');
$is_receptionist = ($role === 'Receptionist');
$is_patient = ($role === 'Patient');

// System Overview variables
try {
    // Total Users
    $stmt = $pdo->query("SELECT COUNT(*) FROM `user`");
    $total_users = $stmt->fetchColumn() ?: 0;

    // Active Users (last 5 minutes)
    $stmt = $pdo->query("SELECT COUNT(*) FROM `user` WHERE last_login >= NOW() - INTERVAL 5 MINUTE");
    $active_users = $stmt->fetchColumn() ?: 0;
} catch (PDOException $e) {
    $total_users = $active_users = 0;
    error_log("User Activity Query Error: " . $e->getMessage());
}

// Hospital Statistics
try {
    // Today's Appointments
    $stmt = $pdo->query("SELECT COUNT(*) FROM `appointment` WHERE DATE(appointment_date) = CURRENT_DATE");
    $todays_appointments = $stmt->fetchColumn() ?: 0;

    // Available Beds
    $stmt = $pdo->query("SELECT COUNT(*) FROM `resource` WHERE type='BED' AND status='AVAILABLE'");
    $available_beds = $stmt->fetchColumn() ?: 0;

    // Total Patients
    $stmt = $pdo->query("SELECT COUNT(*) FROM `patient`");
    $total_patients = $stmt->fetchColumn() ?: 0;
} catch (PDOException $e) {
    $todays_appointments = $available_beds = $total_patients = 0;
    error_log("Hospital Statistics Query Error: " . $e->getMessage());
}

// Recent Activity Log
try {
    $stmt = $pdo->query("SELECT action FROM `audit_log` ORDER BY timestamp DESC LIMIT 5");
    $recent_activities = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
} catch (PDOException $e) {
    $recent_activities = [];
    error_log("Recent Activity Log Query Error: " . $e->getMessage());
}

// Fetch doctor-specific information if user is a doctor
if ($is_doctor) {
    try {
        // Get doctor's information
        $stmt = $pdo->prepare("
            SELECT d.*, dep.name as department_name 
            FROM doctor d 
            LEFT JOIN department dep ON d.department_id = dep.department_id 
            WHERE d.user_id = ?
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $doctor_info = $stmt->fetch();

        // Get doctor's today's appointments
        $stmt = $pdo->prepare("
            SELECT a.*, p.name as patient_name 
            FROM appointment a 
            JOIN patient p ON a.patient_id = p.patient_id 
            WHERE a.doctor_id = ? AND DATE(a.appointment_date) = CURDATE()
            ORDER BY a.appointment_date
        ");
        $stmt->execute([$doctor_info['doctor_id']]);
        $doctor_appointments = $stmt->fetchAll();

        // Get doctor's pending appointments count
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as pending_count 
            FROM appointment 
            WHERE doctor_id = ? AND status = 'Pending'
        ");
        $stmt->execute([$doctor_info['doctor_id']]);
        $pending_appointments = $stmt->fetch();

        // Get doctor's total patients
        $stmt = $pdo->prepare("
            SELECT COUNT(DISTINCT patient_id) as patient_count 
            FROM appointment 
            WHERE doctor_id = ?
        ");
        $stmt->execute([$doctor_info['doctor_id']]);
        $doctor_total_patients = $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Doctor Dashboard Query Error: " . $e->getMessage());
    }
}

// Fetch nurse-specific information
if ($is_nurse) {
    try {
        // Get nurse's information
        $stmt = $pdo->prepare("
            SELECT n.*, d.name as department_name 
            FROM nurse n 
            LEFT JOIN department d ON n.department_id = d.department_id 
            WHERE n.user_id = ?
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $nurse_info = $stmt->fetch();

        // Get today's appointments in their department
        $stmt = $pdo->prepare("
            SELECT a.*, p.name as patient_name, d.name as doctor_name
            FROM appointment a 
            JOIN patient p ON a.patient_id = p.patient_id 
            JOIN doctor d ON a.doctor_id = d.doctor_id
            WHERE d.department_id = ? AND DATE(a.appointment_date) = CURDATE()
            ORDER BY a.appointment_date
        ");
        $stmt->execute([$nurse_info['department_id']]);
        $department_appointments = $stmt->fetchAll();

        // Get patients under care in their department
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as patient_count 
            FROM patient p
            JOIN appointment a ON p.patient_id = a.patient_id
            JOIN doctor d ON a.doctor_id = d.doctor_id
            WHERE d.department_id = ? AND a.status = 'Active'
        ");
        $stmt->execute([$nurse_info['department_id']]);
        $department_patients = $stmt->fetch();

    } catch (PDOException $e) {
        error_log("Nurse Dashboard Query Error: " . $e->getMessage());
    }
}

// Fetch patient-specific information
if ($is_patient) {
    try {
        // Get patient's information
        $stmt = $pdo->prepare("
            SELECT * FROM patient WHERE user_id = ?
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $patient_info = $stmt->fetch();

        // Get upcoming appointments
        $stmt = $pdo->prepare("
            SELECT a.*, d.name as doctor_name, d.specialization,
                   dep.name as department_name
            FROM appointment a 
            JOIN doctor d ON a.doctor_id = d.doctor_id
            JOIN department dep ON d.department_id = dep.department_id
            WHERE a.patient_id = ? AND a.appointment_date >= CURDATE()
            ORDER BY a.appointment_date
            LIMIT 5
        ");
        $stmt->execute([$patient_info['patient_id']]);
        $upcoming_appointments = $stmt->fetchAll();

        // Get recent prescriptions
        $stmt = $pdo->prepare("
            SELECT p.*, d.name as doctor_name
            FROM prescription p
            JOIN doctor d ON p.doctor_id = d.doctor_id
            WHERE p.patient_id = ?
            ORDER BY p.prescription_date DESC
            LIMIT 3
        ");
        $stmt->execute([$patient_info['patient_id']]);
        $recent_prescriptions = $stmt->fetchAll();

        // Get unpaid bills
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as unpaid_count, SUM(amount) as total_unpaid
            FROM bill
            WHERE patient_id = ? AND status = 'Unpaid'
        ");
        $stmt->execute([$patient_info['patient_id']]);
        $bills_info = $stmt->fetch();

    } catch (PDOException $e) {
        error_log("Patient Dashboard Query Error: " . $e->getMessage());
    }
}
?>

<div class="container mx-auto px-6 py-8">
    <?php if ($is_patient): ?>
        <!-- Patient Dashboard -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Welcome, <?php echo htmlspecialchars($patient_info['name']); ?></h1>
            <p class="text-gray-600">Patient ID: <?php echo htmlspecialchars($patient_info['patient_id']); ?></p>
        </div>

        <!-- Quick Actions -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <a href="<?php echo $base_url; ?>appointments/book.php" 
               class="bg-teal-500 hover:bg-teal-600 text-white rounded-lg p-6 text-center transition duration-300">
                <i class="fas fa-calendar-plus text-3xl mb-2"></i>
                <p class="text-lg font-semibold">Book Appointment</p>
            </a>
            <a href="<?php echo $base_url; ?>prescriptions/my-prescriptions.php" 
               class="bg-blue-500 hover:bg-blue-600 text-white rounded-lg p-6 text-center transition duration-300">
                <i class="fas fa-prescription text-3xl mb-2"></i>
                <p class="text-lg font-semibold">View Prescriptions</p>
            </a>
            <a href="<?php echo $base_url; ?>bills/my-bills.php" 
               class="bg-green-500 hover:bg-green-600 text-white rounded-lg p-6 text-center transition duration-300">
                <i class="fas fa-file-invoice-dollar text-3xl mb-2"></i>
                <p class="text-lg font-semibold">View Bills</p>
            </a>
            <a href="<?php echo $base_url; ?>profile.php" 
               class="bg-purple-500 hover:bg-purple-600 text-white rounded-lg p-6 text-center transition duration-300">
                <i class="fas fa-user-circle text-3xl mb-2"></i>
                <p class="text-lg font-semibold">My Profile</p>
            </a>
        </div>

        <!-- Upcoming Appointments -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Upcoming Appointments</h2>
            <?php if (count($upcoming_appointments) > 0): ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date & Time</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Doctor</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Department</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($upcoming_appointments as $appointment): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo date('M d, Y H:i', strtotime($appointment['appointment_date'])); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        Dr. <?php echo htmlspecialchars($appointment['doctor_name']); ?>
                                        <div class="text-xs text-gray-500"><?php echo htmlspecialchars($appointment['specialization']); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo htmlspecialchars($appointment['department_name']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                            <?php echo $appointment['status'] === 'Confirmed' ? 'bg-green-100 text-green-800' : 
                                                    ($appointment['status'] === 'Pending' ? 'bg-yellow-100 text-yellow-800' : 
                                                    'bg-red-100 text-red-800'); ?>">
                                            <?php echo htmlspecialchars($appointment['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-gray-500 text-center py-4">No upcoming appointments.</p>
            <?php endif; ?>
        </div>

        <!-- Recent Prescriptions and Bills Summary -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Recent Prescriptions -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Recent Prescriptions</h2>
                <?php if (count($recent_prescriptions) > 0): ?>
                    <div class="space-y-4">
                        <?php foreach ($recent_prescriptions as $prescription): ?>
                            <div class="border-b pb-4">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <p class="font-medium">Dr. <?php echo htmlspecialchars($prescription['doctor_name']); ?></p>
                                        <p class="text-sm text-gray-500">
                                            <?php echo date('M d, Y', strtotime($prescription['prescription_date'])); ?>
                                        </p>
                                    </div>
                                    <a href="prescriptions/view.php?id=<?php echo $prescription['prescription_id']; ?>" 
                                       class="text-teal-600 hover:text-teal-800 text-sm">View Details</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-gray-500 text-center py-4">No recent prescriptions.</p>
                <?php endif; ?>
            </div>

            <!-- Bills Summary -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Bills Summary</h2>
                <div class="space-y-4">
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Unpaid Bills:</span>
                        <span class="font-semibold"><?php echo $bills_info['unpaid_count']; ?></span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Total Outstanding:</span>
                        <span class="font-semibold text-red-600">$<?php echo number_format($bills_info['total_unpaid'], 2); ?></span>
                    </div>
                    <div class="mt-4">
                        <a href="<?php echo $base_url; ?>bills/my-bills.php" 
                           class="block text-center bg-teal-500 hover:bg-teal-600 text-white rounded-md px-4 py-2 transition duration-300">
                            View All Bills
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <!-- Original Dashboard Content -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <!-- Dashboard Cards -->
            <div class="bg-gradient-to-br from-teal-500 to-teal-600 rounded-xl shadow-lg p-6 text-white">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-xl font-bold">Today's Appointments</h3>
                    <i class="fas fa-calendar-check text-3xl"></i>
                </div>
                <p class="text-3xl font-semibold"><?php echo $todays_appointments; ?></p>
            </div>

            <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl shadow-lg p-6 text-white">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-xl font-bold">Available Beds</h3>
                    <i class="fas fa-bed text-3xl"></i>
                </div>
                <p class="text-3xl font-semibold"><?php echo $available_beds; ?></p>
            </div>

            <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-lg p-6 text-white">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-xl font-bold">Total Patients</h3>
                    <i class="fas fa-user-injured text-3xl"></i>
                </div>
                <p class="text-3xl font-semibold"><?php echo $total_patients; ?></p>
            </div>
        </div>

        <?php if ($is_admin): ?>
            <!-- Admin-only Sections -->
            <div class="bg-white rounded-xl shadow-lg p-8 mt-8">
                <h3 class="text-2xl font-bold text-gray-800 mb-6">System Overview</h3>

                <div class="flex justify-between items-center mb-6">
                    <div class="flex flex-col items-start">
                        <h4 class="text-xl font-semibold text-gray-600">User Activity</h4>
                        <p class="text-sm text-gray-500">Total Users: <strong><?php echo $total_users; ?></strong></p>
                        <p class="text-sm text-gray-500">Active Users: <strong><?php echo $active_users; ?></strong></p>
                    </div>
                </div>

                <div class="flex flex-col">
                    <h4 class="text-xl font-semibold text-gray-600">Recent Activities</h4>
                    <ul class="list-disc pl-5 text-sm text-gray-500">
                        <?php foreach ($recent_activities as $activity): ?>
                            <li><?php echo htmlspecialchars($activity); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php include_once 'includes/footer.php'; ?>