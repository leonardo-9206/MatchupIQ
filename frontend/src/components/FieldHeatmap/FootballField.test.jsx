import { describe, it, expect } from 'vitest';
import { render, screen, within } from '@testing-library/react';
import FootballField, { LINE_OF_SCRIMMAGE_YARD } from './FootballField.jsx';

const markings = () => screen.getByTestId('field-markings');
const lineCount = () => markings().querySelectorAll('line').length;
const numbers = () => [...markings().querySelectorAll('text')].map((t) => t.textContent);

describe('FootballField', () => {
  it('draws every mark of the 100-yard field when no window is given', () => {
    render(<FootballField />);

    // 19 yard lines + 2 goal lines + 80 hash rows x 4 marks
    expect(lineCount()).toBe(19 + 2 + 80 * 4);
    // Each number is drawn on both sidelines
    expect(numbers()).toHaveLength(18);
  });

  it('only creates the marks inside the visible window', () => {
    // Same window the rushing heatmap uses: 8 yards behind the line, 40 ahead
    render(<FootballField fromYard={LINE_OF_SCRIMMAGE_YARD - 8} toYard={LINE_OF_SCRIMMAGE_YARD + 40} />);

    expect(lineCount()).toBeLessThan(250);
    // Own 25 is the line of scrimmage: numbers from the 20 to the opponent's 40 are visible, the 10s are not
    expect(numbers()).toEqual(['20', '20', '30', '30', '40', '40', '50', '50', '40', '40']);
  });

  it('keeps the viewBox on the requested window', () => {
    const { container } = render(<FootballField fromYard={27} toYard={75} fromX={20} toX={53.3} />);

    // x from 20 yd, top at yard 75 (1200 - 750), 33.3 yd wide, 48 yd tall
    expect(container.querySelector('svg').getAttribute('viewBox')).toBe('200 450 333 480');
  });

  it('draws the children on top of the field', () => {
    render(
      <FootballField>
        <circle data-testid="overlay" />
      </FootballField>
    );

    const svg = screen.getByRole('img', { name: 'Football field heatmap' });
    expect(within(svg).getByTestId('overlay')).toBeInTheDocument();
    expect(svg.lastChild).toBe(screen.getByTestId('overlay'));
  });
});
