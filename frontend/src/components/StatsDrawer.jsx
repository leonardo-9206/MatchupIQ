import React, { useState } from 'react';
import { X, Star, TrendingUp, Shield, BarChart3, BookmarkPlus, Check, ArrowRight, ExternalLink } from 'lucide-react';

export default function StatsDrawer({ prop, isOpen, onClose }) {
  const [savedToTracker, setSavedToTracker] = useState(false);

  if (!isOpen || !prop) return null;

  const handleSavePick = () => {
    setSavedToTracker(true);
    setTimeout(() => setSavedToTracker(false), 2500);
  };

  return (
    <div className="fixed inset-0 z-50 overflow-hidden">
      {/* Backdrop */}
      <div
        className="fixed inset-0 bg-black/80 backdrop-blur-sm transition-opacity"
        onClick={onClose}
      />

      <div className="fixed inset-y-0 right-0 flex max-w-full pl-10">
        <div className="w-screen max-w-md border-l border-brand-cyan/30 bg-[#070B16] text-slate-100 shadow-glow-drawer flex flex-col justify-between">
          
          {/* Header */}
          <div className="border-b border-slate-800 p-5">
            <div className="flex items-center justify-between">
              <div className="flex items-center gap-2">
                <span className="rounded-md border border-brand-cyan/40 bg-brand-cyan/15 px-2 py-0.5 font-mono text-[11px] font-bold text-brand-cyan">
                  {prop.sport_name} • ANALYTICS
                </span>
                <span className="font-mono text-xs text-slate-400">ID: #{prop.prop_id}</span>
              </div>
              <button
                onClick={onClose}
                className="rounded-lg p-1.5 text-slate-400 hover:bg-surface-elevated hover:text-white transition-all"
              >
                <X className="h-5 w-5" />
              </button>
            </div>

            <div className="mt-4">
              <h2 className="font-display text-2xl font-black text-white">
                {prop.player_name}
              </h2>
              <p className="text-xs text-slate-400 mt-0.5">
                {prop.player_position} • {prop.team_name} ({prop.team_abbreviation})
              </p>
            </div>

            {/* Edge Pill */}
            <div className="mt-3 flex items-center justify-between rounded-xl border border-emerald-500/30 bg-emerald-500/10 p-3">
              <div>
                <span className="text-[11px] font-mono text-emerald-400 uppercase tracking-wider block">
                  Valor Matemático Esperado
                </span>
                <span className="font-mono text-xl font-black text-edge-positive">
                  +{prop.calculated_edge}% EDGE (+EV)
                </span>
              </div>
              <div className="text-right">
                <span className="text-[10px] text-slate-400 block font-mono">Línea Evaluada</span>
                <span className="font-mono text-base font-bold text-white">{prop.line_over}</span>
              </div>
            </div>
          </div>

          {/* Drawer Body (Scrollable) */}
          <div className="flex-1 overflow-y-auto p-5 space-y-6">
            
            {/* 1. US Sportsbooks Comparative Breakdown */}
            <div>
              <div className="flex items-center justify-between mb-3">
                <h3 className="font-display text-sm font-bold uppercase tracking-wider text-slate-200">
                  Momios en Estados Unidos (The Odds API)
                </h3>
                <span className="text-[10px] text-slate-400 font-mono">Línea OVER</span>
              </div>

              <div className="space-y-2">
                {prop.bookmakers_odds?.map((b) => (
                  <div
                    key={b.name}
                    className={`flex items-center justify-between rounded-xl p-3 border transition-all ${
                      b.is_best
                        ? 'border-amber-500/50 bg-amber-500/10 shadow-sm'
                        : 'border-slate-800 bg-[#0D1527]'
                    }`}
                  >
                    <div className="flex items-center gap-2">
                      {b.is_best ? (
                        <Star className="h-4 w-4 fill-amber-400 text-amber-400" />
                      ) : (
                        <div className="h-2 w-2 rounded-full bg-slate-600 ml-1 mr-1" />
                      )}
                      <div>
                        <span className="font-bold text-xs text-white block">{b.name}</span>
                        {b.is_best && (
                          <span className="text-[10px] font-mono font-semibold text-amber-400 uppercase">
                            ★ Mejor Precio del Mercado
                          </span>
                        )}
                      </div>
                    </div>

                    <div className="text-right font-mono">
                      <span className={`text-sm font-bold ${b.is_best ? 'text-amber-300' : 'text-slate-200'}`}>
                        {b.over}
                      </span>
                      <span className="block text-[10px] text-slate-400">
                        Línea: {b.line} (Dec: {b.decimal})
                      </span>
                    </div>
                  </div>
                ))}
              </div>
            </div>

            {/* 2. Recent Performance Trend (Last 5 Games) */}
            <div className="rounded-xl border border-slate-800 bg-[#0D1527] p-4">
              <div className="flex items-center justify-between mb-3">
                <div className="flex items-center gap-1.5 text-xs font-bold text-slate-200 font-display">
                  <BarChart3 className="h-4 w-4 text-brand-cyan" />
                  <span>Rendimiento Reciente (Últimos 5 Partidos)</span>
                </div>
                <span className="rounded bg-emerald-500/20 px-2 py-0.5 text-[10px] font-bold text-emerald-400 font-mono">
                  {prop.streak_hits} Over
                </span>
              </div>

              {/* Bar charts representation */}
              <div className="space-y-2.5 pt-2">
                {prop.recent_games?.map((g, idx) => {
                  const percentage = Math.min(100, Math.round((g.value / (prop.line_over * 1.3)) * 100));
                  return (
                    <div key={idx} className="text-xs">
                      <div className="flex justify-between text-[11px] font-mono text-slate-400 mb-1">
                        <span>{g.game}</span>
                        <span className={g.hit ? 'text-edge-positive font-bold' : 'text-slate-400'}>
                          {g.value} {g.hit ? '✓ OVER' : '✗ UNDER'}
                        </span>
                      </div>
                      <div className="h-2 w-full rounded-full bg-slate-800 overflow-hidden">
                        <div
                          className={`h-full rounded-full transition-all duration-500 ${
                            g.hit
                              ? 'bg-gradient-to-r from-emerald-500 to-teal-400'
                              : 'bg-slate-600'
                          }`}
                          style={{ width: `${percentage}%` }}
                        />
                      </div>
                    </div>
                  );
                })}
              </div>
            </div>

            {/* 3. Matchup Context & Defense Ranking */}
            <div className="rounded-xl border border-slate-800 bg-[#0D1527] p-4 text-xs space-y-2">
              <div className="flex items-center gap-2 text-slate-200 font-bold font-display">
                <Shield className="h-4 w-4 text-sky-400" />
                <span>Contexto Defensivo del Rival</span>
              </div>
              <p className="text-slate-300 leading-relaxed text-[11px]">
                {prop.opponent_abbreviation} se ubica como la defensa <strong className="text-white">#{prop.opponent_rank}</strong> de la liga permitiendo yardas en esta categoría. Este enfrentamiento otorga una ventaja estadística favorable para el pick sugerido.
              </p>
            </div>

          </div>

          {/* Drawer Footer: Add to Bet Tracker */}
          <div className="border-t border-slate-800 p-5 bg-[#050811]">
            <button
              onClick={handleSavePick}
              className={`flex w-full items-center justify-center gap-2 rounded-xl py-3 text-xs font-bold transition-all shadow-md ${
                savedToTracker
                  ? 'bg-emerald-500 text-slate-950 font-black'
                  : 'bg-gradient-to-r from-brand-cyan to-brand-teal text-slate-950 hover:opacity-90 shadow-glow-cyan'
              }`}
            >
              {savedToTracker ? (
                <>
                  <Check className="h-4 w-4" />
                  <span>¡Guardado en tu Bet Tracker!</span>
                </>
              ) : (
                <>
                  <BookmarkPlus className="h-4 w-4" />
                  <span>Agregar Pick a mi Bet Tracker</span>
                </>
              )}
            </button>
            <p className="text-center text-[10px] text-slate-500 mt-2 font-mono">
              Registra cuota {prop.american_odds} con {prop.best_bookmaker}
            </p>
          </div>

        </div>
      </div>
    </div>
  );
}
