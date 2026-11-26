import React from 'react';
import { X, Star, BarChart3, Shield, BookmarkPlus, Flame } from 'lucide-react';
import Button from './common/Button.jsx';

export default function StatsDrawer({ prop, isOpen, onClose, onOpenHeatmap, onAddBet }) {
  if (!isOpen || !prop) return null;

  const isNfl = prop.sport_slug === 'nfl';
  const hasHeatmap = isNfl && (prop.bet_type === 'rushing_yards' || prop.bet_type === 'receiving_yards' || prop.bet_type === 'passing_yards');

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
            <div
              className={`mt-3 flex items-center justify-between rounded-xl border p-3 ${
                prop.calculated_edge >= 0
                  ? 'border-emerald-500/30 bg-emerald-500/10'
                  : 'border-rose-500/30 bg-rose-500/10'
              }`}
            >
              <div>
                <span
                  className={`text-[11px] font-mono uppercase tracking-wider block ${
                    prop.calculated_edge >= 0 ? 'text-emerald-400' : 'text-rose-400'
                  }`}
                >
                  {prop.calculated_edge >= 0 ? 'Valor Matemático Esperado' : 'Desventaja Estadística Esperada'}
                </span>
                <span
                  className={`font-mono text-xl font-black ${
                    prop.calculated_edge >= 0 ? 'text-edge-positive' : 'text-rose-400'
                  }`}
                >
                  {prop.calculated_edge > 0 ? `+${prop.calculated_edge}%` : `${prop.calculated_edge}%`} EDGE {prop.calculated_edge >= 0 ? '(+EV)' : '(-EV)'}
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
            
            {/* Tactical Heatmap Button directly in drawer */}
            {hasHeatmap && onOpenHeatmap && (
              <div className="rounded-xl border border-sky-500/30 bg-sky-500/10 p-4">
                <div className="flex items-center justify-between">
                  <div>
                    <h4 className="font-display text-sm font-bold text-sky-200">
                      Mapa Táctico del Campo NFL
                    </h4>
                    <p className="text-xs text-slate-400">
                      Desglose de acarreos por carril y éxito en zonas
                    </p>
                  </div>
                  <Button
                    variant="accent"
                    size="sm"
                    icon={Flame}
                    onClick={() => {
                      onOpenHeatmap(prop.player_id);
                    }}
                  >
                    Ver Heatmap
                  </Button>
                </div>
              </div>
            )}

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
                  <span>Historial Real (Últimos 5 Partidos)</span>
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
            {(() => {
              const rank = Number(prop.opponent_rank || prop.opposing_defensive_rank || 16);
              const rival = prop.opponent_abbreviation || prop.opponent_name || 'El rival';
              const isTough = rank <= 10;
              const isFavorable = rank >= 20;

              return (
                <div className="rounded-xl border border-slate-800 bg-[#0D1527] p-4 text-xs space-y-2">
                  <div className="flex items-center gap-2 text-slate-200 font-bold font-display">
                    <Shield className="h-4 w-4 text-sky-400" />
                    <span>Contexto Defensivo del Rival</span>
                  </div>
                  <p className="text-slate-300 leading-relaxed text-[11px]">
                    {rival} se ubica como la defensa <strong className="text-white">#{rank}</strong> de la liga permitiendo yardas en esta categoría {isTough ? '(unidad élite conteniendo el avance)' : isFavorable ? '(unidad permisiva que concede alto yardaje)' : '(rendimiento de media tabla)'}. {
                      prop.calculated_edge >= 0
                        ? (isTough
                            ? 'A pesar del emparejamiento defensivo exigente, el volumen histórico del jugador supera la línea del mercado (+EV).'
                            : 'Este emparejamiento favorable potencia el valor matemático esperado (+EV) para el pick.')
                        : 'El enfrentamiento proyecta una desventaja estadística frente a la línea evaluada (-EV).'
                    }
                  </p>
                </div>
              );
            })()}

          </div>

          {/* Drawer Footer: Open Add Bet Modal */}
          <div className="border-t border-slate-800 p-5 bg-[#050811]">
            <Button
              variant="primary"
              size="lg"
              icon={BookmarkPlus}
              onClick={() => onAddBet(prop)}
              className="w-full justify-center"
            >
              <span>Agregar a mi Bet Tracker</span>
            </Button>
            <p className="text-center text-[10px] text-slate-500 mt-2 font-mono">
              Registra cuota {prop.american_odds} con {prop.best_bookmaker}
            </p>
          </div>

        </div>
      </div>
    </div>
  );
}


