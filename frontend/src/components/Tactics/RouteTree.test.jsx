import { describe, it, expect } from 'vitest';
import { render, screen, fireEvent } from '@testing-library/react';
import RouteTree from './RouteTree.jsx';
import { mockRouteTrees } from '../../mocks/routeTreeMock.js';

const kelce = mockRouteTrees[2];

describe('RouteTree', () => {
  it('draws every known route of the player', () => {
    render(<RouteTree data={kelce} />);

    expect(screen.getAllByRole('button')).toHaveLength(kelce.length);
    expect(screen.getByLabelText('Dig: Catch % 78.6%')).toBeInTheDocument();
  });

  it('ignores route types it does not know how to draw', () => {
    render(<RouteTree data={[...kelce, { route_id: 99, route_type: 'wheel', targets: 3, receptions: 1, yards_gained: 9, catch_rate: 33.3 }]} />);

    expect(screen.getAllByRole('button')).toHaveLength(kelce.length);
  });

  it('shows the route breakdown on hover and dims the rest', () => {
    render(<RouteTree data={kelce} />);

    fireEvent.mouseEnter(screen.getByTestId('route-dig'));

    expect(screen.getByText('158')).toBeInTheDocument(); // yards on digs
    expect(screen.getByTestId('route-dig')).toHaveAttribute('opacity', '1');
    expect(screen.getByTestId('route-go')).toHaveAttribute('opacity', '0.25');
  });

  it('computes yards per target as a metric', () => {
    render(<RouteTree data={kelce} metric="yards_per_target" />);

    // post: 109 yards / 8 targets
    expect(screen.getByLabelText('Post: Yds / Tgt 13.6')).toBeInTheDocument();
  });
});
