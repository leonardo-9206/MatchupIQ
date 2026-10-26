// Static drawing of an American football field (top-down view).
// Scale: 10 SVG units = 1 yard. Full field = 120 yd long (incl. end zones) x 53.3 yd wide.
export const YARD = 10;
export const FIELD_LENGTH = 120 * YARD;
export const FIELD_WIDTH = 53.3 * YARD;
export const END_ZONE = 10 * YARD;

// Hash marks in the NFL sit 70'9" from each sideline (~23.58 yd).
const HASH_OFFSET = 23.58 * YARD;

export default function FootballField({ children }) {
  const yardLines = [];
  for (let yd = 5; yd <= 95; yd += 5) {
    yardLines.push(END_ZONE + yd * YARD);
  }

  const hashMarks = [];
  for (let yd = 1; yd < 100; yd++) {
    if (yd % 5 === 0) continue;
    hashMarks.push(END_ZONE + yd * YARD);
  }

  // Numbers 10, 20, 30, 40, 50, 40, ... 10
  const numbers = [10, 20, 30, 40, 50, 60, 70, 80, 90].map((yd) => ({
    x: END_ZONE + yd * YARD,
    label: yd <= 50 ? yd : 100 - yd,
  }));

  return (
    <svg
      viewBox={`0 0 ${FIELD_LENGTH} ${FIELD_WIDTH}`}
      className="w-full h-auto rounded-xl"
      role="img"
      aria-label="Football field heatmap"
    >
      {/* Turf */}
      <rect width={FIELD_LENGTH} height={FIELD_WIDTH} fill="#0B3D2E" />
      {/* End zones */}
      <rect width={END_ZONE} height={FIELD_WIDTH} fill="#0E1626" />
      <rect x={FIELD_LENGTH - END_ZONE} width={END_ZONE} height={FIELD_WIDTH} fill="#0E1626" />

      {/* Yard lines every 5 yards */}
      {yardLines.map((x) => (
        <line key={x} x1={x} y1={0} x2={x} y2={FIELD_WIDTH} stroke="#ffffff" strokeOpacity="0.35" strokeWidth="2" />
      ))}
      {/* Goal lines */}
      <line x1={END_ZONE} y1={0} x2={END_ZONE} y2={FIELD_WIDTH} stroke="#ffffff" strokeOpacity="0.8" strokeWidth="4" />
      <line x1={FIELD_LENGTH - END_ZONE} y1={0} x2={FIELD_LENGTH - END_ZONE} y2={FIELD_WIDTH} stroke="#ffffff" strokeOpacity="0.8" strokeWidth="4" />

      {/* Hash marks (sidelines + inbounds) */}
      {hashMarks.map((x) => (
        <g key={x} stroke="#ffffff" strokeOpacity="0.3" strokeWidth="1.5">
          <line x1={x} y1={0} x2={x} y2={8} />
          <line x1={x} y1={FIELD_WIDTH - 8} x2={x} y2={FIELD_WIDTH} />
          <line x1={x} y1={HASH_OFFSET - 4} x2={x} y2={HASH_OFFSET + 4} />
          <line x1={x} y1={FIELD_WIDTH - HASH_OFFSET - 4} x2={x} y2={FIELD_WIDTH - HASH_OFFSET + 4} />
        </g>
      ))}

      {/* Yard numbers */}
      {numbers.map(({ x, label }) => (
        <g key={x} fill="#ffffff" fillOpacity="0.35" fontSize="28" fontWeight="700" textAnchor="middle">
          <text x={x} y={60}>{label}</text>
          <text x={x} y={FIELD_WIDTH - 40}>{label}</text>
        </g>
      ))}

      {/* Heat layers and markers are drawn on top of the static field */}
      {children}
    </svg>
  );
}
