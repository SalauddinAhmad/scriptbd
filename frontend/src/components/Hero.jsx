import React, { useEffect, useRef } from 'react';

const STATS = [
  { number: '৫০০+', label: 'বিক্রিত স্ক্রিপ্ট' },
  { number: '১০০+', label: 'সন্তুষ্ট কাস্টমার' },
  { number: '১M+', label: 'ভিউ জেনারেশন' },
];

function Hero({ onOrderClick, onSampleClick }) {
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
    <section className="hero" ref={sectionRef}>
      <div className="hero-floating-elements">
        <span className="float-element">🎬</span>
        <span className="float-element">📝</span>
        <span className="float-element">🎯</span>
        <span className="float-element">✨</span>
        <span className="float-element">🔥</span>
        <span className="float-element">💡</span>
      </div>

      <div className="container">
        <div className="fade-in">
          <span className="hero-badge">🚀 ভাইরাল স্ক্রিপ্ট এখন বাংলায়</span>
        </div>
        <div className="fade-in">
          <h1>
            আপনার <span className="gradient-text">ভাইরাল কন্টেন্টের</span>{' '}
            জন্য প্রফেশনাল বাংলা স্ক্রিপ্ট
          </h1>
        </div>
        <div className="fade-in">
          <p>
            YouTube Shorts, Facebook Reels, এবং TikTok কন্টেন্টের জন্য
            রেডিমেড বাংলা স্ক্রিপ্ট। অর্ডার করুন এবং পেয়ে যান
            ভাইরাল হওয়ার গ্যারান্টিযুক্ত স্ক্রিপ্ট!
          </p>
        </div>
        <div className="fade-in hero-buttons">
          <button className="btn-primary" onClick={onOrderClick}>
            অর্ডার করুন এখনই
          </button>
          <button className="btn-secondary" onClick={onSampleClick}>
            স্যাম্পল দেখুন
          </button>
        </div>

        <div className="fade-in hero-stats">
          {STATS.map((stat) => (
            <div className="hero-stat" key={stat.label}>
              <div className="stat-number">{stat.number}</div>
              <div className="stat-label">{stat.label}</div>
            </div>
          ))}
        </div>
      </div>
    </section>
  );
}

export default Hero;
