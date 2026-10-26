import React, { useState } from 'react';
import { X, Flame, Info } from 'lucide-react';
import FieldHeatmap, { METRICS } from './FieldHeatmap/FieldHeatmap.jsx';
import { mockPlayers, mockHeatmaps } from '../mocks/heatmapMock.js';

export default function TacticalHeatmapModal({ isOpen, onClose }) {
  const [playerId, setPlayerId] = useState(mockPlayers[0]?.player_id ?? 1);
  const [metric, setMetric] = useState('attempts');

  if (!isOpen) return null;

  const currentPlayer = mockPlayers.find((p) => p.player_id === playerId);

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
                <h3 className="font-display text-xl font-bold text-white">
                  Mapa Táctico del Campo NFL
                </h3>
                <p className="text-xs text-slate-400 font-sans">
                  Distribución de acarreos por carril y zona de ataque (Módulo Táctico)
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

          {/* Controls: Player selector & Metric buttons */}
          <div className="mt-5 flex flex-wrap items-center justify-between gap-3">
            <div>
              <label className="block text-[11px] font-mono text-slate-400 uppercase tracking-wider mb-1">
                Atleta Seleccionado
              </label>
              <select
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

            <div>
              <label className="block text-[11px] font-mono text-slate-400 uppercase tracking-wider mb-1">
                Métrica de Análisis
              </label>
              <div className="flex rounded-xl border border-slate-700 bg-[#0D1527] p-1">
                {Object.entries(METRICS).map(([key, { label }]) => (
                  <button
                    key={key}
                    onClick={() => setMetric(key)}
                    className={`rounded-lg px-3 py-1.5 text-xs font-semibold transition-all ${
                      metric === key
                        ? 'bg-sky-500 text-slate-950 font-bold shadow-sm'
                        : 'text-slate-400 hover:text-white'
                    }`}
                  >
                    {label}
                  </button>
                ))}
              </div>
            </div>
          </div>

          {/* Heatmap Visual Container */}
          <div className="mt-5 overflow-hidden rounded-xl border border-slate-800 bg-[#050811] p-4">
            {mockHeatmaps[playerId] ? (
              <FieldHeatmap data={mockHeatmaps[playerId]} metric={metric} />
            ) : (
              <div className="py-12 text-center text-xs text-slate-400">
                No hay datos de mapa de calor registrados para este jugador.
              </div>
            )}
          </div>

          {/* Footer note */}
          <div className="mt-4 flex items-center gap-2 text-[11px] text-slate-400 font-mono">
            <Info className="h-4 w-4 text-sky-400" />
            <span>
              Visualización basada en coordenadas oficiales de jugadas de la NFL (Carriles ofensivos: Extremo izquierdo a extremo derecho).
            </span>
          </div>

        </div>
      </div>
    </div>
  );
}
