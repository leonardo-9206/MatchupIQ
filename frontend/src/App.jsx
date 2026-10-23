import { useState } from 'react';
import FieldHeatmap, { METRICS } from './components/FieldHeatmap/FieldHeatmap.jsx';
import { mockPlayers, mockHeatmaps } from './mocks/heatmapMock.js';

// Temporary demo page for the tactical heatmap.
// Carlos' dashboard will replace this once the main layout and router exist.
export default function App() {
  const [playerId, setPlayerId] = useState(mockPlayers[0].player_id);
  const [metric, setMetric] = useState('attempts');

  return (
    <main className="mx-auto max-w-6xl space-y-6 p-4 md:p-8">
      <header>
        <h1 className="text-2xl font-bold text-primary-electric">Rushing Heatmap</h1>
        <p className="text-sm text-slate-400">Run lane breakdown per player (mock data)</p>
      </header>

      <div className="flex flex-wrap gap-3">
        <select
          value={playerId}
          onChange={(e) => setPlayerId(Number(e.target.value))}
          className="rounded-lg border border-slate-700 bg-surface-1 px-3 py-2 text-sm"
        >
          {mockPlayers.map((p) => (
            <option key={p.player_id} value={p.player_id}>
              {p.name} ({p.team} · {p.position})
            </option>
          ))}
        </select>

        <div className="flex rounded-lg border border-slate-700 bg-surface-1 p-1">
          {Object.entries(METRICS).map(([key, { label }]) => (
            <button
              key={key}
              onClick={() => setMetric(key)}
              className={`rounded-md px-3 py-1 text-sm ${
                metric === key ? 'bg-primary-deep text-white' : 'text-slate-400 hover:text-slate-100'
              }`}
            >
              {label}
            </button>
          ))}
        </div>
      </div>

      <section className="rounded-2xl border border-slate-800 bg-surface-1 p-4">
        <FieldHeatmap data={mockHeatmaps[playerId]} metric={metric} />
      </section>
    </main>
  );
}
