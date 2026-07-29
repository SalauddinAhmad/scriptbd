import React, { useState, useEffect } from 'react';

const NAV_LINKS = [
  { label: 'মূল্য তালিকা', href: '#pricing' },
  { label: 'টপিকস', href: '#categories' },
  { label: 'কিভাবে কাজ করে', href: '#how-it-works' },
  { label: 'স্যাম্পল', href: '#sample' },
  { label: 'যোগাযোগ', href: '#contact' },
];

function Navbar({ onOrderClick }) {
  const [scrolled, setScrolled] = useState(false);
  const [menuOpen, setMenuOpen] = useState(false);

  useEffect(() => {
    const handleScroll = () => setScrolled(window.scrollY > 50);
    window.addEventListener('scroll', handleScroll);
    return () => window.removeEventListener('scroll', handleScroll);
  }, []);

  const handleNavClick = (href) => {
    setMenuOpen(false);
    if (href.startsWith('#')) {
      const el = document.getElementById(href.slice(1));
      if (el) el.scrollIntoView({ behavior: 'smooth' });
    }
  };

  return (
    <nav className={`navbar ${scrolled ? 'glass' : ''}`}>
      <div className="container">
        <div className="nav-logo" onClick={() => window.scrollTo({ top: 0, behavior: 'smooth' })}>
          <div className="logo-icon">✍️</div>
          <span className="gradient-text">ScriptBD</span>
        </div>

        <ul className={`nav-links ${menuOpen ? 'open' : ''}`}>
          {NAV_LINKS.map((link) => (
            <li key={link.href}>
              <a onClick={() => handleNavClick(link.href)}>{link.label}</a>
            </li>
          ))}
          <li>
            <a className="nav-btn" onClick={() => { setMenuOpen(false); onOrderClick?.(); }}>
              অর্ডার করুন
            </a>
          </li>
        </ul>

        <button
          className={`hamburger ${menuOpen ? 'open' : ''}`}
          onClick={() => setMenuOpen(!menuOpen)}
          aria-label="Toggle menu"
        >
          <span></span>
          <span></span>
          <span></span>
        </button>
      </div>
    </nav>
  );
}

export default Navbar;
