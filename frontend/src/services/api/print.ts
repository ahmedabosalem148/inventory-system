import apiClient from './client';

/**
 * Download blob helper
 * Triggers file download in browser
 */
export const downloadBlob = (blob: Blob, filename: string): void => {
  const url = window.URL.createObjectURL(blob);
  const link = document.createElement('a');
  link.href = url;
  link.download = filename;
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
  window.URL.revokeObjectURL(url);
};

/**
 * Print Service
 * Handles all document printing operations
 */

export interface PrintOptions {
  format?: 'pdf' | 'html';
  template?: 'default' | 'thermal';
}

export interface BulkPrintRequest {
  document_type: 'issue-voucher' | 'return-voucher' | 'purchase-order';
  ids: number[];
  format?: 'pdf';
}

export interface CustomerStatementRequest {
  from_date: string;
  to_date: string;
  format?: 'pdf' | 'html';
}

/**
 * Print Issue Voucher
 * GET /api/v1/print/issue-voucher/{id}
 */
export const printIssueVoucher = async (
  id: number,
  options: PrintOptions = {}
): Promise<void> => {
  try {
    const params = new URLSearchParams();
    if (options.format) params.append('format', options.format);
    if (options.template) params.append('template', options.template);

    const response = await apiClient.get(`/print/issue-voucher/${id}?${params}`, {
      responseType: 'blob',
    });

    const filename = `issue-voucher-${id}.pdf`;
    downloadBlob(response.data, filename);
  } catch (error: any) {
    console.error('Failed to print issue voucher:', error);
    
    // Try to parse error message from blob
    if (error.response?.data instanceof Blob) {
      const text = await error.response.data.text();
      try {
        const json = JSON.parse(text);
        throw new Error(json.message || 'فشل في طباعة إذن الصرف');
      } catch {
        throw new Error('فشل في طباعة إذن الصرف');
      }
    }
    
    throw new Error(error.response?.data?.message || 'فشل في طباعة إذن الصرف');
  }
};

/**
 * Print Return Voucher
 * GET /api/v1/print/return-voucher/{id}
 */
export const printReturnVoucher = async (
  id: number,
  options: PrintOptions = {}
): Promise<void> => {
  try {
    const params = new URLSearchParams();
    if (options.format) params.append('format', options.format);
    if (options.template) params.append('template', options.template);

    const response = await apiClient.get(`/print/return-voucher/${id}?${params}`, {
      responseType: 'blob',
    });

    const filename = `return-voucher-${id}.pdf`;
    downloadBlob(response.data, filename);
  } catch (error: any) {
    console.error('Failed to print return voucher:', error);
    
    if (error.response?.data instanceof Blob) {
      const text = await error.response.data.text();
      try {
        const json = JSON.parse(text);
        throw new Error(json.message || 'فشل في طباعة إذن المرتجع');
      } catch {
        throw new Error('فشل في طباعة إذن المرتجع');
      }
    }
    
    throw new Error(error.response?.data?.message || 'فشل في طباعة إذن المرتجع');
  }
};

/**
 * Print Purchase Order
 * GET /api/v1/print/purchase-order/{id}
 */
export const printPurchaseOrder = async (
  id: number,
  options: PrintOptions = {}
): Promise<void> => {
  try {
    const params = new URLSearchParams();
    if (options.format) params.append('format', options.format);

    const response = await apiClient.get(`/print/purchase-order/${id}?${params}`, {
      responseType: 'blob',
    });

    const filename = `purchase-order-${id}.pdf`;
    downloadBlob(response.data, filename);
  } catch (error: any) {
    console.error('Failed to print purchase order:', error);
    
    if (error.response?.data instanceof Blob) {
      const text = await error.response.data.text();
      try {
        const json = JSON.parse(text);
        throw new Error(json.message || 'فشل في طباعة أمر الشراء');
      } catch {
        throw new Error('فشل في طباعة أمر الشراء');
      }
    }
    
    throw new Error(error.response?.data?.message || 'فشل في طباعة أمر الشراء');
  }
};

/**
 * Print Customer Statement
 * GET /api/v1/print/customer-statement/{customerId}
 */
export const printCustomerStatement = async (
  customerId: number,
  request: CustomerStatementRequest
): Promise<void> => {
  try {
    const params = new URLSearchParams();
    params.append('from_date', request.from_date);
    params.append('to_date', request.to_date);
    if (request.format) params.append('format', request.format);

    const response = await apiClient.get(
      `/print/customer-statement/${customerId}?${params}`,
      { responseType: 'blob' }
    );

    const filename = `customer-statement-${customerId}.pdf`;
    downloadBlob(response.data, filename);
  } catch (error: any) {
    console.error('Failed to print customer statement:', error);
    
    if (error.response?.data instanceof Blob) {
      const text = await error.response.data.text();
      try {
        const json = JSON.parse(text);
        throw new Error(json.message || 'فشل في طباعة كشف الحساب');
      } catch {
        throw new Error('فشل في طباعة كشف الحساب');
      }
    }
    
    throw new Error(error.response?.data?.message || 'فشل في طباعة كشف الحساب');
  }
};

/**
 * Print Cheque
 * GET /api/v1/print/cheque/{id}
 */
export const printCheque = async (id: number): Promise<void> => {
  try {
    const response = await apiClient.get(`/print/cheque/${id}`, {
      responseType: 'blob',
    });

    const filename = `cheque-${id}.pdf`;
    downloadBlob(response.data, filename);
  } catch (error: any) {
    console.error('Failed to print cheque:', error);
    
    if (error.response?.data instanceof Blob) {
      const text = await error.response.data.text();
      try {
        const json = JSON.parse(text);
        throw new Error(json.message || 'فشل في طباعة الشيك');
      } catch {
        throw new Error('فشل في طباعة الشيك');
      }
    }
    
    throw new Error(error.response?.data?.message || 'فشل في طباعة الشيك');
  }
};

/**
 * Bulk Print Documents
 * POST /api/v1/print/bulk
 */
export const bulkPrint = async (request: BulkPrintRequest): Promise<void> => {
  try {
    // Validate max 50 documents
    if (request.ids.length > 50) {
      throw new Error('لا يمكن طباعة أكثر من 50 مستند في المرة الواحدة');
    }

    if (request.ids.length === 0) {
      throw new Error('يجب اختيار مستند واحد على الأقل');
    }

    const response = await apiClient.post('/print/bulk', request, {
      responseType: 'blob',
    });

    const filename = `bulk-print-${request.document_type}-${Date.now()}.pdf`;
    downloadBlob(response.data, filename);
  } catch (error: any) {
    console.error('Failed to bulk print:', error);
    
    if (error.response?.data instanceof Blob) {
      const text = await error.response.data.text();
      try {
        const json = JSON.parse(text);
        throw new Error(json.message || 'فشل في الطباعة الجماعية');
      } catch {
        throw new Error('فشل في الطباعة الجماعية');
      }
    }
    
    throw new Error(error.response?.data?.message || 'فشل في الطباعة الجماعية');
  }
};

/**
 * Export all print functions
 */
export default {
  printIssueVoucher,
  printReturnVoucher,
  printPurchaseOrder,
  printCustomerStatement,
  printCheque,
  bulkPrint,
  downloadBlob,
};
