import { describe, it, expect, vi } from 'vitest';
import { render, screen, fireEvent } from '@testing-library/react';
import FieldHeatmap from './FieldHeatmap.jsx';
import { mockHeatmaps } from '../../mocks/heatmapMock.js';

const pacheco = mockHeatmaps[3];

describe('FieldHeatmap', () => {
  it('draws one interactive lane per field zone', () => {
    render(<FieldHeatmap data={pacheco} metric="attempts" />);

    const lanes = screen.getAllByRole('button');
    expect(lanes).toHaveLength(5);
    expect(screen.getByLabelText('Middle: Attempts 68')).toBeInTheDocument();
  });

  it('shows the zone breakdown on hover', () => {
    render(<FieldHeatmap data={pacheco} metric="success_rate" />);

    fireEvent.mouseEnter(screen.getByLabelText('Left Tackle: Success % 64.3%'));

    expect(screen.getByText('Left Tackle')).toBeInTheDocument();
    expect(screen.getByText('215')).toBeInTheDocument(); // yards
    // 42 of 190 total attempts
    expect(screen.getByText('22.1%')).toBeInTheDocument();
  });

  it('shows the legend range of the selected metric', () => {
    render(<FieldHeatmap data={pacheco} metric="attempts" />);

    expect(screen.getByText('18')).toBeInTheDocument();
    expect(screen.getByText('68')).toBeInTheDocument();
  });

  it('loads its own data from playerId and reloads when it changes', async () => {
    // Backend offline -> the service falls back to the mock of each player
    vi.stubGlobal('fetch', vi.fn().mockRejectedValue(new TypeError('Failed to fetch')));

    const { rerender } = render(<FieldHeatmap playerId={3} metric="attempts" />);
    expect(await screen.findByLabelText('Middle: Attempts 68')).toBeInTheDocument();
    expect(screen.getByText('Mock data · backend offline')).toBeInTheDocument();

    rerender(<FieldHeatmap playerId={4} metric="attempts" />);
    expect(await screen.findByLabelText('Middle: Attempts 52')).toBeInTheDocument();
    expect(fetch).toHaveBeenLastCalledWith(expect.stringContaining('player_id=4'), expect.any(Object));
  });

  it('does not call the API when the parent passes data', () => {
    vi.stubGlobal('fetch', vi.fn());

    render(<FieldHeatmap data={pacheco} />);

    expect(fetch).not.toHaveBeenCalled();
  });

  it('shows an empty state for players without heatmap data', () => {
    render(<FieldHeatmap data={[]} />);

    expect(screen.getByText('No tactical data for this player.')).toBeInTheDocument();
  });
});
