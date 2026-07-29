import React, { useState, useEffect } from 'react';
import { submitOrder } from '../api';
import { useToast } from '../App';

const PLANS = {
  'youtube-shorts': { name: 'YouTube Shorts', price: 400 },
  'facebook-reels': { name: 'Facebook Reels', price: 500 },
  'youtube-full': { name: 'YouTube Full', price: 1000 },
};

const PAYMENT_METHODS = [
  { id: 'bkash', name: 'bKash', icon: '💳', color: '#e2136e' },
  { id: 'nagad', name: 'Nagad', icon: '📱', color: '#e82038' },
  { id: 'rocket', name: 'Rocket', icon: '🚀', color: '#8b3fc7' },
];

const PAYMENT_NUMBERS = {
  bkash: { number: '01700000000', type: 'Personal' },
  nagad: { number: '01700000000', type: 'Personal' },
  rocket: { number: '01700000000', type: 'Personal' },
};

const TOPICS = ['রহস্য','ইমোশনাল ড্রামা','নৈতিক গল্প','কমেডি','মোটিভেশনাল','ইতিহাস','অন্যান্য'];

const INITIAL_FORM = { name:'', email:'', phone:'', plan:'', topic:'', message:'' };

function OrderModal({ isOpen, onClose, selectedPlan }) {
  const [step, setStep] = useState(1); // 1=form, 2=payment_method, 3=trxid, 4=success
  const [form, setForm] = useState(INITIAL_FORM);
  const [loading, setLoading] = useState(false);
  const [orderId, setOrderId] = useState(null);
  const [paymentMethod, setPaymentMethod] = useState(null);
  const [transactionId, setTrxId] = useState('');
  const [submittingPayment, setSubmittingPayment] = useState(false);
  const { addToast } = useToast();

  const selectedPlanInfo = PLANS[form.plan] || PLANS[selectedPlan] || null;

  useEffect(() => {
    if (isOpen && selectedPlan) {
      setForm(prev => ({ ...prev, plan: selectedPlan }));
    }
  }, [isOpen, selectedPlan]);

  useEffect(() => {
    if (isOpen) document.body.style.overflow = 'hidden';
    else document.body.style.overflow = '';
    return () => { document.body.style.overflow = ''; };
  }, [isOpen]);

  // Reset on close
  const handleClose = () => {
    setStep(1);
    setForm(INITIAL_FORM);
    setOrderId(null);
    setPaymentMethod(null);
    setTrxId('');
    onClose();
  };

  if (!isOpen) return null;

  // --- STEP 1: Create Order ---
  const handleOrderSubmit = async (e) => {
    e.preventDefault();
    if (!form.name || !form.phone) {
      addToast('নাম ও ফোন নম্বর আবশ্যক', 'error');
      return;
    }
    if (!form.plan) {
      addToast('প্ল্যান সিলেক্ট করুন', 'error');
      return;
    }

    setLoading(true);
    try {
      const orderData = {
        name: form.name,
        email: form.email,
        phone: form.phone,
        plan: form.plan,
        topic: form.topic || 'অন্যান্য',
        message: form.message,
        amount: PLANS[form.plan]?.price || 0,
        payment_status: 'unpaid',
      };
      const res = await submitOrder(orderData);
      if (res.success) {
        setOrderId(res.order_id);
        addToast('অর্ডার তৈরি হয়েছে! এখন পেমেন্ট সম্পন্ন করুন।', 'success');
        setStep(2);
      }
    } catch (err) {
      addToast(err.response?.data?.message || 'অর্ডার তৈরি করতে সমস্যা হয়েছে', 'error');
    } finally {
      setLoading(false);
    }
  };

  // --- STEP 2: Select Payment Method ---
  const handleMethodSelect = (method) => {
    setPaymentMethod(method);
    setStep(3);
  };

  // --- STEP 3: Submit TrxID ---
  const handlePaymentSubmit = async (e) => {
    e.preventDefault();
    if (!transactionId.trim()) {
      addToast('ট্রানজেকশন আইডি দিন', 'error');
      return;
    }

    setSubmittingPayment(true);
    try {
      const { default: api } = await import('../api');
      const res = await api.post('/payments/verify.php', {
        order_id: orderId,
        transaction_id: transactionId.trim(),
        payment_method: paymentMethod.id,
      });
      if (res.data?.success) {
        setStep(4);
        addToast('পেমেন্ট জমা হয়েছে! আমরা ভেরিফাই করে কনফার্ম করবো।', 'success');
      } else {
        addToast(res.data?.message || 'পেমেন্ট জমা দিতে সমস্যা হয়েছে', 'error');
      }
    } catch (err) {
      addToast('পেমেন্ট জমা দিতে সমস্যা হয়েছে', 'error');
    } finally {
      setSubmittingPayment(false);
    }
  };

  const handleOverlayClick = (e) => {
    if (e.target === e.currentTarget && step !== 3) handleClose();
  };

  // --- RENDER: STEP 1 — Order Form ---
  const renderStep1 = () => (
    <>
      <button className="modal-close" onClick={handleClose} aria-label="Close">✕</button>
      <h2 className="gradient-text">অর্ডার ফর্ম</h2>
      {selectedPlanInfo && (
        <div className="modal-plan-badge">
          <span className="plan-badge-icon">📋</span>
          <span>{selectedPlanInfo.name}</span>
          <span className="plan-badge-price">৳{selectedPlanInfo.price}</span>
        </div>
      )}
      <p className="modal-subtitle">তথ্যগুলো পূরণ করে অর্ডার নিশ্চিত করুন</p>

      <form onSubmit={handleOrderSubmit}>
        <div className="form-group">
          <label>প্ল্যান *</label>
          <select name="plan" value={form.plan} onChange={e => setForm({...form, plan: e.target.value})} required>
            <option value="">-- প্ল্যান সিলেক্ট --</option>
            {Object.entries(PLANS).map(([k, v]) => (
              <option key={k} value={k}>{v.name} — ৳{v.price}</option>
            ))}
          </select>
        </div>
        <div className="form-group">
          <label>আপনার নাম *</label>
          <input type="text" name="name" value={form.name} onChange={e => setForm({...form, name: e.target.value})} placeholder="আপনার পূর্ণ নাম" required />
        </div>
        <div className="form-group">
          <label>ইমেইল</label>
          <input type="email" name="email" value={form.email} onChange={e => setForm({...form, email: e.target.value})} placeholder="example@gmail.com" />
        </div>
        <div className="form-group">
          <label>ফোন (WhatsApp) *</label>
          <input type="tel" name="phone" value={form.phone} onChange={e => setForm({...form, phone: e.target.value})} placeholder="০১৭XXXXXXXX" required />
        </div>
        <div className="form-group">
          <label>টপিক</label>
          <select name="topic" value={form.topic} onChange={e => setForm({...form, topic: e.target.value})}>
            <option value="">-- টপিক বাছাই --</option>
            {TOPICS.map(t => <option key={t} value={t}>{t}</option>)}
          </select>
        </div>
        <div className="form-group">
          <label>বিবরণ</label>
          <textarea name="message" value={form.message} onChange={e => setForm({...form, message: e.target.value})} placeholder="কী ধরনের স্ক্রিপ্ট দরকার..." rows={3} />
        </div>
        <button type="submit" className="submit-btn" disabled={loading}>
          {loading ? '⏳ প্রসেসিং...' : 'অর্ডার কনফার্ম করুন →'}
        </button>
      </form>
    </>
  );

  // --- RENDER: STEP 2 — Choose Payment Method ---
  const renderStep2 = () => (
    <>
      <button className="modal-close" onClick={handleClose} aria-label="Close">✕</button>
      <h2 className="gradient-text">পেমেন্ট মেথড সিলেক্ট করুন</h2>
      <div className="modal-plan-badge">
        <span className="plan-badge-icon">✅</span>
        <span>অর্ডার #{orderId}</span>
        <span className="plan-badge-price">৳{selectedPlanInfo?.price || '—'}</span>
      </div>
      <p className="modal-subtitle">Send Money-এর মাধ্যমে পেমেন্ট করুন</p>

      <div className="payment-instructions">
        <p>📌 <strong>কিভাবে পেমেন্ট করবেন:</strong></p>
        <ol>
          <li>নিচের যেকোনো নম্বরে Send Money করুন</li>
          <li>ট্রানজেকশন আইডি (TrxID) কপি করে রাখুন</li>
          <li>পরের ধাপে TrxID দিন</li>
        </ol>
      </div>

      <div className="payment-methods-grid">
        {PAYMENT_METHODS.map(m => (
          <button key={m.id} className="payment-method-card" onClick={() => handleMethodSelect(m)}>
            <span className="pm-icon">{m.icon}</span>
            <span className="pm-name">{m.name}</span>
            <span className="pm-number">{PAYMENT_NUMBERS[m.id]?.number}</span>
            <span className="pm-type">{PAYMENT_NUMBERS[m.id]?.type}</span>
          </button>
        ))}
      </div>

      <p className="modal-alt-contact">
        অথবা <a href="https://wa.me/8801700000000" target="_blank" rel="noopener">WhatsApp</a> /
        <a href="https://facebook.com/scriptbd" target="_blank" rel="noopener"> Facebook</a>-এ সরাসরি যোগাযোগ করুন
      </p>
    </>
  );

  // --- RENDER: STEP 3 — Enter TrxID ---
  const renderStep3 = () => {
    const method = paymentMethod;
    return (
      <>
        <button className="modal-close" onClick={() => setStep(2)} aria-label="Back">←</button>
        <h2 className="gradient-text">{method.icon} {method.name} পেমেন্ট</h2>
        <div className="modal-plan-badge">
          <span>অর্ডার #{orderId}</span>
          <span className="plan-badge-price">৳{selectedPlanInfo?.price || '—'}</span>
        </div>

        <div className="payment-send-box">
          <p className="send-label">Send Money করুন এই নম্বরে:</p>
          <div className="send-number">{PAYMENT_NUMBERS[method.id]?.number}</div>
          <p className="send-type">{PAYMENT_NUMBERS[method.id]?.type} Account</p>
        </div>

        <form onSubmit={handlePaymentSubmit}>
          <div className="form-group">
            <label>ট্রানজেকশন আইডি (TrxID) *</label>
            <input
              type="text"
              value={transactionId}
              onChange={e => setTrxId(e.target.value)}
              placeholder="যেমন: TRX123456789"
              className="trxid-input"
              required
              autoFocus
            />
            <p className="field-hint">{method.name} অ্যাপ থেকে TrxID কপি করে পেস্ট করুন</p>
          </div>
          <button type="submit" className="submit-btn" disabled={submittingPayment}>
            {submittingPayment ? '⏳ জমা হচ্ছে...' : 'পেমেন্ট সাবমিট করুন ✅'}
          </button>
        </form>
        <p className="modal-alt-contact">
          <button onClick={() => setStep(2)} className="btn-back-link">← ভিন্ন পেমেন্ট মেথড</button>
        </p>
      </>
    );
  };

  // --- RENDER: STEP 4 — Success ---
  const renderStep4 = () => (
    <>
      <h2 className="gradient-text" style={{textAlign:'center'}}>🎉 অর্ডার কনফার্ম!</h2>
      <div className="success-box">
        <div className="success-icon">✅</div>
        <h3>অর্ডার #{orderId} — ৳{selectedPlanInfo?.price || '—'}</h3>
        <p className="success-plan">{selectedPlanInfo?.name}</p>
        <div className="success-details">
          <p>🧾 <strong>ট্রানজেকশন:</strong> {transactionId}</p>
          <p>💳 <strong>পেমেন্ট মেথড:</strong> {paymentMethod?.name}</p>
          <p>📱 <strong>ফোন:</strong> {form.phone}</p>
        </div>
        <p className="success-note">আমরা ২৪ ঘণ্টার মধ্যে পেমেন্ট ভেরিফাই করে আপনার ইমেইল/WhatsApp-এ স্ক্রিপ্ট পাঠিয়ে দেবো।</p>
        <div className="success-contact">
          <a href="https://wa.me/8801700000000" target="_blank" rel="noopener" className="btn-whatsapp">💬 WhatsApp</a>
          <a href="https://facebook.com/scriptbd" target="_blank" rel="noopener" className="btn-facebook">👍 Facebook</a>
        </div>
      </div>
      <button className="submit-btn" onClick={handleClose} style={{marginTop:'1.5rem'}}>ঠিক আছে</button>
    </>
  );

  return (
    <div className="modal-overlay" onClick={handleOverlayClick}>
      <div className={`modal ${step >= 2 ? 'modal-wide' : ''}`}>
        {step === 1 && renderStep1()}
        {step === 2 && renderStep2()}
        {step === 3 && renderStep3()}
        {step === 4 && renderStep4()}
      </div>
    </div>
  );
}

export default OrderModal;
