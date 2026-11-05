import React, { useState } from 'react';
import { X, BookmarkPlus, DollarSign, Edit3 } from 'lucide-react';
import Button from './common/Button.jsx';
import { USA_BOOKMAKERS } from '../services/propsService.js';

export default function AddBetModal({ isOpen, onClose, prop, onBetAdded }) {
  const [stakeAmount, setStakeAmount] = useState('200');
  const [selectedBookmaker, setSelectedBookmaker] = useState(prop?.best_bookmaker || 'DraftKings');
  const [notes, setNotes] = useState('');
  const [loading, setLoading] = useState(false);

  if (!isOpen) return null;

  const handleSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);

    const betData = {
      prop_id: prop?.prop_id || null,
      player_name: prop?.player_name || 'Pick Manual',
      player_position: prop?.player_position || 'PROP',
      team_abbreviation: prop?.team_abbreviation || 'NFL',
      opponent_abbreviation: prop?.opponent_abbreviation || 'OPP',
      bet_type_display: prop?.bet_type_display || 'Player Prop',
      line_over: prop?.line_over || 0,
      taken_odds: prop?.odds_over || 1.9,
      american_odds: prop?.american_odds || '-110',
      stake_amount: parseFloat(stakeAmount) || 100,
      status: 'pending',
      bookmaker_name: selectedBookmaker,
      notes: notes.trim(),
    };

    await onBetAdded(betData);
    setLoading(false);
    onClose();
  };

  const potentialPayout = ((parseFloat(stakeAmount) || 0) * (prop?.odds_over || 1.9)).toFixed(2);
  const potentialProfit = (potentialPayout - (parseFloat(stakeAmount) || 0)).toFixed(2);

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
      {/* Backdrop */}
      <div className="fixed inset-0 bg-black/80 backdrop-blur-sm" onClick={onClose} />

      <div className="relative w-full max-w-lg rounded-2xl border border-slate-800 bg-[#070B16] p-6 shadow-2xl z-10 text-slate-100">
        
        {/* Close Button */}
        <button
          onClick={onClose}
          className="absolute top-4 right-4 rounded-lg p-1 text-slate-400 hover:text-white hover:bg-surface-elevated transition-colors"
        >
          <X className="h-5 w-5" />
        </button>

        {/* Title */}
        <div className="flex items-center gap-3 mb-5">
          <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-brand-cyan/15 border border-brand-cyan/30 text-brand-cyan">
            <BookmarkPlus className="h-5 w-5" />
          </div>
          <div>
            <h3 className="font-display text-xl font-bold text-white">
              Registrar en Bet Tracker
            </h3>
            <p className="text-xs text-slate-400">
              Guarda y da seguimiento a tu pick con cuotas reales de USA
            </p>
          </div>
        </div>

        {/* Prop summary card if provided */}
        {prop && (
          <div className="mb-5 rounded-xl border border-slate-800 bg-[#0D1527] p-4">
            <div className="flex items-center justify-between text-xs text-slate-400 mb-1">
              <span>{prop.player_position} • {prop.team_name}</span>
              <span className="font-mono text-edge-positive font-bold">+{prop.calculated_edge}% EDGE</span>
            </div>
            <div className="font-display text-base font-bold text-white">
              {prop.player_name}
            </div>
            <div className="mt-2 flex items-center justify-between text-xs font-mono">
              <span className="text-brand-cyan">Línea OVER: {prop.line_over} ({prop.bet_type_display})</span>
              <span className="text-slate-200 font-bold">{prop.american_odds} (x{prop.odds_over})</span>
            </div>
          </div>
        )}

        {/* Form */}
        <form onSubmit={handleSubmit} className="space-y-4">
          
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
            {/* Stake Amount */}
            <div>
              <label className="block text-xs font-mono uppercase text-slate-400 mb-1">
                Monto Apostado ($MXN / $USD)
              </label>
              <div className="relative">
                <DollarSign className="absolute left-3 top-2.5 h-4 w-4 text-slate-500" />
                <input
                  type="number"
                  step="any"
                  min="1"
                  required
                  value={stakeAmount}
                  onChange={(e) => setStakeAmount(e.target.value)}
                  placeholder="200"
                  className="w-full rounded-xl border border-slate-700 bg-surface-1 py-2 pl-9 pr-3 text-xs sm:text-sm font-mono text-white focus:border-brand-cyan focus:outline-none"
                />
              </div>
            </div>

            {/* Bookmaker Selection */}
            <div>
              <label className="block text-xs font-mono uppercase text-slate-400 mb-1">
                Casa de Apuestas (USA)
              </label>
              <select
                value={selectedBookmaker}
                onChange={(e) => setSelectedBookmaker(e.target.value)}
                className="w-full rounded-xl border border-slate-700 bg-surface-1 py-2 px-3 text-xs sm:text-sm font-mono text-white focus:border-brand-cyan focus:outline-none"
              >
                {USA_BOOKMAKERS.map((b) => (
                  <option key={b.id} value={b.name}>
                    {b.name}
                  </option>
                ))}
              </select>
            </div>
          </div>

          {/* Potential Return Calculator */}
          <div className="rounded-xl border border-slate-800 bg-[#050811] p-3 flex items-center justify-between font-mono text-xs">
            <div>
              <span className="text-slate-500 block">Retorno Potencial:</span>
              <span className="text-sm font-bold text-slate-200">${potentialPayout}</span>
            </div>
            <div className="text-right">
              <span className="text-slate-500 block">Ganancia Neta:</span>
              <span className="text-sm font-bold text-edge-positive">+${potentialProfit}</span>
            </div>
          </div>

          {/* Notes */}
          <div>
            <label className="block text-xs font-mono uppercase text-slate-400 mb-1">
              Notas Personales o Estrategia (Opcional)
            </label>
            <div className="relative">
              <textarea
                rows={2}
                value={notes}
                onChange={(e) => setNotes(e.target.value)}
                placeholder="Ej. Rival permite muchas yardas terrestres en zona roja..."
                className="w-full rounded-xl border border-slate-700 bg-surface-1 p-3 text-xs text-slate-100 placeholder-slate-500 focus:border-brand-cyan focus:outline-none"
              />
            </div>
          </div>

          <div className="pt-2 flex gap-3">
            <Button
              variant="secondary"
              size="md"
              onClick={onClose}
              className="flex-1"
            >
              Cancelar
            </Button>
            <Button
              type="submit"
              variant="primary"
              size="md"
              isLoading={loading}
              className="flex-1"
            >
              Guardar en Tracker
            </Button>
          </div>

        </form>

      </div>
    </div>
  );
}
