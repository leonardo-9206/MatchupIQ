import { useState } from 'react';
import FieldHeatmap, { METRICS } from '../components/FieldHeatmap/FieldHeatmap.jsx';
import RouteTree, { ROUTE_METRICS } from '../components/Tactics/RouteTree.jsx';
import CoverageSplits from '../components/Tactics/CoverageSplits.jsx';

// Players from backend/database/seed.sql that have some tactical data (real or mock)
const PLAYERS = [
  { player_id: 2, name: 'Travis Kelce', label: 'KC · TE' },
  { player_id: 3, name: 'Isiah Pacheco', label: 'KC · RB' },
  { player_id: 4, name: 'Christian McCaffrey', label: 'SF · RB' },
];

const VIEWS = {
  heatmap: { label: 'Rushing Heatmap', metrics: METRICS, defaultMetric: 'success_rate' },
  routes: { label: 'Route Tree', metrics: ROUTE_METRICS, defaultMetric: 'catch_rate' },
  coverage: { label: 'Man vs Zone', metrics: null },
};

function Segmented({ options, value, onChange }) {
  return (
    <div className="flex flex-wrap rounded-lg border border-slate-700 bg-surface-1 p-1">
      {options.map(([key, label]) => (
        <button
          key={key}
          onClick={() => onChange(key)}
          className={`rounded-md px-3 py-1 text-sm ${
            value === key ? 'bg-primary-deep text-white' : 'text-slate-400 hover:text-slate-100'
          }`}
        >
          {label}
        </button>
      ))}
    </div>
  );
}

export default function TacticsPlayground() {
  const [playerId, setPlayerId] = useState(PLAYERS[0].player_id);
  const [view, setView] = useState('routes');
  const [metric, setMetric] = useState(VIEWS.routes.defaultMetric);

  const changeView = (next) => {
    setView(next);
    if (VIEWS[next].defaultMetric) setMetric(VIEWS[next].defaultMetric);
  };

  const { metrics } = VIEWS[view];

  return (
    <main className="mx-auto max-w-5xl space-y-6 p-4 md:p-8">
      <header>
        <h1 className="text-2xl font-bold text-[#00D2FF]">Tactics Playground</h1>
        <p className="text-sm text-slate-400">Dev page for FieldHeatmap, RouteTree and CoverageSplits</p>
      </header>

      <div className="flex flex-wrap gap-3">
        <select
          aria-label="Player"
          value={playerId}
          onChange={(e) => setPlayerId(Number(e.target.value))}
          className="rounded-lg border border-slate-700 bg-surface-1 px-3 py-2 text-sm"
        >
          {PLAYERS.map((p) => (
            <option key={p.player_id} value={p.player_id}>
              {p.name} ({p.label})
            </option>
          ))}
        </select>
        <Segmented options={Object.entries(VIEWS).map(([k, v]) => [k, v.label])} value={view} onChange={changeView} />
        {metrics && (
          <Segmented options={Object.entries(metrics).map(([k, v]) => [k, v.label])} value={metric} onChange={setMetric} />
        )}
      </div>

      <section className="rounded-2xl border border-slate-800 bg-surface-1 p-4">
        {view === 'heatmap' && <FieldHeatmap playerId={playerId} metric={metric} />}
        {view === 'routes' && <RouteTree playerId={playerId} metric={metric} />}
        {view === 'coverage' && <CoverageSplits playerId={playerId} />}
      </section>
    </main>
  );
}
