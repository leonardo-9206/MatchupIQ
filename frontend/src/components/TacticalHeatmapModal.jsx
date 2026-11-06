import React, { useState, useEffect } from 'react';
import { X, Flame, Info, GitBranch, Shield } from 'lucide-react';
import FieldHeatmap, { METRICS } from './FieldHeatmap/FieldHeatmap.jsx';
import RouteTree, { ROUTE_METRICS } from './Tactics/RouteTree.jsx';
import CoverageSplits from './Tactics/CoverageSplits.jsx';
import { mockPlayers } from '../mocks/heatmapMock.js';

const TACTICAL_VIEWS = {
  heatmap: { label: 'Rushing Heatmap', icon: Flame },
  routes: { label: 'Route Tree', icon: GitBranch },
  coverage: { label: 'Man vs Zone', icon: Shield },
};

// Which tactical views make sense for each position:
// RBs run and catch, WRs/TEs only catch, QBs only have designed runs/scrambles.
export const POSITION_VIEWS = {
  RB: ['heatmap', 'routes', 'coverage'],
  WR: ['routes', 'coverage'],
  TE: ['routes', 'coverage'],
  QB: ['heatmap'],
};

const POSITION_COPY = {
  RB: {
    badge: 'RB: Rushing + Passing Maps',
    description: 'Desglose integral de acarreos por carril, árbol de rutas y coberturas defensivas',
  },
  WR: {
    badge: 'WR: Passing & Coverage Maps',
    description: 'Análisis aéreo de árbol de rutas (Route Tree) y rendimiento contra coberturas Man vs Zone',
  },
  TE: {
    badge: 'TE: Passing & Coverage Maps',
    description: 'Análisis aéreo de árbol de rutas (Route Tree) y rendimiento contra coberturas Man vs Zone',
  },
  QB: {
    badge: 'QB: Designed Runs Map',
    description: 'Distribución de acarreos del mariscal de campo por carril (jugadas diseñadas y scrambles)',
  },
};

/**
 * Tactical modal opened from a PropCard / StatsDrawer.
 *
 * @param players          NFL athletes available in the dashboard: [{ player_id, name, position, team }].
 *                         Falls back to the local mock roster when the parent doesn't send any.
 * @param selectedPlayerId Athlete to show when the modal opens (the prop's player_id).
 */
export default function TacticalHeatmapModal({ isOpen, onClose, selectedPlayerId, players }) {
  const roster = players?.length ? players : mockPlayers;
  const [playerId, setPlayerId] = useState(() => Number(selectedPlayerId ?? roster[0]?.player_id));
  const [activeView, setActiveView] = useState('heatmap');
  const [heatmapMetric, setHeatmapMetric] = useState('attempts');
  const [routeMetric, setRouteMetric] = useState('catch_rate');

  // Follow the athlete of the card the user clicked
  useEffect(() => {
    if (selectedPlayerId != null) {
      setPlayerId(Number(selectedPlayerId));
    }
  }, [selectedPlayerId]);

  if (!isOpen) return null;

  // A synced athlete may not be in the roster yet: keep showing them instead of switching to someone else
  const selectedPlayer = roster.find((p) => p.player_id === playerId) ?? {
    player_id: playerId,
    name: `Player #${playerId}`,
    position: null,
    team: '—',
  };
  const options = roster.includes(selectedPlayer) ? roster : [selectedPlayer, ...roster];

  const position = selectedPlayer.position;
  const availableViews = POSITION_VIEWS[position] ?? [];
  // If the new athlete doesn't have the current tab (e.g. RB -> WR on the heatmap), use their first tab
  const currentView = availableViews.includes(activeView) ? activeView : availableViews[0];
  const copy = POSITION_COPY[position];

  const activeMetrics = currentView === 'heatmap' ? METRICS : currentView === 'routes' ? ROUTE_METRICS : null;
  const currentMetricValue = currentView === 'heatmap' ? heatmapMetric : routeMetric;
  const handleSelectMetric = currentView === 'heatmap' ? setHeatmapMetric : setRouteMetric;

  return (
    <div className="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true" aria-labelledby="tactical-modal-title">
      {/* Backdrop */}
      <div className="fixed inset-0 bg-black/85 backdrop-blur-md" onClick={onClose} />

      <div className="flex min-h-full items-center justify-center p-4 sm:p-6">
        <div className="relative w-full max-w-4xl rounded-2xl border border-sky-500/30 bg-[#070B16] p-6 shadow-2xl">

          {/* Header */}
          <div className="flex items-center justify-between pb-4 border-b border-slate-800">
            <div className="flex items-center gap-2.5">
              <div className="flex h-9 w-9 items-center justify-center rounded-xl bg-sky-500/15 border border-sky-500/30">
                <Flame className="h-5 w-5 text-sky-400" />
              </div>
              <div>
                <div className="flex flex-wrap items-center gap-2">
                  <h3 id="tactical-modal-title" className="font-display text-xl font-bold text-white">
                    Mapa Táctico del Campo NFL
                  </h3>
                  {copy && (
                    <span
                      data-testid="position-scope-badge"
                      className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-[10px] font-mono font-semibold border ${
                        position === 'RB'
                          ? 'bg-sky-500/15 text-sky-300 border-sky-500/30'
                          : 'bg-emerald-500/15 text-emerald-300 border-emerald-500/30'
                      }`}
                    >
                      {copy.badge}
                    </span>
                  )}
                </div>
                <p className="text-xs text-slate-400 font-sans">
                  {copy?.description ?? 'Mapas tácticos disponibles para QB, RB, WR y TE'}
                </p>
              </div>
            </div>

            <button
              onClick={onClose}
              aria-label="Cerrar mapa táctico"
              className="rounded-lg p-1.5 text-slate-400 hover:bg-surface-elevated hover:text-white transition-all"
            >
              <X className="h-5 w-5" />
            </button>
          </div>

          {/* Controls: Player selector, Tactical View Tabs & Metric buttons */}
          <div className="mt-5 flex flex-wrap items-end justify-between gap-4">
            <div className="flex flex-wrap items-end gap-3">
              <div>
                <label
                  htmlFor="tactical-player-select"
                  className="block text-[11px] font-mono text-slate-400 uppercase tracking-wider mb-1"
                >
                  Atleta Seleccionado
                </label>
                <select
                  id="tactical-player-select"
                  value={playerId}
                  onChange={(e) => setPlayerId(Number(e.target.value))}
                  className="rounded-xl border border-slate-700 bg-[#0D1527] px-3.5 py-2 text-xs font-semibold text-slate-100 focus:border-sky-500 focus:outline-none"
                >
                  {options.map((p) => (
                    <option key={p.player_id} value={p.player_id}>
                      {p.name} ({p.team} · {p.position ?? '?'})
                    </option>
                  ))}
                </select>
              </div>

              {/* Tactical View Switcher filtered by position */}
              {availableViews.length > 0 && (
                <div>
                  <span className="block text-[11px] font-mono text-slate-400 uppercase tracking-wider mb-1">
                    Vista Táctica ({position})
                  </span>
                  <div className="flex rounded-xl border border-slate-700 bg-[#0D1527] p-1" role="tablist">
                    {availableViews.map((key) => {
                      const { label, icon: Icon } = TACTICAL_VIEWS[key];
                      return (
                        <button
                          key={key}
                          type="button"
                          role="tab"
                          aria-selected={currentView === key}
                          onClick={() => setActiveView(key)}
                          className={`inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-semibold transition-all ${
                            currentView === key
                              ? 'bg-sky-500 text-slate-950 font-bold shadow-sm'
                              : 'text-slate-400 hover:text-white'
                          }`}
                        >
                          <Icon className="h-3.5 w-3.5" />
                          <span>{label}</span>
                        </button>
                      );
                    })}
                  </div>
                </div>
              )}
            </div>

            {/* Metric Selector (applicable to Rushing Heatmap and Route Tree) */}
            {activeMetrics && (
              <div>
                <span className="block text-[11px] font-mono text-slate-400 uppercase tracking-wider mb-1">
                  Métrica de Análisis
                </span>
                <div className="flex rounded-xl border border-slate-700 bg-[#0D1527] p-1">
                  {Object.entries(activeMetrics).map(([key, { label }]) => (
                    <button
                      key={key}
                      type="button"
                      onClick={() => handleSelectMetric(key)}
                      className={`rounded-lg px-3 py-1.5 text-xs font-semibold transition-all ${
                        currentMetricValue === key
                          ? 'bg-sky-500 text-slate-950 font-bold shadow-sm'
                          : 'text-slate-400 hover:text-white'
                      }`}
                    >
                      {label}
                    </button>
                  ))}
                </div>
              </div>
            )}
          </div>

          {/* Tactical Visual Container */}
          <div className="mt-5 overflow-hidden rounded-xl border border-slate-800 bg-[#050811] p-4 relative">
            {currentView === 'heatmap' && <FieldHeatmap playerId={playerId} metric={heatmapMetric} />}
            {currentView === 'routes' && <RouteTree playerId={playerId} metric={routeMetric} />}
            {currentView === 'coverage' && <CoverageSplits playerId={playerId} />}
            {!currentView && (
              <p className="py-16 text-center text-sm text-slate-400">
                No hay mapas tácticos para la posición {position ?? 'de este atleta'}.
              </p>
            )}
          </div>

          {/* Footer note */}
          <div className="mt-4 flex items-center justify-between gap-2 text-[11px] text-slate-400 font-mono">
            <div className="flex items-center gap-2">
              <Info className="h-4 w-4 text-sky-400" />
              <span>
                Visualización basada en métricas tácticas de la NFL (Zonas de Acarreo, Route Tree y Esquemas Man vs Zone).
              </span>
            </div>
            {(position === 'WR' || position === 'TE') && (
              <span className="text-emerald-400">Mapa de acarreos deshabilitado para receptores ({position})</span>
            )}
          </div>

        </div>
      </div>
    </div>
  );
}
