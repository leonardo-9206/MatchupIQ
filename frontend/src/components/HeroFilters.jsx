import React from 'react';
import { Search, SlidersHorizontal, CheckCircle2, TrendingUp, Trophy } from 'lucide-react';

export default function HeroFilters({
  selectedSport,
  onSelectSport,
  searchQuery,
  onSearchChange,
  totalPropsCount,
  featuredCount,
}) {
  return (
    <section className="relative overflow-hidden pt-8 pb-6 border-b border-slate-800/60 bg-gradient-to-b from-[#070B16] to-[#050811]">
      {/* Background ambient lighting */}
      <div className="pointer-events-none absolute -top-24 left-1/2 h-72 w-[600px] -translate-x-1/2 rounded-full bg-brand-cyan/10 blur-3xl" />
      <div className="pointer-events-none absolute -top-20 right-10 h-64 w-64 rounded-full bg-brand-blue/15 blur-3xl" />

      <div className="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        
        {/* Main Clean Headline */}
        <div className="text-center max-w-3xl mx-auto mb-8">
          <div className="inline-flex items-center gap-2 rounded-full border border-brand-cyan/30 bg-brand-cyan/10 px-3 py-1 text-xs font-semibold text-brand-cyan mb-3 shadow-glow-cyan">
            <TrendingUp className="h-3.5 w-3.5 text-brand-cyan" />
            <span>Inteligencia Estadística & Comparador de Cuotas</span>
          </div>
          
          <h1 className="font-display text-3xl font-extrabold tracking-tight text-white sm:text-4xl md:text-5xl">
            Proposiciones con <span className="text-transparent bg-clip-text bg-gradient-to-r from-brand-cyan via-edge-positive to-teal-200">Ventaja Matemática</span>
          </h1>
          <p className="mt-3 text-sm text-slate-300 sm:text-base leading-relaxed">
            Identifica discrepancias en líneas de <strong className="text-white">NFL</strong> y <strong className="text-white">Liga MX</strong> comparando exclusivamente los sportsbooks líderes de Estados Unidos.
          </p>

          {/* USA Sportsbooks pills */}
          <div className="mt-4 flex flex-wrap items-center justify-center gap-2 text-xs text-slate-400 font-mono">
            <span className="text-slate-500 text-[11px] uppercase tracking-wider">Momios en vivo de USA:</span>
            <span className="rounded-md border border-slate-800 bg-surface-1 px-2.5 py-1 text-slate-300 flex items-center gap-1.5">
              <span className="h-1.5 w-1.5 rounded-full bg-[#53d337]" /> DraftKings
            </span>
            <span className="rounded-md border border-slate-800 bg-surface-1 px-2.5 py-1 text-slate-300 flex items-center gap-1.5">
              <span className="h-1.5 w-1.5 rounded-full bg-[#1493ff]" /> FanDuel
            </span>
            <span className="rounded-md border border-slate-800 bg-surface-1 px-2.5 py-1 text-slate-300 flex items-center gap-1.5">
              <span className="h-1.5 w-1.5 rounded-full bg-[#c3a25b]" /> BetMGM
            </span>
            <span className="rounded-md border border-slate-800 bg-surface-1 px-2.5 py-1 text-slate-300 flex items-center gap-1.5">
              <span className="h-1.5 w-1.5 rounded-full bg-[#257551]" /> Caesars
            </span>
          </div>
        </div>

        {/* Filter and Search Bar */}
        <div className="rounded-2xl border border-slate-800 bg-surface-card/90 p-3 sm:p-4 shadow-xl backdrop-blur-md">
          <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            
            {/* Sport Tabs */}
            <div className="flex items-center gap-1.5 bg-[#070B16] p-1 rounded-xl border border-slate-800/80">
              <button
                onClick={() => onSelectSport('all')}
                className={`rounded-lg px-3.5 py-1.5 text-xs font-semibold transition-all ${
                  selectedSport === 'all'
                    ? 'bg-gradient-to-r from-brand-cyan to-brand-teal text-slate-950 shadow-md font-bold'
                    : 'text-slate-400 hover:text-white'
                }`}
              >
                Todos ({totalPropsCount})
              </button>
              <button
                onClick={() => onSelectSport('nfl')}
                className={`flex items-center gap-1.5 rounded-lg px-3.5 py-1.5 text-xs font-semibold transition-all ${
                  selectedSport === 'nfl'
                    ? 'bg-sky-500 text-slate-950 font-bold shadow-md'
                    : 'text-slate-400 hover:text-white'
                }`}
              >
                <span>🏈 NFL</span>
              </button>
              <button
                onClick={() => onSelectSport('ligamx')}
                className={`flex items-center gap-1.5 rounded-lg px-3.5 py-1.5 text-xs font-semibold transition-all ${
                  selectedSport === 'ligamx'
                    ? 'bg-emerald-500 text-slate-950 font-bold shadow-md'
                    : 'text-slate-400 hover:text-white'
                }`}
              >
                <span>⚽ Liga MX</span>
              </button>
            </div>

            {/* Real-time Search Input */}
            <div className="relative flex-1 sm:max-w-md">
              <div className="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                <Search className="h-4 w-4" />
              </div>
              <input
                type="text"
                value={searchQuery}
                onChange={(e) => onSearchChange(e.target.value)}
                placeholder="Buscar por jugador, equipo o estadística..."
                className="w-full rounded-xl border border-slate-700/80 bg-[#070B16] py-2 pl-9 pr-4 text-xs sm:text-sm text-slate-100 placeholder-slate-500 focus:border-brand-cyan focus:outline-none focus:ring-1 focus:ring-brand-cyan transition-all"
              />
              {searchQuery && (
                <button
                  onClick={() => onSearchChange('')}
                  className="absolute inset-y-0 right-0 flex items-center pr-3 text-xs text-slate-500 hover:text-slate-300"
                >
                  Limpiar
                </button>
              )}
            </div>

          </div>
        </div>

      </div>
    </section>
  );
}
