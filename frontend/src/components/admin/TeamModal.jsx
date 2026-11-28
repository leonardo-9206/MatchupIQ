import React, { useState } from 'react';
import { X, Shield, Plus, Edit2, Check } from 'lucide-react';
import Button from '../common/Button.jsx';

export default function TeamModal({ isOpen, onClose, team = null, onSave }) {
  const isEditing = Boolean(team);
  const [name, setName] = useState(team?.name || '');
  const [abbreviation, setAbbreviation] = useState(team?.abbreviation || '');
  const [sportId, setSportId] = useState(team?.sport_id || 1);
  const [defensiveRank, setDefensiveRank] = useState(team?.defensive_rank || 16);
  const [loading, setLoading] = useState(false);

  if (!isOpen) return null;

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (!name.trim() || !abbreviation.trim()) return;

    setLoading(true);
    await onSave({
      name: name.trim(),
      abbreviation: abbreviation.trim().toUpperCase(),
      sport_id: parseInt(sportId),
      defensive_rank: parseInt(defensiveRank),
    });
    setLoading(false);
    onClose();
  };

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div className="fixed inset-0 bg-black/80 backdrop-blur-sm" onClick={onClose} />

      <div className="relative w-full max-w-md rounded-2xl border border-slate-800 bg-[#070B16] p-6 shadow-2xl z-10 text-slate-100">
        <button
          onClick={onClose}
          className="absolute top-4 right-4 rounded-lg p-1 text-slate-400 hover:text-white hover:bg-surface-elevated transition-colors"
        >
          <X className="h-5 w-5" />
        </button>

        <div className="flex items-center gap-3 mb-5">
          <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-brand-cyan/15 border border-brand-cyan/30 text-brand-cyan">
            {isEditing ? <Edit2 className="h-5 w-5" /> : <Plus className="h-5 w-5" />}
          </div>
          <div>
            <h3 className="font-display text-xl font-bold text-white">
              {isEditing ? 'Editar Equipo' : 'Nuevo Equipo'}
            </h3>
            <p className="text-xs text-slate-400">
              {isEditing ? 'Actualiza los datos del club deportivo' : 'Registra un club en el catálogo de ligas'}
            </p>
          </div>
        </div>

        <form onSubmit={handleSubmit} className="space-y-4">
          <div>
            <label className="block text-xs font-mono uppercase text-slate-400 mb-1">
              Nombre Oficial del Equipo
            </label>
            <input
              type="text"
              required
              value={name}
              onChange={(e) => setName(e.target.value)}
              placeholder="Ej. Kansas City Chiefs"
              className="w-full rounded-xl border border-slate-700 bg-surface-1 p-2.5 text-xs sm:text-sm text-slate-100 placeholder-slate-500 focus:border-brand-cyan focus:outline-none"
            />
          </div>

          <div className="grid grid-cols-2 gap-3">
            <div>
              <label className="block text-xs font-mono uppercase text-slate-400 mb-1">
                Abreviación (Ticker)
              </label>
              <input
                type="text"
                required
                maxLength={5}
                value={abbreviation}
                onChange={(e) => setAbbreviation(e.target.value.toUpperCase())}
                placeholder="KC"
                className="w-full rounded-xl border border-slate-700 bg-surface-1 p-2.5 text-xs sm:text-sm font-mono text-white focus:border-brand-cyan focus:outline-none"
              />
            </div>

            <div>
              <label className="block text-xs font-mono uppercase text-slate-400 mb-1">
                Liga Deportiva
              </label>
              <select
                value={sportId}
                onChange={(e) => setSportId(Number(e.target.value))}
                className="w-full rounded-xl border border-slate-700 bg-surface-1 p-2.5 text-xs sm:text-sm font-mono text-white focus:border-brand-cyan focus:outline-none"
              >
                <option value={1}>🏈 NFL</option>
                <option value={2}>⚽ Liga MX</option>
              </select>
            </div>
          </div>

          <div>
            <div className="flex justify-between items-center mb-1">
              <label className="block text-xs font-mono uppercase text-slate-400">
                Ranking Defensivo (1 = Mejor defensa)
              </label>
              <span className="font-mono text-xs font-bold text-brand-cyan">#{defensiveRank}</span>
            </div>
            <input
              type="number"
              min={1}
              max={32}
              required
              value={defensiveRank}
              onChange={(e) => setDefensiveRank(e.target.value)}
              className="w-full rounded-xl border border-slate-700 bg-surface-1 p-2.5 text-xs sm:text-sm font-mono text-white focus:border-brand-cyan focus:outline-none"
            />
          </div>

          <div className="pt-2 flex gap-3">
            <Button variant="secondary" size="md" onClick={onClose} className="flex-1">
              Cancelar
            </Button>
            <Button type="submit" variant="primary" size="md" isLoading={loading} className="flex-1">
              {isEditing ? 'Actualizar' : 'Guardar'}
            </Button>
          </div>
        </form>
      </div>
    </div>
  );
}
