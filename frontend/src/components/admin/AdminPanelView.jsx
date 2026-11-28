import React, { useState, useEffect, useMemo } from 'react';
import {
  Shield,
  Users,
  Plus,
  Edit2,
  Trash2,
  Search,
  CheckCircle2,
  AlertTriangle,
  RefreshCw,
  Database,
  BarChart2,
  Settings
} from 'lucide-react';
import Button from '../common/Button.jsx';
import TeamModal from './TeamModal.jsx';
import PlayerModal from './PlayerModal.jsx';
import {
  getTeams,
  createTeam,
  updateTeam,
  deleteTeam,
  getPlayers,
  createPlayer,
  updatePlayer,
  deletePlayer
} from '../../services/catalogService.js';
import { refreshAdminOdds } from '../../services/adminService.js';

export default function AdminPanelView({ currentUser }) {
  const [activeCatalog, setActiveCatalog] = useState('players'); // 'players' | 'teams'
  const [teams, setTeams] = useState([]);
  const [players, setPlayers] = useState([]);
  const [loading, setLoading] = useState(true);
  const [searchQuery, setSearchQuery] = useState('');

  // Modals
  const [isTeamModalOpen, setIsTeamModalOpen] = useState(false);
  const [editingTeam, setEditingTeam] = useState(null);
  const [isPlayerModalOpen, setIsPlayerModalOpen] = useState(false);
  const [editingPlayer, setEditingPlayer] = useState(null);

  // Sync actions
  const [isSyncingOdds, setIsSyncingOdds] = useState(false);
  const [syncMessage, setSyncMessage] = useState('');

  const loadData = async () => {
    setLoading(true);
    const [teamsRes, playersRes] = await Promise.all([getTeams(), getPlayers()]);
    setTeams(teamsRes.teams);
    setPlayers(playersRes.players);
    setLoading(false);
  };

  useEffect(() => {
    loadData();
  }, []);

  const handleSyncOdds = async () => {
    setIsSyncingOdds(true);
    setSyncMessage('');
    const res = await refreshAdminOdds('americanfootball_nfl');
    setIsSyncingOdds(false);
    if (res.success) {
      setSyncMessage(res.message);
      setTimeout(() => setSyncMessage(''), 4000);
    }
  };

  // Team CRUD handlers
  const handleSaveTeam = async (teamData) => {
    if (editingTeam) {
      await updateTeam(editingTeam.team_id, teamData);
    } else {
      await createTeam(teamData);
    }
    await loadData();
    setEditingTeam(null);
  };

  const handleDeleteTeam = async (teamId) => {
    if (!window.confirm('¿Seguro que deseas eliminar este equipo?')) return;
    await deleteTeam(teamId);
    await loadData();
  };

  // Player CRUD handlers
  const handleSavePlayer = async (playerData) => {
    if (editingPlayer) {
      await updatePlayer(editingPlayer.player_id, playerData);
    } else {
      await createPlayer(playerData);
    }
    await loadData();
    setEditingPlayer(null);
  };

  const handleDeletePlayer = async (playerId) => {
    if (!window.confirm('¿Seguro que deseas eliminar este jugador del catálogo?')) return;
    await deletePlayer(playerId);
    await loadData();
  };

  // Filtered collections
  const filteredPlayers = useMemo(() => {
    if (!searchQuery.trim()) return players;
    const q = searchQuery.toLowerCase().trim();
    return players.filter(
      (p) =>
        p.name.toLowerCase().includes(q) ||
        p.team_name.toLowerCase().includes(q) ||
        p.team_abbreviation.toLowerCase().includes(q) ||
        p.position.toLowerCase().includes(q)
    );
  }, [players, searchQuery]);

  const filteredTeams = useMemo(() => {
    if (!searchQuery.trim()) return teams;
    const q = searchQuery.toLowerCase().trim();
    return teams.filter(
      (t) =>
        t.name.toLowerCase().includes(q) ||
        t.abbreviation.toLowerCase().includes(q) ||
        t.sport_name.toLowerCase().includes(q)
    );
  }, [teams, searchQuery]);

  return (
    <section className="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8 space-y-6">
      
      {/* Header */}
      <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-4 border-b border-slate-800">
        <div>
          <div className="inline-flex items-center gap-1.5 text-xs font-mono uppercase tracking-wider text-brand-cyan mb-1 font-semibold">
            <Settings className="h-4 w-4" />
            <span>Módulo Administrativo (Sprint 4)</span>
          </div>
          <h1 className="font-display text-3xl font-extrabold text-white">
            Panel de Administración de Catálogos
          </h1>
          <p className="text-xs sm:text-sm text-slate-400 mt-1">
            Gestión integral de Equipos, Jugadores y sincronización con The Odds API.
          </p>
        </div>

        {/* Admin Quick Action */}
        <div className="flex items-center gap-3">
          <Button
            variant="secondary"
            size="md"
            icon={RefreshCw}
            isLoading={isSyncingOdds}
            onClick={handleSyncOdds}
            className="border-brand-cyan/40 bg-[#0A1020] text-brand-cyan hover:bg-brand-cyan/15 text-xs font-mono"
          >
            <span>{isSyncingOdds ? 'Sincronizando...' : 'Recargar Odds API'}</span>
          </Button>

          {activeCatalog === 'players' ? (
            <Button
              variant="primary"
              size="md"
              icon={Plus}
              onClick={() => {
                setEditingPlayer(null);
                setIsPlayerModalOpen(true);
              }}
            >
              <span>Nuevo Jugador</span>
            </Button>
          ) : (
            <Button
              variant="primary"
              size="md"
              icon={Plus}
              onClick={() => {
                setEditingTeam(null);
                setIsTeamModalOpen(true);
              }}
            >
              <span>Nuevo Equipo</span>
            </Button>
          )}
        </div>
      </div>

      {syncMessage && (
        <div className="flex items-center gap-2 rounded-xl border border-emerald-500/30 bg-emerald-500/10 p-3 text-xs text-edge-positive font-mono">
          <CheckCircle2 className="h-4 w-4 shrink-0" />
          <span>{syncMessage}</span>
        </div>
      )}

      {/* KPI Overview Cards */}
      <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div className="rounded-2xl border border-slate-800 bg-[#0D1527] p-4">
          <div className="flex items-center justify-between text-xs text-slate-400 font-mono mb-1">
            <span>Total Atletas</span>
            <Users className="h-4 w-4 text-brand-cyan" />
          </div>
          <div className="font-mono text-2xl font-black text-white">{players.length}</div>
          <p className="text-[10px] text-slate-500 mt-1 font-mono">NFL y Liga MX</p>
        </div>

        <div className="rounded-2xl border border-slate-800 bg-[#0D1527] p-4">
          <div className="flex items-center justify-between text-xs text-slate-400 font-mono mb-1">
            <span>Clubes Deportivos</span>
            <Shield className="h-4 w-4 text-emerald-400" />
          </div>
          <div className="font-mono text-2xl font-black text-white">{teams.length}</div>
          <p className="text-[10px] text-slate-500 mt-1 font-mono">Equipos registrados</p>
        </div>

        <div className="rounded-2xl border border-slate-800 bg-[#0D1527] p-4">
          <div className="flex items-center justify-between text-xs text-slate-400 font-mono mb-1">
            <span>Defensa Promedio</span>
            <BarChart2 className="h-4 w-4 text-amber-400" />
          </div>
          <div className="font-mono text-2xl font-black text-amber-300">#8.4</div>
          <p className="text-[10px] text-slate-500 mt-1 font-mono">Rango de oposición</p>
        </div>

        <div className="rounded-2xl border border-slate-800 bg-[#0D1527] p-4">
          <div className="flex items-center justify-between text-xs text-slate-400 font-mono mb-1">
            <span>Acceso de Usuario</span>
            <Database className="h-4 w-4 text-sky-400" />
          </div>
          <div className="font-mono text-sm font-bold text-slate-200 truncate">
            {currentUser?.email || 'admin@matchupiq.com'}
          </div>
          <p className="text-[10px] text-brand-cyan mt-1 font-mono uppercase font-bold">Rol: Administrador</p>
        </div>
      </div>

      {/* Catalog Selector Tabs and Search */}
      <div className="flex flex-col sm:flex-row items-center justify-between gap-4">
        <div className="flex items-center gap-1.5 bg-[#070B16] p-1 rounded-xl border border-slate-800">
          <button
            onClick={() => setActiveCatalog('players')}
            className={`flex items-center gap-2 rounded-lg px-4 py-2 text-xs font-semibold transition-all ${
              activeCatalog === 'players'
                ? 'bg-brand-cyan text-slate-950 font-bold shadow-sm'
                : 'text-slate-400 hover:text-white'
            }`}
          >
            <Users className="h-4 w-4" />
            <span>Catálogo de Jugadores ({players.length})</span>
          </button>

          <button
            onClick={() => setActiveCatalog('teams')}
            className={`flex items-center gap-2 rounded-lg px-4 py-2 text-xs font-semibold transition-all ${
              activeCatalog === 'teams'
                ? 'bg-brand-cyan text-slate-950 font-bold shadow-sm'
                : 'text-slate-400 hover:text-white'
            }`}
          >
            <Shield className="h-4 w-4" />
            <span>Catálogo de Equipos ({teams.length})</span>
          </button>
        </div>

        {/* Search */}
        <div className="relative w-full sm:max-w-xs">
          <Search className="absolute left-3 top-2.5 h-4 w-4 text-slate-500" />
          <input
            type="text"
            value={searchQuery}
            onChange={(e) => setSearchQuery(e.target.value)}
            placeholder={`Buscar en ${activeCatalog === 'players' ? 'jugadores...' : 'equipos...'}`}
            className="w-full rounded-xl border border-slate-700 bg-surface-1 py-2 pl-9 pr-3 text-xs text-slate-100 placeholder-slate-500 focus:border-brand-cyan focus:outline-none"
          />
        </div>
      </div>

      {/* Tables View */}
      {activeCatalog === 'players' ? (
        <div className="overflow-hidden rounded-2xl border border-slate-800 bg-[#0D1527] shadow-xl">
          <div className="overflow-x-auto">
            <table className="w-full text-left text-xs">
              <thead className="border-b border-slate-800 bg-[#070B16] font-mono text-[11px] uppercase tracking-wider text-slate-400">
                <tr>
                  <th className="px-5 py-3.5">ID</th>
                  <th className="px-5 py-3.5">Atleta</th>
                  <th className="px-4 py-3.5">Posición</th>
                  <th className="px-4 py-3.5">Equipo</th>
                  <th className="px-4 py-3.5">Liga</th>
                  <th className="px-4 py-3.5">Stat Base</th>
                  <th className="px-5 py-3.5 text-right">Acciones</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-800/80">
                {filteredPlayers.map((player) => (
                  <tr key={player.player_id} className="hover:bg-[#131E35]/60 transition-colors">
                    <td className="px-5 py-3.5 font-mono text-slate-500">#{player.player_id}</td>
                    <td className="px-5 py-3.5 font-display text-sm font-bold text-white">
                      {player.name}
                    </td>
                    <td className="px-4 py-3.5 font-mono">
                      <span className="rounded bg-surface-1 border border-slate-800 px-2 py-0.5 text-slate-300 font-semibold">
                        {player.position}
                      </span>
                    </td>
                    <td className="px-4 py-3.5 text-slate-300 font-medium">
                      {player.team_name} ({player.team_abbreviation})
                    </td>
                    <td className="px-4 py-3.5 font-mono">
                      <span
                        className={`rounded px-2 py-0.5 text-[10px] font-bold ${
                          player.sport_name === 'NFL'
                            ? 'bg-sky-500/20 text-sky-400 border border-sky-500/30'
                            : 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30'
                        }`}
                      >
                        {player.sport_name || 'NFL'}
                      </span>
                    </td>
                    <td className="px-4 py-3.5 font-mono text-brand-cyan font-bold">
                      {player.stat_average}
                    </td>
                    <td className="px-5 py-3.5 text-right space-x-1">
                      <button
                        onClick={() => {
                          setEditingPlayer(player);
                          setIsPlayerModalOpen(true);
                        }}
                        className="rounded-lg p-1.5 text-slate-400 hover:text-white hover:bg-surface-elevated transition-colors"
                        title="Editar Jugador"
                      >
                        <Edit2 className="h-4 w-4" />
                      </button>
                      <button
                        onClick={() => handleDeletePlayer(player.player_id)}
                        className="rounded-lg p-1.5 text-slate-400 hover:text-red-400 hover:bg-red-500/10 transition-colors"
                        title="Eliminar Jugador"
                      >
                        <Trash2 className="h-4 w-4" />
                      </button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      ) : (
        <div className="overflow-hidden rounded-2xl border border-slate-800 bg-[#0D1527] shadow-xl">
          <div className="overflow-x-auto">
            <table className="w-full text-left text-xs">
              <thead className="border-b border-slate-800 bg-[#070B16] font-mono text-[11px] uppercase tracking-wider text-slate-400">
                <tr>
                  <th className="px-5 py-3.5">ID</th>
                  <th className="px-5 py-3.5">Nombre del Club</th>
                  <th className="px-4 py-3.5">Ticker</th>
                  <th className="px-4 py-3.5">Liga</th>
                  <th className="px-4 py-3.5">Defensive Rank</th>
                  <th className="px-5 py-3.5 text-right">Acciones</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-800/80">
                {filteredTeams.map((team) => (
                  <tr key={team.team_id} className="hover:bg-[#131E35]/60 transition-colors">
                    <td className="px-5 py-3.5 font-mono text-slate-500">#{team.team_id}</td>
                    <td className="px-5 py-3.5 font-display text-sm font-bold text-white">
                      {team.name}
                    </td>
                    <td className="px-4 py-3.5 font-mono text-brand-cyan font-bold">
                      {team.abbreviation}
                    </td>
                    <td className="px-4 py-3.5 font-mono">
                      <span
                        className={`rounded px-2 py-0.5 text-[10px] font-bold ${
                          team.sport_name === 'NFL'
                            ? 'bg-sky-500/20 text-sky-400 border border-sky-500/30'
                            : 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30'
                        }`}
                      >
                        {team.sport_name}
                      </span>
                    </td>
                    <td className="px-4 py-3.5 font-mono">
                      <span className="rounded bg-surface-1 border border-slate-800 px-2 py-0.5 text-slate-300 font-semibold">
                        Rank #{team.defensive_rank}
                      </span>
                    </td>
                    <td className="px-5 py-3.5 text-right space-x-1">
                      <button
                        onClick={() => {
                          setEditingTeam(team);
                          setIsTeamModalOpen(true);
                        }}
                        className="rounded-lg p-1.5 text-slate-400 hover:text-white hover:bg-surface-elevated transition-colors"
                        title="Editar Equipo"
                      >
                        <Edit2 className="h-4 w-4" />
                      </button>
                      <button
                        onClick={() => handleDeleteTeam(team.team_id)}
                        className="rounded-lg p-1.5 text-slate-400 hover:text-red-400 hover:bg-red-500/10 transition-colors"
                        title="Eliminar Equipo"
                      >
                        <Trash2 className="h-4 w-4" />
                      </button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      )}

      {/* Modals */}
      <TeamModal
        isOpen={isTeamModalOpen}
        onClose={() => setIsTeamModalOpen(false)}
        team={editingTeam}
        onSave={handleSaveTeam}
      />

      <PlayerModal
        isOpen={isPlayerModalOpen}
        onClose={() => setIsPlayerModalOpen(false)}
        player={editingPlayer}
        teams={teams}
        onSave={handleSavePlayer}
      />

    </section>
  );
}
