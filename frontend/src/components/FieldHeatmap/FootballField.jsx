// Static drawing of an American football field in vertical orientation:
// the offense's own end zone is at the bottom and it attacks upward, like the view from behind the QB.
// Scale: 10 SVG units = 1 yard. Full field = 120 yd long (incl. end zones) x 53.3 yd wide.
export const YARD = 10;
export const FIELD_LENGTH = 120 * YARD;
export const FIELD_WIDTH = 53.3 * YARD;
export const END_ZONE = 10 * YARD;

// Hash marks in the NFL sit 70'9" from each sideline (~23.58 yd).
const HASH_OFFSET = 23.58 * YARD;

// "Absolute" yards go from 0 (back of the offense's end zone) to 120 (back of the opponent's end zone).
// In SVG, y grows downward, so we flip it to put yard 0 at the bottom.
export const yardToY = (absYard) => FIELD_LENGTH - absYard * YARD;

/**
 * Draws the whole field but only shows the window between fromYard and toYard (absolute yards),
 * so the parent can zoom into the area around the line of scrimmage.
 */
export default function FootballField({ fromYard = 0, toYard = 120, children }) {
  const yardLines = [];
  for (let yd = 5; yd <= 95; yd += 5) {
    yardLines.push(yardToY(10 + yd));
  }

  const hashMarks = [];
  for (let yd = 1; yd < 100; yd++) {
    if (yd % 5 === 0) continue;
    hashMarks.push(yardToY(10 + yd));
  }

  // Numbers 10, 20, 30, 40, 50, 40, ... 10, read from the sidelines
  const numbers = [10, 20, 30, 40, 50, 60, 70, 80, 90].map((yd) => ({
    y: yardToY(10 + yd),
    label: yd <= 50 ? yd : 100 - yd,
  }));

  const viewTop = yardToY(toYard);
  const viewHeight = (toYard - fromYard) * YARD;

  return (
    <svg
      viewBox={`0 ${viewTop} ${FIELD_WIDTH} ${viewHeight}`}
      className="h-auto w-full rounded-xl"
      role="img"
      aria-label="Football field heatmap"
    >
      {/* Turf */}
      <rect width={FIELD_WIDTH} height={FIELD_LENGTH} fill="#0B3D2E" />
      {/* End zones (opponent's at the top, offense's at the bottom) */}
      <rect width={FIELD_WIDTH} height={END_ZONE} fill="#0E1626" />
      <rect y={FIELD_LENGTH - END_ZONE} width={FIELD_WIDTH} height={END_ZONE} fill="#0E1626" />

      {/* Yard lines every 5 yards */}
      {yardLines.map((y) => (
        <line key={y} x1={0} y1={y} x2={FIELD_WIDTH} y2={y} stroke="#ffffff" strokeOpacity="0.35" strokeWidth="2" />
      ))}
      {/* Goal lines */}
      <line x1={0} y1={END_ZONE} x2={FIELD_WIDTH} y2={END_ZONE} stroke="#ffffff" strokeOpacity="0.8" strokeWidth="4" />
      <line x1={0} y1={FIELD_LENGTH - END_ZONE} x2={FIELD_WIDTH} y2={FIELD_LENGTH - END_ZONE} stroke="#ffffff" strokeOpacity="0.8" strokeWidth="4" />

      {/* Hash marks (sidelines + inbounds) */}
      {hashMarks.map((y) => (
        <g key={y} stroke="#ffffff" strokeOpacity="0.3" strokeWidth="1.5">
          <line x1={0} y1={y} x2={8} y2={y} />
          <line x1={FIELD_WIDTH - 8} y1={y} x2={FIELD_WIDTH} y2={y} />
          <line x1={HASH_OFFSET - 4} y1={y} x2={HASH_OFFSET + 4} y2={y} />
          <line x1={FIELD_WIDTH - HASH_OFFSET - 4} y1={y} x2={FIELD_WIDTH - HASH_OFFSET + 4} y2={y} />
        </g>
      ))}

      {/* Yard numbers, rotated so they face each sideline like on a real field */}
      {numbers.map(({ y, label }) => (
        <g key={y} fill="#ffffff" fillOpacity="0.35" fontSize="28" fontWeight="700" textAnchor="middle">
          <text x={50} y={y} dominantBaseline="middle" transform={`rotate(90 50 ${y})`}>{label}</text>
          <text x={FIELD_WIDTH - 50} y={y} dominantBaseline="middle" transform={`rotate(-90 ${FIELD_WIDTH - 50} ${y})`}>{label}</text>
        </g>
      ))}

      {/* Heat layers and markers are drawn on top of the static field */}
      {children}
    </svg>
  );
}
