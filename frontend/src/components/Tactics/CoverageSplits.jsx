import { usePlayerCoverage } from '../../hooks/useTacticsData.js';
import { SourceBadge, TacticsPlaceholder } from './TacticsUI.jsx';

const SCHEMES = {
  man: { label: 'Man Coverage', short: 'Man', color: '#0066FF' },
  zone: { label: 'Zone Coverage', short: 'Zone', color: '#00D2FF' },
};

/**
 * Man vs Zone breakdown for a receiver (data from GET /api/tactics/coverage.php).
 *   <CoverageSplits playerId={prop.player_id} />
 */
export default function CoverageSplits({ playerId }) {
  const { splits = [], summary, source, loading } = usePlayerCoverage(playerId);

  if (splits.length === 0) {
    return <TacticsPlaceholder loading={loading} />;
  }

  const totalTargets = splits.reduce((sum, s) => sum + s.targets, 0);
  // The backend already sends preferred_scheme; the mock doesn't, so we compute it the same way
  const preferred =
    summary?.preferred_scheme ?? splits.reduce((best, s) => (s.catch_rate > best.catch_rate ? s : best)).coverage_type;

  return (
    <div className={`space-y-5 ${loading ? 'opacity-60' : ''}`}>
      <SourceBadge source={source} />

      {/* Target share: how often the defense played each scheme when this player was targeted */}
      <div>
        <p className="mb-1 text-xs uppercase tracking-wide text-slate-400">Target share</p>
        <div className="flex h-3 overflow-hidden rounded-full" role="img" aria-label="Target share by coverage">
          {splits.map((s) => (
            <div
              key={s.coverage_type}
              style={{ width: `${(s.targets / totalTargets) * 100}%`, background: SCHEMES[s.coverage_type]?.color }}
            />
          ))}
        </div>
        <div className="mt-1 flex justify-between text-xs text-slate-400">
          {splits.map((s) => (
            <span key={s.coverage_type}>
              {SCHEMES[s.coverage_type]?.short} {((s.targets / totalTargets) * 100).toFixed(1)}%
            </span>
          ))}
        </div>
      </div>

      <div className="grid gap-3 sm:grid-cols-2">
        {splits.map((s) => {
          const scheme = SCHEMES[s.coverage_type] ?? { label: s.coverage_type, color: '#94A3B8' };
          const isPreferred = s.coverage_type === preferred;
          return (
            <article
              key={s.coverage_type}
              data-testid={`coverage-${s.coverage_type}`}
              className={`rounded-xl border bg-surface-2 p-4 ${isPreferred ? 'border-[#00E676]/50' : 'border-slate-800'}`}
            >
              <header className="flex items-center justify-between">
                <h4 className="text-sm font-semibold" style={{ color: scheme.color }}>{scheme.label}</h4>
                {isPreferred && (
                  <span className="rounded-full bg-emerald-500/10 px-2 py-0.5 text-[11px] font-semibold text-[#00E676]">
                    Best matchup
                  </span>
                )}
              </header>

              <p className="mt-3 text-3xl font-bold">{s.catch_rate.toFixed(1)}%</p>
              <p className="text-xs text-slate-400">catch rate</p>

              {/* Catch rate bar on a 0-100% scale */}
              <div className="mt-3 h-2 overflow-hidden rounded-full bg-slate-800">
                <div className="h-full rounded-full" style={{ width: `${s.catch_rate}%`, background: scheme.color }} />
              </div>

              <dl className="mt-3 grid grid-cols-2 gap-2 text-center text-sm">
                <div><dt className="text-xs text-slate-400">Targets</dt><dd>{s.targets}</dd></div>
                <div><dt className="text-xs text-slate-400">Receptions</dt><dd>{s.receptions}</dd></div>
              </dl>
            </article>
          );
        })}
      </div>
    </div>
  );
}
