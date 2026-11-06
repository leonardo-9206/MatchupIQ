import { useEffect, useState } from 'react';
import { getPlayerHeatmap } from '../services/tacticsService.js';

/**
 * Loads the heatmap of a player every time playerId changes.
 * Any component (demo page, Carlos' TacticalHeatmapModal, etc.) can use it like:
 *   const { zones, source, loading } = usePlayerHeatmap(playerId);
 */
export default function usePlayerHeatmap(playerId) {
  const [state, setState] = useState({ zones: [], player: null, summary: null, source: null, loading: true });

  useEffect(() => {
    // If the user switches players quickly, the older request may resolve last.
    // This flag makes sure we only keep the answer for the current playerId.
    let ignore = false;
    setState((prev) => ({ ...prev, loading: true }));

    getPlayerHeatmap(playerId).then((result) => {
      if (!ignore) setState({ ...result, loading: false });
    });

    return () => {
      ignore = true;
    };
  }, [playerId]);

  return state;
}
