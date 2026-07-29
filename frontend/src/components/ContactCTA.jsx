import React, { useEffect, useRef } from 'react';

function ContactCTA({ onOrderClick }) {
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
    <section className="contact-section" id="contact" ref={sectionRef}>
      <div className="container">
        <div className="contact-card fade-in">
          <h2 className="gradient-text">আপনার ভাইরাল জার্নি শুরু হোক আজই!</h2>
          <p>
            অর্ডার করতে বা যেকোনো প্রশ্নের জন্য আমাদের সাথে যোগাযোগ করুন।
            আমরা ২৪ ঘন্টার মধ্যে রেসপন্ড করি।
          </p>

          <div className="contact-buttons">
            <a
              href="https://wa.me/8801700000000"
              target="_blank"
              rel="noopener noreferrer"
              className="btn-whatsapp"
            >
              💬 WhatsApp
            </a>
            <a
              href="https://facebook.com/scriptbd"
              target="_blank"
              rel="noopener noreferrer"
              className="btn-facebook"
            >
              👍 Facebook
            </a>
            <button className="btn-primary" onClick={onOrderClick}>
              📝 অর্ডার ফর্ম
            </button>
          </div>
        </div>
      </div>
    </section>
  );
}

export default ContactCTA;
