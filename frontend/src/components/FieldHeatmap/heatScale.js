// Color scale for the heatmap: cold (few) -> hot (many).
// Stops use the official palette: Royal Blue Tech -> Cyan Pulse -> Hyper Orange -> Crimson Alert.
export const HEAT_STOPS = ['#0066FF', '#00D2FF', '#FF9100', '#FF3B30'];

function hexToRgb(hex) {
  const n = parseInt(hex.slice(1), 16);
  return [(n >> 16) & 255, (n >> 8) & 255, n & 255];
}

// t goes from 0 to 1. We find which pair of stops t falls between and blend them linearly.
export function heatColor(t) {
  const clamped = Math.min(Math.max(t, 0), 1);
  const segment = clamped * (HEAT_STOPS.length - 1);
  const i = Math.min(Math.floor(segment), HEAT_STOPS.length - 2);
  const local = segment - i;
  const [r1, g1, b1] = hexToRgb(HEAT_STOPS[i]);
  const [r2, g2, b2] = hexToRgb(HEAT_STOPS[i + 1]);
  const mix = (a, b) => Math.round(a + (b - a) * local);
  return `rgb(${mix(r1, r2)}, ${mix(g1, g2)}, ${mix(b1, b2)})`;
}

// Normalizes a value against the min/max of the current dataset,
// so the hottest zone is always red and the coldest is always blue.
export function normalize(value, min, max) {
  if (max === min) return 1;
  return (value - min) / (max - min);
}
