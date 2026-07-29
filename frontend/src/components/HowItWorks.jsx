import React, { useEffect, useRef } from 'react';

const STEPS = [
  {
    number: '১',
    title: 'অর্ডার করুন',
    desc: 'আপনার পছন্দের প্যাকেজ ও টপিক সিলেক্ট করে আমাদের অর্ডার ফর্ম পূরণ করুন। সহজ ও দ্রুত প্রক্রিয়া।',
  },
  {
    number: '২',
    title: 'স্ক্রিপ্ট লেখা',
    desc: 'আমাদের অভিজ্ঞ টিম আপনার প্রয়োজন অনুযায়ী প্রফেশনাল স্ক্রিপ্ট তৈরি করে। প্রতিটি স্ক্রিপ্ট ভাইরাল হওয়ার ফর্মুলা অনুযায়ী লেখা হয়।',
  },
  {
    number: '৩',
    title: 'ডেলিভারি',
    desc: 'নির্ধারিত সময়ের মধ্যে আপনার ইমেইল বা WhatsApp-এ স্ক্রিপ্ট পেয়ে যান। প্রয়োজন অনুযায়ী রিভিশনের সুবিধাও রয়েছে।',
  },
];

function HowItWorks() {
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
    <section className="how-section" id="how-it-works" ref={sectionRef}>
      <div className="container">
        <div className="fade-in">
          <h2 className="section-title">কিভাবে কাজ করে</h2>
          <p className="section-subtitle">তিনটি সহজ ধাপে আপনার কাঙ্ক্ষিত স্ক্রিপ্ট পান</p>
        </div>

        <div className="how-steps">
          {STEPS.map((step, i) => (
            <div
              key={i}
              className="step-card fade-in"
              style={{ transitionDelay: `${i * 0.2}s` }}
            >
              <div className="step-number">{step.number}</div>
              <h3>{step.title}</h3>
              <p>{step.desc}</p>
            </div>
          ))}
        </div>
      </div>
    </section>
  );
}

export default HowItWorks;
