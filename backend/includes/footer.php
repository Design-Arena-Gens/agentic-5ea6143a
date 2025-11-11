    </div>
    <script>
        // Global CSRF token
        const CSRF_TOKEN = '<?php echo generate_csrf_token(); ?>';

        // Helper function for API calls
        async function apiCall(url, method = 'GET', data = null) {
            const options = {
                method: method,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Content-Type': 'application/json'
                }
            };

            if (data) {
                data.csrf_token = CSRF_TOKEN;
                options.body = JSON.stringify(data);
            }

            try {
                const response = await fetch(url, options);
                const result = await response.json();

                if (!response.ok) {
                    throw new Error(result.message || 'Request failed');
                }

                return result;
            } catch (error) {
                console.error('API Error:', error);
                throw error;
            }
        }

        // Show alert message
        function showAlert(message, type = 'info') {
            const alert = document.createElement('div');
            alert.className = `alert ${type}`;
            alert.textContent = message;

            const container = document.querySelector('.container');
            container.insertBefore(alert, container.firstChild);

            setTimeout(() => alert.remove(), 5000);
        }

        // Confirm delete
        function confirmDelete(message = 'Are you sure you want to delete this item?') {
            return confirm(message);
        }
    </script>
</body>
</html>
