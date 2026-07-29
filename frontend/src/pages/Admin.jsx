import React, { useState, useEffect, useCallback } from 'react';
import { useNavigate } from 'react-router-dom';
import { getOrders, updateOrderStatus, deleteOrder } from '../api';
import { useToast } from '../App';

const STATUS_LABELS = {
  pending: 'পেন্ডিং',
  processing: 'প্রসেসিং',
  completed: 'সম্পন্ন',
  cancelled: 'বাতিল',
};

function Admin() {
  const [orders, setOrders] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [authChecked, setAuthChecked] = useState(false);
  const navigate = useNavigate();
  const { addToast } = useToast();

  const fetchOrders = useCallback(async () => {
    try {
      setLoading(true);
      const data = await getOrders();
      if (data.success === false && data.message?.includes('Unauthorized')) {
        setError('অনুগ্রহ করে অ্যাডমিন প্যানেলে লগইন করুন');
        return;
      }
      setOrders(Array.isArray(data.data) ? data.data : data.orders || []);
      setError(null);
      setAuthChecked(true);
    } catch (err) {
      if (err.response?.status === 401) {
        setError('অনুগ্রহ করে অ্যাডমিন প্যানেলে লগইন করুন');
      } else {
        setError('অর্ডার লোড করতে সমস্যা হয়েছে');
      }
      console.error(err);
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    fetchOrders();
  }, [fetchOrders]);

  const handleStatusChange = async (id, status) => {
    try {
      await updateOrderStatus(id, status);
      setOrders((prev) =>
        prev.map((o) => (o.id === id || o._id === id ? { ...o, status } : o))
      );
      addToast(`অর্ডার স্ট্যাটাস আপডেট হয়েছে: ${STATUS_LABELS[status] || status}`, 'success');
    } catch (err) {
      addToast('স্ট্যাটাস আপডেট করতে ব্যর্থ হয়েছে', 'error');
    }
  };

  const handleDelete = async (id) => {
    if (!window.confirm('আপনি কি নিশ্চিত এই অর্ডারটি ডিলিট করতে চান?')) return;
    try {
      await deleteOrder(id);
      setOrders((prev) => prev.filter((o) => o.id !== id && o._id !== id));
      addToast('অর্ডার ডিলিট হয়েছে', 'success');
    } catch (err) {
      addToast('অর্ডার ডিলিট করতে ব্যর্থ হয়েছে', 'error');
    }
  };

  const STATUS_OPTIONS = ['pending', 'processing', 'completed', 'cancelled'];

  if (loading) {
    return (
      <div className="admin-page">
        <div className="admin-loading">
          <div className="admin-spinner"></div>
        </div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="admin-page">
        <div className="container">
          <div className="admin-empty">
            <p>{error}</p>
            <div style={{ display: 'flex', gap: '1rem', justifyContent: 'center', marginTop: '1.5rem' }}>
              <button className="admin-back-btn" onClick={fetchOrders}>
                আবার চেষ্টা করুন
              </button>
              <button className="admin-back-btn" onClick={() => navigate('/')}>
                ← হোম
              </button>
            </div>
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="admin-page">
      <div className="container">
        <div className="admin-header">
          <h1 className="gradient-text">📋 অর্ডার ম্যানেজমেন্ট</h1>
          <div style={{ display: 'flex', gap: '1rem' }}>
            <button className="admin-back-btn" onClick={fetchOrders}>
              🔄 রিফ্রেশ
            </button>
            <button className="admin-back-btn" onClick={() => navigate('/')}>
              ← হোম
            </button>
          </div>
        </div>

        {orders.length === 0 ? (
          <div className="admin-empty">
            <p>এখনো কোনো অর্ডার জমা হয়নি।</p>
          </div>
        ) : (
          <div className="admin-table-wrapper">
            <table className="admin-table">
              <thead>
                <tr>
                  <th>আইডি</th>
                  <th>তারিখ</th>
                  <th>নাম</th>
                  <th>ইমেইল</th>
                  <th>ফোন</th>
                  <th>প্ল্যান</th>
                  <th>টপিক</th>
                  <th>মেসেজ</th>
                  <th>স্ট্যাটাস</th>
                  <th>অ্যাকশন</th>
                </tr>
              </thead>
              <tbody>
                {orders.map((order) => (
                  <tr key={order.id || order._id}>
                    <td>#{order.id || order._id}</td>
                    <td>
                      {order.created_at
                        ? new Date(order.created_at).toLocaleDateString('bn-BD')
                        : '-'}
                    </td>
                    <td>{order.name}</td>
                    <td>{order.email || '-'}</td>
                    <td>{order.phone}</td>
                    <td style={{ textTransform: 'capitalize' }}>
                      {(order.plan || '').replace(/-/g, ' ')}
                    </td>
                    <td>{order.topic || '-'}</td>
                    <td style={{ maxWidth: '200px', overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>
                      {order.message || '-'}
                    </td>
                    <td>
                      <span className={`admin-status ${order.status || 'pending'}`}>
                        {STATUS_LABELS[order.status] || order.status || 'পেন্ডিং'}
                      </span>
                    </td>
                    <td>
                      <div className="admin-actions">
                        <select
                          value={order.status || 'pending'}
                          onChange={(e) =>
                            handleStatusChange(order.id || order._id, e.target.value)
                          }
                          style={{
                            background: 'var(--bg-surface)',
                            border: '1px solid var(--border)',
                            color: 'var(--text-primary)',
                            borderRadius: '6px',
                            padding: '0.25rem 0.5rem',
                            fontFamily: 'var(--font-bengali)',
                            fontSize: '0.8rem',
                            cursor: 'pointer',
                          }}
                        >
                          {STATUS_OPTIONS.map((s) => (
                            <option key={s} value={s}>{STATUS_LABELS[s]}</option>
                          ))}
                        </select>
                        <button
                          className="danger"
                          onClick={() => handleDelete(order.id || order._id)}
                        >
                          🗑️
                        </button>
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </div>
    </div>
  );
}

export default Admin;
