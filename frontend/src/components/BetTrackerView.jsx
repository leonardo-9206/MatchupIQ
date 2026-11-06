import React, { useState } from 'react';
import {
  TrendingUp,
  DollarSign,
  Percent,
  CheckCircle2,
  XCircle,
  Clock,
  Trash2,
  Edit2,
  Plus,
  Filter,
  Check,
  Ban
} from 'lucide-react';
import Button from './common/Button.jsx';
import { calculateTrackerMetrics } from '../services/trackerService.js';

export default function BetTrackerView({
  bets,
  onUpdateStatus,
  onUpdateNotes,
  onDeleteBet,
  onOpenAddModal,
}) {
  const [statusFilter, setStatusFilter] = useState('all'); // 'all' | 'pending' | 'won' | 'lost'
  const [editingBetId, setEditingBetId] = useState(null);
  const [editNoteText, setEditNoteText] = useState('');

  const metrics = calculateTrackerMetrics(bets);

  const filteredBets = bets.filter((b) => {
    if (statusFilter === 'all') return true;
    return b.status === statusFilter;
  });

  const handleStartEdit = (bet) => {
    setEditingBetId(bet.bet_id);
    setEditNoteText(bet.notes || '');
  };

  const handleSaveEdit = (betId) => {
    onUpdateNotes(betId, editNoteText);
    setEditingBetId(null);
  };

  return (
    <section className="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8 space-y-8">
      
      {/* Header and Add Button */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-slate-800">
        <div>
          <div className="inline-flex items-center gap-1.5 text-xs font-mono uppercase tracking-wider text-brand-cyan mb-1 font-semibold">
            <TrendingUp className="h-4 w-4" />
            <span>Gestión y Auditoría de Apuestas (Sprint 3)</span>
          </div>
          <h1 className="font-display text-3xl font-extrabold text-white">
            Bet Tracker Personal
          </h1>
          <p className="text-xs sm:text-sm text-slate-400 mt-1">
            Control de bankroll, retorno de inversión (ROI) y bitácora de selecciones.
          </p>
        </div>

        <Button
          variant="primary"
          size="md"
          icon={Plus}
          onClick={() => onOpenAddModal(null)}
        >
          <span>Registrar Nueva Apuesta</span>
        </Button>
      </div>

      {/* KPI Metrics Cards */}
      <div className="grid grid-cols-2 lg:grid-cols-4 gap-4">
        
        {/* Total Profit / Loss */}
        <div className="rounded-2xl border border-slate-800 bg-[#0D1527] p-5">
          <div className="flex items-center justify-between text-xs text-slate-400 font-mono mb-2">
            <span>Ganancia Neta (P/L)</span>
            <DollarSign className="h-4 w-4 text-emerald-400" />
          </div>
          <div className={`font-mono text-2xl sm:text-3xl font-black ${metrics.isProfitable ? 'text-edge-positive' : 'text-red-400'}`}>
            {metrics.isProfitable ? '+' : ''}${metrics.totalProfit}
          </div>
          <p className="text-[11px] text-slate-500 mt-1 font-mono">
            Total Apostado: ${metrics.totalStaked}
          </p>
        </div>

        {/* ROI % */}
        <div className="rounded-2xl border border-slate-800 bg-[#0D1527] p-5">
          <div className="flex items-center justify-between text-xs text-slate-400 font-mono mb-2">
            <span>Retorno de Inversión (ROI)</span>
            <Percent className="h-4 w-4 text-brand-cyan" />
          </div>
          <div className={`font-mono text-2xl sm:text-3xl font-black ${parseFloat(metrics.roi) >= 0 ? 'text-brand-cyan' : 'text-red-400'}`}>
            {metrics.roi}%
          </div>
          <p className="text-[11px] text-slate-500 mt-1 font-mono">
            Rendimiento sobre bankroll
          </p>
        </div>

        {/* Win Rate % */}
        <div className="rounded-2xl border border-slate-800 bg-[#0D1527] p-5">
          <div className="flex items-center justify-between text-xs text-slate-400 font-mono mb-2">
            <span>Tasa de Acierto (Win Rate)</span>
            <CheckCircle2 className="h-4 w-4 text-emerald-400" />
          </div>
          <div className="font-mono text-2xl sm:text-3xl font-black text-white">
            {metrics.winRate}%
          </div>
          <p className="text-[11px] text-slate-500 mt-1 font-mono">
            {metrics.wonCount} Ganadas / {metrics.lostCount} Perdidas
          </p>
        </div>

        {/* Active / Pending Bets */}
        <div className="rounded-2xl border border-slate-800 bg-[#0D1527] p-5">
          <div className="flex items-center justify-between text-xs text-slate-400 font-mono mb-2">
            <span>En Juego / Pendientes</span>
            <Clock className="h-4 w-4 text-amber-400" />
          </div>
          <div className="font-mono text-2xl sm:text-3xl font-black text-amber-300">
            {metrics.pendingCount}
          </div>
          <p className="text-[11px] text-slate-500 mt-1 font-mono">
            {metrics.totalBets} apuestas registradas
          </p>
        </div>

      </div>

      {/* Filter Tabs */}
      <div className="flex items-center justify-between flex-wrap gap-3">
        <div className="flex items-center gap-1.5 bg-[#070B16] p-1 rounded-xl border border-slate-800">
          <button
            onClick={() => setStatusFilter('all')}
            className={`rounded-lg px-3.5 py-1.5 text-xs font-semibold transition-all ${
              statusFilter === 'all'
                ? 'bg-brand-cyan text-slate-950 font-bold'
                : 'text-slate-400 hover:text-white'
            }`}
          >
            Todas ({bets.length})
          </button>
          <button
            onClick={() => setStatusFilter('pending')}
            className={`rounded-lg px-3.5 py-1.5 text-xs font-semibold transition-all ${
              statusFilter === 'pending'
                ? 'bg-amber-500 text-slate-950 font-bold'
                : 'text-slate-400 hover:text-white'
            }`}
          >
            Pendientes ({metrics.pendingCount})
          </button>
          <button
            onClick={() => setStatusFilter('won')}
            className={`rounded-lg px-3.5 py-1.5 text-xs font-semibold transition-all ${
              statusFilter === 'won'
                ? 'bg-emerald-500 text-slate-950 font-bold'
                : 'text-slate-400 hover:text-white'
            }`}
          >
            Ganadas ({metrics.wonCount})
          </button>
          <button
            onClick={() => setStatusFilter('lost')}
            className={`rounded-lg px-3.5 py-1.5 text-xs font-semibold transition-all ${
              statusFilter === 'lost'
                ? 'bg-red-500 text-white font-bold'
                : 'text-slate-400 hover:text-white'
            }`}
          >
            Perdidas ({metrics.lostCount})
          </button>
        </div>
      </div>

      {/* Bets List / Table */}
      {filteredBets.length === 0 ? (
        <div className="rounded-2xl border border-slate-800 bg-[#0D1527] p-12 text-center">
          <p className="text-slate-400 text-sm">
            No tienes apuestas registradas con el filtro seleccionado.
          </p>
          <Button
            variant="primary"
            size="sm"
            icon={Plus}
            onClick={() => onOpenAddModal(null)}
            className="mt-4"
          >
            Agregar tu primera apuesta
          </Button>
        </div>
      ) : (
        <div className="space-y-3">
          {filteredBets.map((bet) => {
            const isEditing = editingBetId === bet.bet_id;
            const payout = (bet.stake_amount * bet.taken_odds).toFixed(2);
            const profit = (payout - bet.stake_amount).toFixed(2);

            return (
              <div
                key={bet.bet_id}
                className="rounded-2xl border border-slate-800 bg-[#0D1527] p-5 shadow-sm hover:border-slate-700 transition-all"
              >
                <div className="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                  
                  {/* Left: Player & Bet details */}
                  <div className="space-y-1">
                    <div className="flex items-center gap-2">
                      <span className="font-display text-lg font-bold text-white">
                        {bet.player_name}
                      </span>
                      <span className="font-mono text-xs text-slate-400 bg-surface-1 px-2 py-0.5 rounded border border-slate-800">
                        {bet.player_position} • {bet.team_abbreviation}
                      </span>
                      <span className="text-xs text-slate-400 font-mono">
                        vs {bet.opponent_abbreviation}
                      </span>
                    </div>

                    <div className="text-xs text-slate-300 font-mono flex items-center gap-2">
                      <span className="text-brand-cyan">{bet.bet_type_display}</span>
                      {bet.line_over > 0 && <span>• Línea: <strong>{bet.line_over}</strong></span>}
                      <span>• Book: <strong className="text-slate-200">{bet.bookmaker_name}</strong></span>
                      <span className="text-slate-500">({bet.created_at})</span>
                    </div>
                  </div>

                  {/* Middle: Financials */}
                  <div className="flex items-center gap-6 font-mono text-xs">
                    <div>
                      <span className="text-slate-500 block uppercase">Monto</span>
                      <span className="text-base font-bold text-white">${bet.stake_amount}</span>
                    </div>
                    <div>
                      <span className="text-slate-500 block uppercase">Cuota</span>
                      <span className="text-base font-bold text-brand-cyan">
                        {bet.american_odds || `x${bet.taken_odds}`}
                      </span>
                    </div>
                    <div>
                      <span className="text-slate-500 block uppercase">Ganancia Est.</span>
                      <span className={`text-base font-bold ${bet.status === 'lost' ? 'text-slate-500 line-through' : 'text-edge-positive'}`}>
                        +${profit}
                      </span>
                    </div>
                  </div>

                  {/* Right: Status Pill & Actions */}
                  <div className="flex items-center gap-2 flex-wrap">
                    
                    {/* Status Changer Buttons */}
                    <div className="flex rounded-xl bg-[#050811] p-1 border border-slate-800">
                      <button
                        title="Marcar como Ganada"
                        onClick={() => onUpdateStatus(bet.bet_id, 'won')}
                        className={`rounded-lg px-2.5 py-1 text-xs font-semibold flex items-center gap-1 transition-all ${
                          bet.status === 'won'
                            ? 'bg-emerald-500 text-slate-950 font-bold'
                            : 'text-slate-400 hover:text-emerald-400'
                        }`}
                      >
                        <CheckCircle2 className="h-3.5 w-3.5" />
                        <span>Ganada</span>
                      </button>

                      <button
                        title="Marcar como Perdida"
                        onClick={() => onUpdateStatus(bet.bet_id, 'lost')}
                        className={`rounded-lg px-2.5 py-1 text-xs font-semibold flex items-center gap-1 transition-all ${
                          bet.status === 'lost'
                            ? 'bg-red-500 text-white font-bold'
                            : 'text-slate-400 hover:text-red-400'
                        }`}
                      >
                        <XCircle className="h-3.5 w-3.5" />
                        <span>Perdida</span>
                      </button>

                      <button
                        title="Marcar como Pendiente"
                        onClick={() => onUpdateStatus(bet.bet_id, 'pending')}
                        className={`rounded-lg px-2.5 py-1 text-xs font-semibold flex items-center gap-1 transition-all ${
                          bet.status === 'pending'
                            ? 'bg-amber-500 text-slate-950 font-bold'
                            : 'text-slate-400 hover:text-amber-400'
                        }`}
                      >
                        <Clock className="h-3.5 w-3.5" />
                        <span>En Juego</span>
                      </button>
                    </div>

                    {/* Edit Notes button */}
                    <button
                      onClick={() => handleStartEdit(bet)}
                      className="rounded-xl border border-slate-800 bg-surface-1 p-2 text-slate-400 hover:text-white hover:bg-surface-elevated transition-colors"
                      title="Editar Notas"
                    >
                      <Edit2 className="h-4 w-4" />
                    </button>

                    {/* Delete button */}
                    <button
                      onClick={() => onDeleteBet(bet.bet_id)}
                      className="rounded-xl border border-slate-800 bg-surface-1 p-2 text-slate-400 hover:text-red-400 hover:bg-red-500/10 transition-colors"
                      title="Eliminar Apuesta"
                    >
                      <Trash2 className="h-4 w-4" />
                    </button>

                  </div>

                </div>

                {/* Notes or Note Editor */}
                {isEditing ? (
                  <div className="mt-3 pt-3 border-t border-slate-800 flex items-center gap-2">
                    <input
                      type="text"
                      value={editNoteText}
                      onChange={(e) => setEditNoteText(e.target.value)}
                      placeholder="Escribe tus notas de análisis..."
                      className="flex-1 rounded-xl border border-slate-700 bg-surface-1 px-3 py-1.5 text-xs text-white focus:outline-none focus:border-brand-cyan"
                    />
                    <Button
                      variant="primary"
                      size="sm"
                      icon={Check}
                      onClick={() => handleSaveEdit(bet.bet_id)}
                    >
                      Guardar
                    </Button>
                    <Button
                      variant="ghost"
                      size="sm"
                      onClick={() => setEditingBetId(null)}
                    >
                      Cancelar
                    </Button>
                  </div>
                ) : bet.notes ? (
                  <div className="mt-3 pt-3 border-t border-slate-800/80 text-xs text-slate-400 flex items-start gap-1.5">
                    <span className="font-semibold text-slate-500 font-mono">Nota:</span>
                    <span className="italic text-slate-300">{bet.notes}</span>
                  </div>
                ) : null}

              </div>
            );
          })}
        </div>
      )}

    </section>
  );
}
