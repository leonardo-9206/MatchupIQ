import { useEffect, useState } from 'react';
import { getPlayerHeatmap } from '../services/tacticsService.js';

const IDLE = { zones: [], player: null, summary: null, source: null, loading: false };

/**
 * Loads the heatmap of a player every time playerId changes.
 * Any component (FieldHeatmap, Carlos' TacticalHeatmapModal, etc.) can use it like:
 *   const { zones, source, loading } = usePlayerHeatmap(playerId);
 * Passing null/undefined skips the request (useful when the data comes from somewhere else).
 */
export default function usePlayerHeatmap(playerId) {
  const [state, setState] = useState(playerId == null ? IDLE : { ...IDLE, loading: true });

  useEffect(() => {
    if (playerId == null) {
      setState(IDLE);
      return undefined;
    }

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
