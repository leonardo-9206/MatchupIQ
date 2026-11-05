import { useEffect, useState } from 'react';
import { getPlayerCoverage, getPlayerRouteTree } from '../services/tacticsService.js';

const IDLE = { player: null, summary: null, source: null, loading: false };

/**
 * Generic loader for the tactics endpoints: calls `loader(playerId)` every time playerId changes.
 * Passing null/undefined as playerId skips the request.
 * The returned object is whatever the loader resolved (zones / splits / routes...) plus `loading`.
 */
export default function useTacticsData(loader, playerId) {
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

    loader(playerId).then((result) => {
      if (!ignore) setState({ ...result, loading: false });
    });

    return () => {
      ignore = true;
    };
  }, [loader, playerId]);

  return state;
}

export const usePlayerCoverage = (playerId) => useTacticsData(getPlayerCoverage, playerId);
export const usePlayerRouteTree = (playerId) => useTacticsData(getPlayerRouteTree, playerId);
