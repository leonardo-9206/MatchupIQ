import { HEAT_STOPS } from '../FieldHeatmap/heatScale.js';

// Small pieces shared by FieldHeatmap, RouteTree and CoverageSplits.

/** Shows whether the numbers come from the PHP API or from the local mock. */
export function SourceBadge({ source }) {
  if (!source) return null;
  const live = source === 'backend';
  return (
    <span
      className={`self-start rounded-full px-3 py-1 text-xs font-semibold ${
        live ? 'bg-emerald-500/10 text-[#00E676]' : 'bg-amber-500/10 text-[#FF9100]'
      }`}
    >
      {live ? 'Live data · /api/tactics' : 'Mock data · backend offline'}
    </span>
  );
}

/** Cold-to-hot gradient bar with the min and max values of the current metric. */
export function HeatLegend({ label, min, max }) {
  return (
    <div>
      <p className="mb-1 text-xs uppercase tracking-wide text-slate-400">{label}</p>
      <div className="h-3 rounded-full" style={{ background: `linear-gradient(to right, ${HEAT_STOPS.join(', ')})` }} />
      <div className="mt-1 flex justify-between text-xs text-slate-400">
        <span>{min}</span>
        <span>{max}</span>
      </div>
    </div>
  );
}

/** Loading / empty placeholder used while a player has no rows to draw. */
export function TacticsPlaceholder({ loading }) {
  return (
    <p className="py-16 text-center text-sm text-slate-400">
      {loading ? 'Loading tactical data…' : 'No tactical data for this player.'}
    </p>
  );
}
