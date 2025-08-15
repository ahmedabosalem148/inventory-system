<div class="table-container">
    <div class="card-header">
        <div class="card-title">إدارة المنتجات</div>
        <button id="create-product-btn" class="btn btn-primary" data-testid="btn-open-modal">
            <i class="fa fa-plus"></i> إضافة منتج
        </button>
    </div>
</div>

<!-- Modal -->
<div id="product-modal" class="modal-overlay" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>إضافة منتج جديد</h3>
            <button id="close-modal" class="btn-close">&times;</button>
        </div>
        
        <form id="product-form">
            <div class="form-group">
                <label for="name">اسم المنتج *</label>
                <input type="text" id="name" name="name" required maxlength="255" data-testid="input-name" placeholder="مثال: شامبو الأطفال أو Baby Shampoo">
                <div class="error-message" id="name-error"></div>
            </div>
            
            <div class="form-group">
                <label for="carton_size">عدد القطع في الكرتونة *</label>
                <input type="number" id="carton_size" name="carton_size" required min="1" max="1000" data-testid="input-carton_size" placeholder="12">
                <div class="error-message" id="carton_size-error"></div>
            </div>
            
            <div class="form-group">
                <label for="carton_quantity">عدد الكراتين المطلوب إضافتها *</label>
                <input type="number" id="carton_quantity" name="carton_quantity" required min="1" max="1000" data-testid="input-carton_quantity" placeholder="10">
                <div class="error-message" id="carton_quantity-error"></div>
            </div>
            
            <div class="form-group">
                <label for="warehouse_id">المخزن *</label>
                <select id="warehouse_id" name="warehouse_id" required data-testid="input-warehouse_id">
                    <option value="">اختر المخزن</option>
                    @foreach($warehouses as $warehouse)
                        <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                    @endforeach
                </select>
                <div class="error-message" id="warehouse_id-error"></div>
            </div>
            
            <div class="form-group">
                <label>
                    <input type="checkbox" id="active" name="active" checked data-testid="input-active">
                    منتج مفعّل
                </label>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary" data-testid="btn-save-product" id="submit-btn">
                    <span class="btn-text">
                        <i class="fa fa-save"></i> حفظ المنتج وإضافة المخزون
                    </span>
                    <span class="btn-loading" style="display: none;">
                        <i class="fa fa-spinner fa-spin"></i> جاري الحفظ...
                    </span>
                </button>
                <button type="button" id="cancel-btn" class="btn btn-secondary" data-testid="btn-cancel-product">
                    <i class="fa fa-times"></i> إلغاء
                </button>
            </div>
        </form>
    </div>
</div>

<style>
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
}

.modal-content {
    background: white;
    border-radius: 8px;
    width: 90%;
    max-width: 500px;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    border-bottom: 1px solid #eee;
}

.modal-header h3 {
    margin: 0;
    color: #333;
}

.btn-close {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #999;
}

.btn-close:hover {
    color: #333;
}

.form-group {
    padding: 15px 20px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
    color: #333;
}

.form-group input[type="text"],
.form-group input[type="number"],
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
    font-family: inherit;
}

.form-group select {
    background-color: white;
    cursor: pointer;
}

.form-group textarea {
    resize: vertical;
    min-height: 80px;
}

.form-text {
    color: #6c757d;
    font-size: 12px;
    margin-top: 5px;
    display: block;
}

.form-group input[type="checkbox"] {
    margin-left: 8px;
}

.error-message {
    color: #e74c3c;
    font-size: 12px;
    margin-top: 5px;
    display: none;
}

.form-actions {
    padding: 20px;
    border-top: 1px solid #eee;
    display: flex;
    gap: 10px;
    justify-content: flex-end;
}

.btn {
    padding: 10px 20px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
}

.btn-primary {
    background: #3498db;
    color: white;
}

.btn-primary:hover {
    background: #2980b9;
}

.btn-secondary {
    background: #95a5a6;
    color: white;
}

.btn-secondary:hover {
    background: #7f8c8d;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const createBtn = document.getElementById('create-product-btn');
    const modal = document.getElementById('product-modal');
    const closeBtn = document.getElementById('close-modal');
    const cancelBtn = document.getElementById('cancel-btn');
    const form = document.getElementById('product-form');

    // Show modal
    createBtn.addEventListener('click', function() {
        modal.style.display = 'flex';
        clearErrors();
        form.reset();
        document.getElementById('active').checked = true;
    });

    // Hide modal
    function hideModal() {
        modal.style.display = 'none';
    }

    closeBtn.addEventListener('click', hideModal);
    cancelBtn.addEventListener('click', hideModal);
    
    // Hide modal when clicking outside
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            hideModal();
        }
    });

    // Handle form submission
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        clearErrors();
        setLoadingState(true);

        const formData = {
            name: document.getElementById('name').value,
            carton_size: parseInt(document.getElementById('carton_size').value),
            carton_quantity: parseInt(document.getElementById('carton_quantity').value),
            warehouse_id: parseInt(document.getElementById('warehouse_id').value),
            active: document.getElementById('active').checked
        };

        // Submit via App.fetchJSON
        window.App.fetchJSON('/admin/products', {
            method: 'POST',
            body: JSON.stringify(formData)
        })
        .then(response => {
            if (response.ok) {
                window.App.toast('تم إضافة المنتج بنجاح');
                
                // Play new product sound if requested
                if (response.play_sound === 'new_product') {
                    console.log('🎵 Playing new product sound...');
                    window.App.playNewProductSound();
                }
                
                hideModal();
                // Reload page to update the dashboard
                setTimeout(() => location.reload(), 500);
            }
        })
        .catch(error => {
            if (error.status === 422 && error.errors) {
                // Show validation errors
                for (const [field, messages] of Object.entries(error.errors)) {
                    showError(field, messages[0]);
                }
            } else {
                window.App.toast('حدث خطأ أثناء إضافة المنتج', 'error');
            }
        })
        .finally(() => {
            setLoadingState(false);
        });
    });

    function setLoadingState(loading) {
        const submitBtn = document.getElementById('submit-btn');
        const btnText = submitBtn.querySelector('.btn-text');
        const btnLoading = submitBtn.querySelector('.btn-loading');
        
        if (loading) {
            submitBtn.disabled = true;
            btnText.style.display = 'none';
            btnLoading.style.display = 'inline-flex';
        } else {
            submitBtn.disabled = false;
            btnText.style.display = 'inline-flex';
            btnLoading.style.display = 'none';
        }
    }

    function clearErrors() {
        document.querySelectorAll('.error-message').forEach(el => {
            el.style.display = 'none';
            el.textContent = '';
        });
    }

    function showError(field, message) {
        const errorEl = document.getElementById(field + '-error');
        if (errorEl) {
            errorEl.textContent = message;
            errorEl.style.display = 'block';
        }
    }
});
</script>
