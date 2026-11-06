import { useMemo, useState } from 'react';
import FootballField, { YARD, FIELD_WIDTH, LINE_OF_SCRIMMAGE_YARD, yardToY } from '../FieldHeatmap/FootballField.jsx';
import { heatColor, normalize } from '../FieldHeatmap/heatScale.js';
import { usePlayerRouteTree } from '../../hooks/useTacticsData.js';
import { SourceBadge, HeatLegend, TacticsPlaceholder } from './TacticsUI.jsx';

// Classic route tree, drawn for a receiver lined up on the right side of the formation.
// Each route is a list of [dx, dy] points in yards from where the receiver starts:
// dx > 0 breaks toward the sideline (outside), dx < 0 toward the middle (inside); dy is depth upfield.
// `labelAt` is placed by hand so the names don't overlap where several routes share the stem.
export const ROUTES = {
  flat: { label: 'Flat', points: [[0, 0], [2, 1.5], [9, 3]], labelAt: [11.5, 3.5] },
  slant: { label: 'Slant', points: [[0, 0], [0, 3], [-7, 10]], labelAt: [-8.5, 8.5] },
  comeback: { label: 'Comeback', points: [[0, 0], [0, 15], [3, 12]], labelAt: [6, 13.5] },
  curl: { label: 'Curl', points: [[0, 0], [0, 12], [-2, 10]], labelAt: [-2.5, 8] },
  out: { label: 'Out', points: [[0, 0], [0, 10], [9, 10]], labelAt: [11.5, 10] },
  dig: { label: 'Dig', points: [[0, 0], [0, 12], [-12, 12]], labelAt: [-12, 14] },
  corner: { label: 'Corner', points: [[0, 0], [0, 12], [7, 21]], labelAt: [8, 23] },
  post: { label: 'Post', points: [[0, 0], [0, 12], [-7, 23]], labelAt: [-8, 25] },
  go: { label: 'Go', points: [[0, 0], [0, 28]], labelAt: [0, 30] },
};

export const ROUTE_METRICS = {
  catch_rate: { label: 'Catch %', format: (v) => `${v.toFixed(1)}%` },
  targets: { label: 'Targets', format: (v) => v },
  yards_per_target: { label: 'Yds / Tgt', format: (v) => v.toFixed(1) },
};

// Receiver alignment: slot on the right, 15 yards from the sideline (field is 53.3 yd wide)
const START_X_YARDS = FIELD_WIDTH / YARD - 15;
const START_X = START_X_YARDS * YARD;
const LOS_Y = yardToY(LINE_OF_SCRIMMAGE_YARD);
// Zoom on the right side of the field, where the whole tree happens
const VIEW = {
  fromYard: LINE_OF_SCRIMMAGE_YARD - 4,
  toYard: LINE_OF_SCRIMMAGE_YARD + 32,
  fromX: START_X_YARDS - 16,
  toX: FIELD_WIDTH / YARD,
};

// Converts a point in route yards [dx, dy] to SVG coordinates
const toSvg = ([dx, dy]) => [START_X + dx * YARD, LOS_Y - dy * YARD];
const toSvgPoints = (points) => points.map((p) => toSvg(p).join(',')).join(' ');

/**
 * Interactive route tree of a receiver.
 *   <RouteTree playerId={prop.player_id} />   -> loads its own data
 *   <RouteTree data={routes} />                -> renders rows loaded by the parent
 * Line color = selected metric (cold to hot), line width = share of the player's targets.
 */
export default function RouteTree({ playerId = null, data = null, metric = 'catch_rate' }) {
  const [activeRoute, setActiveRoute] = useState(null);

  const fetched = usePlayerRouteTree(data ? null : playerId);
  const rows = data ?? fetched.routes ?? [];
  const loading = !data && fetched.loading;

  const routes = useMemo(
    () =>
      rows
        .filter((r) => ROUTES[r.route_type])
        .map((r) => ({ ...r, yards_per_target: r.targets > 0 ? r.yards_gained / r.targets : 0 })),
    [rows]
  );

  if (routes.length === 0) {
    return <TacticsPlaceholder loading={loading} />;
  }

  const values = routes.map((r) => r[metric]);
  const min = Math.min(...values);
  const max = Math.max(...values);
  const totalTargets = routes.reduce((sum, r) => sum + r.targets, 0);
  const maxShare = Math.max(...routes.map((r) => r.targets / totalTargets));
  const active = routes.find((r) => r.route_type === activeRoute);

  // Draw the hovered route last so it stays on top of the others
  const drawOrder = [...routes].sort((a, b) => (a.route_type === activeRoute) - (b.route_type === activeRoute));

  return (
    <div className={`flex flex-col gap-4 md:flex-row md:items-start ${loading ? 'opacity-60' : ''}`}>
      <div className="mx-auto w-full max-w-md md:mx-0">
        <FootballField {...VIEW}>
          <defs>
            {/* context-stroke paints the arrow head with the same color as the line using it.
                userSpaceOnUse keeps the head the same size no matter how thick the line is. */}
            <marker id="route-arrow" viewBox="0 0 10 10" refX="5" refY="5" markerWidth="18" markerHeight="18" markerUnits="userSpaceOnUse" orient="auto-start-reverse">
              <path d="M0,0 L10,5 L0,10 z" fill="context-stroke" />
            </marker>
          </defs>

          {/* Line of scrimmage */}
          <line x1={0} y1={LOS_Y} x2={FIELD_WIDTH} y2={LOS_Y} stroke="#00D2FF" strokeWidth="4" strokeDasharray="12 8" pointerEvents="none" />

          {drawOrder.map((route) => {
            const shape = ROUTES[route.route_type];
            const color = heatColor(normalize(route[metric], min, max));
            const width = 3 + (route.targets / totalTargets / maxShare) * 7;
            const isActive = activeRoute === route.route_type;
            const dimmed = activeRoute && !isActive;
            const [labelX, labelY] = toSvg(shape.labelAt);

            return (
              <g
                key={route.route_type}
                data-testid={`route-${route.route_type}`}
                tabIndex={0}
                role="button"
                aria-label={`${shape.label}: ${ROUTE_METRICS[metric].label} ${ROUTE_METRICS[metric].format(route[metric])}`}
                className="cursor-pointer outline-none transition-opacity"
                opacity={dimmed ? 0.25 : 1}
                onMouseEnter={() => setActiveRoute(route.route_type)}
                onMouseLeave={() => setActiveRoute(null)}
                onFocus={() => setActiveRoute(route.route_type)}
                onBlur={() => setActiveRoute(null)}
              >
                {/* Wide invisible line so thin routes are still easy to hover */}
                <polyline points={toSvgPoints(shape.points)} fill="none" stroke="transparent" strokeWidth="24" />
                <polyline
                  points={toSvgPoints(shape.points)}
                  fill="none"
                  stroke={color}
                  strokeWidth={isActive ? width + 2 : width}
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  markerEnd="url(#route-arrow)"
                />
                <text x={labelX} y={labelY} fill="#ffffff" fontSize="13" fontWeight="600" textAnchor="middle" dominantBaseline="middle">
                  {shape.label}
                </text>
              </g>
            );
          })}

          {/* Receiver starting spot */}
          <circle cx={START_X} cy={LOS_Y} r="9" fill="#0E1626" stroke="#00D2FF" strokeWidth="3" pointerEvents="none" />
        </FootballField>
      </div>

      <div className="flex w-full flex-col gap-4 md:max-w-xs">
        {!data && <SourceBadge source={fetched.source} />}

        <HeatLegend
          label={`${ROUTE_METRICS[metric].label} · width = target share`}
          min={ROUTE_METRICS[metric].format(min)}
          max={ROUTE_METRICS[metric].format(max)}
        />

        <div className="min-h-[88px] rounded-xl border border-slate-800 bg-surface-2 p-4">
          {active ? (
            <>
              <p className="text-sm font-semibold text-[#00D2FF]">{ROUTES[active.route_type].label}</p>
              <dl className="mt-2 grid grid-cols-4 gap-2 text-center text-sm">
                <div><dt className="text-xs text-slate-400">Tgt</dt><dd>{active.targets}</dd></div>
                <div><dt className="text-xs text-slate-400">Rec</dt><dd>{active.receptions}</dd></div>
                <div><dt className="text-xs text-slate-400">Yds</dt><dd>{active.yards_gained}</dd></div>
                <div><dt className="text-xs text-slate-400">Catch</dt><dd>{active.catch_rate.toFixed(1)}%</dd></div>
              </dl>
            </>
          ) : (
            <p className="text-sm text-slate-400">Hover or tab through a route to see its breakdown.</p>
          )}
        </div>
      </div>
    </div>
  );
}
