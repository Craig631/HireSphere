<?php 
include 'includes/header.php'; 
?>

<div class="container py-5 text-center">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm border-0 rounded-4 p-5">
                <div class="display-1 text-danger mb-4">
                    <i class="bi bi-x-circle-fill"></i>
                </div>
                
                <h2 class="fw-bold">Payment Cancelled</h2>
                <p class="text-muted mb-4">
                    Your transaction was cancelled and no charges were made. If you encountered an issue, you can try again or contact support.
                </p>

                <div class="d-grid gap-2">
                    <a href="javascript:history.back()" class="btn btn-primary py-3 rounded-3 fw-semibold">
                        Try Again
                    </a>
                    <a href="index.php" class="btn btn-outline-secondary py-3 rounded-3">
                        Return to Marketplace
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>