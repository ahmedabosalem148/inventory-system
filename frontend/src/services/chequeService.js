import api from './api';

/**
 * Cheque Service
 * إدارة الشيكات - عرض، تصفية، تصفية، إضافة، bounce/clear
 */

const chequeService = {
  /**
   * الحصول على كل الشيكات (pending, overdue, cleared)
   */
  async getAllCheques(params = {}) {
    const response = await api.get('/cheques', { params });
    return response.data;
  },

  /**
   * الحصول على الشيكات المعلقة (Pending)
   */
  async getPendingCheques(params = {}) {
    const response = await api.get('/cheques/pending', { params });
    return response.data;
  },

  /**
   * الحصول على الشيكات المتأخرة (Overdue)
   */
  async getOverdueCheques(params = {}) {
    const response = await api.get('/cheques/overdue', { params });
    return response.data;
  },

  /**
   * الحصول على الشيكات المصرفة (Cleared)
   */
  async getClearedCheques(params = {}) {
    const response = await api.get('/cheques/cleared', { params });
    return response.data;
  },

  /**
   * الحصول على تفاصيل شيك واحد
   */
  async getCheque(id) {
    const response = await api.get(`/cheques/${id}`);
    return response.data;
  },

  /**
   * إضافة شيك جديد
   */
  async createCheque(data) {
    const response = await api.post('/cheques', data);
    return response.data;
  },

  /**
   * تعديل شيك موجود
   */
  async updateCheque(id, data) {
    const response = await api.put(`/cheques/${id}`, data);
    return response.data;
  },

  /**
   * حذف شيك
   */
  async deleteCheque(id) {
    const response = await api.delete(`/cheques/${id}`);
    return response.data;
  },

  /**
   * صرف شيك (Clear)
   */
  async clearCheque(id, data = {}) {
    const response = await api.post(`/cheques/${id}/clear`, data);
    return response.data;
  },

  /**
   * إرجاع شيك (Bounce)
   */
  async bounceCheque(id, data) {
    const response = await api.post(`/cheques/${id}/bounce`, data);
    return response.data;
  },

  /**
   * إحصائيات الشيكات
   */
  async getChequeStats() {
    const response = await api.get('/cheques/stats');
    return response.data;
  },
};

export default chequeService;
