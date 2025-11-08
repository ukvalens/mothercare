// report-download.js

function downloadHealthReport() {
    showReportOptionsModal();
}

function showReportOptionsModal() {
    const modalHtml = `
        <div id="reportModal" style="position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);display:flex;align-items:center;justify-content:center;z-index:1000;">
            <div style="background:white;padding:20px;border-radius:8px;width:400px;max-width:90%;">
                <h3 style="margin-top:0;">Download Health Report</h3>
                <form id="reportForm">
                    <div style="margin-bottom:15px;">
                        <label style="display:block;margin-bottom:5px;font-weight:bold;">Report Type:</label>
                        <select name="report_type" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px;">
                            <option value="health_summary">Health Summary</option>
                            <option value="anc_visits">ANC Visits Report</option>
                            <option value="appointments">Appointments Report</option>
                        </select>
                    </div>
                    
                    <div style="margin-bottom:15px;">
                        <label style="display:block;margin-bottom:5px;font-weight:bold;">Date Range:</label>
                        <div style="display:flex;gap:10px;">
                            <input type="date" name="start_date" style="flex:1;padding:8px;border:1px solid #ddd;border-radius:4px;">
                            <input type="date" name="end_date" style="flex:1;padding:8px;border:1px solid #ddd;border-radius:4px;">
                        </div>
                    </div>
                    
                    <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:20px;">
                        <button type="button" onclick="closeReportModal()" style="padding:8px 16px;border:1px solid #ddd;background:white;border-radius:4px;cursor:pointer;">Cancel</button>
                        <button type="submit" style="padding:8px 16px;border:none;background:#007bff;color:white;border-radius:4px;cursor:pointer;">Generate Report</button>
                    </div>
                </form>
            </div>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    
    document.getElementById('reportForm').addEventListener('submit', function(e) {
        e.preventDefault();
        generateReport(this);
    });
}

function closeReportModal() {
    const modal = document.getElementById('reportModal');
    if (modal) modal.remove();
}

function generateReport(form) {
    const formData = new FormData(form);
    formData.append('download_report', 'true');

    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;
    submitBtn.textContent = 'Generating...';
    submitBtn.disabled = true;

    fetch('download_report.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (response.ok) return response.blob();
        throw new Error('Network response was not ok.');
    })
    .then(blob => {
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.style.display = 'none';
        a.href = url;
        a.download = `health_report_${new Date().toISOString().split('T')[0]}.pdf`;
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
        document.body.removeChild(a);
        closeReportModal();
    })
    .catch(error => {
        console.error('Error generating report:', error);
        alert('Error generating report. Please try again.');
    })
    .finally(() => {
        submitBtn.textContent = originalText;
        submitBtn.disabled = false;
    });
}

// Simple version without modal
function downloadSimpleHealthReport() {
    const formData = new FormData();
    formData.append('download_report', 'true');
    formData.append('report_type', 'health_summary');

    fetch('download_report.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.blob())
    .then(blob => {
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `health_report_${new Date().toISOString().split('T')[0]}.pdf`;
        a.click();
        window.URL.revokeObjectURL(url);
    })
    .catch(error => {
        console.error('Error downloading report:', error);
        alert('Error downloading report. Please try again.');
    });
}