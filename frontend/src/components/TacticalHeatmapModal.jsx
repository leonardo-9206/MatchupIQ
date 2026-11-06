import React, { useState, useEffect } from 'react';
import { X, Flame, Info, GitBranch, Shield } from 'lucide-react';
import FieldHeatmap, { METRICS } from './FieldHeatmap/FieldHeatmap.jsx';
import RouteTree, { ROUTE_METRICS } from './Tactics/RouteTree.jsx';
import CoverageSplits from './Tactics/CoverageSplits.jsx';
import { mockPlayers } from '../mocks/heatmapMock.js';

const TACTICAL_VIEWS = [
  { key: 'heatmap', label: 'Rushing Heatmap', icon: Flame, rusherOnly: true },
  { key: 'routes', label: 'Route Tree', icon: GitBranch, rusherOnly: false },
  { key: 'coverage', label: 'Man vs Zone', icon: Shield, rusherOnly: false },
];

function resolveValidPlayerId(candidateId) {
  const numericId = Number(candidateId);
  if (mockPlayers.some((p) => p.player_id === numericId)) {
    return numericId;
  }
  return mockPlayers[0]?.player_id ?? 3;
}

export default function TacticalHeatmapModal({ isOpen, onClose, selectedPlayerId }) {
  const [playerId, setPlayerId] = useState(() => resolveValidPlayerId(selectedPlayerId));
  const [activeView, setActiveView] = useState('heatmap');
  const [heatmapMetric, setHeatmapMetric] = useState('attempts');
  const [routeMetric, setRouteMetric] = useState('catch_rate');

  // Keep playerId in sync whenever selectedPlayerId changes
  useEffect(() => {
    if (selectedPlayerId != null) {
      setPlayerId(resolveValidPlayerId(selectedPlayerId));
    }
  }, [selectedPlayerId]);

  const selectedPlayer =
    mockPlayers.find((p) => p.player_id === Number(playerId)) ?? mockPlayers[0];
  const playerPosition = selectedPlayer?.position ?? 'RB';
  const isRunningBack = playerPosition === 'RB';

  // Position-based view filtering:
  // RBs have rushing + passing maps (all 3 views); TE/WR only have passing & coverage maps (2 views).
  const availableViews = TACTICAL_VIEWS.filter((v) => isRunningBack || !v.rusherOnly);

  // Automatically switch out of 'heatmap' if the user selects a TE/WR
  useEffect(() => {
    if (!isRunningBack && activeView === 'heatmap') {
      setActiveView('routes');
    }
  }, [isRunningBack, activeView]);

  const currentView = !isRunningBack && activeView === 'heatmap' ? 'routes' : activeView;

  if (!isOpen) return null;

  const activeMetrics =
    currentView === 'heatmap' ? METRICS : currentView === 'routes' ? ROUTE_METRICS : null;
  const currentMetricValue = currentView === 'heatmap' ? heatmapMetric : routeMetric;
  const handleSelectMetric = (key) => {
    if (currentView === 'heatmap') {
      setHeatmapMetric(key);
    } else if (currentView === 'routes') {
      setRouteMetric(key);
    }
  };

  return (
    <div className="fixed inset-0 z-50 overflow-y-auto">
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
                  <h3 className="font-display text-xl font-bold text-white">
                    Mapa Táctico del Campo NFL
                  </h3>
                  <span
                    data-testid="position-scope-badge"
                    className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-[10px] font-mono font-semibold border ${
                      isRunningBack
                        ? 'bg-sky-500/15 text-sky-300 border-sky-500/30'
                        : 'bg-emerald-500/15 text-emerald-300 border-emerald-500/30'
                    }`}
                  >
                    {isRunningBack ? 'RB: Rushing + Passing Maps' : 'TE/WR: Passing & Coverage Maps'}
                  </span>
                </div>
                <p className="text-xs text-slate-400 font-sans">
                  {isRunningBack
                    ? 'Desglose integral de acarreos por carril, árbol de rutas y coberturas defensivas'
                    : 'Análisis aéreo de árbol de rutas (Route Tree) y rendimiento contra coberturas Man vs Zone'}
                </p>
              </div>
            </div>

            <button
              onClick={onClose}
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
                  {mockPlayers.map((p) => (
                    <option key={p.player_id} value={p.player_id}>
                      {p.name} ({p.team} · {p.position})
                    </option>
                  ))}
                </select>
              </div>

              {/* Tactical View Switcher filtered by position */}
              <div>
                <label className="block text-[11px] font-mono text-slate-400 uppercase tracking-wider mb-1">
                  Vista Táctica ({playerPosition})
                </label>
                <div className="flex rounded-xl border border-slate-700 bg-[#0D1527] p-1">
                  {availableViews.map(({ key, label, icon: Icon }) => (
                    <button
                      key={key}
                      type="button"
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
                  ))}
                </div>
              </div>
            </div>

            {/* Metric Selector (applicable to Rushing Heatmap and Route Tree) */}
            {activeMetrics && (
              <div>
                <label className="block text-[11px] font-mono text-slate-400 uppercase tracking-wider mb-1">
                  Métrica de Análisis
                </label>
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
            {currentView === 'heatmap' && isRunningBack && (
              <FieldHeatmap playerId={playerId} metric={heatmapMetric} />
            )}
            {currentView === 'routes' && (
              <RouteTree playerId={playerId} metric={routeMetric} />
            )}
            {currentView === 'coverage' && (
              <CoverageSplits playerId={playerId} />
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
            {!isRunningBack && (
              <span className="text-emerald-400">
                Mapa de acarreos deshabilitado para receptores ({playerPosition})
              </span>
            )}
          </div>

        </div>
      </div>
    </div>
  );
}
