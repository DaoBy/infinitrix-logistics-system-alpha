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
    
    <form id="debugForm">
        <div class="form-group">
            <label>Package ID:</label>
            <input type="number" name="package_updates[0][package_id]" required>
        </div>
        
        <div class="form-group">
            <label>Status:</label>
            <select name="package_updates[0][status]" required>
                <option value="delivered">Delivered</option>
                <option value="damaged_in_transit">Damaged in Transit</option>
                <option value="lost_in_transit">Lost in Transit</option>
            </select>
        </div>
        
        <div class="form-group">
            <label>Remarks:</label>
            <textarea name="package_updates[0][remarks]" rows="3"></textarea>
        </div>
        
        <button type="submit">Test Update</button>
    </form>
    
    <div id="result"></div>

    <script>
        document.getElementById('debugForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const resultDiv = document.getElementById('result');
            
            try {
                const response = await fetch('/test-package-update-form', {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: formData
                });
                
                const data = await response.json();
                
                resultDiv.innerHTML = `
                    <div class="result success">
                        <h3>Form Submission Successful</h3>
                        <pre>${JSON.stringify(data, null, 2)}</pre>
                    </div>
                `;
            } catch (error) {
                resultDiv.innerHTML = `
                    <div class="result error">
                        <h3>Form Submission Failed</h3>
                        <pre>${error.toString()}</pre>
                    </div>
                `;
            }
        });
    </script>
</body>
</html>