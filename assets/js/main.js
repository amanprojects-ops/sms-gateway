/**
 * SMS Gateway - Main JavaScript
 */

document.addEventListener('DOMContentLoaded', function () {
    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Initialize popovers
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });

    // API key copy function
    const copyButtons = document.querySelectorAll('.copy-btn');
    if (copyButtons) {
        copyButtons.forEach(btn => {
            btn.addEventListener('click', function () {
                const textToCopy = this.getAttribute('data-copy');
                navigator.clipboard.writeText(textToCopy).then(function () {
                    // Change button text temporarily
                    const originalText = btn.innerHTML;
                    btn.innerHTML = '<i class="fas fa-check"></i> Copied!';
                    setTimeout(function () {
                        btn.innerHTML = originalText;
                    }, 2000);
                });
            });
        });
    }

    // SMS Test Form
    const smsTestForm = document.getElementById('smsTestForm');
    if (smsTestForm) {
        smsTestForm.addEventListener('submit', function (e) {
            e.preventDefault();

            // Show loader
            const loader = document.getElementById('smsTestLoader');
            loader.classList.remove('d-none');
            const resultElement = document.getElementById('smsTestResult');
            resultElement.classList.add('d-none');

            // Get form data
            const phone = document.getElementById('testPhone').value;
            const message = document.getElementById('testMessage').value;

            console.log('Debug Mode: Form submitted with phone:', phone, 'and message:', message); // Debugging line

            // Send AJAX request to test SMS
            fetch('test_sms.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ phone: phone, message: message })
            })
                .then(response => {
                    console.log('Debug Mode: Response status:', response.status); // Debugging line
                    // Check if response is ok before parsing JSON
                    if (!response.ok) {
                        throw new Error(`Debug Mode: Server responded with status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    // Hide loader
                    loader.classList.add('d-none');

                    // Show result
                    resultElement.classList.remove('d-none');
                    console.log('Debug Mode: Response data:', data); // Debugging line

                    if (data.success) {
                        resultElement.innerHTML = `<div class="alert alert-success">
                        <i class="fas fa-check-circle me-2"></i> ${data.message}
                    </div>`;
                    } else {
                        resultElement.innerHTML = `<div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle me-2"></i> ${data.message}
                    </div>`;
                    }
                })
                .catch(error => {
                    // Hide loader
                    loader.classList.add('d-none');

                    // Show error
                    resultElement.classList.remove('d-none');
                    console.error('Debug Mode: Error occurred:', error); // Debugging line
                    resultElement.innerHTML = `<div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle me-2"></i> An error occurred: ${error.message}
                </div>`;
                });
        });
    }

    // OTP Test Form
    document.addEventListener('DOMContentLoaded', function () {
        const otpTestForm = document.getElementById('otpTestForm');
        if (otpTestForm) {
            otpTestForm.addEventListener('submit', function (e) {
                e.preventDefault();
                // Show loader
                document.getElementById('otpTestLoader').classList.remove('d-none');
                document.getElementById('otpTestResult').classList.add('d-none');

                // Get form data
                const phone = document.getElementById('otpPhone').value;

                // Send AJAX request to generate OTP
                fetch('api/generate_otp.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-API-Key': '3c8a5f96798b0b9769da0cb6d86f22c8',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: `phone=${encodeURIComponent(phone)}`
                })
                    .then(response => {
                        // Check if response is ok
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(data => {
                        // Hide loader
                        document.getElementById('otpTestLoader').classList.add('d-none');

                        // Show result
                        const resultElement = document.getElementById('otpTestResult');
                        resultElement.classList.remove('d-none');

                        if (data.success) {
                            resultElement.innerHTML = `<div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i> ${data.message}
                            <div class="mt-3">
                                <form id="verifyOtpForm" class="row g-3">
                                    <div class="col-md-8">
                                        <input type="text" class="form-control" id="otpCode" placeholder="Enter OTP" required>
                                    </div>
                                    <div class="col-md-4">
                                        <button type="submit" class="btn btn-primary w-100">Verify OTP</button>
                                    </div>
                                </form>
                                <div id="verifyOtpResult" class="mt-3 d-none"></div>
                            </div>
                        </div>`;

                            // Attach event listener to verify OTP form
                            document.getElementById('verifyOtpForm').addEventListener('submit', function (e) {
                                e.preventDefault();

                                const otp = document.getElementById('otpCode').value;

                                // Send AJAX request to verify OTP
                                fetch('api/verify_otp.php', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/x-www-form-urlencoded',
                                    },
                                    body: `phone=${encodeURIComponent(phone)}&otp=${encodeURIComponent(otp)}`
                                })
                                    .then(response => {
                                        if (!response.ok) {
                                            throw new Error('Network response was not ok');
                                        }
                                        return response.json();
                                    })
                                    .then(data => {
                                        const verifyResultElement = document.getElementById('verifyOtpResult');
                                        verifyResultElement.classList.remove('d-none');

                                        if (data.success) {
                                            verifyResultElement.innerHTML = `<div class="alert alert-success">
                                        <i class="fas fa-check-circle me-2"></i> ${data.message}
                                    </div>`;
                                        } else {
                                            verifyResultElement.innerHTML = `<div class="alert alert-danger">
                                        <i class="fas fa-exclamation-circle me-2"></i> ${data.message}
                                    </div>`;
                                        }
                                    })
                                    .catch(error => {
                                        const verifyResultElement = document.getElementById('verifyOtpResult');
                                        verifyResultElement.classList.remove('d-none');
                                        verifyResultElement.innerHTML = `<div class="alert alert-danger">
                                    <i class="fas fa-exclamation-circle me-2"></i> An error occurred: ${error.message}
                                </div>`;
                                    });
                            });

                        } else {
                            resultElement.innerHTML = `<div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle me-2"></i> ${data.message}
                        </div>`;
                        }
                    })
                    .catch(error => {
                        // Hide loader
                        document.getElementById('otpTestLoader').classList.add('d-none');

                        // Show error
                        const resultElement = document.getElementById('otpTestResult');
                        resultElement.classList.remove('d-none');
                        resultElement.innerHTML = `<div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle me-2"></i> An error occurred: ${error.message}
                    </div>`;
                    });
            });
        }
    });

    // Toggle API provider switch
    const apiSwitch = document.getElementById('apiProviderSwitch');
    if (apiSwitch) {
        apiSwitch.addEventListener('change', function () {
            const isChecked = this.checked;
            const provider = isChecked ? 'backup' : 'primary';

            // Send AJAX request to update active provider
            fetch('admin/update_provider.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `provider=${provider}`
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Show success toast
                        const toast = document.createElement('div');
                        toast.className = 'position-fixed bottom-0 end-0 p-3';
                        toast.style.zIndex = '5';
                        toast.innerHTML = `
                        <div class="toast show" role="alert" aria-live="assertive" aria-atomic="true">
                            <div class="toast-header bg-success text-white">
                                <strong class="me-auto">Success</strong>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
                            </div>
                            <div class="toast-body">
                                API Provider switched to ${isChecked ? 'Backup' : 'Primary'} successfully.
                            </div>
                        </div>
                    `;
                        document.body.appendChild(toast);

                        // Remove toast after 3 seconds
                        setTimeout(() => {
                            document.body.removeChild(toast);
                        }, 3000);
                    }
                });
        });
    }

    // Initialize charts if they exist
    if (typeof Chart !== 'undefined' && document.getElementById('smsChart')) {
        initializeCharts();
    }
});

// Initialize charts for dashboard
function initializeCharts() {
    // SMS Usage Chart
    const ctx = document.getElementById('smsChart').getContext('2d');

    // Get data from data attributes
    const labels = JSON.parse(document.getElementById('smsChart').getAttribute('data-labels'));
    const sentData = JSON.parse(document.getElementById('smsChart').getAttribute('data-sent'));
    const failedData = JSON.parse(document.getElementById('smsChart').getAttribute('data-failed'));

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Successful SMS',
                    data: sentData,
                    borderColor: '#2ecc71',
                    backgroundColor: 'rgba(46, 204, 113, 0.1)',
                    borderWidth: 2,
                    tension: 0.4,
                    fill: true
                },
                {
                    label: 'Failed SMS',
                    data: failedData,
                    borderColor: '#e74c3c',
                    backgroundColor: 'rgba(231, 76, 60, 0.1)',
                    borderWidth: 2,
                    tension: 0.4,
                    fill: true
                }
            ]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                },
                title: {
                    display: true,
                    text: 'SMS Usage Statistics (Last 30 Days)'
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Number of SMS'
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Date'
                    }
                }
            }
        }
    });
}

// Auto-close alerts after 5 seconds
setTimeout(function () {
    const alerts = document.querySelectorAll('.alert-dismissible');
    if (alerts) {
        alerts.forEach(alert => {
            const bsAlert = new bootstrap.Alert(alert);
            setTimeout(() => {
                bsAlert.close();
            }, 5000);
        });
    }
}, 1000); 