<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verify login status
if (!isset($_SESSION['user_id'])) {
    header("Location: " . $base_url . "login.php");
    exit;
}

$is_admin = ($_SESSION['role'] === 'Administrator');
$is_doctor = ($_SESSION['role'] === 'Doctor');
$is_nurse = ($_SESSION['role'] === 'Nurse');
$is_receptionist = ($_SESSION['role'] === 'Receptionist');
$is_patient = ($_SESSION['role'] === 'Patient');
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HMS - Hospital Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>

<body class="bg-gray-100">
    <nav class="bg-teal-600 shadow-lg">
        <div class="container mx-auto px-4">
            <div class="flex items-center justify-between h-16">
                <a href="<?php echo $base_url; ?>" class="flex items-center space-x-2">
                    <i class="fas fa-hospital text-2xl text-white"></i>
                    <span class="text-white text-lg font-semibold">HMS</span>
                </a>

                <div class="flex space-x-4">
                    <a href="<?php echo $base_url; ?>" class="text-white hover:bg-teal-700 px-3 py-2 rounded-md">
                        <i class="fas fa-home mr-2"></i>Dashboard
                    </a>

                    <?php if ($is_admin): ?>
                        <!-- Admin Management Dropdown -->
                        <div class="relative group">
                            <button class="text-white hover:bg-teal-700 px-3 py-2 rounded-md inline-flex items-center">
                                <i class="fas fa-cogs mr-2"></i>
                                <span>Management</span>
                                <i class="fas fa-chevron-down ml-2"></i>
                            </button>
                            <div class="hidden group-hover:block absolute left-0 mt-2 w-56 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5">
                                <div class="py-1">
                                    <a href="<?php echo $base_url; ?>staff/manage.php"
                                        class="block px-4 py-2 text-sm text-gray-700 hover:bg-teal-500 hover:text-white">
                                        <i class="fas fa-users mr-2"></i>Staff Management
                                    </a>
                                    <a href="<?php echo $base_url; ?>resources/manage.php"
                                        class="block px-4 py-2 text-sm text-gray-700 hover:bg-teal-500 hover:text-white">
                                        <i class="fas fa-box mr-2"></i>Resources
                                    </a>
                                    <a href="<?php echo $base_url; ?>medicines/manage.php"
                                        class="block px-4 py-2 text-sm text-gray-700 hover:bg-teal-500 hover:text-white">
                                        <i class="fas fa-pills mr-2"></i>Medicines
                                    </a>
                                    <a href="<?php echo $base_url; ?>bills/manage.php"
                                        class="block px-4 py-2 text-sm text-gray-700 hover:bg-teal-500 hover:text-white">
                                        <i class="fas fa-file-invoice-dollar mr-2"></i>Bills
                                    </a>
                                    <a href="<?php echo $base_url; ?>departments/manage.php"
                                        class="block px-4 py-2 text-sm text-gray-700 hover:bg-teal-500 hover:text-white">
                                        <i class="fas fa-hospital-alt mr-2"></i>Departments
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($is_doctor): ?>
                        <a href="<?php echo $base_url; ?>appointments/manage.php" class="text-white hover:bg-teal-700 px-3 py-2 rounded-md">
                            <i class="fas fa-calendar-check mr-2"></i>Appointments
                        </a>
                        <a href="<?php echo $base_url; ?>patients/manage.php" class="text-white hover:bg-teal-700 px-3 py-2 rounded-md">
                            <i class="fas fa-user-injured mr-2"></i>Patients
                        </a>
                        <a href="<?php echo $base_url; ?>prescriptions/manage.php" class="text-white hover:bg-teal-700 px-3 py-2 rounded-md">
                            <i class="fas fa-prescription mr-2"></i>Prescriptions
                        </a>
                    <?php endif; ?>

                    <?php if ($is_nurse): ?>
                        <a href="<?php echo $base_url; ?>patients/manage.php" class="text-white hover:bg-teal-700 px-3 py-2 rounded-md">
                            <i class="fas fa-user-injured mr-2"></i>Patients
                        </a>
                        <a href="<?php echo $base_url; ?>appointments/manage.php" class="text-white hover:bg-teal-700 px-3 py-2 rounded-md">
                            <i class="fas fa-calendar-check mr-2"></i>Appointments
                        </a>
                        <a href="<?php echo $base_url; ?>medicines/view.php" class="text-white hover:bg-teal-700 px-3 py-2 rounded-md">
                            <i class="fas fa-pills mr-2"></i>Medicines
                        </a>
                    <?php endif; ?>

                    <?php if ($is_patient): ?>
                        <a href="<?php echo $base_url; ?>appointments/book.php" class="text-white hover:bg-teal-700 px-3 py-2 rounded-md">
                            <i class="fas fa-calendar-plus mr-2"></i>Book Appointment
                        </a>
                        <a href="<?php echo $base_url; ?>appointments/my-appointments.php" class="text-white hover:bg-teal-700 px-3 py-2 rounded-md">
                            <i class="fas fa-calendar-check mr-2"></i>My Appointments
                        </a>
                        <a href="<?php echo $base_url; ?>prescriptions/my-prescriptions.php" class="text-white hover:bg-teal-700 px-3 py-2 rounded-md">
                            <i class="fas fa-prescription mr-2"></i>My Prescriptions
                        </a>
                        <a href="<?php echo $base_url; ?>bills/my-bills.php" class="text-white hover:bg-teal-700 px-3 py-2 rounded-md">
                            <i class="fas fa-file-invoice-dollar mr-2"></i>My Bills
                        </a>
                    <?php endif; ?>
                </div>

                <!-- User Info and Logout -->
                <div class="flex items-center space-x-4">
                    <span class="text-white">
                        <i class="fas fa-user-circle mr-2"></i>
                        <?php echo htmlspecialchars($_SESSION['username']); ?>
                    </span>
                    <a href="<?php echo $base_url; ?>logout.php"
                        class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-md transition duration-300">
                        <i class="fas fa-sign-out-alt mr-2"></i>Logout
                    </a>
                </div>

            </div>
        </div>
    </nav>

    <script>
        // Add event listeners for dropdown functionality
        document.addEventListener('DOMContentLoaded', function() {
            const dropdownButton = document.querySelector('.relative button');
            const dropdownMenu = document.querySelector('.relative .hidden');

            dropdownButton.addEventListener('click', function() {
                dropdownMenu.classList.toggle('hidden');
            });

            // Close dropdown when clicking outside
            document.addEventListener('click', function(event) {
                if (!dropdownButton.contains(event.target) && !dropdownMenu.contains(event.target)) {
                    dropdownMenu.classList.add('hidden');
                }
            });
        });
    </script>
</body>

</html>