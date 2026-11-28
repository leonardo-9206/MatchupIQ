import React, { useState } from 'react';
import { X, User, Plus, Edit2 } from 'lucide-react';
import Button from '../common/Button.jsx';

export default function PlayerModal({ isOpen, onClose, player = null, teams = [], onSave }) {
  const isEditing = Boolean(player);
  const [name, setName] = useState(player?.name || '');
  const [position, setPosition] = useState(player?.position || 'QB');
  const [teamId, setTeamId] = useState(player?.team_id || teams[0]?.team_id || 1);
  const [statAverage, setStatAverage] = useState(player?.stat_average || 0.0);
  const [loading, setLoading] = useState(false);

  if (!isOpen) return null;

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (!name.trim()) return;

    setLoading(true);
    await onSave({
      name: name.trim(),
      position: position.trim().toUpperCase(),
      team_id: parseInt(teamId),
      stat_average: parseFloat(statAverage) || 0.0,
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
              {isEditing ? 'Editar Jugador' : 'Nuevo Jugador'}
            </h3>
            <p className="text-xs text-slate-400">
              {isEditing ? 'Modifica la información deportiva del atleta' : 'Agrega un nuevo atleta al catálogo'}
            </p>
          </div>
        </div>

        <form onSubmit={handleSubmit} className="space-y-4">
          <div>
            <label className="block text-xs font-mono uppercase text-slate-400 mb-1">
              Nombre Completo del Atleta
            </label>
            <input
              type="text"
              required
              value={name}
              onChange={(e) => setName(e.target.value)}
              placeholder="Ej. Patrick Mahomes"
              className="w-full rounded-xl border border-slate-700 bg-surface-1 p-2.5 text-xs sm:text-sm text-slate-100 placeholder-slate-500 focus:border-brand-cyan focus:outline-none"
            />
          </div>

          <div className="grid grid-cols-2 gap-3">
            <div>
              <label className="block text-xs font-mono uppercase text-slate-400 mb-1">
                Posición
              </label>
              <select
                value={position}
                onChange={(e) => setPosition(e.target.value)}
                className="w-full rounded-xl border border-slate-700 bg-surface-1 p-2.5 text-xs sm:text-sm font-mono text-white focus:border-brand-cyan focus:outline-none"
              >
                <optgroup label="NFL">
                  <option value="QB">QB (Quarterback)</option>
                  <option value="RB">RB (Running Back)</option>
                  <option value="WR">WR (Wide Receiver)</option>
                  <option value="TE">TE (Tight End)</option>
                  <option value="K">K (Kicker)</option>
                </optgroup>
                <optgroup label="Liga MX">
                  <option value="DEL">DEL (Delantero)</option>
                  <option value="MED">MED (Mediocampista)</option>
                  <option value="DEF">DEF (Defensa)</option>
                  <option value="POR">POR (Portero)</option>
                </optgroup>
              </select>
            </div>

            <div>
              <label className="block text-xs font-mono uppercase text-slate-400 mb-1">
                Equipo / Club
              </label>
              <select
                value={teamId}
                onChange={(e) => setTeamId(Number(e.target.value))}
                className="w-full rounded-xl border border-slate-700 bg-surface-1 p-2.5 text-xs sm:text-sm font-mono text-white focus:border-brand-cyan focus:outline-none"
              >
                {teams.map((t) => (
                  <option key={t.team_id} value={t.team_id}>
                    {t.name} ({t.abbreviation})
                  </option>
                ))}
              </select>
            </div>
          </div>

          <div>
            <label className="block text-xs font-mono uppercase text-slate-400 mb-1">
              Promedio Estadístico Base (Stat Average)
            </label>
            <input
              type="number"
              step="0.01"
              min={0}
              required
              value={statAverage}
              onChange={(e) => setStatAverage(e.target.value)}
              placeholder="Ej. 282.40"
              className="w-full rounded-xl border border-slate-700 bg-surface-1 p-2.5 text-xs sm:text-sm font-mono text-white focus:border-brand-cyan focus:outline-none"
            />
            <span className="text-[10px] text-slate-500 mt-1 block">
              Utilizado por el algoritmo de Value Edge para proyectar ventajas matemáticas.
            </span>
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
