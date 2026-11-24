import React from 'react';
import { Layers, Star, BarChart2, Check, ArrowRight } from 'lucide-react';

export default function PropsTableList({ props, onOpenStats, searchQuery }) {
  return (
    <section className="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
      {/* Clean Section Header */}
      <div className="flex flex-col sm:flex-row sm:items-end justify-between mb-6 pb-3 border-b border-slate-800">
        <div>
          <div className="inline-flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wider text-teal-400 mb-1 font-mono">
            <Layers className="h-3.5 w-3.5 text-teal-400" />
            <span>Monitoreo Integral de Mercados</span>
          </div>
          <h2 className="font-display text-2xl font-bold tracking-tight text-white sm:text-3xl">
            Catálogo de Proposiciones
          </h2>
        </div>
        <p className="text-xs sm:text-sm text-slate-400 font-sans mt-1 sm:mt-0">
          Cuotas comparativas en vivo entre las 4 casas autorizadas de Estados Unidos.
        </p>
      </div>

      {props.length === 0 ? (
        <div className="rounded-2xl border border-slate-800 bg-[#0D1527] p-12 text-center">
          <p className="text-slate-400 text-sm">
            No se encontraron proposiciones que coincidan con "{searchQuery}".
          </p>
        </div>
      ) : (
        <div className="overflow-hidden rounded-2xl border border-brand-cyan/20 bg-[#0D1527] shadow-xl">
          <div className="overflow-x-auto">
            <table className="w-full text-left text-xs">
              {/* Table Header */}
              <thead className="border-b border-slate-800 bg-[#070B16] font-mono text-[11px] uppercase tracking-wider text-slate-400">
                <tr>
                  <th scope="col" className="px-4 py-3.5 sm:px-6">Jugador & Partido</th>
                  <th scope="col" className="px-4 py-3.5">Mercado</th>
                  <th scope="col" className="px-4 py-3.5 text-center">Línea Base</th>
                  <th scope="col" className="px-3 py-3.5 text-center text-[#53d337]">DraftKings</th>
                  <th scope="col" className="px-3 py-3.5 text-center text-[#1493ff]">FanDuel</th>
                  <th scope="col" className="px-3 py-3.5 text-center text-[#c3a25b]">BetMGM</th>
                  <th scope="col" className="px-3 py-3.5 text-center text-[#257551]">Caesars</th>
                  <th scope="col" className="px-4 py-3.5 text-center">Edge</th>
                  <th scope="col" className="px-4 py-3.5 text-right sm:px-6">Acción</th>
                </tr>
              </thead>

              {/* Table Body */}
              <tbody className="divide-y divide-slate-800/80 font-sans">
                {props.map((p) => {
                  return (
                    <tr
                      key={p.prop_id}
                      className="group transition-colors hover:bg-[#131E35]/70"
                    >
                      {/* Player & Match */}
                      <td className="px-4 py-4 sm:px-6">
                        <div className="flex items-center gap-3">
                          <span
                            className={`rounded px-1.5 py-0.5 text-[10px] font-bold uppercase font-mono ${
                              p.sport_slug === 'nfl'
                                ? 'bg-sky-500/20 text-sky-400 border border-sky-500/30'
                                : 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30'
                            }`}
                          >
                            {p.sport_slug.toUpperCase()}
                          </span>
                          <div>
                            <span className="font-display text-sm font-bold text-white group-hover:text-brand-cyan transition-colors">
                              {p.player_name}
                            </span>
                            <span className="ml-2 font-mono text-[11px] text-slate-400">
                              ({p.player_position} · {p.team_abbreviation})
                            </span>
                            <div className="text-[11px] text-slate-400">
                              vs {p.opponent_abbreviation} (Def #{p.opponent_rank})
                            </div>
                          </div>
                        </div>
                      </td>

                      {/* Market / Prop Type */}
                      <td className="px-4 py-4 font-medium text-slate-200">
                        {p.bet_type_display}
                      </td>

                      {/* Line Over */}
                      <td className="px-4 py-4 text-center font-mono text-sm font-bold text-white">
                        {p.line_over}
                      </td>

                      {/* DraftKings */}
                      <td className="px-3 py-4 text-center">
                        <BookmakerCell
                          bookmaker="DraftKings"
                          odds={p.bookmakers_odds?.find((b) => b.name === 'DraftKings')}
                        />
                      </td>

                      {/* FanDuel */}
                      <td className="px-3 py-4 text-center">
                        <BookmakerCell
                          bookmaker="FanDuel"
                          odds={p.bookmakers_odds?.find((b) => b.name === 'FanDuel')}
                        />
                      </td>

                      {/* BetMGM */}
                      <td className="px-3 py-4 text-center">
                        <BookmakerCell
                          bookmaker="BetMGM"
                          odds={p.bookmakers_odds?.find((b) => b.name === 'BetMGM')}
                        />
                      </td>

                      {/* Caesars */}
                      <td className="px-3 py-4 text-center">
                        <BookmakerCell
                          bookmaker="Caesars"
                          odds={p.bookmakers_odds?.find((b) => b.name === 'Caesars')}
                        />
                      </td>

                      {/* Value Edge */}
                      <td className="px-4 py-4 text-center font-mono">
                        <span className="inline-flex items-center gap-1 rounded-full border border-emerald-500/30 bg-emerald-500/10 px-2 py-0.5 text-xs font-bold text-edge-positive">
                          +{p.calculated_edge}%
                        </span>
                      </td>

                      {/* Action */}
                      <td className="px-4 py-4 text-right sm:px-6">
                        <button
                          onClick={() => onOpenStats(p)}
                          className="inline-flex items-center gap-1.5 rounded-lg border border-brand-cyan/40 bg-brand-cyan/10 px-3 py-1.5 text-xs font-semibold text-brand-cyan hover:bg-brand-cyan hover:text-slate-950 transition-all"
                        >
                          <BarChart2 className="h-3.5 w-3.5" />
                          <span className="hidden sm:inline">Ver Análisis</span>
                        </button>
                      </td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          </div>
        </div>
      )}
    </section>
  );
}

function BookmakerCell({ odds }) {
  if (!odds) {
    return <span className="font-mono text-slate-600">-</span>;
  }

  return (
    <div
      className={`inline-flex flex-col items-center justify-center rounded-lg px-2 py-1 font-mono text-xs transition-all ${
        odds.is_best
          ? 'border border-amber-500/40 bg-amber-500/15 text-amber-300 font-bold shadow-sm'
          : 'text-slate-300'
      }`}
    >
      <div className="flex items-center gap-0.5">
        {odds.is_best && <Star className="h-2.5 w-2.5 fill-amber-400 text-amber-400" />}
        <span>{odds.over}</span>
      </div>
      <span className="text-[10px] text-slate-400">({odds.line})</span>
    </div>
  );
}
