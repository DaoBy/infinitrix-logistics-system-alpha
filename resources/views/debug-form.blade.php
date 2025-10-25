<!DOCTYPE html>
<html>
<head>
    <title>Package Update Debug Form</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; }
        input, select, textarea { width: 300px; padding: 8px; }
        button { padding: 10px 20px; background: #007bff; color: white; border: none; cursor: pointer; }
        .result { margin-top: 20px; padding: 15px; border-radius: 5px; }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    <h1>Package Update Debug Form</h1>
    
    <!-- Test with CSRF token -->
    <h3>Test with CSRF (Normal Form)</h3>
    <form id="debugFormWithCSRF" method="POST" action="/driver/packages/update-destination-status">
        @csrf
        <div class="form-group">
            <label>Package ID:</label>
            <input type="number" name="package_updates[0][package_id]" value="183" required>
        </div>
        
        <div class="form-group">
            <label>Status:</label>
            <select name="package_updates[0][status]" required>
                <option value="delivered">Delivered</option>
                <option value="damaged_in_transit" selected>Damaged in Transit</option>
                <option value="lost_in_transit">Lost in Transit</option>
            </select>
        </div>
        
        <div class="form-group">
            <label>Remarks:</label>
            <textarea name="package_updates[0][remarks]" rows="3">Test from debug form</textarea>
        </div>
        
        <button type="submit">Test Update (With CSRF)</button>
    </form>
    
    <!-- Test without CSRF token -->
    <h3>Test without CSRF (Direct Route)</h3>
    <form id="debugFormDirect">
        <div class="form-group">
            <label>Package ID:</label>
            <input type="number" name="package_id" value="183" required>
        </div>
        
        <div class="form-group">
            <label>Status:</label>
            <select name="status" required>
                <option value="delivered">Delivered</option>
                <option value="damaged_in_transit" selected>Damaged in Transit</option>
                <option value="lost_in_transit">Lost in Transit</option>
            </select>
        </div>
        
        <button type="submit">Test Update (Direct Route)</button>
    </form>
    
    <div id="result"></div>

    <script>
        // Test with CSRF (normal form)
        document.getElementById('debugFormWithCSRF').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const resultDiv = document.getElementById('result');
            
            try {
                const response = await fetch(this.action, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: formData
                });
                
                const data = await response.json();
                
                resultDiv.innerHTML = `
                    <div class="result success">
                        <h3>Form Submission Successful (With CSRF)</h3>
                        <pre>${JSON.stringify(data, null, 2)}</pre>
                    </div>
                `;
            } catch (error) {
                resultDiv.innerHTML = `
                    <div class="result error">
                        <h3>Form Submission Failed (With CSRF)</h3>
                        <pre>${error.toString()}</pre>
                    </div>
                `;
            }
        });

        // Test without CSRF (direct route)
        document.getElementById('debugFormDirect').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const resultDiv = document.getElementById('result');
            
            try {
                const response = await fetch('/test-package-update-direct', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                resultDiv.innerHTML = `
                    <div class="result success">
                        <h3>Direct Route Submission Successful</h3>
                        <pre>${JSON.stringify(data, null, 2)}</pre>
                    </div>
                `;
            } catch (error) {
                resultDiv.innerHTML = `
                    <div class="result error">
                        <h3>Direct Route Submission Failed</h3>
                        <pre>${error.toString()}</pre>
                    </div>
                `;
            }
        });
    </script>
</body>
</html>