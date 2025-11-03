import axios from '../utils/axios';

/**
 * Branch Service
 * Handles all API calls related to branches/warehouses management
 */
const branchService = {
  /**
   * Get all branches with pagination, filtering, and sorting
   * @param {Object} params - Query parameters
   * @returns {Promise} API response
   */
  getAll: async (params = {}) => {
    const response = await axios.get('/branches', { params });
    return response.data;
  },

  /**
   * Get a single branch by ID
   * @param {number} id - Branch ID
   * @returns {Promise} API response
   */
  getById: async (id) => {
    const response = await axios.get(`/branches/${id}`);
    return response.data;
  },

  /**
   * Create a new branch
   * @param {Object} data - Branch data
   * @returns {Promise} API response
   */
  create: async (data) => {
    const response = await axios.post('/branches', data);
    return response.data;
  },

  /**
   * Update an existing branch
   * @param {number} id - Branch ID
   * @param {Object} data - Updated branch data
   * @returns {Promise} API response
   */
  update: async (id, data) => {
    const response = await axios.put(`/branches/${id}`, data);
    return response.data;
  },

  /**
   * Delete a branch
   * @param {number} id - Branch ID
   * @returns {Promise} API response
   */
  delete: async (id) => {
    const response = await axios.delete(`/branches/${id}`);
    return response.data;
  },

  /**
   * Get branch stock summary
   * @param {number} id - Branch ID
   * @returns {Promise} API response with stock data
   */
  getStockSummary: async (id) => {
    const response = await axios.get(`/branches/${id}/stock`);
    return response.data;
  }
};

export default branchService;
