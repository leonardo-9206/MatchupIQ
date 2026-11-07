import { describe, it, expect, vi } from 'vitest';
import { render, screen, fireEvent } from '@testing-library/react';
import TacticalHeatmapModal from './TacticalHeatmapModal.jsx';

// These tests are about tabs and player selection, so the three visualizations are replaced
// by stubs that print which player they received (they have their own test files).
vi.mock('./FieldHeatmap/FieldHeatmap.jsx', async (importOriginal) => ({
  ...(await importOriginal()),
  default: ({ playerId }) => <p>heatmap of {playerId}</p>,
}));
vi.mock('./Tactics/RouteTree.jsx', async (importOriginal) => ({
  ...(await importOriginal()),
  default: ({ playerId }) => <p>routes of {playerId}</p>,
}));
vi.mock('./Tactics/CoverageSplits.jsx', () => ({
  default: ({ playerId }) => <p>coverage of {playerId}</p>,
}));

// Roster shaped like the one App.jsx builds from the loaded NFL props
const players = [
  { player_id: 3, name: 'Isiah Pacheco', position: 'RB', team: 'KC' },
  { player_id: 2, name: 'Travis Kelce', position: 'TE', team: 'KC' },
  { player_id: 31, name: 'A.J. Brown', position: 'WR', team: 'NE' },
  { player_id: 8, name: 'Lamar Jackson', position: 'QB', team: 'BAL' },
  { player_id: 5, name: 'Henry Martin', position: 'ST', team: 'AME' },
];

const tabs = () => screen.queryAllByRole('tab').map((t) => t.textContent);
const openFor = (selectedPlayerId) =>
  render(<TacticalHeatmapModal isOpen onClose={() => {}} selectedPlayerId={selectedPlayerId} players={players} />);

describe('TacticalHeatmapModal', () => {
  it('renders nothing while closed', () => {
    const { container } = render(<TacticalHeatmapModal isOpen={false} onClose={() => {}} players={players} />);
    expect(container).toBeEmptyDOMElement();
  });

  it('shows the three tactical views for a RB, starting on the heatmap', () => {
    openFor(3);

    expect(tabs()).toEqual(['Rushing Heatmap', 'Route Tree', 'Man vs Zone']);
    expect(screen.getByRole('tab', { name: 'Rushing Heatmap' })).toHaveAttribute('aria-selected', 'true');
    expect(screen.getByText('heatmap of 3')).toBeInTheDocument();
    expect(screen.getByTestId('position-scope-badge')).toHaveTextContent('RB: Rushing + Passing Maps');
  });

  it('passes the selected athlete to the view of the clicked tab', () => {
    openFor(3);

    fireEvent.click(screen.getByRole('tab', { name: 'Man vs Zone' }));

    expect(screen.getByText('coverage of 3')).toBeInTheDocument();
  });

  it('hides the rushing heatmap for a TE and opens the route tree', () => {
    openFor(2);

    expect(tabs()).toEqual(['Route Tree', 'Man vs Zone']);
    expect(screen.getByRole('tab', { name: 'Route Tree' })).toHaveAttribute('aria-selected', 'true');
    expect(screen.getByTestId('position-scope-badge')).toHaveTextContent('TE: Passing & Coverage Maps');
  });

  it('keeps a newly synced WR selected instead of falling back to another player', () => {
    openFor(31);

    expect(screen.getByLabelText('Atleta Seleccionado')).toHaveValue('31');
    expect(screen.getByRole('option', { name: 'A.J. Brown (NE · WR)' }).selected).toBe(true);
    expect(tabs()).toEqual(['Route Tree', 'Man vs Zone']);
    expect(screen.getByText('routes of 31')).toBeInTheDocument();
    expect(screen.getByTestId('position-scope-badge')).toHaveTextContent('WR: Passing & Coverage Maps');
  });

  it('only offers the rushing heatmap for a QB', () => {
    openFor(8);

    expect(tabs()).toEqual(['Rushing Heatmap']);
  });

  it('shows a clean message for positions without tactical maps', () => {
    openFor(5);

    expect(tabs()).toEqual([]);
    expect(screen.getByText('No hay mapas tácticos para la posición ST.')).toBeInTheDocument();
  });

  it('switches tabs when the user changes from a RB to a WR', () => {
    openFor(3);

    fireEvent.change(screen.getByLabelText('Atleta Seleccionado'), { target: { value: '31' } });

    expect(tabs()).toEqual(['Route Tree', 'Man vs Zone']);
    expect(screen.getByRole('tab', { name: 'Route Tree' })).toHaveAttribute('aria-selected', 'true');
  });

  it('follows the athlete of the card that opened it', () => {
    const { rerender } = openFor(3);

    rerender(<TacticalHeatmapModal isOpen onClose={() => {}} selectedPlayerId={2} players={players} />);

    expect(screen.getByLabelText('Atleta Seleccionado')).toHaveValue('2');
  });

  it('still shows an athlete that is not in the roster yet', () => {
    openFor(99);

    expect(screen.getByRole('option', { name: 'Player #99 (— · ?)' }).selected).toBe(true);
    expect(screen.getByText('No hay mapas tácticos para la posición de este atleta.')).toBeInTheDocument();
  });

  it('falls back to the mock roster when the dashboard has no NFL props loaded', () => {
    render(<TacticalHeatmapModal isOpen onClose={() => {}} selectedPlayerId={null} players={[]} />);

    expect(screen.getByRole('option', { name: 'Isiah Pacheco (KC · RB)' }).selected).toBe(true);
  });

  it('closes with the close button', () => {
    const onClose = vi.fn();
    render(<TacticalHeatmapModal isOpen onClose={onClose} selectedPlayerId={3} players={players} />);

    fireEvent.click(screen.getByLabelText('Cerrar mapa táctico'));

    expect(onClose).toHaveBeenCalled();
  });
});
