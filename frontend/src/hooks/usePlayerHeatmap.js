import useTacticsData from './useTacticsData.js';
import { getPlayerHeatmap } from '../services/tacticsService.js';

/**
 * Loads the heatmap of a player every time playerId changes.
 * Any component (FieldHeatmap, Carlos' TacticalHeatmapModal, etc.) can use it like:
 *   const { zones, source, loading } = usePlayerHeatmap(playerId);
 * Passing null/undefined skips the request (useful when the data comes from somewhere else).
 */
export default function usePlayerHeatmap(playerId) {
  const state = useTacticsData(getPlayerHeatmap, playerId);
  return { ...state, zones: state.zones ?? [] };
}
