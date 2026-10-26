import React from 'react';
import PropCard from './PropCard.jsx';
import { Sparkles, Trophy } from 'lucide-react';

export default function FeaturedPropsGrid({ props, onOpenStats }) {
  const featured = props.filter((p) => p.is_featured);

  if (featured.length === 0) {
    return null;
  }

  return (
    <section className="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
      {/* Clean Product Section Header */}
      <div className="flex flex-col sm:flex-row sm:items-end justify-between mb-6 pb-3 border-b border-slate-800">
        <div>
          <div className="inline-flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wider text-brand-cyan mb-1 font-mono">
            <Sparkles className="h-3.5 w-3.5 text-brand-cyan" />
            <span>Mayor Discrepancia & Retorno Esperado</span>
          </div>
          <h2 className="font-display text-2xl font-bold tracking-tight text-white sm:text-3xl">
            Picks Destacados del Día
          </h2>
        </div>
        <p className="text-xs sm:text-sm text-slate-400 font-sans mt-1 sm:mt-0">
          Proposiciones con mayor porcentaje de ventaja (+EV) frente a la línea de apertura.
        </p>
      </div>

      {/* Grid of Featured Cards */}
      <div className="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
        {featured.map((prop) => (
          <PropCard key={prop.prop_id} prop={prop} onOpenStats={onOpenStats} />
        ))}
      </div>
    </section>
  );
}
