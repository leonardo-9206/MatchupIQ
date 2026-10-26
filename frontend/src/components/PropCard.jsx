import React from 'react';
import { ArrowUpRight, Flame, BarChart2, Star, ShieldAlert } from 'lucide-react';

export default function PropCard({ prop, onOpenStats }) {
  const isPositiveEdge = prop.calculated_edge > 0;

  return (
    <div className="group relative flex flex-col justify-between rounded-2xl border border-brand-cyan/25 bg-[#0D1527] p-5 shadow-glow-card transition-all duration-200 hover:-translate-y-1 hover:border-brand-cyan/50 hover:shadow-glow-cyan">
      
      {/* Top Bar: Sport & Edge Badge */}
      <div>
        <div className="flex items-center justify-between gap-2 mb-3">
          <div className="flex items-center gap-2">
            <span
              className={`rounded-md px-2 py-0.5 text-[11px] font-bold uppercase tracking-wider ${
                prop.sport_slug === 'nfl'
                  ? 'border border-sky-500/40 bg-sky-500/15 text-sky-300'
                  : 'border border-emerald-500/40 bg-emerald-500/15 text-emerald-300'
              }`}
            >
              {prop.sport_name}
            </span>
            <span className="text-xs text-slate-400 font-mono">
              {prop.team_abbreviation} vs {prop.opponent_abbreviation}
            </span>
          </div>

          {/* Value Edge Badge */}
          {isPositiveEdge && (
            <span className="inline-flex items-center gap-1.5 rounded-full border border-emerald-500/40 bg-emerald-500/15 px-2.5 py-0.5 font-mono text-xs font-bold text-edge-positive">
              <span className="h-1.5 w-1.5 rounded-full bg-edge-positive animate-pulse" />
              +{prop.calculated_edge}% EDGE
            </span>
          )}
        </div>

        {/* Player Name and Position */}
        <div className="mb-2">
          <div className="flex items-baseline justify-between">
            <h3 className="font-display text-lg font-bold text-white group-hover:text-brand-cyan transition-colors">
              {prop.player_name}
            </h3>
            <span className="font-mono text-xs font-semibold text-slate-400 rounded bg-surface-1 px-1.5 py-0.5 border border-slate-800">
              {prop.player_position}
            </span>
          </div>
          <p className="text-xs text-slate-400">
            {prop.team_name} • Rival Def Rank #{prop.opponent_rank}
          </p>
        </div>

        {/* Line & Prop Metric Box */}
        <div className="mt-3 rounded-xl border border-slate-800/80 bg-[#070B16] p-3">
          <div className="flex items-center justify-between text-xs text-slate-400 mb-1">
            <span>{prop.bet_type_display}</span>
            <span className="font-mono text-slate-300">Promedio: {prop.stat_average}</span>
          </div>
          
          <div className="flex items-baseline justify-between">
            <div>
              <span className="text-xs font-semibold uppercase text-brand-cyan">Línea OVER:</span>
              <span className="ml-2 font-mono text-2xl font-black text-white tracking-tight">
                {prop.line_over}
              </span>
            </div>

            <div className="text-right">
              <span className="font-mono text-sm font-bold text-edge-positive">
                {prop.american_odds || `x${prop.odds_over}`}
              </span>
              <span className="block text-[10px] text-slate-400 font-mono">
                Dec: {prop.odds_over}
              </span>
            </div>
          </div>
        </div>

        {/* Best USA Sportsbook Highlight */}
        <div className="mt-3 flex items-center justify-between rounded-lg border border-amber-500/25 bg-amber-500/10 px-3 py-1.5 text-xs">
          <div className="flex items-center gap-1.5 text-amber-300 font-medium">
            <Star className="h-3.5 w-3.5 fill-amber-400 text-amber-400" />
            <span className="text-[11px] font-bold tracking-wide">MEJOR MOMIO:</span>
            <span className="font-bold text-white">{prop.best_bookmaker}</span>
          </div>
          <span className="font-mono font-bold text-amber-300">
            {prop.american_odds}
          </span>
        </div>

        {/* Hot Streak Pill */}
        {prop.streak_hits && (
          <div className="mt-2 flex items-center gap-1.5 text-[11px] text-orange-400 font-mono">
            <Flame className="h-3.5 w-3.5 text-orange-400" />
            <span>Cubierto en {prop.streak_hits} juegos recientes</span>
          </div>
        )}
      </div>

      {/* Footer Action Button: Stats & Odds Drawer */}
      <div className="mt-4 pt-3 border-t border-slate-800/70">
        <button
          onClick={() => onOpenStats(prop)}
          className="flex w-full items-center justify-center gap-2 rounded-xl border border-brand-cyan/40 bg-brand-cyan/10 px-4 py-2.5 text-xs font-bold text-brand-cyan hover:bg-brand-cyan hover:text-slate-950 transition-all shadow-sm group-hover:shadow-glow-cyan"
        >
          <BarChart2 className="h-4 w-4" />
          <span>Stats & Odds en USA</span>
          <ArrowUpRight className="h-3.5 w-3.5" />
        </button>
      </div>

    </div>
  );
}
