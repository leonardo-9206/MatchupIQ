import { useMemo, useState } from 'react';
import FootballField, { YARD, END_ZONE, FIELD_WIDTH } from './FootballField.jsx';
import { heatColor, normalize, HEAT_STOPS } from './heatScale.js';

// Lanes ordered from the offense's left to its right.
// The offense attacks to the right of the screen, so its left side is the top of the field.
const ZONES = [
  { key: 'left_end', label: 'Left End' },
  { key: 'left_tackle', label: 'Left Tackle' },
  { key: 'middle', label: 'Middle' },
  { key: 'right_tackle', label: 'Right Tackle' },
  { key: 'right_end', label: 'Right End' },
];

export const METRICS = {
  attempts: { label: 'Attempts', format: (v) => v },
  yards_gained: { label: 'Yards', format: (v) => v },
  success_rate: { label: 'Success %', format: (v) => `${v.toFixed(1)}%` },
  yards_per_attempt: { label: 'Yds / Att', format: (v) => v.toFixed(2) },
};

const LINE_OF_SCRIMMAGE = END_ZONE + 25 * YARD; // own 25-yard line
const LANE_LENGTH = 30 * YARD;
const LANE_HEIGHT = FIELD_WIDTH / ZONES.length;
const LANE_GAP = 4;

// Adds derived metrics to every row and indexes them by field_zone.
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

export default function FieldHeatmap({ data, metric = 'attempts' }) {
  const [activeZone, setActiveZone] = useState(null);

  const stats = useMemo(() => buildZoneStats(data), [data]);
  const values = ZONES.map((z) => stats[z.key]?.[metric] ?? 0);
  const min = Math.min(...values);
  const max = Math.max(...values);
  const totalAttempts = ZONES.reduce((sum, z) => sum + (stats[z.key]?.attempts ?? 0), 0);

  const active = activeZone ? stats[activeZone] : null;

  return (
    <div className="space-y-4">
      <FootballField>
        <defs>
          {/* One fading gradient per lane: solid at the line of scrimmage, transparent downfield */}
          {ZONES.map((zone, i) => (
            <linearGradient key={zone.key} id={`lane-${zone.key}`} x1="0" x2="1" y1="0" y2="0">
              <stop offset="0%" stopColor={heatColor(normalize(values[i], min, max))} stopOpacity="0.9" />
              <stop offset="100%" stopColor={heatColor(normalize(values[i], min, max))} stopOpacity="0" />
            </linearGradient>
          ))}
        </defs>

        {ZONES.map((zone, i) => {
          const row = stats[zone.key];
          const y = i * LANE_HEIGHT + LANE_GAP / 2;
          const height = LANE_HEIGHT - LANE_GAP;
          const isActive = activeZone === zone.key;
          const ypaX = LINE_OF_SCRIMMAGE + (row?.yards_per_attempt ?? 0) * YARD;

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
                x={LINE_OF_SCRIMMAGE}
                y={y}
                width={LANE_LENGTH}
                height={height}
                rx="8"
                fill={`url(#lane-${zone.key})`}
                stroke={isActive ? '#00D2FF' : 'transparent'}
                strokeWidth="3"
              />
              {/* Average gain marker */}
              {row && (
                <g>
                  <line x1={ypaX} y1={y + 10} x2={ypaX} y2={y + height - 10} stroke="#ffffff" strokeWidth="3" strokeDasharray="6 4" />
                  <text x={ypaX + 8} y={y + height / 2 + 6} fill="#ffffff" fontSize="16" fontWeight="600">
                    {row.yards_per_attempt.toFixed(1)} yd
                  </text>
                </g>
              )}
              {/* Lane label behind the line of scrimmage, on a pill so it stays readable over the yard numbers */}
              <rect
                x={LINE_OF_SCRIMMAGE - 150}
                y={y + height / 2 - 16}
                width="140"
                height="32"
                rx="16"
                fill="#0E1626"
                fillOpacity="0.85"
                stroke={isActive ? '#00D2FF' : 'transparent'}
              />
              <text
                x={LINE_OF_SCRIMMAGE - 80}
                y={y + height / 2 + 6}
                fill="#ffffff"
                fillOpacity={isActive ? 1 : 0.7}
                fontSize="18"
                fontWeight="600"
                textAnchor="middle"
              >
                {zone.label}
              </text>
            </g>
          );
        })}

        {/* Line of scrimmage */}
        <line
          x1={LINE_OF_SCRIMMAGE}
          y1={0}
          x2={LINE_OF_SCRIMMAGE}
          y2={FIELD_WIDTH}
          stroke="#00D2FF"
          strokeWidth="4"
          strokeDasharray="12 8"
          pointerEvents="none"
        />
      </FootballField>

      <div className="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
        {/* Legend */}
        <div className="w-full max-w-xs">
          <p className="mb-1 text-xs uppercase tracking-wide text-slate-400">{METRICS[metric].label}</p>
          <div className="h-3 rounded-full" style={{ background: `linear-gradient(to right, ${HEAT_STOPS.join(', ')})` }} />
          <div className="mt-1 flex justify-between text-xs text-slate-400">
            <span>{METRICS[metric].format(min)}</span>
            <span>{METRICS[metric].format(max)}</span>
          </div>
        </div>

        {/* Zone detail */}
        <div className="min-h-[88px] w-full rounded-xl border border-slate-800 bg-surface-2 p-4 md:max-w-sm">
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
