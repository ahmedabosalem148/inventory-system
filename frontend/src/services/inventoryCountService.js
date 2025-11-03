import axios from '../utils/axios';

/**
 * Inventory Count Service
 * Handles all API calls related to inventory counting/cycle counting
 */
const inventoryCountService = {
  /**
   * Get all inventory counts with pagination and filtering
   */
  getAll: async (params = {}) => {
    const response = await axios.get('/inventory-counts', { params });
    return response.data;
  },

  /**
   * Get a single inventory count by ID
   */
  getById: async (id) => {
    const response = await axios.get(`/inventory-counts/${id}`);
    return response.data;
  },

  /**
   * Create a new inventory count
   */
  create: async (data) => {
    const response = await axios.post('/inventory-counts', data);
    return response.data;
  },

  /**
   * Update an existing inventory count
   */
  update: async (id, data) => {
    const response = await axios.put(`/inventory-counts/${id}`, data);
    return response.data;
  },

  /**
   * Delete an inventory count
   */
  delete: async (id) => {
    const response = await axios.delete(`/inventory-counts/${id}`);
    return response.data;
  },

  /**
   * Submit count for approval
   */
  submit: async (id) => {
    const response = await axios.post(`/inventory-counts/${id}/submit`);
    return response.data;
  },

  /**
   * Approve count and adjust stock
   */
  approve: async (id) => {
    const response = await axios.post(`/inventory-counts/${id}/approve`);
    return response.data;
  },

  /**
   * Reject count with reason
   */
  reject: async (id, rejectionReason) => {
    const response = await axios.post(`/inventory-counts/${id}/reject`, {
      rejection_reason: rejectionReason
    });
    return response.data;
  }
};

export default inventoryCountService;
