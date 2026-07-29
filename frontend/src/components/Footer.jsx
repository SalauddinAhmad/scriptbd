import React from 'react';

const FOOTER_LINKS = [
  { label: 'মূল্য তালিকা', href: '#pricing' },
  { label: 'টপিকস', href: '#categories' },
  { label: 'কিভাবে কাজ করে', href: '#how-it-works' },
  { label: 'স্যাম্পল', href: '#sample' },
  { label: 'যোগাযোগ', href: '#contact' },
];

function Footer() {
  const handleClick = (href) => {
    if (href.startsWith('#')) {
      const el = document.getElementById(href.slice(1));
      if (el) el.scrollIntoView({ behavior: 'smooth' });
    }
  };

  return (
    <footer className="footer">
      <div className="container">
        <div className="footer-brand">
          <span>ScriptBD</span>
        </div>
        <p style={{ color: 'var(--text-dim)', fontSize: '0.9rem', marginBottom: '1rem' }}>
          বাংলা ভিডিও স্ক্রিপ্ট বিক্রির সেরা প্ল্যাটফর্ম
        </p>

        <div className="footer-links">
          {FOOTER_LINKS.map((link) => (
            <a key={link.href} onClick={() => handleClick(link.href)} style={{ cursor: 'pointer' }}>
              {link.label}
            </a>
          ))}
        </div>

        <div className="footer-social">
          <a href="https://facebook.com/scriptbd" target="_blank" rel="noopener noreferrer" title="Facebook">
            📘
          </a>
          <a href="https://wa.me/8801700000000" target="_blank" rel="noopener noreferrer" title="WhatsApp">
            💬
          </a>
          <a href="mailto:contact@scriptbd.com" title="Email">
            ✉️
          </a>
        </div>

        <p className="footer-copy">
          © {new Date().getFullYear()} ScriptBD. সর্বস্বত্ব সংরক্ষিত।
        </p>
      </div>
    </footer>
  );
}

export default Footer;
