import axios from 'axios';

const API_KEY = '***';

const api = axios.create({
  baseURL: '/backend/api',
  timeout: 10000,
  headers: {
    'Content-Type': 'application/json',
    'X-API-Key': API_KEY,
  },
});

export const submitOrder = async (orderData) => {
  const response = await api.post('/orders/create.php', orderData);
  return response.data;
};

export const verifyPayment = async (orderId, transactionId, paymentMethod) => {
  const response = await api.post('/payments/verify.php', {
    order_id: orderId,
    transaction_id: transactionId,
    payment_method: paymentMethod,
  });
  return response.data;
};

export const getPaymentSettings = async () => {
  const response = await api.get('/payments/settings.php');
  return response.data;
};

export const getOrders = async () => {
  const response = await api.get('/orders/list.php');
  return response.data;
};

export const updateOrderStatus = async (id, status) => {
  const response = await api.post('/orders/update.php', { id, status });
  return response.data;
};

export const deleteOrder = async (id) => {
  const response = await api.post('/orders/delete.php', { id });
  return response.data;
};

export const adminLogin = async (username, password) => {
  const response = await api.post('/auth/login.php', { username, password });
  return response.data;
};

export default api;
