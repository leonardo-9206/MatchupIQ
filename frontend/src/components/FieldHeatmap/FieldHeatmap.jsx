import { useMemo, useState } from 'react';
import FootballField, { YARD, FIELD_WIDTH, yardToY } from './FootballField.jsx';
import { heatColor, normalize, HEAT_STOPS } from './heatScale.js';
import usePlayerHeatmap from '../../hooks/usePlayerHeatmap.js';

// Lanes ordered from the offense's left to its right.
// The offense attacks upward, so its left side is also the left of the screen (view from behind the QB).
const ZONES = [
  { key: 'left_end', label: 'Left End', short: 'L End' },
  { key: 'left_tackle', label: 'Left Tackle', short: 'L Tackle' },
  { key: 'middle', label: 'Middle', short: 'Middle' },
  { key: 'right_tackle', label: 'Right Tackle', short: 'R Tackle' },
  { key: 'right_end', label: 'Right End', short: 'R End' },
];

export const METRICS = {
  attempts: { label: 'Attempts', format: (v) => v },
  yards_gained: { label: 'Yards', format: (v) => v },
  success_rate: { label: 'Success %', format: (v) => `${v.toFixed(1)}%` },
  yards_per_attempt: { label: 'Yds / Att', format: (v) => v.toFixed(2) },
};

// Absolute yards (0 = back of the offense's end zone). The ball is snapped from the offense's own 25.
const LINE_OF_SCRIMMAGE_YARD = 10 + 25;
const LANE_YARDS = 35;
// Visible window: a few yards behind the line (for the lane labels) up to a bit past the end of the lanes
const VIEW_FROM_YARD = LINE_OF_SCRIMMAGE_YARD - 8;
const VIEW_TO_YARD = LINE_OF_SCRIMMAGE_YARD + LANE_YARDS + 5;

const LOS_Y = yardToY(LINE_OF_SCRIMMAGE_YARD);
const LANE_HEIGHT = LANE_YARDS * YARD;
const LANE_WIDTH = FIELD_WIDTH / ZONES.length;
const LANE_GAP = 4;

// Adds derived metrics to every row and indexes them by field_zone.
// The backend already sends yards_per_attempt, but the mock doesn't, so we compute it here for both.
function buildZoneStats(rows) {
  const byZone = {};
  for (const row of rows) {
    byZone[row.field_zone] = {
      ...row,
      yards_per_attempt: row.attempts > 0 ? row.yards_gained / row.attempts : 0,
    };
  }
  return byZone;
}

/**
 * Vertical rushing heatmap of the 5 run lanes.
 *
 * Two ways to use it:
 *   <FieldHeatmap playerId={prop.player_id} />  -> loads the data itself and reloads when playerId changes
 *   <FieldHeatmap data={zones} />               -> renders rows that the parent already loaded
 */
export default function FieldHeatmap({ playerId = null, data = null, metric = 'success_rate' }) {
  const [activeZone, setActiveZone] = useState(null);

  // When the parent passes `data` we skip the request by sending null to the hook
  const fetched = usePlayerHeatmap(data ? null : playerId);
  const rows = data ?? fetched.zones;
  const loading = !data && fetched.loading;

  const stats = useMemo(() => buildZoneStats(rows), [rows]);

  if (loading && rows.length === 0) {
    return <p className="py-16 text-center text-sm text-slate-400">Loading heatmap…</p>;
  }
  if (rows.length === 0) {
    return <p className="py-16 text-center text-sm text-slate-400">No heatmap data for this player.</p>;
  }

  const values = ZONES.map((z) => stats[z.key]?.[metric] ?? 0);
  const min = Math.min(...values);
  const max = Math.max(...values);
  const totalAttempts = ZONES.reduce((sum, z) => sum + (stats[z.key]?.attempts ?? 0), 0);
  const active = activeZone ? stats[activeZone] : null;

  return (
    <div className={`flex flex-col gap-4 md:flex-row md:items-start ${loading ? 'opacity-60' : ''}`}>
      <div className="mx-auto w-full max-w-md md:mx-0">
        <FootballField fromYard={VIEW_FROM_YARD} toYard={VIEW_TO_YARD}>
          <defs>
            {/* One fading gradient per lane: solid at the line of scrimmage (bottom), transparent downfield (top) */}
            {ZONES.map((zone, i) => (
              <linearGradient key={zone.key} id={`lane-${zone.key}`} x1="0" x2="0" y1="1" y2="0">
                <stop offset="0%" stopColor={heatColor(normalize(values[i], min, max))} stopOpacity="0.9" />
                <stop offset="100%" stopColor={heatColor(normalize(values[i], min, max))} stopOpacity="0" />
              </linearGradient>
            ))}
          </defs>

          {ZONES.map((zone, i) => {
            const row = stats[zone.key];
            const x = i * LANE_WIDTH + LANE_GAP / 2;
            const width = LANE_WIDTH - LANE_GAP;
            const centerX = x + width / 2;
            const isActive = activeZone === zone.key;
            const ypaY = LOS_Y - (row?.yards_per_attempt ?? 0) * YARD;

            return (
              <g
                key={zone.key}
                tabIndex={0}
                role="button"
                aria-label={`${zone.label}: ${METRICS[metric].label} ${row ? METRICS[metric].format(row[metric]) : 'no data'}`}
                className="cursor-pointer outline-none"
                onMouseEnter={() => setActiveZone(zone.key)}
                onMouseLeave={() => setActiveZone(null)}
                onFocus={() => setActiveZone(zone.key)}
                onBlur={() => setActiveZone(null)}
              >
                <rect
                  x={x}
                  y={LOS_Y - LANE_HEIGHT}
                  width={width}
                  height={LANE_HEIGHT}
                  rx="8"
                  fill={`url(#lane-${zone.key})`}
                  stroke={isActive ? '#00D2FF' : 'transparent'}
                  strokeWidth="3"
                />
                {/* Average gain marker: how far upfield the player gets per carry in this lane */}
                {row && (
                  <g>
                    <line x1={x + 10} y1={ypaY} x2={x + width - 10} y2={ypaY} stroke="#ffffff" strokeWidth="3" strokeDasharray="6 4" />
                    <text x={centerX} y={ypaY - 10} fill="#ffffff" fontSize="16" fontWeight="600" textAnchor="middle">
                      {row.yards_per_attempt.toFixed(1)} yd
                    </text>
                  </g>
                )}
                {/* Lane label behind the line of scrimmage, on a pill so it stays readable over the field */}
                <rect
                  x={x + 4}
                  y={LOS_Y + 24}
                  width={width - 8}
                  height="32"
                  rx="16"
                  fill="#0E1626"
                  fillOpacity="0.85"
                  stroke={isActive ? '#00D2FF' : 'transparent'}
                />
                <text
                  x={centerX}
                  y={LOS_Y + 46}
                  fill="#ffffff"
                  fillOpacity={isActive ? 1 : 0.75}
                  fontSize="15"
                  fontWeight="600"
                  textAnchor="middle"
                >
                  {zone.short}
                </text>
              </g>
            );
          })}

          {/* Line of scrimmage */}
          <line
            x1={0}
            y1={LOS_Y}
            x2={FIELD_WIDTH}
            y2={LOS_Y}
            stroke="#00D2FF"
            strokeWidth="4"
            strokeDasharray="12 8"
            pointerEvents="none"
          />
        </FootballField>
      </div>

      <div className="flex w-full flex-col gap-4 md:max-w-xs">
        {!data && fetched.source && (
          <span
            className={`self-start rounded-full px-3 py-1 text-xs font-semibold ${
              fetched.source === 'backend' ? 'bg-emerald-500/10 text-[#00E676]' : 'bg-amber-500/10 text-[#FF9100]'
            }`}
          >
            {fetched.source === 'backend' ? 'Live data · /api/tactics' : 'Mock data · backend offline'}
          </span>
        )}

        {/* Legend */}
        <div>
          <p className="mb-1 text-xs uppercase tracking-wide text-slate-400">{METRICS[metric].label}</p>
          <div className="h-3 rounded-full" style={{ background: `linear-gradient(to right, ${HEAT_STOPS.join(', ')})` }} />
          <div className="mt-1 flex justify-between text-xs text-slate-400">
            <span>{METRICS[metric].format(min)}</span>
            <span>{METRICS[metric].format(max)}</span>
          </div>
        </div>

        {/* Zone detail */}
        <div className="min-h-[88px] rounded-xl border border-slate-800 bg-surface-2 p-4">
          {active ? (
            <>
              <p className="text-sm font-semibold text-[#00D2FF]">
                {ZONES.find((z) => z.key === activeZone).label}
              </p>
              <dl className="mt-2 grid grid-cols-4 gap-2 text-center text-sm">
                <div><dt className="text-xs text-slate-400">Att</dt><dd>{active.attempts}</dd></div>
                <div><dt className="text-xs text-slate-400">Yds</dt><dd>{active.yards_gained}</dd></div>
                <div><dt className="text-xs text-slate-400">Succ</dt><dd>{active.success_rate.toFixed(1)}%</dd></div>
                <div>
                  <dt className="text-xs text-slate-400">Share</dt>
                  <dd>{totalAttempts ? ((active.attempts / totalAttempts) * 100).toFixed(1) : 0}%</dd>
                </div>
              </dl>
            </>
          ) : (
            <p className="text-sm text-slate-400">Hover or tab through a lane to see its breakdown.</p>
          )}
        </div>
      </div>
    </div>
  );
}
