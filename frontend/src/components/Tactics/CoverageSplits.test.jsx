import { describe, it, expect, vi } from 'vitest';
import { render, screen, within } from '@testing-library/react';
import CoverageSplits from './CoverageSplits.jsx';

const offline = () => vi.stubGlobal('fetch', vi.fn().mockRejectedValue(new TypeError('Failed to fetch')));

describe('CoverageSplits', () => {
  it('shows Man and Zone cards with their catch rates (mock fallback)', async () => {
    offline();
    render(<CoverageSplits playerId={2} />);

    const man = await screen.findByTestId('coverage-man');
    expect(within(man).getByText('74.3%')).toBeInTheDocument();
    expect(within(screen.getByTestId('coverage-zone')).getByText('79.3%')).toBeInTheDocument();
  });

  it('marks the scheme with the best catch rate as the best matchup', async () => {
    offline();
    render(<CoverageSplits playerId={2} />);

    const zone = await screen.findByTestId('coverage-zone');
    expect(within(zone).getByText('Best matchup')).toBeInTheDocument();
    expect(within(screen.getByTestId('coverage-man')).queryByText('Best matchup')).not.toBeInTheDocument();
  });

  it('trusts preferred_scheme from the backend summary', async () => {
    vi.stubGlobal(
      'fetch',
      vi.fn().mockResolvedValue({
        ok: true,
        json: async () => ({
          status: 'success',
          data: {
            player: {},
            splits: [
              { coverage_type: 'man', targets: 10, receptions: 8, catch_rate: 80 },
              { coverage_type: 'zone', targets: 10, receptions: 7, catch_rate: 70 },
            ],
            summary: { preferred_scheme: 'man' },
          },
        }),
      })
    );
    render(<CoverageSplits playerId={7} />);

    const man = await screen.findByTestId('coverage-man');
    expect(within(man).getByText('Best matchup')).toBeInTheDocument();
    expect(screen.getByText('Live data · /api/tactics')).toBeInTheDocument();
  });

  it('shows the empty state for players without coverage data', async () => {
    offline();
    render(<CoverageSplits playerId={999} />);

    expect(await screen.findByText('No tactical data for this player.')).toBeInTheDocument();
  });
});
