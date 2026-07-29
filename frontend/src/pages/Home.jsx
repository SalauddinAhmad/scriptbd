import React, { useState } from 'react';
import Navbar from '../components/Navbar';
import Hero from '../components/Hero';
import Pricing from '../components/Pricing';
import Categories from '../components/Categories';
import HowItWorks from '../components/HowItWorks';
import SampleScript from '../components/SampleScript';
import ContactCTA from '../components/ContactCTA';
import Footer from '../components/Footer';
import OrderModal from '../components/OrderModal';

function Home() {
  const [modalOpen, setModalOpen] = useState(false);
  const [selectedPlan, setSelectedPlan] = useState('');

  const openOrderModal = (plan) => {
    setSelectedPlan(plan || '');
    setModalOpen(true);
  };

  const scrollToSample = () => {
    const el = document.getElementById('sample');
    if (el) el.scrollIntoView({ behavior: 'smooth' });
  };

  return (
    <>
      <Navbar onOrderClick={() => openOrderModal('')} />
      <Hero onOrderClick={() => openOrderModal('')} onSampleClick={scrollToSample} />
      <Pricing onOrderClick={openOrderModal} />
      <Categories onOrderClick={() => openOrderModal('')} />
      <HowItWorks />
      <SampleScript />
      <ContactCTA onOrderClick={() => openOrderModal('')} />
      <Footer />
      <OrderModal isOpen={modalOpen} onClose={() => setModalOpen(false)} selectedPlan={selectedPlan} />
    </>
  );
}

export default Home;