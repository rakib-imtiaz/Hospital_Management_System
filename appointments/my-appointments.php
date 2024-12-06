<?php
ob_start();
require_once '../includes/db_connect.php';
session_start();

// Verify patient access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Patient') {
    header("Location: ../login.php");
    exit;
}

include_once '../includes/header.php';

// Get patient ID
try {
    $stmt = $pdo->prepare("SELECT patient_id FROM patient WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $patient = $stmt->fetch();
    $patient_id = $patient['patient_id'];
} catch (PDOException $e) {
    error_log("Error fetching patient ID: " . $e->getMessage());
    $error = "System error occurred";
}

// Fetch appointments
try {
    $stmt = $pdo->prepare("
        SELECT 
            a.*,
            d.name as doctor_name,
            d.specialization,
            dep.name as department_name
        FROM appointment a
        JOIN doctor d ON a.doctor_id = d.doctor_id
        JOIN department dep ON d.department_id = dep.department_id
        WHERE a.patient_id = ?
        ORDER BY a.appointment_date DESC
    ");
    $stmt->execute([$patient_id]);
    $appointments = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Error fetching appointments: " . $e->getMessage());
    $error = "Error fetching appointments";
}

// Handle appointment cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_appointment'])) {
    try {
        $appointment_id = $_POST['appointment_id'];
        
        // Verify appointment belongs to patient and is cancellable
        $stmt = $pdo->prepare("
            SELECT status 
            FROM appointment 
            WHERE appointment_id = ? AND patient_id = ?
        ");
        $stmt->execute([$appointment_id, $patient_id]);
        $appointment = $stmt->fetch();

        if ($appointment && in_array($appointment['status'], ['Pending', 'Confirmed'])) {
            $stmt = $pdo->prepare("
                UPDATE appointment 
                SET status = 'Cancelled', updated_at = NOW()
                WHERE appointment_id = ?
            ");
            $stmt->execute([$appointment_id]);

            $_SESSION['success'] = "Appointment cancelled successfully.";
            header("Location: my-appointments.php");
            exit;
        } else {
            $error = "Invalid appointment or cannot be cancelled";
        }
    } catch (PDOException $e) {
        error_log("Error cancelling appointment: " . $e->getMessage());
        $error = "Error cancelling appointment";
    }
}
?>

<div class="container mx-auto px-6 py-8">
    <div class="max-w-4xl mx-auto">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-gray-800">My Appointments</h1>
            <a href="book.php" class="bg-teal-500 text-white px-4 py-2 rounded-md hover:bg-teal-600">
                <i class="fas fa-plus mr-2"></i>Book New Appointment
            </a>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                <?php 
                echo htmlspecialchars($_SESSION['success']);
                unset($_SESSION['success']);
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <!-- Appointments List -->
        <?php if (count($appointments) > 0): ?>
            <div class="bg-white shadow-md rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date & Time</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Doctor</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Department</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($appointments as $appointment): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php echo date('M d, Y H:i', strtotime($appointment['appointment_date'])); ?>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900">
                                        Dr. <?php echo htmlspecialchars($appointment['doctor_name']); ?>
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        <?php echo htmlspecialchars($appointment['specialization']); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php echo htmlspecialchars($appointment['department_name']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                        <?php 
                                        echo match($appointment['status']) {
                                            'Confirmed' => 'bg-green-100 text-green-800',
                                            'Pending' => 'bg-yellow-100 text-yellow-800',
                                            'Completed' => 'bg-blue-100 text-blue-800',
                                            'Cancelled' => 'bg-red-100 text-red-800',
                                            default => 'bg-gray-100 text-gray-800'
                                        };
                                        ?>">
                                        <?php echo htmlspecialchars($appointment['status']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <?php if (in_array($appointment['status'], ['Pending', 'Confirmed'])): ?>
                                        <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to cancel this appointment?');">
                                            <input type="hidden" name="appointment_id" value="<?php echo $appointment['appointment_id']; ?>">
                                            <button type="submit" name="cancel_appointment" 
                                                    class="text-red-600 hover:text-red-900">
                                                Cancel
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    
                                    <?php if ($appointment['status'] === 'Completed'): ?>
                                        <a href="../prescriptions/view.php?appointment_id=<?php echo $appointment['appointment_id']; ?>" 
                                           class="text-teal-600 hover:text-teal-900 ml-3">
                                            View Prescription
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="bg-white shadow-md rounded-lg p-6 text-center">
                <p class="text-gray-500">No appointments found.</p>
                <a href="book.php" class="inline-block mt-4 text-teal-600 hover:text-teal-800">
                    Book your first appointment
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php 
include_once '../includes/footer.php';
ob_end_flush();
?> 