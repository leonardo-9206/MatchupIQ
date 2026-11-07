import React, { useState, useEffect, useMemo } from 'react';
import Navbar from './components/Navbar.jsx';
import HeroFilters from './components/HeroFilters.jsx';
import FeaturedPropsGrid from './components/FeaturedPropsGrid.jsx';
import PropsTableList from './components/PropsTableList.jsx';
import StatsDrawer from './components/StatsDrawer.jsx';
import TacticalHeatmapModal from './components/TacticalHeatmapModal.jsx';
import AuthModal from './components/AuthModal.jsx';
import BetTrackerView from './components/BetTrackerView.jsx';
import AddBetModal from './components/AddBetModal.jsx';
import { getProps } from './services/propsService.js';
import { checkSession, logoutUser } from './services/authService.js';
import { getBets, createBet, updateBet, deleteBet } from './services/trackerService.js';
import { refreshAdminOdds } from './services/adminService.js';
import { Loader2 } from 'lucide-react';

export default function App() {
  const [selectedSport, setSelectedSport] = useState('all');
  const [searchQuery, setSearchQuery] = useState('');
  const [propsData, setPropsData] = useState([]);
  const [dataSource, setDataSource] = useState('mock');
  const [loading, setLoading] = useState(true);
  const [selectedProp, setSelectedProp] = useState(null);
  const [isDrawerOpen, setIsDrawerOpen] = useState(false);
  const [isHeatmapOpen, setIsHeatmapOpen] = useState(false);
  const [heatmapPlayerId, setHeatmapPlayerId] = useState(null);
  const [activeTab, setActiveTab] = useState('dashboard'); // 'dashboard' | 'catalog' | 'tracker'

  // Admin sync states
  const [isRefreshingOdds, setIsRefreshingOdds] = useState(false);
  const [refreshSuccessMsg, setRefreshSuccessMsg] = useState('');

  // Authentication State
  const [currentUser, setCurrentUser] = useState(null);
  const [authModalOpen, setAuthModalOpen] = useState(false);
  const [authModalMode, setAuthModalMode] = useState('login');

  // Bet Tracker State (Sprint 3)
  const [bets, setBets] = useState([]);
  const [addBetModalOpen, setAddBetModalOpen] = useState(false);
  const [betModalProp, setBetModalProp] = useState(null);

  // Verify initial user session
  useEffect(() => {
    async function verifyUser() {
      const res = await checkSession();
      if (res.isAuthenticated) {
        setCurrentUser(res.user);
      }
    }
    verifyUser();
  }, []);

  // Load Bet Tracker bets
  useEffect(() => {
    async function loadBets() {
      const res = await getBets();
      setBets(res.bets);
    }
    loadBets();
  }, []);

  // Load props from asynchronous service (Fetch API)
  const loadData = async () => {
    setLoading(true);
    try {
      const res = await getProps(selectedSport);
      setPropsData(res.props);
      setDataSource(res.source);
    } catch (err) {
      console.error('Error fetching props:', err);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    loadData();
  }, [selectedSport]);

  // Admin Odds Refresh Action
  const handleRefreshOdds = async () => {
    setIsRefreshingOdds(true);
    setRefreshSuccessMsg('');
    const res = await refreshAdminOdds('americanfootball_nfl');
    setIsRefreshingOdds(false);
    if (res.success) {
      setRefreshSuccessMsg(res.message);
      await loadData(); // Reload props with fresh odds
      setTimeout(() => setRefreshSuccessMsg(''), 4000);
    }
  };

  // Client-side real-time filter by search query
  const filteredProps = useMemo(() => {
    if (!searchQuery.trim()) return propsData;
    const q = searchQuery.toLowerCase().trim();
    return propsData.filter((p) => {
      return (
        p.player_name.toLowerCase().includes(q) ||
        p.team_name.toLowerCase().includes(q) ||
        p.team_abbreviation.toLowerCase().includes(q) ||
        p.bet_type_display.toLowerCase().includes(q) ||
        p.player_position.toLowerCase().includes(q)
      );
    });
  }, [propsData, searchQuery]);

  // NFL athletes that appear in the loaded props, for the tactical modal's player selector
  const tacticalPlayers = useMemo(() => {
    const byId = new Map();
    for (const p of propsData) {
      if (p.sport_slug === 'nfl' && !byId.has(p.player_id)) {
        byId.set(p.player_id, {
          player_id: p.player_id,
          name: p.player_name,
          position: p.player_position,
          team: p.team_abbreviation,
        });
      }
    }
    return [...byId.values()];
  }, [propsData]);

  const handleOpenStats = (prop) => {
    setSelectedProp(prop);
    setIsDrawerOpen(true);
  };

  const handleCloseStats = () => {
    setIsDrawerOpen(false);
  };

  const handleOpenHeatmap = (playerId) => {
    setHeatmapPlayerId(playerId);
    setIsHeatmapOpen(true);
  };

  const handleLogout = async () => {
    await logoutUser();
    setCurrentUser(null);
  };

  // Bet Tracker Handlers (Sprint 3)
  const handleOpenAddBetModal = (prop = null) => {
    setBetModalProp(prop);
    setAddBetModalOpen(true);
  };

  const handleBetAdded = async (betData) => {
    const res = await createBet(betData);
    if (res.success) {
      setBets((prev) => [res.bet, ...prev]);
    }
  };

  const handleUpdateBetStatus = async (betId, newStatus) => {
    const res = await updateBet(betId, { status: newStatus });
    if (res.success) {
      setBets((prev) => prev.map((b) => (b.bet_id === betId ? { ...b, status: newStatus } : b)));
    }
  };

  const handleUpdateBetNotes = async (betId, newNotes) => {
    const res = await updateBet(betId, { notes: newNotes });
    if (res.success) {
      setBets((prev) => prev.map((b) => (b.bet_id === betId ? { ...b, notes: newNotes } : b)));
    }
  };

  const handleDeleteBet = async (betId) => {
    const res = await deleteBet(betId);
    if (res.success) {
      setBets((prev) => prev.filter((b) => b.bet_id !== betId));
    }
  };

  return (
    <div className="min-h-screen bg-[#050811] text-slate-100 flex flex-col justify-between selection:bg-brand-cyan/20 selection:text-brand-cyan">
      
      {/* Top Navigation */}
      <div>
        <Navbar
          activeTab={activeTab}
          onSelectTab={setActiveTab}
          currentUser={currentUser}
          onOpenLogin={() => { setAuthModalMode('login'); setAuthModalOpen(true); }}
          onOpenRegister={() => { setAuthModalMode('register'); setAuthModalOpen(true); }}
          onLogout={handleLogout}
          trackerBetsCount={bets.filter((b) => b.status === 'pending').length}
        />

        {/* View Switcher: Bet Tracker View vs Main Dashboard */}
        {activeTab === 'tracker' ? (
          <main>
            <BetTrackerView
              bets={bets}
              onUpdateStatus={handleUpdateBetStatus}
              onUpdateNotes={handleUpdateBetNotes}
              onDeleteBet={handleDeleteBet}
              onOpenAddModal={handleOpenAddBetModal}
            />
          </main>
        ) : (
          <>
            {/* Hero & Real-time Filter Bar with Live Sync Button */}
            <HeroFilters
              selectedSport={selectedSport}
              onSelectSport={setSelectedSport}
              searchQuery={searchQuery}
              onSearchChange={setSearchQuery}
              totalPropsCount={filteredProps.length}
              featuredCount={filteredProps.filter((p) => p.is_featured).length}
              isAdmin={currentUser?.role === 'admin'}
              isRefreshingOdds={isRefreshingOdds}
              onRefreshOdds={handleRefreshOdds}
              refreshSuccessMsg={refreshSuccessMsg}
            />

            {/* Loading Indicator */}
            {loading ? (
              <div className="flex flex-col items-center justify-center py-20 text-slate-400">
                <Loader2 className="h-8 w-8 animate-spin text-brand-cyan mb-2" />
                <p className="text-xs font-mono">Consultando cuotas en vivo...</p>
              </div>
            ) : (
              <main>
                {/* Level 1: Featured Picks Grid (Only on Dashboard tab or when searching) */}
                {(activeTab === 'dashboard' || searchQuery) && (
                  <FeaturedPropsGrid
                    props={filteredProps}
                    onOpenStats={handleOpenStats}
                    onOpenHeatmap={handleOpenHeatmap}
                  />
                )}

                {/* Level 2: Full Propositions Catalog with USA Sportsbooks */}
                <PropsTableList
                  props={filteredProps}
                  onOpenStats={handleOpenStats}
                  searchQuery={searchQuery}
                />
              </main>
            )}
          </>
        )}
      </div>

      {/* Slide-over Stats & Odds Drawer */}
      <StatsDrawer
        prop={selectedProp}
        isOpen={isDrawerOpen}
        onClose={handleCloseStats}
        onOpenHeatmap={handleOpenHeatmap}
        onAddBet={(prop) => {
          handleCloseStats();
          handleOpenAddBetModal(prop);
        }}
      />

      {/* Tactical NFL Field Heatmap Modal directly bound to player */}
      <TacticalHeatmapModal
        isOpen={isHeatmapOpen}
        selectedPlayerId={heatmapPlayerId}
        players={tacticalPlayers}
        onClose={() => setIsHeatmapOpen(false)}
      />

      {/* Add / Register Bet Modal (Sprint 3) */}
      <AddBetModal
        isOpen={addBetModalOpen}
        onClose={() => setAddBetModalOpen(false)}
        prop={betModalProp}
        onBetAdded={handleBetAdded}
      />

      {/* Authentication Modal */}
      <AuthModal
        isOpen={authModalOpen}
        initialMode={authModalMode}
        onClose={() => setAuthModalOpen(false)}
        onAuthSuccess={(user) => setCurrentUser(user)}
      />

      {/* Institutional Formal Footer */}
      <footer className="mt-16 border-t border-slate-800/80 bg-[#070B16] py-8 text-center text-xs text-slate-400">
        <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-4">
          <div className="flex items-center gap-2">
            <img src="/logo_matchupiq_app.png" alt="MatchupIQ" className="h-6 w-auto opacity-75" />
            <span className="font-display font-bold text-white tracking-wide">MatchupIQ</span>
          </div>

          <div className="text-[11px] font-mono text-slate-400">
            MatchupIQ © 2026 — ISC 5-A (UAA)
          </div>

          <div className="text-[11px] text-slate-400">
            Programación de Sistemas Web
          </div>
        </div>
      </footer>

    </div>
  );
}
