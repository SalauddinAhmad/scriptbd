import React, { useEffect, useRef } from 'react';

function SampleScript() {
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
    <section className="sample-section" id="sample" ref={sectionRef}>
      <div className="container">
        <div className="fade-in">
          <h2 className="section-title">স্যাম্পল স্ক্রিপ্ট</h2>
          <p className="section-subtitle">আমাদের স্ক্রিপ্টের একটি নমুনা দেখুন</p>
        </div>

        <div className="sample-box fade-in">
          <div className="sample-header">
            <span className="dot"></span>
            <span className="dot"></span>
            <span className="dot"></span>
            <span className="sample-title">নৈতিক গল্প • ইমোশনাল</span>
          </div>
          <div className="sample-content">
            <p>
              <span className="highlight">[হুক - ০-৫ সেকেন্ড]</span>
            </p>
            <p>
              "একজন বাবা তার ছেলেকে জীবনের সবচেয়ে বড় শিক্ষা দিলেন মাত্র ২ মিনিটে..."
            </p>
            <p>&nbsp;</p>
            <p>
              <span className="voice">[ভয়েসওভার]</span> রফিক সাহেব ছিলেন একজন সাধারণ
              দিনমজুর। সারাদিন খেটে তিনি যা আয় করতেন, তা দিয়েই চালাতেন তার ছোট
              সংসার। তার একমাত্র ছেলে রাতুল পড়তো ক্লাস টেনে।
            </p>
            <p>&nbsp;</p>
            <p>
              <span className="dialogue">রাতুল:</span> "আব্বু, আমার ক্লাসের সব
              বন্ধুদের কাছে দামি মোবাইল আছে। আমারটাও একটা কিনে দাও না!"
            </p>
            <p>&nbsp;</p>
            <p>
              <span className="voice">[ভয়েসওভার]</span> রফিক সাহেব কিছু বললেন না।
              পরের দিন তিনি রাতুলকে নিয়ে গেলেন তার কাজের জায়গায়...
            </p>
            <p>&nbsp;</p>
            <p>
              <span className="highlight">[CTA - শেষ ৫ সেকেন্ড]</span>
            </p>
            <p>
              "এই গল্পটি আপনার কেমন লাগলো? ❤️ লাইক ও শেয়ার করতে ভুলবেন না!"
            </p>
          </div>
        </div>
      </div>
    </section>
  );
}

export default SampleScript;
