import React from 'react';
import { Helmet } from 'react-helmet';
import { motion } from 'framer-motion';
import NewTest from '@/pages/NewTest';
import MyTests from '@/pages/MyTests';

export default function Tests() {
  return (
    <>
      <Helmet>
        <title>Mes Tests - Pool Assistant</title>
        <meta name="description" content="Effectuez un nouveau test et consultez l'historique de vos analyses d'eau." />
      </Helmet>
      <motion.div
        initial={{ opacity: 0 }}
        animate={{ opacity: 1 }}
        className="space-y-12"
      >
        <NewTest />
        <div className="border-t border-pool-blue-200 my-8"></div>
        <MyTests />
      </motion.div>
    </>
  );
}