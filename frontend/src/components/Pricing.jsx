import React, { useState } from 'react';

const PLANS = [
  {
    key: 'youtube-shorts',
    title: 'YouTube Shorts',
    price: '৪০০',
    desc: 'YouTube Shorts-এর জন্য পারফেক্ট স্ক্রিপ্ট',
    icon: '📱',
    features: [
      '১-২ মিনিটের স্ক্রিপ্ট',
      'ভাইরাল আইডিয়া সহ',
      'হুক ও CTA অন্তর্ভুক্ত',
      '২৪ ঘন্টায় ডেলিভারি',
      '১টি ফ্রি রিভিশন',
    ],
    featured: false,
  },
  {
    key: 'facebook-reels',
    title: 'Facebook Reels',
    price: '৫০০',
    desc: 'Facebook Reels-এর জন্য অপটিমাইজড স্ক্রিপ্ট',
    icon: '🎥',
    features: [
      '২-৩ মিনিটের স্ক্রিপ্ট',
      'ইমোশনাল স্টোরি ফরম্যাট',
      'ভাইরাল ট্রিগার সহ',
      '৪৮ ঘন্টায় ডেলিভারি',
      '২টি ফ্রি রিভিশন',
      'স্পেশাল ক্যারেক্টার ডেভেলপমেন্ট',
    ],
    featured: true,
  },
  {
    key: 'youtube-full',
    title: 'YouTube Full',
    price: '১০০০',
    desc: 'লং-ফর্ম YouTube ভিডিওর জন্য কমপ্লিট স্ক্রিপ্ট',
    icon: '🎬',
    features: [
      '৮-১২ মিনিটের স্ক্রিপ্ট',
      'ডিটেইল্ড রিসার্চ সহ',
      'সিন ও সেগমেন্ট ব্রেকডাউন',
      '৭২ ঘন্টায় ডেলিভারি',
      'আনলিমিটেড রিভিশন',
      'SEO অপটিমাইজড টাইটেল',
    ],
    featured: false,
  },
];

function Pricing({ onOrderClick }) {
  const sectionRef = React.useRef(null);

  React.useEffect(() => {
    const observer = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            entry.target.querySelectorAll('.fade-in').forEach((el) => el.classList.add('visible'));
          }
        });
      },
      { threshold: 0.1 }
    );
    if (sectionRef.current) observer.observe(sectionRef.current);
    return () => observer.disconnect();
  }, []);

  return (
    <section className="pricing-section" id="pricing" ref={sectionRef}>
      <div className="container">
        <div className="fade-in">
          <h2 className="section-title">মূল্য তালিকা</h2>
          <p className="section-subtitle">আপনার প্রয়োজন অনুযায়ী সেরা প্যাকেজ বেছে নিন</p>
        </div>

        <div className="pricing-grid">
          {PLANS.map((plan, i) => (
            <div
              key={i}
              className={`pricing-card fade-in ${plan.featured ? 'featured' : ''}`}
              style={{ transitionDelay: `${i * 0.15}s` }}
            >
              {plan.featured && <span className="pricing-badge">সর্বাধিক জনপ্রিয়</span>}
              <div className="pricing-icon">{plan.icon}</div>
              <h3>{plan.title}</h3>
              <p className="pricing-desc">{plan.desc}</p>
              <div className="pricing-price">
                <span className="currency">৳</span>{plan.price}
              </div>
              <ul className="pricing-features">
                {plan.features.map((f, j) => (
                  <li key={j}>{f}</li>
                ))}
              </ul>
              <button className="pricing-btn" onClick={() => onOrderClick(plan.key)}>
                অর্ডার করুন
              </button>
            </div>
          ))}
        </div>
      </div>
    </section>
  );
}

export default Pricing;
