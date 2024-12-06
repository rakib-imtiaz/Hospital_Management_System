<?php
ob_start();
require_once '../includes/db_connect.php';
session_start();

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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

    if (!$patient) {
        throw new Exception("Patient record not found");
    }

    $patient_id = $patient['patient_id'];

    // Debug log
    error_log("Patient ID retrieved: " . $patient_id);
} catch (Exception $e) {
    error_log("Error fetching patient ID: " . $e->getMessage());
    $error = "System error occurred: Unable to retrieve patient information";
}

// Fetch bills with error handling
if (isset($patient_id)) {
    try {
        // Modified query to match the actual database structure
        $stmt = $pdo->prepare("
            SELECT 
                b.bill_id,
                b.patient_id,
                b.amount,
                b.status,
                b.description,
                b.bill_date as created_at,
                p.name as patient_name
            FROM bill b
            JOIN patient p ON b.patient_id = p.patient_id
            WHERE b.patient_id = :patient_id
            ORDER BY b.bill_date DESC
        ");

        $stmt->execute(['patient_id' => $patient_id]);
        $bills = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Debug log
        error_log("Number of bills fetched: " . count($bills));

        // Initialize totals
        $total_amount = 0;
        $total_paid = 0;
        $total_pending = 0;

        foreach ($bills as $bill) {
            $amount = floatval($bill['amount']);
            $total_amount += $amount;
            if ($bill['status'] === 'Paid') {
                $total_paid += $amount;
            } else {
                $total_pending += $amount;
            }
        }

    } catch (PDOException $e) {
        error_log("Database Error: " . $e->getMessage());
        $error = "Error fetching bills. Please try again later.";
        $bills = [];
    }
} else {
    $bills = [];
    $error = "Unable to retrieve bills due to missing patient information.";
}
?>

<div class="container mx-auto px-6 py-8">
    <div class="max-w-4xl mx-auto">
        <h1 class="text-3xl font-bold text-gray-800 mb-6">My Bills</h1>

        <?php if (isset($error)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <!-- Bills Summary -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="text-sm text-gray-500 mb-1">Total Amount</div>
                <div class="text-2xl font-bold text-gray-800">
                    $<?php echo number_format($total_amount ?? 0, 2); ?>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="text-sm text-gray-500 mb-1">Total Paid</div>
                <div class="text-2xl font-bold text-green-600">
                    $<?php echo number_format($total_paid ?? 0, 2); ?>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="text-sm text-gray-500 mb-1">Total Pending</div>
                <div class="text-2xl font-bold text-red-600">
                    $<?php echo number_format($total_pending ?? 0, 2); ?>
                </div>
            </div>
        </div>

        <!-- Bills List -->
        <?php if (count($bills) > 0): ?>
            <div class="bg-white shadow-md rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Bill Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($bills as $bill): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php echo date('M d, Y', strtotime($bill['created_at'])); ?>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900">
                                        <?php echo htmlspecialchars($bill['description']); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">
                                        $<?php echo number_format($bill['amount'], 2); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                        <?php echo $bill['status'] === 'Paid' ? 
                                            'bg-green-100 text-green-800' : 
                                            'bg-yellow-100 text-yellow-800'; ?>">
                                        <?php echo htmlspecialchars($bill['status']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <?php if ($bill['status'] !== 'Paid'): ?>
                                        <a href="pay.php?bill_id=<?php echo $bill['bill_id']; ?>" 
                                           class="text-teal-600 hover:text-teal-900">
                                            Pay Now
                                        </a>
                                    <?php endif; ?>
                                    <a href="view.php?bill_id=<?php echo $bill['bill_id']; ?>" 
                                       class="text-blue-600 hover:text-blue-900 ml-3">
                                        View Details
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="bg-white shadow-md rounded-lg p-6 text-center">
                <p class="text-gray-500">No bills found.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
include_once '../includes/footer.php';
ob_end_flush();
?>