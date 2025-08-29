// Simple loadInventory function replacement
async function loadInventory() {
    try {
        const response = await fetch(`/api/warehouses/${warehouseId}/inventory`);
        const data = await response.json();
        
        if (Array.isArray(data)) {
            renderInventoryTable(data);
        } else {
            document.getElementById('inventory-container').innerHTML = 
                '<div class="empty-state"><h3>خطأ في البيانات</h3></div>';
        }
    } catch (error) {
        document.getElementById('inventory-container').innerHTML = 
            `<div class="empty-state">
                <h3>خطأ في تحميل البيانات</h3>
                <p>${error.message}</p>
                <button onclick="loadInventory()" class="btn btn-primary">إعادة المحاولة</button>
            </div>`;
    }
}
