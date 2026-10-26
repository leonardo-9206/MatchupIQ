import React from 'react';
import { Activity, ShieldCheck, Flame, Layers } from 'lucide-react';

export default function Navbar({ activeTab, onSelectTab, onOpenHeatmap }) {
  return (
    <header className="sticky top-0 z-40 w-full border-b border-slate-800/80 bg-[#070B16]/95 backdrop-blur-md">
      <div className="mx-auto flex max-w-7xl items-center justify-between px-4 py-3 sm:px-6 lg:px-8">
        
        {/* Brand Logo & Name */}
        <div className="flex items-center gap-3">
          <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-brand-cyan/20 to-brand-blue/30 p-1 border border-brand-cyan/30 shadow-glow-cyan">
            <img
              src="/logo_matchupiq_app.png"
              alt="MatchupIQ"
              className="h-8 w-auto object-contain drop-shadow"
            />
          </div>
          <div>
            <div className="flex items-center gap-2">
              <span className="font-display text-xl font-extrabold tracking-tight text-white">
                Matchup<span className="text-brand-cyan">IQ</span>
              </span>
              <span className="hidden rounded-full border border-emerald-500/30 bg-emerald-500/10 px-2 py-0.5 font-mono text-[10px] font-semibold text-edge-positive sm:inline-flex items-center gap-1">
                <span className="h-1.5 w-1.5 rounded-full bg-edge-positive animate-pulse" />
                VALUE EDGE
              </span>
            </div>
            <p className="text-[11px] text-slate-400 font-sans hidden sm:block">
              NFL & Liga MX • US Sportsbooks Comparison
            </p>
          </div>
        </div>

        {/* Navigation Tabs */}
        <nav className="flex items-center gap-1 sm:gap-2">
          <button
            onClick={() => onSelectTab('dashboard')}
            className={`flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-medium transition-all ${
              activeTab === 'dashboard'
                ? 'bg-brand-cyan/15 text-brand-cyan border border-brand-cyan/40 shadow-glow-cyan'
                : 'text-slate-400 hover:bg-surface-elevated hover:text-slate-200'
            }`}
          >
            <Activity className="h-3.5 w-3.5" />
            <span>Dashboard</span>
          </button>

          <button
            onClick={() => onSelectTab('catalog')}
            className={`flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-medium transition-all ${
              activeTab === 'catalog'
                ? 'bg-brand-cyan/15 text-brand-cyan border border-brand-cyan/40 shadow-glow-cyan'
                : 'text-slate-400 hover:bg-surface-elevated hover:text-slate-200'
            }`}
          >
            <Layers className="h-3.5 w-3.5" />
            <span>Catálogo</span>
          </button>

          {/* Heatmap Modal Trigger (Colaboración con Josh) */}
          <button
            onClick={onOpenHeatmap}
            className="flex items-center gap-1.5 rounded-lg border border-sky-500/30 bg-sky-500/10 px-3 py-1.5 text-xs font-medium text-sky-400 hover:bg-sky-500/20 hover:text-white transition-all"
          >
            <Flame className="h-3.5 w-3.5 text-sky-400" />
            <span className="hidden sm:inline">Mapa Táctico</span>
            <span className="sm:hidden">Heatmap</span>
          </button>
        </nav>

        {/* User Pill / Status */}
        <div className="flex items-center gap-3">
          <div className="hidden lg:flex items-center gap-2 rounded-lg border border-slate-800 bg-surface-1 px-3 py-1 text-xs font-mono text-slate-400">
            <span className="h-2 w-2 rounded-full bg-edge-positive" />
            <span>US Odds Live</span>
          </div>

          <div className="flex items-center gap-2.5 rounded-full border border-slate-700/80 bg-surface-card px-3 py-1.5 text-xs font-medium">
            <div className="flex h-6 w-6 items-center justify-center rounded-full bg-gradient-to-tr from-brand-cyan to-brand-blue text-[11px] font-bold text-slate-900 font-display">
              AV
            </div>
            <div className="hidden sm:block text-left leading-tight">
              <span className="block font-semibold text-slate-200">Antonio V.</span>
              <span className="block text-[10px] text-brand-cyan">Lead Frontend</span>
            </div>
          </div>
        </div>

      </div>
    </header>
  );
}
