import React, { useEffect, useRef } from 'react';

const CATEGORIES = [
  { icon: '🔍', title: 'রহস্য', desc: 'থ্রিলার ও সাসপেন্স স্ক্রিপ্ট যা দর্শকদের শেষ পর্যন্ত আটকে রাখে' },
  { icon: '😢', title: 'ইমোশনাল ড্রামা', desc: 'হৃদয়স্পর্শী গল্প যা ভাইরাল হওয়ার সম্ভাবনা বেশি' },
  { icon: '📖', title: 'নৈতিক গল্প', desc: 'শিক্ষণীয় ও নৈতিক মূল্যবোধ সম্পন্ন গল্পের স্ক্রিপ্ট' },
  { icon: '😂', title: 'কমেডি', desc: 'মজার ও হাস্যরসপূর্ণ স্ক্রিপ্ট যা মানুষ শেয়ার করতে ভালোবাসে' },
  { icon: '💪', title: 'মোটিভেশনাল', desc: 'অনুপ্রেরণামূলক স্ক্রিপ্ট যা দর্শকদের উৎসাহিত করে' },
  { icon: '🏛️', title: 'ইতিহাস', desc: 'ঐতিহাসিক ঘটনা ও ব্যক্তিত্ব নিয়ে আকর্ষণীয় স্ক্রিপ্ট' },
];

function Categories({ onOrderClick }) {
  const sectionRef = useRef(null);

  useEffect(() => {
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
    <section className="categories-section" id="categories" ref={sectionRef}>
      <div className="container">
        <div className="fade-in">
          <h2 className="section-title">ক্যাটাগরি ও টপিকস</h2>
          <p className="section-subtitle">যেকোনো ধরনের কন্টেন্টের জন্য আমাদের কাছে আছে পারফেক্ট স্ক্রিপ্ট</p>
        </div>

        <div className="categories-grid">
          {CATEGORIES.map((cat, i) => (
            <div
              key={i}
              className="category-card fade-in"
              style={{ transitionDelay: `${i * 0.1}s` }}
              onClick={onOrderClick}
            >
              <span className="category-icon">{cat.icon}</span>
              <h3>{cat.title}</h3>
              <p>{cat.desc}</p>
            </div>
          ))}
        </div>
      </div>
    </section>
  );
}

export default Categories;
