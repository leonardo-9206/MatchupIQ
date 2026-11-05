import React from 'react';
import { Activity, Layers, BookmarkCheck, LogIn, LogOut } from 'lucide-react';
import Button from './common/Button.jsx';

export default function Navbar({
  activeTab,
  onSelectTab,
  currentUser,
  onOpenLogin,
  onOpenRegister,
  onLogout,
  trackerBetsCount = 0,
}) {
  return (
    <header className="sticky top-0 z-40 w-full border-b border-slate-800/80 bg-[#070B16]/95 backdrop-blur-md">
      <div className="mx-auto flex max-w-7xl items-center justify-between px-4 py-3 sm:px-6 lg:px-8">
        
        {/* Brand Logo & Name */}
        <div className="flex items-center gap-3">
          <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-brand-cyan/20 to-brand-blue/30 p-1 border border-brand-cyan/30 shadow-glow-cyan">
            <img
              src="/logo_matchupiq_app.png"
              alt="MatchupIQ"
              className="h-8 w-auto object-contain drop-shadow"
            />
          </div>
          <div>
            <div className="flex items-center gap-2">
              <span className="font-display text-xl font-extrabold tracking-tight text-white">
                Matchup<span className="text-brand-cyan">IQ</span>
              </span>
              <span className="hidden rounded-full border border-emerald-500/30 bg-emerald-500/10 px-2 py-0.5 font-mono text-[10px] font-semibold text-edge-positive sm:inline-flex items-center gap-1">
                <span className="h-1.5 w-1.5 rounded-full bg-edge-positive animate-pulse" />
                VALUE EDGE
              </span>
            </div>
            <p className="text-[11px] text-slate-400 font-sans hidden sm:block">
              NFL & Liga MX • US Sportsbooks Comparison
            </p>
          </div>
        </div>

        {/* Navigation Tabs */}
        <nav className="flex items-center gap-1 sm:gap-2">
          <Button
            variant={activeTab === 'dashboard' ? 'primary' : 'ghost'}
            size="sm"
            icon={Activity}
            onClick={() => onSelectTab('dashboard')}
          >
            <span>Dashboard</span>
          </Button>

          <Button
            variant={activeTab === 'catalog' ? 'primary' : 'ghost'}
            size="sm"
            icon={Layers}
            onClick={() => onSelectTab('catalog')}
          >
            <span>Catálogo</span>
          </Button>

          {/* Bet Tracker Tab (Sprint 3) */}
          <Button
            variant={activeTab === 'tracker' ? 'primary' : 'ghost'}
            size="sm"
            icon={BookmarkCheck}
            onClick={() => onSelectTab('tracker')}
            className="relative"
          >
            <span>Bet Tracker</span>
            {trackerBetsCount > 0 && (
              <span className="ml-1 rounded-full bg-emerald-500/20 text-edge-positive px-1.5 py-0.2 text-[10px] font-mono font-bold">
                {trackerBetsCount}
              </span>
            )}
          </Button>
        </nav>

        {/* Authentication State Section */}
        <div className="flex items-center gap-2 sm:gap-3">
          <div className="hidden lg:flex items-center gap-2 rounded-lg border border-slate-800 bg-surface-1 px-3 py-1 text-xs font-mono text-slate-400">
            <span className="h-2 w-2 rounded-full bg-edge-positive" />
            <span>US Odds Live</span>
          </div>

          {currentUser ? (
            <div className="flex items-center gap-2 sm:gap-3">
              <div className="flex items-center gap-2 rounded-full border border-slate-700/80 bg-surface-card px-3 py-1 text-xs font-medium">
                <div className="flex h-6 w-6 items-center justify-center rounded-full bg-gradient-to-tr from-brand-cyan to-brand-blue text-[11px] font-bold text-slate-950 font-display uppercase">
                  {currentUser.full_name ? currentUser.full_name.charAt(0) : 'U'}
                </div>
                <div className="hidden sm:block text-left leading-tight">
                  <span className="block font-semibold text-slate-200">{currentUser.full_name || currentUser.email}</span>
                  <span className="block text-[10px] text-brand-cyan uppercase font-mono">{currentUser.role_name || currentUser.role || 'Usuario'}</span>
                </div>
              </div>

              <Button
                variant="ghost"
                size="sm"
                icon={LogOut}
                onClick={onLogout}
                className="text-xs text-slate-400 hover:text-red-400"
                title="Cerrar Sesión"
              >
                <span className="hidden sm:inline">Salir</span>
              </Button>
            </div>
          ) : (
            <div className="flex items-center gap-2">
              <Button
                variant="ghost"
                size="sm"
                icon={LogIn}
                onClick={onOpenLogin}
              >
                <span>Ingresar</span>
              </Button>
              <Button
                variant="primary"
                size="sm"
                onClick={onOpenRegister}
              >
                <span>Registro</span>
              </Button>
            </div>
          )}
        </div>

      </div>
    </header>
  );
}

