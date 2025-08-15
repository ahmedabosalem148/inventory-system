// Self-Check Panel for Frontend Testing
// سكربت الفحص الذاتي للواجهة الأمامية

window.SelfCheck = {
    results: [],
    
    run() {
        console.log('🔍 بدء الفحص الذاتي للواجهة...');
        this.results = [];
        
        // Reset previous results
        const resultContainer = document.getElementById('selfcheck-results');
        if (resultContainer) {
            resultContainer.innerHTML = '';
        }
        
        // Run all checks
        this.checkMetaCSRF();
        this.checkAudioSystem();
        this.checkMuteButton();
        this.checkAdminTable();
        this.checkWarehouseTable();
        this.checkTestIds();
        
        // Display results
        this.displayResults();
        
        return this.results;
    },
    
    addResult(id, ok, msg) {
        this.results.push({ id, ok, msg });
        console.log(`${ok ? '✅' : '❌'} ${id}: ${msg}`);
    },
    
    checkMetaCSRF() {
        const csrfMeta = document.querySelector('meta[name="csrf-token"]');
        this.addResult(
            'csrf-token',
            !!csrfMeta,
            csrfMeta ? 'تم العثور على CSRF token' : 'CSRF token مفقود'
        );
    },
    
    checkAudioSystem() {
        const audioElement = document.getElementById('alert-audio');
        let audioOk = false;
        let audioMsg = 'عنصر الصوت مفقود';
        
        if (audioElement) {
            try {
                // Test play method exists
                if (typeof audioElement.play === 'function') {
                    audioOk = true;
                    audioMsg = 'نظام الصوت متوفر';
                } else {
                    audioMsg = 'عنصر الصوت موجود لكن play() غير متوفر';
                }
            } catch (e) {
                audioMsg = `خطأ في اختبار الصوت: ${e.message}`;
            }
        }
        
        // Check fallback
        if (!audioOk && window.App && typeof window.App.beep === 'function') {
            audioOk = true;
            audioMsg = 'نظام الصوت الاحتياطي (WebAudio) متوفر';
        }
        
        this.addResult('audio-system', audioOk, audioMsg);
    },
    
    checkMuteButton() {
        const muteBtn = document.querySelector('[data-testid="btn-toggle-mute"]');
        let muteOk = false;
        let muteMsg = 'زر الكتم مفقود';
        
        if (muteBtn) {
            // Check localStorage functionality
            try {
                const testKey = 'test-storage';
                localStorage.setItem(testKey, 'test');
                const retrieved = localStorage.getItem(testKey);
                localStorage.removeItem(testKey);
                
                if (retrieved === 'test') {
                    muteOk = true;
                    muteMsg = 'زر الكتم موجود و localStorage يعمل';
                } else {
                    muteMsg = 'زر الكتم موجود لكن localStorage لا يعمل';
                }
            } catch (e) {
                muteMsg = `زر الكتم موجود لكن localStorage معطل: ${e.message}`;
            }
        }
        
        this.addResult('mute-button', muteOk, muteMsg);
    },
    
    checkAdminTable() {
        const adminTable = document.querySelector('[data-testid="admin-table"]');
        if (!adminTable) {
            this.addResult('admin-table', false, 'جدول الأدمن غير موجود في هذه الصفحة');
            return;
        }
        
        // Check required columns
        const requiredTestIds = [
            'row-product', 'row-warehouse', 'row-cc', 
            'row-size', 'row-lu', 'row-total', 'row-min', 'row-status'
        ];
        
        const rows = adminTable.querySelectorAll('tbody tr');
        let allRowsValid = true;
        let invalidRows = [];
        
        rows.forEach((row, index) => {
            const missingTestIds = [];
            
            requiredTestIds.forEach(testId => {
                if (!row.querySelector(`[data-testid="${testId}"]`)) {
                    missingTestIds.push(testId);
                }
            });
            
            if (missingTestIds.length > 0) {
                allRowsValid = false;
                invalidRows.push(`الصف ${index + 1}: مفقود ${missingTestIds.join(', ')}`);
            }
            
            // Check calculation: total = cc * size + lu
            this.validateRowCalculation(row, index + 1);
            
            // Check below-min styling
            this.checkBelowMinStyling(row, index + 1);
        });
        
        this.addResult(
            'admin-table-structure',
            allRowsValid,
            allRowsValid ? `جدول الأدمن صحيح (${rows.length} صف)` : 
            `مشاكل في الجدول: ${invalidRows.join('; ')}`
        );
    },
    
    validateRowCalculation(row, rowNum) {
        const ccElement = row.querySelector('[data-testid="row-cc"]');
        const sizeElement = row.querySelector('[data-testid="row-size"]');
        const luElement = row.querySelector('[data-testid="row-lu"]');
        const totalElement = row.querySelector('[data-testid="row-total"]');
        
        if (!ccElement || !sizeElement || !luElement || !totalElement) {
            return; // Already reported in structure check
        }
        
        const cc = parseInt(ccElement.textContent) || 0;
        const size = parseInt(sizeElement.textContent) || 0;
        const lu = parseInt(luElement.textContent) || 0;
        const displayedTotal = parseInt(totalElement.textContent) || 0;
        const calculatedTotal = cc * size + lu;
        
        this.addResult(
            `calculation-row-${rowNum}`,
            calculatedTotal === displayedTotal,
            calculatedTotal === displayedTotal ? 
            `حساب الصف ${rowNum} صحيح (${cc}×${size}+${lu}=${calculatedTotal})` :
            `خطأ في حساب الصف ${rowNum}: متوقع ${calculatedTotal} لكن ظاهر ${displayedTotal}`
        );
    },
    
    checkBelowMinStyling(row, rowNum) {
        const belowMin = row.getAttribute('data-below-min') === '1';
        
        if (belowMin) {
            const hasWarningClass = row.classList.contains('table-warning') || 
                                  row.classList.contains('table-danger') ||
                                  row.classList.contains('bg-warning') ||
                                  row.classList.contains('bg-danger');
            
            const hasBellIcon = row.querySelector('🔔') || 
                              row.textContent.includes('🔔') ||
                              row.querySelector('.bell-icon');
            
            this.addResult(
                `styling-row-${rowNum}`,
                hasWarningClass,
                hasWarningClass ? 
                `الصف ${rowNum} تحت الحد الأدنى ومُلوّن بشكل صحيح` :
                `الصف ${rowNum} تحت الحد الأدنى لكن بدون تلوين تحذيري`
            );
            
            this.addResult(
                `bell-row-${rowNum}`,
                hasBellIcon,
                hasBellIcon ? 
                `الصف ${rowNum} يحتوي على أيقونة الجرس` :
                `الصف ${rowNum} تحت الحد الأدنى لكن بدون أيقونة جرس`
            );
        }
    },
    
    checkWarehouseTable() {
        const whTable = document.querySelector('[data-testid="wh-table"]');
        if (!whTable) {
            this.addResult('warehouse-table', false, 'جدول المخزن غير موجود في هذه الصفحة');
            return;
        }
        
        const detailBtns = whTable.querySelectorAll('[data-testid="btn-details"]');
        this.addResult(
            'warehouse-details-buttons',
            detailBtns.length > 0,
            detailBtns.length > 0 ? 
            `وجد ${detailBtns.length} زر تفاصيل في جدول المخزن` :
            'لا توجد أزرار تفاصيل في جدول المخزن'
        );
    },
    
    checkTestIds() {
        const criticalTestIds = [
            'btn-toggle-mute',
            'btn-open-modal',
            'input-name_ar',
            'input-carton_size',
            'btn-save-product'
        ];
        
        let foundTestIds = 0;
        criticalTestIds.forEach(testId => {
            if (document.querySelector(`[data-testid="${testId}"]`)) {
                foundTestIds++;
            }
        });
        
        this.addResult(
            'critical-testids',
            foundTestIds >= 2, // At least some are present
            `وجد ${foundTestIds} من ${criticalTestIds.length} عناصر حرجة`
        );
    },
    
    displayResults() {
        const container = document.getElementById('selfcheck-results');
        if (!container) return;
        
        const passed = this.results.filter(r => r.ok).length;
        const total = this.results.length;
        const percentage = Math.round((passed / total) * 100);
        
        let html = `
            <div class="alert ${percentage >= 80 ? 'alert-success' : percentage >= 60 ? 'alert-warning' : 'alert-danger'} mb-3">
                <h6 class="mb-2">نتيجة الفحص: ${passed}/${total} (${percentage}%)</h6>
                <small>آخر فحص: ${new Date().toLocaleString('ar-EG')}</small>
            </div>
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>الحالة</th>
                            <th>العنصر</th>
                            <th>التفاصيل</th>
                        </tr>
                    </thead>
                    <tbody>
        `;
        
        this.results.forEach(result => {
            const icon = result.ok ? '✅' : '❌';
            const rowClass = result.ok ? 'table-success' : 'table-danger';
            html += `
                <tr class="${rowClass}">
                    <td>${icon}</td>
                    <td><code>${result.id}</code></td>
                    <td>${result.msg}</td>
                </tr>
            `;
        });
        
        html += `
                    </tbody>
                </table>
            </div>
        `;
        
        container.innerHTML = html;
    }
};

// Auto-run on page load if debug mode
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('debug') === '1') {
        console.log('🔍 وضع التصحيح مفعل - لوحة الفحص الذاتي متاحة');
    }
});
