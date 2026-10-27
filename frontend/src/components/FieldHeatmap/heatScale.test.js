import { describe, it, expect } from 'vitest';
import { heatColor, normalize } from './heatScale.js';

describe('heatColor', () => {
  it('returns the first stop (blue) for the coldest value', () => {
    expect(heatColor(0)).toBe('rgb(0, 102, 255)');
  });

  it('returns the last stop (red) for the hottest value', () => {
    expect(heatColor(1)).toBe('rgb(255, 59, 48)');
  });

  it('clamps values outside 0..1', () => {
    expect(heatColor(-5)).toBe(heatColor(0));
    expect(heatColor(7)).toBe(heatColor(1));
  });
});

describe('normalize', () => {
  it('maps the range to 0..1', () => {
    expect(normalize(18, 18, 68)).toBe(0);
    expect(normalize(68, 18, 68)).toBe(1);
    expect(normalize(43, 18, 68)).toBe(0.5);
  });

  it('avoids dividing by zero when every value is equal', () => {
    expect(normalize(5, 5, 5)).toBe(1);
  });
});
