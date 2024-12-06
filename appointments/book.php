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
$patient_id = null;
try {
    $stmt = $pdo->prepare("SELECT patient_id FROM patient WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $result = $stmt->fetch();
    if ($result) {
        $patient_id = $result['patient_id'];
    } else {
        throw new Exception("Patient record not found");
    }
} catch (PDOException $e) {
    $error = "System error occurred. Please try again later.";
    error_log("Error in patient lookup: " . $e->getMessage());
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (
        isset($_POST['doctor_id'], $_POST['appointment_date'], $_POST['reason']) &&
        !empty($_POST['doctor_id']) &&
        !empty($_POST['appointment_date']) &&
        !empty($_POST['reason'])
    ) {

        try {
            // Simple insert query
            $stmt = $pdo->prepare("
                INSERT INTO appointment 
                (patient_id, doctor_id, appointment_date, reason, status) 
                VALUES 
                (?, ?, ?, ?, 'pending')
            ");

            if ($stmt->execute([
                $patient_id,
                $_POST['doctor_id'],
                $_POST['appointment_date'],
                $_POST['reason']
            ])) {
                $_SESSION['success'] = "Appointment booked successfully!";
                header("Location: my-appointments.php");
                exit;
            } else {
                $error = "Failed to book appointment. Please try again.";
            }
        } catch (PDOException $e) {
            $error = "Error booking appointment. Please try again.";
            error_log("Booking error: " . $e->getMessage());
        }
    } else {
        $error = "Please fill in all required fields.";
    }
}

// Fetch departments
try {
    $stmt = $pdo->query("SELECT department_id, name FROM department ORDER BY name");
    $departments = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = "Error loading departments.";
    error_log("Department fetch error: " . $e->getMessage());
    $departments = [];
}
?>

<div class="container mx-auto px-4 py-8">
    <div class="max-w-2xl mx-auto">
        <div class="bg-white rounded-lg shadow-md p-6">
            <h1 class="text-2xl font-bold text-gray-800 mb-6">Book an Appointment</h1>

            <?php if (isset($error)): ?>
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" id="appointmentForm">
                <!-- Department Selection -->
                <div class="mb-6">
                    <label for="department" class="block text-gray-700 font-semibold mb-2">
                        Select Department *
                    </label>
                    <select name="department_id" id="department" required
                        class="w-full p-2 border rounded-md focus:ring-2 focus:ring-teal-500">
                        <option value="">Choose Department</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?php echo htmlspecialchars($dept['department_id']); ?>">
                                <?php echo htmlspecialchars($dept['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Doctor Selection -->
                <div class="mb-6">
                    <label for="doctor" class="block text-gray-700 font-semibold mb-2">
                        Select Doctor *
                    </label>
                    <select name="doctor_id" id="doctor" required
                        class="w-full p-2 border rounded-md focus:ring-2 focus:ring-teal-500" disabled>
                        <option value="">First select a department</option>
                    </select>
                </div>

                <!-- Appointment Date -->
                <div class="mb-6">
                    <label for="appointment_date" class="block text-gray-700 font-semibold mb-2">
                        Appointment Date and Time *
                    </label>
                    <input type="datetime-local" id="appointment_date" name="appointment_date"
                        min="<?php echo date('Y-m-d\TH:i'); ?>" required
                        class="w-full p-2 border rounded-md focus:ring-2 focus:ring-teal-500">
                </div>

                <!-- Reason -->
                <div class="mb-6">
                    <label for="reason" class="block text-gray-700 font-semibold mb-2">
                        Reason for Visit *
                    </label>
                    <textarea id="reason" name="reason" rows="4" required
                        class="w-full p-2 border rounded-md focus:ring-2 focus:ring-teal-500"
                        placeholder="Please describe your symptoms or reason for visit"></textarea>
                </div>

                <!-- Submit Button -->
                <div class="flex justify-end">
                    <button type="submit"
                        class="bg-teal-500 text-white px-6 py-2 rounded-md hover:bg-teal-600 
                                   focus:outline-none focus:ring-2 focus:ring-teal-500">
                        Book Appointment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.getElementById('department').addEventListener('change', function() {
        const departmentId = this.value;
        const doctorSelect = document.getElementById('doctor');

        if (!departmentId) {
            doctorSelect.innerHTML = '<option value="">First select a department</option>';
            doctorSelect.disabled = true;
            return;
        }

        // Enable and load doctors
        doctorSelect.disabled = false;
        doctorSelect.innerHTML = '<option value="">Loading doctors...</option>';

        fetch(`get_doctors.php?department_id=${departmentId}`)
            .then(response => response.json())
            .then(doctors => {
                doctorSelect.innerHTML = '<option value="">Select a Doctor</option>';
                doctors.forEach(doctor => {
                    const option = document.createElement('option');
                    option.value = doctor.doctor_id;
                    option.textContent = `Dr. ${doctor.name} (${doctor.specialization})`;
                    doctorSelect.appendChild(option);
                });
            })
            .catch(error => {
                console.error('Error loading doctors:', error);
                doctorSelect.innerHTML = '<option value="">Error loading doctors</option>';
            });
    });

    // Form validation
    document.getElementById('appointmentForm').addEventListener('submit', function(e) {
        const doctor = document.getElementById('doctor').value;
        const date = document.getElementById('appointment_date').value;
        const reason = document.getElementById('reason').value.trim();

        if (!doctor || !date || !reason) {
            e.preventDefault();
            alert('Please fill in all required fields');
        }
    });
</script>

<?php
include_once '../includes/footer.php';
ob_end_flush();
?>